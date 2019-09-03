<?php

namespace Console\App\Commands\DbBackups;

use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbBackupsNewCommand extends Command
{
	protected static $defaultName = 'db_backups:new';

	const API_ENDPOINT = 'https://api.lamp.io/db_backups';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Back up files in database')
			->setHelp('https://www.lamp.io/api#/db_backups/dbBackupsCreate')
			->addArgument('database_id', InputArgument::REQUIRED, 'The id of database');
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
			$response = $this->httpHelper->getClient()->request(
				'POST',
				self::API_ENDPOINT,
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody($input->getArgument('database_id')),
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->writeln('<info>Backuping database with id ' . $input->getArgument('database_id') . ', started</info>');
			}
		} catch (GuzzleException $guzzleException) {
			$output->writeln('<error>' . $guzzleException->getMessage() . '</error>');
			return 1;
		}
	}

	/**
	 * @param string $dbId
	 * @return string
	 */
	protected function getRequestBody(string $dbId): string
	{
		return json_encode([
			'data' => [
				'attributes' => [
					'database_id' => $dbId,
				],
				'type'       => 'db_backups',
			],
		]);
	}
}