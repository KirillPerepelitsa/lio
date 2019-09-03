<?php

namespace Console\App\Commands\Files\SubCommands;

use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpdateFetchCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/?%s';

	protected static $defaultName = 'files:update:fetch';

	protected $subCommand = [
		'command' => 'fetch',
		'source'  => '',
	];

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Fill file with fetched data')
			->setHelp('https://www.lamp.io/api#/files/filesUpdateID')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('remote_path', InputArgument::REQUIRED, 'File path on app, that should be filled with fetched data')
			->addArgument('source', InputArgument::REQUIRED, 'A URL that should be fetched');
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
			$this->subCommand['source'] = $input->getArgument('source');
			$progressBar = self::getProgressBar('Fetching it', $output);
			$response = $this->httpHelper->getClient()->request(
				'POST',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_id'),
					http_build_query($this->subCommand)
				),
				[
					'headers'  => $this->httpHelper->getHeaders(),
					'body'     => $this->getRequestBody(
						$input->getArgument('remote_path')
					),
					'progress' => function () use ($progressBar) {
						$progressBar->advance();
					},
				]);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->writeln(PHP_EOL . '<info>Success, file ' . $input->getArgument('remote_path') . ' has been filled, with fetched data</info>');
			}
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			return 1;
		}
	}

	/**
	 * @param string $remoteFile
	 * @return string
	 */
	protected function getRequestBody(string $remoteFile): string
	{
		return json_encode([
			'data' => [
				'id'   => ltrim($remoteFile, '/'),
				'type' => 'files',
			],
		], JSON_UNESCAPED_SLASHES);

	}

}