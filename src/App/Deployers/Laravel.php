<?php

namespace Console\App\Deployers;

use Console\App\Commands\Files\FilesDeleteCommand;
use Console\App\Commands\Files\FilesUpdateCommand;
use Console\App\Helpers\DeployHelper;
use Dotenv\Dotenv;
use Exception;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;

class Laravel extends DeployerAbstract
{

	const SKIP_COMMANDS = [
		'artisan migrate',
	];

	const SHARED_DIR = ['storage'];

	const SHARED_FILES = ['.env'];

	/**
	 * @param string $appPath
	 * @param bool $isFirstDeploy
	 * @param bool $isNewDbInstance
	 * @return void
	 * @throws Exception
	 * @throws GuzzleException
	 */
	public function deployApp(string $appPath, bool $isFirstDeploy, bool $isNewDbInstance)
	{
		parent::deployApp($appPath, $isFirstDeploy, $isNewDbInstance);
		(Dotenv::create($this->appPath))->load();
		$zip = $this->getZipApp();
		$this->uploadToApp($zip, $this->releaseFolder . self::ARCHIVE_NAME);
		$this->unarchiveApp($this->releaseFolder . self::ARCHIVE_NAME);
		$this->deleteArchiveRemote($this->releaseFolder . self::ARCHIVE_NAME);
		$this->createSharedStorage($this->getSharedStorageCommands($this->getSharedDirs()));
		if ($this->isFirstDeploy) {

		}
		$this->setUpPermissions();
		if ($this->isFirstDeploy) {
			if ($this->config['database']['system'] == 'sqlite') {
				$this->initSqliteDatabase();
			} else {
				if ($this->config['database']['type'] == 'internal') {
					$this->initDatabase(getenv('DB_HOST'));
					$this->createDatabaseUser(
						getenv('DB_HOST'),
						getenv('DB_USERNAME'),
						getenv('DB_PASSWORD'),
						$this->config['database']['root_password']
					);
				}

				$this->createDatabase(
					getenv('DB_HOST'),
					getenv('DB_DATABASE'),
					getenv('DB_USERNAME'),
					getenv('DB_PASSWORD')
				);
				if (!empty($this->config['database']['sql_dump'])) {
					$this->importSqlDump(
						getenv('DB_HOST'),
						getenv('DB_DATABASE'),
						getenv('DB_USERNAME'),
						getenv('DB_PASSWORD')
					);
				}
			}
		}
		$dbBackupId = $this->backupDatabase();
		$this->deleteArchiveLocal();
		$this->runMigrations('artisan migrate --force', $dbBackupId);
		$this->runCommands(self::SKIP_COMMANDS);
		$this->symlinkRelease($this->releaseFolder . 'public', 'Linking your current release', $this->isFirstDeploy);
	}

	private function getSharedDirs(): array
	{
		return array_merge(
			self::SHARED_DIR,
			!empty($this->config['shared']) ? $this->config['shared'] : []
		);
	}

	/**
	 * @param array $dirs
	 * @return array
	 * @throws Exception
	 */
	protected function getSharedStorageCommands(array $dirs): array
	{
		return [
			'delete_public_storage'            => [
				'message' => 'Removing release/public/storage',
				'execute' => function (string $message) {
					$deleteFileUrl = sprintf(
						FilesDeleteCommand::API_ENDPOINT,
						$this->config['app']['id'],
						$this->releaseFolder . 'public/storage'
					);
					try {
						$this->sendRequest($deleteFileUrl, 'DELETE', $message);
					} catch (ClientException $clientException) {
						$this->consoleOutput->write(PHP_EOL);
						return;
					}
				},
			],
			'create_shared'                    => [
				'message' => 'Creating shared storage folder if not exists',
				'execute' => function (string $message) {
					$this->createRemoteIfFileNotExists('shared', $message);
				},
			],
			'copy_storage_to_shared'           => [
				'message' => 'Copying release %s to shared folder',
				'execute' => function (string $message) use ($dirs) {
					foreach ($dirs as $dir) {
						if (file_exists($this->appPath . $dir)) {
							$this->appRunCommand(
								$this->config['app']['id'],
								'cp -rv ' . $this->releaseFolder . rtrim($dir, '/') . '/ shared/',
								sprintf($message, $dir)
							);
						}
					}
				},
			],
			'delete_release_storage'           => [
				'message' => 'Removing %s folder from release',
				'execute' => function (string $message) use ($dirs) {
					foreach ($dirs as $dir) {
						if (file_exists($this->appPath . $dir)) {
							$this->appRunCommand(
								$this->config['app']['id'],
								'rm -rf ' . $this->releaseFolder . rtrim($dir, '/') . '/',
								sprintf($message, $dir)
							);
						}
					}
				},
			],
			'symlink_shared_to_release'        => [
				'message' => 'Symlink shared %s to release',
				'execute' => function (string $message) use ($dirs) {
					foreach ($dirs as $dir) {
						if (file_exists($this->appPath . $dir)) {
							$dirName = explode('/', $dir);
							$dirName = $dirName[count($dirName) - 1];
							$this->appRunCommand(
								$this->config['app']['id'],
								'ln -s /var/www/shared/' . rtrim($dirName, '/') . ' ' . $this->releaseFolder . rtrim($dir, '/'),
								sprintf($message, $dir)
							);
						}
					}
				},
			],
			'symlink_shared_to_release_public' => [
				'message' => 'Symlink storage to public dir',
				'execute' => function (string $message) {
					$this->appRunCommand(
						$this->config['app']['id'],
						'ln -s /var/www/' . $this->releaseFolder . 'storage/app/public ' . $this->releaseFolder . 'public/storage',
						$message
					);
				},
			],
			'give_permissions'                 => [
				'message' => 'Apache can write in shared/%s',
				'execute' => function (string $message) use ($dirs) {
					foreach ($dirs as $dir) {
						$dirName = explode('/', $dir);
						$dirName = $dirName[count($dirName) - 1];
						$fileUpdateUrl = sprintf(
							sprintf(
								FilesUpdateCommand::API_ENDPOINT . '?recur=true',
								$this->config['app']['id'],
								'shared/' . rtrim($dirName, '/')
							)
						);
						$this->sendRequest($fileUpdateUrl, 'PATCH', sprintf($message, $dir), json_encode([
							'data' => [
								'attributes' => [
									'apache_writable' => true,
								],
								'id'         => 'shared/' . rtrim($dirName, '/'),
								'type'       => 'files',
							],
						]));
					}
				},
			],
		];
	}


	/**
	 *
	 */
	public function revertProcess()
	{
		$this->consoleOutput->writeln('<comment>Starting revert</comment>');
		foreach (array_reverse($this->steps) as $step) {
			if ($step['status'] == 'success') {
				$step['revertFunction']();
			}
		}
	}

	/**
	 * @param array $localEnv
	 * @return array
	 */
	private function prepareEnvFile(array $localEnv): array
	{
		if ($this->config['database']['system'] == 'sqlite') {
			$env = [
				'APP_URL'     => $this->config['app']['url'],
				'DB_DATABASE' => DeployHelper::SQLITE_ABSOLUTE_REMOTE_PATH,
			];
		} else {
			$env = [
				'APP_URL' => $this->config['app']['url'],
				'DB_HOST' => $this->config['database']['id'],
			];
		}
		$envFromConfig = !empty($this->config['environment']) ? $this->config['environment'] : [];
		$remoteEnv = array_merge($envFromConfig, $env);
		return array_merge($localEnv, $remoteEnv);
	}
}