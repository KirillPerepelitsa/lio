<?php

namespace Console\App\Commands\Files\SubCommands;

use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FilesUpdateUnarchiveCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s/?%s';

	protected static $defaultName = 'files:update:unarchive';

	protected $subCommand = ['command' => 'unarchive'];

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Extract your archived file, on your app')
			->setHelp('https://www.lamp.io/api#/files/filesUpdateID')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app')
			->addArgument('remote_path', InputArgument::REQUIRED, 'File path on app, that should be unarchived');
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
			$progressBar = self::getProgressBar('Extracting ' . $input->getArgument('remote_path'), $output);
			$this->httpHelper->getClient()->request(
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
						$input->getArgument('remote_path')
					),
					'progress' => function () use ($progressBar) {
						$progressBar->advance();
					},
				]);
			if (empty($input->getOption('json'))) {
				$output->writeln('<info>Success, file ' . $input->getArgument('remote_path') . ' has been updated</info>');
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