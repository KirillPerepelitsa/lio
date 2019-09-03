<?php

namespace Console\App\Commands\Files\SubCommands;

use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpdateMoveCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s/?%s';

	protected static $defaultName = 'files:update:move';

	protected $subCommand = [
		'command' => 'move',
	];

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Move file on app to another directory')
			->setHelp('https://www.lamp.io/api#/files/filesUpdateID')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('remote_path', InputArgument::REQUIRED, 'File path on app, that should be moved')
			->addArgument('move_path', InputArgument::REQUIRED, 'File path on app, which should be moved. NOTE: * target directory MUST exists, * move path MUST have same name as a target file');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		try {
			$progressBar = self::getProgressBar(
				'Moving ' . $input->getArgument('remote_path') . ' to ' . $input->getArgument('move_path'),
				$output
			);
			$response = $this->httpHelper->getClient()->request(
				'PATCH',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_id'),
					ltrim($input->getArgument('remote_path'), '/'),
					http_build_query($this->subCommand)
				),
				[
					'headers'  => $this->httpHelper->getHeaders(),
					'body'     => $this->getRequestBody(
						$input->getArgument('remote_path'),
						$input->getArgument('move_path')
					),
					'progress' => function () use ($progressBar) {
						$progressBar->advance();
					},
				]);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->writeln('<info>Success, file ' . $input->getArgument('remote_path') . ' has been moved</info>');
			}
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			return 1;
		}
	}

	/**
	 * @param string $remoteFile
	 * @param string $pathToMove
	 * @return string
	 */
	protected function getRequestBody(string $remoteFile, string $pathToMove): string
	{
		return json_encode([
			'data' => [
				'attributes' => [
					'target' => $pathToMove,
				],
				'id'         => ltrim($remoteFile, '/'),
				'type'       => 'files',
			],
		], JSON_UNESCAPED_SLASHES);

	}
}