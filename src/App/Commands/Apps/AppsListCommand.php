<?php

namespace Console\App\Commands\Apps;

use Art4\JsonApiClient\V1\Document;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Console\App\Commands\Command;

class AppsListCommand extends Command
{
	protected static $defaultName = 'apps:list';

	const API_ENDPOINT = 'https://api.lamp.io/apps';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('gets the set of apps from the org associated with your token')
			->setHelp('try rebooting');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			$response = $this->httpHelper->getClient()->request(
				'GET',
				self::API_ENDPOINT,
				['headers' => $this->httpHelper->getHeaders()]
			);
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			return 1;
		}

		try {
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				/** @var Document $document */
				$document = Parser::parseResponseString($response->getBody()->getContents());
				$table = $this->getOutputAsTable($document, new Table($output));
				$table->render();
			}
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
		$table->setHeaderTitle('Apps');
		$table->setHeaders(['App id', 'App description']);
		$serializer = new ArraySerializer(['recursive' => true]);
		$apps = $serializer->serialize($document);
		foreach ($apps['data'] as $key => $app) {
			$table->addRow([
				$app['id'],
				$app['attributes']['description'],
			]);
			if ($key != count($apps['data']) - 1) {
				$table->addRow(new TableSeparator());
			}
		}

		return $table;
	}
}
