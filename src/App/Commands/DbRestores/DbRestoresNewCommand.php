<?php

namespace Console\App\Commands\DbRestores;

use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DbRestoresNewCommand extends Command
{
	protected static $defaultName = 'db_restores:new';

	const API_ENDPOINT = 'https://api.lamp.io/db_restores';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Create db restore job (restore a db backup to a database)')
			->setHelp('https://www.lamp.io/api#/db_restores/dbRestoresCreate')
			->addArgument('database_id', InputArgument::REQUIRED, 'The id of database')
			->addArgument('db_backup_id', InputArgument::REQUIRED, 'The ID of the db backup');
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
					'body'    => $this->getRequestBody($input->getArgument('db_backup_id'), $input->getArgument('database_id')),
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->writeln('<info> On database ' . $input->getArgument('database_id') . ' restore job started, with backup ' . $input->getArgument('db_backup_id') . '</info>');
			}
		} catch (GuzzleException $guzzleException) {
			$output->writeln('<error>' . $guzzleException->getMessage() . '</error>');
			return 1;
		}
	}

	/**
	 * @param string $dbBackup
	 * @param string $dbId
	 * @return string
	 */
	protected function getRequestBody(string $dbBackup, string $dbId): string
	{
		return json_encode([
			'data' => [
				'attributes' => [
					'db_backup_id'       => $dbBackup,
					'target_database_id' => $dbId,
				],
				'type'       => 'db_restores',
			],
		]);
	}
}