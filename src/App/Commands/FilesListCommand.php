<?php


namespace Console\App\Commands;


use Art4\JsonApiClient\Exception\ValidationException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Art4\JsonApiClient\Serializer\ArraySerializer;

class FilesListCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s';

	const DEFAULT_FORMAT = 'json';

	const MAX_LIMIT = '1000';

	const RESPONSE_FORMAT_TYPES = [
		'json' => [
			'AcceptHeader' => 'Accept: application/vnd.api+json',
		],
		'gzip' => [
			'AcceptHeader' => 'Accept: application/x-gzip',
		],
		'zip'  => [
			'AcceptHeader' => 'Accept: application/zip',
		],
	];

	protected static $defaultName = 'files:list';

	protected $counter = 0;

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('Return files from the root of an app')
			->setHelp('try rebooting')
			->addArgument('app_id', InputArgument::REQUIRED, 'From which app_id need to get fields?')
			->addArgument('file_id', InputArgument::OPTIONAL, 'The ID of the file. The ID is also the file path relative to its app root.', '/')
			->addOption('limit', 'l', InputOption::VALUE_REQUIRED, ' The number of results to return in each response to a list operation. The default value is 1000 (the maximum allowed). Using a lower value may help if an operation times out', self::MAX_LIMIT)
			->addOption('human-readable', '', InputOption::VALUE_NONE, 'Format size values from raw bytes to human readable format')
			->addOption('recursive', 'r', InputOption::VALUE_NONE, 'Command is performed on all files or objects under the specified path')
			->addOption('gzip', 'g', InputOption::VALUE_NONE, 'Set this flag, if you want response as a gzip archive')
			->addOption('zip', 'z', InputOption::VALUE_NONE, 'Set this flag, if you want response as a zip archive');
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
		$format = $this->getResponseFormat($input);
		try {
			if ($format != self::DEFAULT_FORMAT) {
				$this->sendRequest(
					$format,
					$input->getArgument('app_id'),
					$input->getArgument('file_id')
				);
				$output->writeln('<info>File received, ' . $this->getPath('lamp') . '.' . $format . '</info>');
			} else {
				$table = new Table($output);
				$table->setStyle('compact');
				$this->prepareOutput($input, $table, $input->getArgument('file_id'));
				$table->render();

			}
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
		} catch (ValidationException $validationException) {
			$output->writeln('<error>' . $validationException->getMessage() . '</error>');
		}

	}

	/**
	 * @param InputInterface $input
	 * @param Table $table
	 * @param string $filePath
	 * @throws GuzzleException
	 * @throws  ValidationException
	 */
	protected function prepareOutput(InputInterface $input, Table $table, string $filePath)
	{
		if ($this->counter == $input->getOption('limit') || $this->counter == self::MAX_LIMIT) {
			return;
		}
		$response = $this->sendRequest(
			self::DEFAULT_FORMAT,
			$input->getArgument('app_id'),
			$filePath
		);
		/** @var Document $document */
		$document = Parser::parseResponseString($response->getBody()->getContents());
		$serializer = new ArraySerializer(['recursive' => true]);
		$siblings = [];
		if ($document->has('data.relationships.siblings')) {
			$siblingsData = $document->get('data.relationships.siblings.data');
			foreach ($serializer->serialize($siblingsData) as $val) {
				$siblings[] = $val['id'];
			}
		}

		foreach ($serializer->serialize($document->get('included')) as $key => $val) {
			if (empty($val['relationships']) || in_array($val['id'], $siblings)) {
				continue;
			}
			$table->addRow(explode(' ', $this->formatFileInfo(
				$val['id'],
				$val['attributes'],
				$input->getOption('human-readable')
			)));
			$this->counter += 1;
			if ($val['attributes']['is_dir'] && !empty($input->getOption('recursive'))) {
				$this->prepareOutput($input, $table, $val['id']);
			}
		}
	}

	/**
	 * @param string $format
	 * @param string $appId
	 * @param string $filePath
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	protected function sendRequest(string $format, string $appId, string $filePath): ResponseInterface
	{
		return $this->httpHelper->getClient()->request(
			'GET',
			sprintf(self::API_ENDPOINT,
				$appId,
				urlencode($filePath)
			),
			$this->getRequestOptions($format)
		);


	}

	/**
	 * @param string $fileName
	 * @param array $fileAttributes
	 * @param bool $isHumanReadable
	 * @return string
	 */
	protected function formatFileInfo(string $fileName, array $fileAttributes, bool $isHumanReadable = true): string
	{
		$date = date('Y-m-d H:i:s', strtotime($fileAttributes['modify_time']));
		$size = $isHumanReadable ? $this->formatBytes($fileAttributes['size']) : $fileAttributes['size'];
		return $date . '  ' . $size . '  ' . $fileName;
	}

	/**
	 * @param int $bytes
	 * @param int $precision
	 * @return string
	 */
	protected function formatBytes(int $bytes, int $precision = 2): string
	{
		$unit = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
		$exp = floor(log($bytes, 1024)) | 0;
		if (empty($unit[$exp])) {
			$result = (string)$bytes;
		} else {
			$result = round($bytes / (pow(1024, $exp)), $precision) . $unit[$exp];
		}
		return $result;
	}


	/**
	 * @param string $format
	 * @return array
	 */
	protected function getRequestOptions(string $format): array
	{
		return array_merge([
			'headers' => array_merge($this->httpHelper->getHeaders(), ['Accept' => self::RESPONSE_FORMAT_TYPES[$format]['AcceptHeader']]),
		], $format != self::DEFAULT_FORMAT ? ['sink' => fopen($this->getPath('lamp') . '.' . $format, 'w')] : []);
	}


	/**
	 * @param string $fileName
	 * @return string
	 */
	protected function getPath(string $fileName): string
	{
		return getenv('HOME') . getenv("HOMEDRIVE") . getenv("HOMEPATH") . DIRECTORY_SEPARATOR . '.config' . DIRECTORY_SEPARATOR . 'lamp.io/' . $fileName;
	}


	/**
	 * @param InputInterface $input
	 * @return string
	 */
	protected function getResponseFormat(InputInterface $input): string
	{
		$format = self::DEFAULT_FORMAT;
		foreach ($input->getOptions() as $key => $option) {
			if (array_key_exists($key, self::RESPONSE_FORMAT_TYPES) && !empty($option)) {
				$format = $key;
			}
		}
		return $format;
	}


}