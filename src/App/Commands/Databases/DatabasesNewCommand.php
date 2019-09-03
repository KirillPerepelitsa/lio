<?php

namespace Console\App\Commands\Databases;

use Console\App\Commands\Command;
use Console\App\Helpers\PasswordHelper;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use InvalidArgumentException;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\V1\Document;
use Symfony\Component\Console\Helper\Table;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\Helper\Parser;

class DatabasesNewCommand extends Command
{
	protected static $defaultName = 'databases:new';

	const API_ENDPOINT = 'https://api.lamp.io/databases';

	const EXCLUDE_FROM_OUTPUT = [
		'my_cnf',
		'mysql_root_password',
	];

	/**
	 * @var
	 * string
	 */
	protected $password = '';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Create a new database')
			->setHelp('https://www.lamp.io/api#/databases/databasesCreate')
			->addOption('description', 'd', InputOption::VALUE_REQUIRED, 'Description of your database', '')
			->addOption('memory', 'm', InputOption::VALUE_REQUIRED, 'Amount of virtual memory on your database, default 512Mi', '512Mi')
			->addOption('organization_id', null, InputOption::VALUE_REQUIRED, 'Name of your organization', '')
			->addOption('mysql_root_password', null, InputOption::VALUE_OPTIONAL, 'Root password', false)
			->addOption('my_cnf', null, InputOption::VALUE_REQUIRED, 'Path to your database config file', '')
			->addOption('ssd', null, InputOption::VALUE_REQUIRED, 'Size of ssd storage, default 1Gi', '1Gi')
			->addOption('vcpu', null, InputOption::VALUE_REQUIRED, 'The number of virtual cpu cores available, default 0.25', '0.25');
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

		if ($this->isPassWordOptionExist($input)) {
			$this->password = $this->handlePasswordOption($input, $output);
		}

		try {
			$response = $this->httpHelper->getClient()->request(
				'POST',
				self::API_ENDPOINT,
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
				if (empty($this->password)) {
					$password = $document->get('data.attributes.mysql_root_password');
					$output->writeln(
						'<warning>Database password: ' . $password . '</warning>' . PHP_EOL . '<warning>WARNING: This is the last opportunity to see this password!</warning>'
					);
				}
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
	 * @param InputInterface $input
	 * @return bool
	 */
	protected function isPassWordOptionExist(InputInterface $input): bool
	{
		return $input->getOption('mysql_root_password') !== false;
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return string
	 */
	protected function handlePasswordOption(InputInterface $input, OutputInterface $output): string
	{
		if (is_string($input->getOption('mysql_root_password'))) {
			if (empty(trim($input->getOption('mysql_root_password')))) {
				throw new InvalidArgumentException('Error: Refusing to set empty password');
			}
			$password = $input->getOption('mysql_root_password');
		} else {
			/** @var QuestionHelper $helper */
			$helper = $this->getHelper('question');
			$question = PasswordHelper::getPasswordQuestion(
				'<info>Please provide a password for the MySQL root user</info>',
				null,
				$output
			);
			$password = $helper->ask($input, $output, $question);
		}

		return $password;
	}


	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getRequestBody(InputInterface $input): string
	{
		if (!empty($input->getOption('my_cnf')) && !file_exists($input->getOption('my_cnf'))) {
			throw  new InvalidArgumentException('Path to mysql config not valid');
		}

		$attributes = [];
		foreach ($input->getOptions() as $optionKey => $option) {
			if (!in_array($optionKey, self::DEFAULT_CLI_OPTIONS) && !empty($option)) {
				$attributes[$optionKey] = ($optionKey == 'vcpu') ? (float)$option : $option;
			}
		}

		if (!empty($this->password)) {
			$attributes['mysql_root_password'] = $this->password;
		}

		return json_encode(
			[
				'data' => [
					'attributes' => $attributes,
					'type'       => 'databases',
				],
			]
		);
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
		$serializedDocument = $serializer->serialize($document);
		$headers = ['Id'];
		$row = [$serializedDocument['data']['id']];

		foreach ($serializedDocument['data']['attributes'] as $key => $value) {
			if (!empty($value) && !in_array($key, self::EXCLUDE_FROM_OUTPUT)) {
				array_push($headers, $key);
				array_push($row, $value);
			}
		}
		$table->setHeaders($headers);
		$table->addRow($row);
		return $table;
	}
}