<?php

namespace Console\App\Helpers;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Files\FilesDeleteCommand;
use Console\App\Commands\Files\FilesListCommand;
use Exception;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class DeployHelper
{
	const RELEASE_FOLDER = 'releases';

	const PUBLIC_FOLDER = 'public';

	const SQLITE_ABSOLUTE_REMOTE_PATH = '/var/www/sqlite/db.sqlite';

	const SQLITE_RELATIVE_REMOTE_PATH = 'sqlite/db.sqlite';

	/**
	 * @param string $appType
	 * @param string $appPath
	 * @return bool
	 */
	static public function isCorrectApp(string $appType, string $appPath): bool
	{
		switch ($appType) {
			case 'laravel':
				$composerJson = json_decode(file_get_contents($appPath . 'composer.json'), true);
				return array_key_exists('laravel/framework', $composerJson['require']);
			case 'symfony':
				$composerJson = json_decode(file_get_contents($appPath . 'composer.json'), true);
				return array_key_exists('symfony/framework-bundle', $composerJson['require']);
			default:
				return false;
		}
	}

	/**
	 * @param string $appId
	 * @param Application $application
	 * @return string
	 * @throws Exception
	 */
	static public function getActiveRelease(string $appId, Application $application): string
	{
		$filesListCommand = $application->find(FilesListCommand::getDefaultName());
		$args = [
			'command' => FilesListCommand::getDefaultName(),
			'app_id'  => $appId,
			'file_id' => self::PUBLIC_FOLDER,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($filesListCommand->run(new ArrayInput($args), $bufferOutput) != '0') {
			throw new Exception($bufferOutput->fetch());
		}
		/** @var Document $document */
		$document = Parser::parseResponseString(trim($bufferOutput->fetch()));
		$releaseName = [];
		preg_match('/\/[0-9]*\//', $document->get('data.attributes.target'), $releaseName);
		return !empty($releaseName[0]) ? self::RELEASE_FOLDER . '/' . trim($releaseName[0], '/') : '';
	}

	/**
	 * @param string $appId
	 * @param string $releasePath
	 * @param Application $application
	 * @param OutputInterface $output
	 * @throws Exception
	 */
	public static function deleteRelease(string $appId, string $releasePath, Application $application, OutputInterface $output)
	{
		$filesDeleteCommand = $application->find(FilesDeleteCommand::getDefaultName());
		$args = [
			'command'     => FilesDeleteCommand::getDefaultName(),
			'app_id'      => $appId,
			'remote_path' => $releasePath,
			'--yes'       => true,
			'--json'      => true,
		];
		$filesDeleteCommand->run(new ArrayInput($args), $output);
	}

	/**
	 * @param string $appId
	 * @param Application $application
	 * @return array
	 * @throws Exception
	 */
	static public function getReleases(string $appId, Application $application): array
	{
		$filesListCommand = $application->find(FilesListCommand::getDefaultName());
		$args = [
			'command' => FilesListCommand::getDefaultName(),
			'app_id'  => $appId,
			'file_id' => DeployHelper::RELEASE_FOLDER,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($filesListCommand->run(new ArrayInput($args), $bufferOutput) != '0') {
			throw new Exception($bufferOutput->fetch());
		}
		/** @var Document $document */
		$document = Parser::parseResponseString(trim($bufferOutput->fetch()));
		$releaseKey = 'data.relationships.children.data';
		$serializer = new ArraySerializer(['recursive' => true]);
		return !empty($document->has($releaseKey)) ? $serializer->serialize($document->get($releaseKey)) : [];
	}

	/**
	 * @param string $appId
	 * @param Application $application
	 * @return bool
	 * @throws Exception
	 */
	static public function isReleasesFolderExists(string $appId, Application $application): bool
	{
		$filesListCommand = $application->find(FilesListCommand::getDefaultName());
		$args = [
			'command' => FilesListCommand::getDefaultName(),
			'app_id'  => $appId,
			'file_id' => self::RELEASE_FOLDER,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		return $filesListCommand->run(new ArrayInput($args), $bufferOutput) == '0';
	}

	/**
	 * @param string $appId
	 * @param string $release
	 * @param Application $application
	 * @return string
	 * @throws Exception
	 */
	public static function getReleaseConfigContent(string $appId, string $release, Application $application): string
	{
		$filesListCommand = $application->find(FilesListCommand::getDefaultName());
		$args = [
			'command' => FilesListCommand::getDefaultName(),
			'app_id'  => $appId,
			'file_id' => $release . '/' . ConfigHelper::LAMP_IO_CONFIG,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($filesListCommand->run(new ArrayInput($args), $bufferOutput) != '0') {
			throw new Exception($bufferOutput->fetch());
		}
		/** @var Document $document */
		$document = Parser::parseResponseString(trim($bufferOutput->fetch()));
		return !empty($document->has('data.attributes.contents')) ? $document->get('data.attributes.contents') : '';
	}

	/**
	 * @param string $appId
	 * @param string $releaseMigrationFolder
	 * @param Application $application
	 * @return array
	 * @throws Exception
	 */
	public static function getReleaseMigrations(string $appId, string $releaseMigrationFolder, Application $application): array
	{
		$filesListCommand = $application->find(FilesListCommand::getDefaultName());
		$args = [
			'command' => FilesListCommand::getDefaultName(),
			'app_id'  => $appId,
			'file_id' => $releaseMigrationFolder,
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($filesListCommand->run(new ArrayInput($args), $bufferOutput) != '0') {
			throw new Exception($bufferOutput->fetch());
		}
		/** @var Document $document */
		$document = Parser::parseResponseString(trim($bufferOutput->fetch()));
		if (empty($document->has('data.relationships.children.data'))) {
			return [];
		} else {
			$serializer = new ArraySerializer(['recursive' => true]);
			$migrations = [];
			foreach ($serializer->serialize($document->get('data.relationships.children.data')) as $value) {
				preg_match('/((\/[0-9]*)_([a-zA-Z]*)).*$/', $value['id'], $matched);
				$migrations[] = ltrim(rtrim($matched[0], '.php'), '/');
			}
			return $migrations;
		}
	}
}