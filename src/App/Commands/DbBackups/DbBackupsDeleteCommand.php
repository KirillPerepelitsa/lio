<?php

namespace Console\App\Commands\DbBackups;

use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DbBackupsDeleteCommand extends Command
{
	protected static $defaultName = 'db_backups:delete';

	const API_ENDPOINT = 'https://api.lamp.io/db_backups/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete a db backup')
			->setHelp('https://www.lamp.io/api#/db_backups/dbBackupsDelete')
			->addArgument('db_backup_id', InputArgument::REQUIRED, 'The ID of the db backup')
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

		if (!$this->askConfirm('<info>Are you sure you want to delete db backup? (y/N)</info>', $output, $input)) {
			return 0;
		}

		try {
			$response = $this->httpHelper->getClient()->request(
				'DELETE',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('db_backup_id')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->writeln(
					'<info>Backup deleted ' . $input->getArgument('db_backup_id') . '</info>'
				);
			}
		} catch (GuzzleException $guzzleException) {
			$output->writeln('<error>' . $guzzleException->getMessage() . '</error>');
			return 1;
		}
	}
}