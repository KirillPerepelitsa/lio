<?php

namespace Console\App\Commands\AppBackups;

use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppBackupsDownloadCommand extends Command
{
	protected static $defaultName = 'app_backups:download';

	const API_ENDPOINT = 'https://api.lamp.io/app_backups/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Download an app backup')
			->setHelp('https://www.lamp.io/api#/app_backups/appBackupsShow')
			->addArgument('app_backup_id', InputArgument::REQUIRED, 'The ID of the app backup')
			->addArgument('dir', InputArgument::OPTIONAL, 'Specify path where will be stored downloaded files (Default is your working dir)', getcwd());
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$output->writeln('<info>Downloading started</info>');
			$this->httpHelper->getClient()->request(
				'GET',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_backup_id')
				),
				[
					'headers' => array_merge(
						$this->httpHelper->getHeaders(),
						['Accept' => 'application/x-gzip']
					),
					'sink'    => fopen($input->getArgument('dir') . DIRECTORY_SEPARATOR . $input->getArgument('app_backup_id') . '.tar.gz' , 'w+'),
				]

			);
			$output->writeln(
				'<info>File received, ' . $input->getArgument('dir') . DIRECTORY_SEPARATOR . $input->getArgument('app_backup_id') . '.tar.gz' . '</info>'
			);

		} catch (GuzzleException $guzzleException) {
			$output->writeln('<error>' . $guzzleException->getMessage() . '</error>');
			return 1;
		}
	}

}