<?php


namespace Console\App\Commands\AppRuns;

use Art4\JsonApiClient\Exception\ValidationException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Console\App\Commands\Command;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Symfony\Component\Console\Helper\Table;
use Art4\JsonApiClient\Serializer\ArraySerializer;

class AppRunsListCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/app_runs/';

	protected static $defaultName = 'app_runs:list';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Get all runned commands on all apps associated to your token ')
			->setHelp('https://www.lamp.io/api#/app_runs/appRunsList');
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
				self::API_ENDPOINT,
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
		} catch (ValidationException $validationException) {
			$output->writeln($validationException->getMessage());
			return 1;
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
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
		$table->setHeaderTitle('App runs list');
		$serializer = new ArraySerializer(['recursive' => true]);
		$appRuns = $serializer->serialize($document->get('data'));
		$table->setHeaders(['Id', 'App ID', 'Complete', 'Command', 'Execution date', 'Complete date']);
		$sortedData = $this->sortData($appRuns, 'updated_at');
		$lastElement = end($sortedData);
		foreach ($sortedData as $key => $data) {
			$table->addRow([
				$data['id'],
				$data['attributes']['app_id'],
				$data['attributes']['complete'],
				wordwrap($data['attributes']['command'], 20, PHP_EOL),
				$data['attributes']['created_at'],
				$data['attributes']['updated_at'],
			]);
			if ($data != $lastElement) {
				$table->addRow(new TableSeparator());
			}
		}

		return $table;
	}
}
