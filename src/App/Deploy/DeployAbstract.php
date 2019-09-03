<?php

namespace Console\App\Deploy;

use Closure;
use Console\App\Commands\AppRuns\AppRunsDescribeCommand;
use Console\App\Commands\AppRuns\AppRunsNewCommand;
use Console\App\Commands\Command;
use Console\App\Commands\Databases\DatabasesDescribeCommand;
use Console\App\Commands\DbBackups\DbBackupsDescribeCommand;
use Console\App\Commands\DbBackups\DbBackupsNewCommand;
use Console\App\Commands\DbRestores\DbRestoresDescribeCommand;
use Console\App\Commands\DbRestores\DbRestoresNewCommand;
use Console\App\Commands\Files\FilesDeleteCommand;
use Console\App\Commands\Files\FilesUpdateCommand;
use Console\App\Commands\Files\FilesUploadCommand;
use Console\App\Commands\Files\SubCommands\FilesUpdateUnarchiveCommand;
use Console\App\Helpers\AuthHelper;
use Console\App\Helpers\DeployHelper;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use ZipArchive;
use GuzzleHttp\Exception\GuzzleException;

abstract class DeployAbstract implements DeployInterface
{
	const ARCHIVE_NAME = 'lamp-io.zip';

	const SQL_DUMP_NAME = 'dump.sql';

	/**
	 * @var string
	 */
	protected $appPath;

	/**
	 * @var Application
	 */
	protected $application;

	/**
	 * @var ConsoleOutput
	 */
	protected $consoleOutput;

	/**
	 * @var string
	 */
	protected $releaseFolder;

	protected $config;

	protected $isFirstDeploy;

	protected $steps = [];

	/**
	 * @var Client
	 */
	protected $httpClient;

	/**
	 * DeployAbstract constructor.
	 * @param Application $application
	 * @param array $config
	 * @param Client $httpClient
	 */
	public function __construct(Application $application, array $config, Client $httpClient)
	{
		$this->application = $application;
		$this->consoleOutput = new ConsoleOutput();
		$this->config = $config;
		$this->httpClient = $httpClient;
	}

