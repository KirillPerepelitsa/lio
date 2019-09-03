<?php


namespace Console\App\Commands\Files;

use Art4\JsonApiClient\Exception\ValidationException;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Console\App\Commands\Command;
use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Helper\QuestionHelper;

class FilesDownloadCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/apps/%s/files/%s';

	protected static $defaultName = 'files:download';

	const RESPONSE_FORMAT_TYPES = [
		'gzip'   => [
			'AcceptHeader' => 'Accept: application/x-gzip',
		],
		'zip'    => [
			'AcceptHeader' => 'Accept: application/zip',
		],
		'stream' => [
			'AcceptHeader' => 'Accept: application/octet-stream',
		],
	];

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Download files')
			->setHelp('try rebooting')
			->addArgument('app_id', InputArgument::REQUIRED, 'App ID')
			->addArgument('file_id', InputArgument::REQUIRED, 'The ID of the file. The ID is also the file path relative to its app root.')
			->addArgument('dir', InputArgument::OPTIONAL, 'Specify path where will be stored downloaded files (Default is your working dir)', getcwd());
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
			$appId = $input->getArgument('app_id');
			$fileId = $input->getArgument('file_id');
			$filePathAsArray = explode('/', rtrim($fileId, '/'));
			$fileName = end($filePathAsArray);
			if ($this->isDirectory($appId, $fileId)) {
				if ($this->askQuestion($input, $output)) {
					$output->writeln('<info>Downloading started</info>');
					$this->downloadDirectory(
						$appId, $fileId, $fileName, rtrim($input->getArgument('dir') . DIRECTORY_SEPARATOR)
					);
					$fileExtension = '.zip';
				} else {
					return 0;
				}
			} else {
				$output->writeln('<info>Downloading started</info>');
				$this->downloadFile(
					$appId, $fileId, $fileName, rtrim($input->getArgument('dir') . DIRECTORY_SEPARATOR)
				);
				$fileExtension = '';
			}
			$output->writeln(
				'<info>File received, ' . $fileName . $fileExtension . '</info>'
			);
		} catch (GuzzleException $exception) {
			$output->writeln($exception->getMessage());
			return 1;
		} catch (ValidationException $validationException) {
			$output->writeln($validationException->getMessage());
			return 1;
		}
	}

	/**
	 * @param string $appId
	 * @param string $fileId
	 * @param string $fileName
	 * @param string $downloadPath
	 * @throws GuzzleException
	 */
	protected function downloadFile(string $appId, string $fileId, string $fileName, string $downloadPath)
	{
		$this->httpHelper->getClient()->request(
			'GET',
			sprintf(self::API_ENDPOINT,
				$appId,
				urlencode($fileId)
			),
			[
				'headers' => array_merge(
					$this->httpHelper->getHeaders(),
					['Accept' => self::RESPONSE_FORMAT_TYPES['stream']['AcceptHeader']]
				),
				'sink'    => fopen($downloadPath . DIRECTORY_SEPARATOR . $fileName, 'w+'),
			]
		);
	}

	/**
	 * @param string $appId
	 * @param string $fileId
	 * @param string $fileName
	 * @param string $downloadPath
	 * @throws GuzzleException
	 */
	protected function downloadDirectory(string $appId, string $fileId, string $fileName, string $downloadPath)
	{
		$this->httpHelper->getClient()->request(
			'GET',
			sprintf(self::API_ENDPOINT,
				$appId,
				urlencode($fileId)
			),
			[
				'headers' => array_merge(
					$this->httpHelper->getHeaders(),
					['Accept' => self::RESPONSE_FORMAT_TYPES['zip']['AcceptHeader']]
				),
				'sink'    => fopen($downloadPath . $fileName . '.zip', 'w+'),
			]
		);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return bool
	 */
	protected function askQuestion(InputInterface $input, OutputInterface $output)
	{
		$question = new Question('You specified as file_id path to a directory, do you want to download it as ZIP?[yes/no]' . PHP_EOL);
		$question->setMaxAttempts(3);
		$question->setValidator(function ($answer) {
			if (!in_array($answer, ['yes', 'no', 'y', 'n'])) {
				throw new \RuntimeException(
					'Please prompt your answer as [yes, no, y, n]'
				);
			}
			return $answer;
		});
		/** @var QuestionHelper $questionHelper */
		$questionHelper = $this->getHelper('question');
		$answer = $questionHelper->ask($input, $output, $question);
		return in_array($answer, ['yes', 'y',]);
	}

	/**
	 * @param string $appId
	 * @param string $fileId
	 * @throws GuzzleException
	 * @return bool
	 */
	protected function isDirectory(string $appId, string $fileId): bool
	{
		/** @var Document $document */
		$document = Parser::parseResponseString($this->getFileInfo($appId, $fileId)->getBody()->getContents());
		return $document->get('data.attributes.is_dir');
	}


	/**
	 * @param string $appId
	 * @param string $fileId
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	protected function getFileInfo(string $appId, string $fileId): ResponseInterface
	{
		return $this->httpHelper->getClient()->request(
			'GET',
			sprintf(self::API_ENDPOINT,
				$appId,
				urlencode($fileId)
			),
			[
				'headers' => $this->httpHelper->getHeaders(),
			]
		);
	}
}
