<?php


namespace Console\App\Commands\Databases;

use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DatabasesDeleteCommand extends Command
{
	protected static $defaultName = 'databases:delete';

	const API_ENDPOINT = 'https://api.lamp.io/databases/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete a database')
			->setHelp('https://www.lamp.io/api#/databases/databasesDelete')
			->addArgument('database_id', InputArgument::REQUIRED, 'The id of database')
			->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirm delete question');
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
		if (!$this->askConfirm('<info>Are you sure you want to delete database? (y/N)</info>', $output, $input)) {
			return 0;
		}
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
			return 1;
		} catch (InvalidArgumentException $invalidArgumentException) {
			$output->writeln($invalidArgumentException->getMessage());
			return 1;
		}

	}
}