	/**
	 * @param string $appPath
	 * @param bool $isFirstDeploy
	 * @throws GuzzleException
	 */
	public function deployApp(string $appPath, bool $isFirstDeploy)
	{
		$this->appPath = $appPath;
		$this->isFirstDeploy = $isFirstDeploy;
		$this->releaseFolder = DeployHelper::RELEASE_FOLDER . '/' . $this->config['release'] . '/';

		if ($this->isFirstDeploy) {
			$this->clearApp();
		}
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	protected function getZipApp()
	{
		$stepName = 'zip';
		$this->setStep($stepName, function () {
			if (file_exists($this->appPath . self::ARCHIVE_NAME)) {
				unlink($this->appPath . self::ARCHIVE_NAME);
			}
		});
		$zipName = $this->appPath . self::ARCHIVE_NAME;
		if (file_exists($zipName)) {
			unlink($zipName);
		}
		$zip = new ZipArchive();
		$finder = new Finder();
		$progressBar = Command::getProgressBar('Creating a zip', $this->consoleOutput);
		$finder->in($this->appPath)->ignoreDotFiles(false);
		if (!$finder->hasResults()) {
			throw new Exception('Empty app directory');
		}

		if (!$zip->open($zipName, ZipArchive::CREATE)) {
			throw new Exception('Cant zip your app');
		}
		$progressBar->start();
		foreach ($finder as $file) {
			if (is_dir($file->getRealPath())) {
				$zip->addEmptyDir($file->getRelativePathname());
			} else {
				$zip->addFile($file->getRealPath(), $file->getRelativePathname());
			}
			$progressBar->advance();
		}
		$zip->close();
		$progressBar->finish();
		$this->consoleOutput->write(PHP_EOL);
		$this->updateStepToSuccess($stepName);
		return $zipName;
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	protected function backupDatabase(): string
	{
		if ($this->config['database']['type'] == 'external' || $this->config['database']['system'] == 'sqlite') {
			return '';
		}
		$step = 'backupDatabase';
		$this->setStep($step, function () {
			return;
		});
		$dbBackupNewCommand = $this->application->find(DbBackupsNewCommand::getDefaultName());
		$args = [
			'command'     => DbBackupsNewCommand::getDefaultName(),
			'database_id' => $this->config['database']['id'],
			'--json'      => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($dbBackupNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			$dbBackupId = $document->get('data.id');
			$progressBar = Command::getProgressBar('Backup current database state', $this->consoleOutput);
			$progressBar->start();
			while (!DbBackupsDescribeCommand::isDbBackupCreated($dbBackupId, $this->application)) {
				$progressBar->advance();
			}
			$progressBar->finish();
			$this->consoleOutput->write(PHP_EOL);
			return $dbBackupId;
		} else {
			throw new Exception('Database backup creation failed');
		}
	}

	/**
	 * @param string $dbBackupId
	 * @throws Exception
	 */
	protected function restoreDatabase(string $dbBackupId)
	{
		$dbRestoreNewCommand = $this->application->find(DbRestoresNewCommand::getDefaultName());
		$args = [
			'command'      => DbRestoresNewCommand::getDefaultName(),
			'database_id'  => $this->config['database']['id'],
			'db_backup_id' => $dbBackupId,
			'--json'       => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($dbRestoreNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			$document = Parser::parseResponseString($bufferOutput->fetch());
			/** @var Document $document */
			$dbRestoreId = $document->get('data.id');
			$progressBar = Command::getProgressBar('Restoring db to previous state', $this->consoleOutput);
			$progressBar->start();
			while (!DbRestoresDescribeCommand::isDbRestoreCompleted($dbRestoreId, $this->application)) {
				$progressBar->advance();
			}
			$progressBar->finish();
			$this->consoleOutput->write(PHP_EOL);
		} else {
			throw new Exception('Database restore failed');
		}
	}

	/**
	 * @param string $appId
	 * @param string $command
	 * @param string $progressMessage
	 * @throws Exception
	 */
	protected function appRunCommand(string $appId, string $command, string $progressMessage)
	{
		$appRunsNewCommand = $this->application->find(AppRunsNewCommand::getDefaultName());
		$args = [
			'command' => AppRunsNewCommand::getDefaultName(),
			'app_id'  => $appId,
			'exec'    => $command,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($appRunsNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			$appRunId = $document->get('data.id');
			$progressBar = Command::getProgressBar($progressMessage, $this->consoleOutput);
			$progressBar->start();
			while (!AppRunsDescribeCommand::isExecutionCompleted($appRunId, $this->application)) {
				$progressBar->advance();
			}
			$progressBar->finish();
			$this->consoleOutput->write(PHP_EOL);
		} else {
			throw new Exception('Command ' . $command . '. Failed');
		}
	}

	/**
	 * @throws GuzzleException
	 */
	protected function clearApp()
	{
		$step = 'clearApp';
		$this->setStep($step, function () {
			return;
		});
		try {
			$deleteFileUrl = sprintf(
				FilesDeleteCommand::API_ENDPOINT,
				$this->config['app']['id'],
				'public'
			);
			$this->sendRequest($deleteFileUrl, 'DELETE', 'Removing default files');
		} catch (ClientException $clientException) {
			$this->consoleOutput->write(PHP_EOL);
		}
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param array $localEnv
	 * @param array $remoteEnv
	 */
	protected function updateEnvFileToUpload(array $localEnv, array $remoteEnv)
	{
		$step = 'updateEnvFileToUpload';
		$this->setStep($step, function () use ($localEnv) {
			$this->updateEnvFile($localEnv);
		});
		$this->updateEnvFile($remoteEnv);
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param array $localEnv
	 */
	protected function restoreLocalEnvFile(array $localEnv)
	{
		$step = 'restoreLocalEnvFile';
		$this->setStep($step, function () {
			return;
		});
		$this->updateEnvFile($localEnv);
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param array $newEnvVars
	 */
	protected function updateEnvFile(array $newEnvVars)
	{
		file_put_contents($this->appPath . '.env', '');
		foreach ($newEnvVars as $key => $val) {
			file_put_contents($this->appPath . '.env', $key . '=' . $val . PHP_EOL, FILE_APPEND);
		}
	}

	/**
	 * @param string $dbName
	 * @throws Exception
	 */
	protected function importSqlDump(string $dbName)
	{
		$step = 'ImportSqlDump';
		$this->setStep($step, function () {
			return;
		});
		$filesUploadCommand = $this->application->find(FilesUploadCommand::getDefaultName());
		$args = [
			'command'     => FilesUploadCommand::getDefaultName(),
			'file'        => $this->config['database']['sql_dump'],
			'app_id'      => $this->config['app']['id'],
			'remote_path' => $this->releaseFolder . self::SQL_DUMP_NAME,
			'--json'      => true,
		];
		if ($filesUploadCommand->run(new ArrayInput($args), $this->consoleOutput) == '0') {
			$command = sprintf(
				'mysql -u %s --host=%s --password=%s  %s < %s',
				$this->config['database']['connection']['user'],
				$this->config['database']['connection']['host'],
				$this->config['database']['connection']['password'],
				$dbName,
				$this->releaseFolder . self::SQL_DUMP_NAME
			);
			$this->appRunCommand(
				$this->config['app']['id'],
				$command,
				'Importing sql dump'
			);
			$this->updateStepToSuccess($step);
		} else {
			throw new Exception('Uploading sql dump to app failed');
		}
	}

	/**
	 * @throws Exception
	 */
	protected function createDatabaseUser()
	{
		$step = 'createDatabaseUser';
		$this->setStep($step, function () {
			$command = sprintf(
				'mysql --user=root --host=%s --password=%s --execute "DROP USER \'%s\'"',
				$this->config['database']['connection']['host'],
				$this->config['database']['root_password'],
				$this->config['database']['connection']['user']
			);
			$this->appRunCommand(
				$this->config['app']['id'],
				$command,
				'Drop database user ' . $this->config['database']['connection']['user']
			);
		});
		$command = sprintf(
			'mysql --user=root --host=%s --password=%s --execute "CREATE USER \'%s\'@\'%%\' IDENTIFIED WITH mysql_native_password BY \'%s\';GRANT ALL PRIVILEGES ON * . * TO \'%s\'@\'%%\';FLUSH PRIVILEGES;"',
			$this->config['database']['connection']['host'],
			$this->config['database']['root_password'],
			$this->config['database']['connection']['user'],
			$this->config['database']['connection']['password'],
			$this->config['database']['connection']['user']
		);
		$this->appRunCommand(
			$this->config['app']['id'],
			$command,
			'Creating database user ' . $this->config['database']['connection']['user']
		);
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param string $dbId
	 * @return bool
	 * @throws Exception
	 */
	protected function isDbAlreadyRunning(string $dbId): bool
	{
		$appRunsNewCommand = $this->application->find(DatabasesDescribeCommand::getDefaultName());
		$args = [
			'command'     => DatabasesDescribeCommand::getDefaultName(),
			'database_id' => $dbId,
			'--json'      => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($appRunsNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			return $document->get('data.attributes.status') === 'running';
		} else {
			throw new Exception('Checking db status failed');
		}
	}

	/**
	 * @throws Exception
	 */
	protected function initDatabase()
	{
		$progressBar = Command::getProgressBar('Initializing database', $this->consoleOutput);
		$progressBar->start();
		$dbIsRunning = false;
		while (!$dbIsRunning) {
			$dbIsRunning = $this->isDbAlreadyRunning($this->config['database']['connection']['host']);
			$progressBar->advance();
		}
		$counter = 0;
		/** We need this hack with sleep, because status of database is running,
		 * but it still not ready for connections so ~30-40 secs need to wait
		 */
		while ($counter != 50) {
			$progressBar->advance();
			$counter++;
			sleep(1);
		}
		$progressBar->finish();
		$this->consoleOutput->write(PHP_EOL);
	}

	/**
	 * @param string $dbName
	 * @throws Exception
	 */
	protected function createDatabase(string $dbName)
	{
		$step = 'CreateDatabase';
		$this->setStep($step, function () use ($dbName) {
			$command = sprintf(
				'mysql --user=%s --host=%s --password=%s --execute "drop database %s;"',
				$this->config['database']['connection']['user'],
				$this->config['database']['connection']['host'],
				$this->config['database']['connection']['password'],
				$dbName
			);
			$this->appRunCommand(
				$this->config['app']['id'],
				$command,
				'Drop database'
			);
		});

		$command = sprintf(
			'mysql --user=%s --host=%s --password=%s --execute "create database %s;"',
			$this->config['database']['connection']['user'],
			$this->config['database']['connection']['host'],
			$this->config['database']['connection']['password'],
			$dbName
		);
		$this->appRunCommand(
			$this->config['app']['id'],
			$command,
			'Creating database `' . $dbName . '`'
		);
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param string $localFile
	 * @param string $remotePath
	 * @throws Exception
	 */
	protected function uploadToApp(string $localFile, string $remotePath)
	{
		$step = 'uploadToApp';
		$this->setStep($step, function () use ($remotePath) {
			if ($this->isFirstDeploy) {
				$deleteFileUrl = sprintf(
					FilesDeleteCommand::API_ENDPOINT,
					$this->config['app']['id'],
					DeployHelper::RELEASE_FOLDER
				);
				$this->sendRequest($deleteFileUrl, 'DELETE', 'Clean up failed deploy');
			} else {
				$this->consoleOutput->writeln('Deleting failed release');
				$filesDeleteCommand = $this->application->find(FilesDeleteCommand::getDefaultName());
				$args = [
					'command'     => FilesDeleteCommand::getDefaultName(),
					'remote_path' => str_replace(self::ARCHIVE_NAME, '', $remotePath),
					'app_id'      => $this->config['app']['id'],
					'--json'      => true,
					'--yes'       => true,
				];
				$filesDeleteCommand->run(new ArrayInput($args), $this->consoleOutput);
			}
		});
		$filesUploadCommand = $this->application->find(FilesUploadCommand::getDefaultName());
		$args = [
			'command'     => FilesUploadCommand::getDefaultName(),
			'file'        => $localFile,
			'app_id'      => $this->config['app']['id'],
			'remote_path' => $remotePath,
			'--json'      => true,
		];
		if ($filesUploadCommand->run(new ArrayInput($args), $this->consoleOutput) == '0') {
			$this->consoleOutput->write(PHP_EOL);
			$this->updateStepToSuccess($step);
		} else {
			throw new Exception('Uploading app failed');
		}
	}

	/**
	 * @param string $remotePath
	 * @throws Exception
	 */
	protected function unarchiveApp(string $remotePath)
	{
		$step = 'unarchiveApp';
		$this->setStep($step, function () {
			return;
		});

		$appRunsDescribeCommand = $this->application->find(FilesUpdateUnarchiveCommand::getDefaultName());
		$args = [
			'command'     => FilesUpdateUnarchiveCommand::getDefaultName(),
			'remote_path' => $remotePath,
			'app_id'      => $this->config['app']['id'],
			'--json'      => true,
		];
		if ($appRunsDescribeCommand->run(new ArrayInput($args), $this->consoleOutput) == '0') {
			$this->consoleOutput->write(PHP_EOL);
			$this->updateStepToSuccess($step);
		} else {
			throw new Exception('Extracting archive failed');
		}
	}

	/**
	 *
	 */
	protected function deleteArchiveLocal()
	{
		$step = 'deleteArchiveLocal';
		$this->setStep($step, function () {
			return;
		});
		unlink($this->appPath . self::ARCHIVE_NAME);
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param string $remotePath
	 * @throws Exception
	 */
	protected function deleteArchiveRemote(string $remotePath)
	{
		$step = 'deleteArchiveRemote';
		$this->setStep($step, function () {
			return;
		});
		$deleteFileCommand = $this->application->find(FilesDeleteCommand::getDefaultName());
		$args = [
			'command'     => FilesDeleteCommand::getDefaultName(),
			'remote_path' => $remotePath,
			'app_id'      => $this->config['app']['id'],
			'--json'      => true,
			'--yes'       => true,
		];
		if ($deleteFileCommand->run(new ArrayInput($args), new NullOutput()) == '0') {
			$this->updateStepToSuccess($step);
		} else {
			throw new Exception('Deleting archive failed');
		}
	}

	/**
	 * @param string $url
	 * @param string $httpType
	 * @param string $progressBarMessage
	 * @param string $body
	 * @param array $headers
	 * @return array
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function sendRequest(string $url, string $httpType, string $progressBarMessage = '', string $body = '', array $headers = []): array
	{
		$output = !empty($progressBarMessage) ? new ConsoleOutput() : new NullOutput();
		if (empty($headers)) {
			$headers = [
				'Content-type'  => 'application/vnd.api+json',
				'Accept'        => 'application/vnd.api+json',
				'Authorization' => 'Bearer ' . AuthHelper::getToken(),
			];
		}
		$progressBar = Command::getProgressBar($progressBarMessage, $output);
		$response = $this->httpClient->request(
			$httpType,
			$url,
			[
				'headers'  => $headers,
				'body'     => $body,
				'progress' => function () use ($progressBar) {
					$progressBar->advance();
				},
			]
		);
		$progressBar->finish();
		$output->write(PHP_EOL);
		return [
			'http' => $response->getStatusCode(),
			'body' => trim($response->getBody()->getContents()),
		];
	}

	/**
	 * @param array $directories
	 * @param bool $recur
	 * @throws GuzzleException
	 */
	protected function setUpPermissions(array $directories = [], bool $recur = false)
	{
		$step = 'setDirectoryPermissions';
		$this->setStep($step, function () {
			return;
		});

		if (!empty($this->config['apache_permissions_dir']) && is_array($this->config['apache_permissions_dir'])) {
			$directories = array_merge($directories, $this->config['apache_permissions_dir']);
		}

		foreach ($directories as $directory) {
			try {
				$this->sendRequest(
					sprintf(
						FilesUpdateCommand::API_ENDPOINT,
						$this->config['app']['id'],
						($recur) ? '?recur=true' : ''
					),
					'PATCH',
					'Setting apache writable ' . $directory,
					json_encode([
						'data' => [
							'attributes' => [
								'apache_writable' => true,
							],
							'id'         => $this->releaseFolder . $directory,
							'type'       => 'files',
						],
					])
				);
			} catch (ClientException $clientException) {
				$this->consoleOutput->writeln(PHP_EOL . '<comment>Directory ' . $directory . '/ not exists</comment>');
			}
		}
		$this->updateStepToSuccess($step);
	}

	/**
	 * @throws Exception
	 * @throws GuzzleException
	 */
	protected function initSqliteDatabase()
	{
		$step = 'initSqliteDatabase';
		$this->setStep($step, function () {
			return;
		});
		if (!empty($this->config['database']['sql_dump'])) {
			$filesUploadCommand = $this->application->find(FilesUploadCommand::getDefaultName());
			$args = [
				'command'     => FilesUploadCommand::getDefaultName(),
				'file'        => $this->config['database']['sql_dump'],
				'app_id'      => $this->config['app']['id'],
				'remote_path' => DeployHelper::SQLITE_RELATIVE_REMOTE_PATH,
				'--json'      => true,
			];
			if ($filesUploadCommand->run(new ArrayInput($args), $this->consoleOutput) != '0') {
				throw new Exception('Cant up load mysqli dump');
			}
			$this->consoleOutput->write(PHP_EOL);
		} else {
			$url = sprintf(
				FilesUploadCommand::API_ENDPOINT,
				$this->config['app']['id']
			);
			$this->sendRequest($url, 'POST', 'Creating sqlite database', json_encode([
				'data' => [
					'attributes' => [
						'apache_writable' => true,
					],
					'id'         => DeployHelper::SQLITE_RELATIVE_REMOTE_PATH,
					'type'       => 'files',
				],
			]));
		}
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param array $skipCommands
	 * @throws Exception
	 */
	protected function runCommands(array $skipCommands = [])
	{
		$step = 'runCommands';
		$this->setStep($step, function () {
			return;
		});
		if (!empty($this->config['commands'])) {
			foreach ($this->config['commands'] as $command) {
				foreach ($skipCommands as $skipCommand) {
					if (preg_match('/' . $skipCommand . '/', $command)) {
						$skip = true;
					}
				}
				if (empty($skip)) {
					$this->appRunCommand(
						$this->config['app']['id'],
						'cd ' . $this->releaseFolder . ' && ' . $command,
						$command
					);
				}
				$skip = false;
			}
		}
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param string $migrationCommand
	 * @param string $dbBackupId
	 * @throws Exception
	 */
	protected function runMigrations(string $migrationCommand, string $dbBackupId = '')
	{
		if (!empty($this->config['no_migrations'])) {
			return;
		}
		$step = 'runMigrations';
		$this->setStep($step, function () use ($dbBackupId) {
			if (!empty($dbBackupId)) {
				$this->restoreDatabase($dbBackupId);
			}
		});

		$this->appRunCommand(
			$this->config['app']['id'],
			'php ' . $this->releaseFolder . $migrationCommand,
			'Migrating schema'
		);
		$this->updateStepToSuccess($step);

	}

	/**
	 * @param string $appPublic
	 * @param string $message
	 * @param bool $isFirstDeploy
	 * @throws GuzzleException
	 * @throws Exception
	 */
	protected function symlinkRelease(string $appPublic, string $message, bool $isFirstDeploy = false)
	{
		$step = 'symlinkRelease';
		$this->setStep($step, function () {
			return;
		});
		if ($isFirstDeploy) {
			$url = sprintf(
				FilesUploadCommand::API_ENDPOINT,
				$this->config['app']['id']
			);
			$httpType = 'POST';
		} else {
			$url = sprintf(
				FilesUpdateCommand::API_ENDPOINT,
				$this->config['app']['id'],
				'public'
			);
			$httpType = 'PATCH';
		}

		$this->sendRequest($url, $httpType, $message, json_encode([
			'data' => [
				'attributes' => [
					'target'     => $appPublic,
					'is_dir'     => false,
					'is_symlink' => true,
				],
				'id'         => 'public',
				'type'       => 'files',
			],
		]));
		$this->updateStepToSuccess($step);
	}

	protected function createSharedStorage(array $commands)
	{
		$step = 'createSymlinkStorage';
		$this->setStep($step, function () {
			return;
		});

		foreach ($commands as $command) {
			$command['execute']($command['message']);
		}
		$this->updateStepToSuccess($step);
	}

	/**
	 * @param string $name
	 * @param string $message
	 * @throws GuzzleException
	 */
	protected function createRemoteIfFileNotExists(string $name, string $message)
	{
		$postFileUrl = sprintf(
			FilesUploadCommand::API_ENDPOINT,
			$this->config['app']['id']
		);
		$postFileBody = json_encode([
			'data' => [
				'attributes' => [
					'is_dir' => true,
				],
				'id'         => $name,
				'type'       => 'files',
			],
		]);
		try {
			$this->sendRequest($postFileUrl, 'POST', $message, $postFileBody);
		} catch (ClientException $clientException) {
			return;
		}

	}

	/**
	 * @param string $step
	 * @param Closure $revertFunction
	 */
	protected function setStep(string $step, Closure $revertFunction)
	{
		$this->steps[$step] = [
			'status'         => 'init',
			'revertFunction' => $revertFunction,
		];
	}

	/**
	 * @param string $step
	 */
	protected function updateStepToSuccess(string $step)
	{
		$this->steps[$step]['status'] = 'success';
	}

}