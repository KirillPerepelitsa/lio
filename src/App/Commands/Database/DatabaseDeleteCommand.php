<?php


namespace Console\App\Commands\Database;

use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DatabaseDeleteCommand extends Command
{
	protected static $defaultName = 'database:delete';

	const API_ENDPOINT = 'https://api.lamp.io/databases/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete a database')
			->setHelp('https://www.lamp.io/api#/databases/databasesDelete')
			->addArgument('database_id', InputArgument::REQUIRED, 'The id of database');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|void|null
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);
		try {
			$this->httpHelper->getClient()->request(
				'DELETE',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('database_id')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
				]
			);
			if (empty($input->getOption('json'))) {
				$output->writeln('<info>Database ' . $input->getArgument('database_id') . ' successfully deleted</info>');
			}
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			exit(1);
		} catch (InvalidArgumentException $invalidArgumentException) {
			$output->writeln($invalidArgumentException->getMessage());
			exit(1);
		}

	}
}