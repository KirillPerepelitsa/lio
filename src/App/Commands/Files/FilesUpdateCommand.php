<?php

namespace Console\App\Commands\Files;

use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpdateCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s%s';

	const ALLOWED_COMMANDS = [
		'fetch', 'move', 'unarchive',
	];

	const OPTIONS_KEYS = [
		'recur', 'command', 'source',
	];

	protected static $defaultName = 'files:update';

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('This will update the file at specified file ID (file path including file name, relative to app root)')
			->setHelp('https://www.lamp.io/api#/files/filesUpdateID')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('remote_path', InputArgument::REQUIRED, 'File path on app, that should be updated')
			->addArgument('file', InputArgument::REQUIRED, 'Path to a local file, which content will sent to remote')
			->addOption('recur', 'r', InputOption::VALUE_NONE, 'Recur into directories')
			->addOption('command', null, InputOption::VALUE_REQUIRED, 'Command that will be executed in your app (Allowed: fetch, move, unarchive)')
			->addOption('source', '', InputOption::VALUE_REQUIRED, 'A URL to that will be retrieved if "command" is "fetch"');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		try {
			$this->validateArguments($input->getArgument('file'), $input->getOption('command'));

			$this->httpHelper->getClient()->request(
				'PATCH',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_id'),
					$input->getArgument('file'),
					$this->httpHelper->optionsToQuery($input->getOptions(), self::OPTIONS_KEYS)
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody(
						$input->getArgument('file'),
						$input->getArgument('remote_path')
					),
				]);
			$output->writeln('<info>Success, file ' . $input->getArgument('remote_path') . ' has been updated</info>');
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			exit(1);
		}
	}

	/**
	 * @param $file
	 * @param $command
	 */
	protected function validateArguments($file, $command)
	{
		if (!file_exists($file)) {
			throw new \InvalidArgumentException('File not exists');
		}
		if (!empty($command) && !in_array($command, self::ALLOWED_COMMANDS)) {
			throw new \InvalidArgumentException('Commands option not allowed, list of allowed options: ' . implode(', ', self::ALLOWED_COMMANDS));
		}
	}

	/**
	 * @param string $localFile
	 * @param string $remoteFile
	 * @return string
	 */
	protected function getRequestBody(string $localFile, string $remoteFile): string
	{
		return json_encode([
			'data' => [
				'attributes' =>
					[
						'apache_writeable' => true,
						'contents'         => file_get_contents($localFile),
						'target'           => 'string',
					],
				'id'         => $remoteFile,
				'type'       => 'files',
			],
		]);

	}

}