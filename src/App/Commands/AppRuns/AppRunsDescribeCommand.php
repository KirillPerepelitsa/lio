<?php

namespace Console\App\Commands\AppRuns;

use Art4\JsonApiClient\Exception\ValidationException;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Console\App\Commands\Command;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Symfony\Component\Console\Helper\Table;
use Art4\JsonApiClient\Serializer\ArraySerializer;

class AppRunsDescribeCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/app_runs/%s';

	protected static $defaultName = 'app_runs:describe';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Get info about runned command')
			->setHelp('https://www.lamp.io/api#/app_runs/appRunsCreate')
			->addArgument('app_run_id', InputArgument::REQUIRED, 'ID of runned command');
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
					$input->getArgument('app_run_id')
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
		} catch (ValidationException $validationException) {
			$output->writeln($validationException->getMessage());
			exit(1);
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			exit(1);
		}
	}

	/**
	 * @param Document $document
	 * @param Table $table
	 * @return Table
	 */
	protected function getOutputAsTable(Document $document, Table $table): Table
	{
		$table->setHeaderTitle('App runs describe');
		$serializer = new ArraySerializer(['recursive' => true]);
		$appRuns = $serializer->serialize($document);
		$tableHeader = ['id'];
		$row = ['app_id' => $document->get('data.id')];
		foreach ($appRuns['data']['attributes'] as $key => $attribute) {
			if ($key == 'command') {
				$attribute = wordwrap(trim(preg_replace(
					'/\s\s+|\t/', ' ', $attribute
				)), 20);
			}
			array_push($row, $attribute);
			array_push($tableHeader, $key);
		}
		$table->setHeaders($tableHeader);
		$table->addRow($row);

		return $table;
	}
}