<?php

namespace Console\App\Deploy;

use Closure;
use Console\App\Commands\AppRuns\AppRunsDescribeCommand;
use Console\App\Commands\AppRuns\AppRunsNewCommand;
use Console\App\Commands\Command;
use Console\App\Commands\DbBackups\DbBackupsDescribeCommand;
use Console\App\Commands\DbBackups\DbBackupsNewCommand;
use Console\App\Commands\DbRestores\DbRestoresDescribeCommand;
use Console\App\Commands\DbRestores\DbRestoresNewCommand;
use Console\App\Commands\Files\FilesDeleteCommand;
use Console\App\Commands\Files\FilesUploadCommand;
use Console\App\Commands\Files\SubCommands\FilesUpdateUnarchiveCommand;
use Console\App\Helpers\DeployHelper;
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use ZipArchive;

abstract class DeployAbstract implements DeployInterface
{
	const ARCHIVE_NAME = 'lamp-io.zip';

	const SQL_DUMP_NAME = 'dump.sql';

	const KEEP_OLD_RELEASES = 10;

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
	 * DeployAbstract constructor.
	 * @param Application $application
	 * @param array $config
	 */
	public function __construct(Application $application, array $config)
	{
		$this->application = $application;
		$this->consoleOutput = new ConsoleOutput();
		$this->config = $config;
	}

	public function deleteOldReleases()
	{

	}

	/**
	 * @param string $appPath
	 * @param bool $isFirstDeploy
	 */
	public function deployApp(string $appPath, bool $isFirstDeploy)
	{
		$this->appPath = $appPath;
		$this->isFirstDeploy = $isFirstDeploy;
		$this->releaseFolder = DeployHelper::RELEASE_FOLDER . '/' . $this->config['release'] . '/';
	}

	/**
	 * @return string
	 * @throws Exception
	 */
	protected function getZipApp()
	{
		$stepName = 'zip';
		$this->setStep($stepName, function () {
			unlink($this->appPath . self::ARCHIVE_NAME);
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
	 * @throws Exception
	 */
	protected function clearApp()
	{
		$step = 'clearApp';
		$this->setStep($step, function () {
			return;
		});
		$command = 'rm -rf *';
		$this->appRunCommand($this->config['app']['id'], $command, 'Removing default files');
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