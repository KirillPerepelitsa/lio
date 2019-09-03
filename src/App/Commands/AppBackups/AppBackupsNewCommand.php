<?php


namespace Console\App\Commands\AppBackups;

use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppBackupsNewCommand extends Command
{
	protected static $defaultName = 'app_backups:new';

	const API_ENDPOINT = 'https://api.lamp.io/app_backups';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Back up files in app')
			->setHelp('https://www.lamp.io/api#/app_backups/appBackupsCreate')
			->addArgument('app_id', InputArgument::REQUIRED, 'The ID of the app');
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
			$this->httpHelper->getClient()->request(
				'POST',
				self::API_ENDPOINT,
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody($input->getArgument('app_id')),
				]
			);
			$output->writeln('<info>Backuping app with id ' . $input->getArgument('app_id') . ', started</info>');
		} catch (GuzzleException $guzzleException) {
			$output->writeln('<error>' . $guzzleException->getMessage() . '</error>');
			return 1;
		}
	}

	/**
	 * @param string $appId
	 * @return string
	 */
	protected function getRequestBody(string $appId): string
	{
		return json_encode([
			'data' =>
				[
					'attributes' =>
						[
							'app_id' => $appId,
						],
					'type'       => 'app_backups',
				],
		]);
	}
}