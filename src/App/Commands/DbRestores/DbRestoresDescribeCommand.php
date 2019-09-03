<?php

namespace Console\App\Commands\DbRestores;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class DbRestoresDescribeCommand extends Command
{
	protected static $defaultName = 'db_restores:describe';

	const API_ENDPOINT = 'https://api.lamp.io/db_restores/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return a db restore job')
			->setHelp('https://www.lamp.io/api#/db_restores/dbRestoresShow')
			->addArgument('db_restore_id', InputArgument::REQUIRED, 'The ID of the db restore');
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
				'GET',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('db_restore_id')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$table = $this->getOutputAsTable($document, new Table($output));
				$table->render();
			}
		} catch (GuzzleException $guzzleException) {
			$output->writeln('<error>' . $guzzleException->getMessage() . '</error>');
			return 1;
		}
	}

	/**
	 * @param string $dbRestoreId
	 * @param Application $application
	 * @return bool
	 * @throws Exception
	 */
	public static function isDbRestoreCompleted(string $dbRestoreId, Application $application): bool
	{
		$dbBackupDescribeCommand = $application->find(self::getDefaultName());
		$args = [
			'command'       => self::getDefaultName(),
			'db_restore_id' => $dbRestoreId,
			'--json'        => true,
		];
		$bufferOutput = new BufferedOutput();
		$dbBackupDescribeCommand->run(new ArrayInput($args), $bufferOutput);
		/** @var Document $document */
		$document = Parser::parseResponseString($bufferOutput->fetch());
		if ($document->get('data.attributes.status') === 'failed') {
			throw new Exception('Database restore job failed');
		}
		return $document->get('data.attributes.complete');
	}

	/**
	 * @param Document $document
	 * @param Table $table
	 * @return Table
	 */
	protected function getOutputAsTable(Document $document, Table $table): Table
	{
		$table->setHeaderTitle('Db restore job ' . $document->get('data.id'));
		$table->setHeaders(['Db restore ID', 'Database', 'Db backup', 'Complete', 'Created at', 'Organization id', 'Status', 'Updated at']);
		$table->addRow([
			$document->get('data.id'),
			$document->get('data.attributes.target_database_id'),
			$document->get('data.attributes.db_backup_id'),
			$document->get('data.attributes.complete'),
			$document->get('data.attributes.created_at'),
			$document->get('data.attributes.organization_id'),
			$document->get('data.attributes.status'),
			$document->get('data.attributes.updated_at'),
		]);
		return $table;
	}
}