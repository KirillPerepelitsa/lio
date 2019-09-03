<?php

namespace Console\App\Commands\Tokens;

use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TokensNewCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/tokens';

	protected static $defaultName = 'tokens:new';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Creates a new token')
			->setHelp('https://www.lamp.io/api#/tokens/tokensCreate')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'Token description', '')
			->addOption('enable', null, InputOption::VALUE_NONE, 'Enable token');
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
				'POST',
				self::API_ENDPOINT,
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody(
						$input->getOption('description'),
						$input->getOption('enable')
					),
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
		$table->setHeaderTitle('Token Created');
		$table->setStyle('box');
		$table->setHeaders(['Id', 'Attributes']);
		$serializer = new ArraySerializer(['recursive' => true]);
		$serializedDocument = $serializer->serialize($document);
		$attributes = [];
		foreach ($serializedDocument['data']['attributes'] as $attributeKey => $attribute) {
			array_push($attributes, $attributeKey . ' : ' . $attribute);
		}
		$table->addRow([
			$serializedDocument['data']['id'],
			implode(PHP_EOL, $attributes),
		]);


		return $table;
	}

	/**
	 * @param string $description
	 * @param bool $enable
	 * @return string
	 */
	protected function getRequestBody(string $description, bool $enable): string
	{
		return json_encode([
			'data' => [
				'attributes' => [
					'description' => $description,
					'enabled'     => $enable,
				],
				'type'       => 'tokens',
			],
		]);
	}
}