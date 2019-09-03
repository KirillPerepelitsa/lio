<?php


namespace Console\App\Commands\Organizations;


use Art4\JsonApiClient\Exception\ValidationException;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class OrganizationsUpdateCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/organizations/%s';

	const OPTIONS_TO_PARAM = [
		'payment' => 'stripe_source_id',
	];

	/**
	 * @var string
	 */
	protected static $defaultName = 'organizations:update';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Update an organization')
			->setHelp('https://www.lamp.io/api#/organizations/organizationsUpdate')
			->addArgument('organization_id', InputArgument::REQUIRED, 'The ID of the organization')
			->addOption('name', null, InputOption::VALUE_REQUIRED, 'New organization name')
			->addOption('promo_code', null, InputOption::VALUE_REQUIRED, 'Apply promo code')
			->addOption('payment', 'p', InputOption::VALUE_REQUIRED, 'Stripe source id');
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
				'PATCH',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('organization_id')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
					'body'    => $this->getRequestBody($input),
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
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		$attributes = [];
		foreach ($input->getOptions() as $optionKey => $optionValue) {
			if (!in_array($optionKey, self::DEFAULT_CLI_OPTIONS) && !empty($input->getOption($optionKey))) {
				$key = isset(self::OPTIONS_TO_PARAM[$optionKey]) ? self::OPTIONS_TO_PARAM[$optionKey] : $optionKey;
				$attributes[$key] = $optionValue;
			}

		}
		if (empty($attributes)) {
			throw new InvalidArgumentException('Command requires at least one option to be executed');
		}
		return json_encode([
			'data' => [
				'attributes' => $attributes,
				'id'         => $input->getArgument('organization_id'),
				'type'       => 'organizations',
			],
		]);
	}

	/**
	 * @param Document $document
	 * @param Table $table
	 * @return Table
	 */
	protected function getOutputAsTable(Document $document, Table $table): Table
	{
		$table->setHeaderTitle('Organization');
		$table->setStyle('box');
		$table->setHeaders([
			'Id', 'Attributes',
		]);
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
}