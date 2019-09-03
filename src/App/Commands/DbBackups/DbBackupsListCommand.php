<?php

namespace Console\App\Commands\DbBackups;

use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DbBackupsListCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/db_backups%s';

	const OPTIONS_TO_QUERY_KEYS = [
		'organization_id' => 'filter[organization_id]',
	];
	/**
	 * @var string
	 */
	protected static $defaultName = 'db_backups:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Return db backups')
			->setHelp('https://www.lamp.io/api#/db_backups/dbBackupsList')
			->addOption('organization_id', 'o', InputOption::VALUE_REQUIRED, 'Comma-separated list of requested organization_ids. If omitted defaults to user\'s default organization');
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
			$response = $this->httpHelper->getClient()->request(
				'GET',
				sprintf(
					self::API_ENDPOINT,
					$this->httpHelper->optionsToQuery($input->getOptions(), self::OPTIONS_TO_QUERY_KEYS)
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
			$output->writeln($guzzleException->getMessage());
			return 1;
		} catch (ValidationException $e) {
			$output->writeln($e->getMessage());
			return 1;
		}
	}

	/**
	 * @param Document $document
	 * @param Table $table
	 * @return Table
	 */
	protected function getOutputAsTable(Document $document, Table $table): Table
	{
		$table->setHeaderTitle('Databases Backups');
		$table->setStyle('box');
		$table->setHeaders([
			'Id', 'Db Id', 'Complete', 'Created at', 'Organization Id', 'Status', 'Updated at',
		]);
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$sortedData = $this->sortData($serializedDocument['data'], 'updated_at');
		$lastElement = end($sortedData);
		foreach ($sortedData as $key => $value) {
			$table->addRow([
				$value['id'],
				$value['attributes']['database_id'],
				$value['attributes']['complete'],
				$value['attributes']['created_at'],
				$value['attributes']['organization_id'],
				$value['attributes']['status'],
				$value['attributes']['updated_at'],
			]);
			if ($value != $lastElement) {
				$table->addRow(new TableSeparator());
			}

		}
		return $table;
	}
}