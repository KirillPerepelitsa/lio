<?php


namespace Console\App\Commands\Databases;


use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use InvalidArgumentException;

class DatabasesDescribeCommand extends Command
{
	protected static $defaultName = 'databases:describe';

	const API_ENDPOINT = 'https://api.lamp.io/databases/%s';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Returns a database')
			->setHelp('https://www.lamp.io/api#/databases/databasesShow')
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
			$response = $this->httpHelper->getClient()->request(
				'GET',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('database_id')
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
		} catch (InvalidArgumentException $invalidArgumentException) {
			$output->writeln($invalidArgumentException->getMessage());
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
		$table->setHeaderTitle('Database');
		$serializer = new ArraySerializer(['recursive' => true]);
		$headers = ['Id'];
		$row = [$document->get('data.id')];
		foreach ($serializer->serialize($document)['data']['attributes'] as $attributeKey => $attribute) {
			array_push(
				$row, ($attributeKey != 'my_cnf') ? $attribute : wordwrap(trim(preg_replace(
				'/\s\s+|\t/', ' ', $attribute
			)), 40));
			array_push($headers, $attributeKey);
		}
		$table->setHeaders($headers);
		$table->addRow($row);
		return $table;

	}
}

