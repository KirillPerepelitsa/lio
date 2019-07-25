<?php

namespace Console\App\Commands;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Apps\AppsNewCommand;
use Console\App\Deploy\DeployInterface;
use Console\App\Deploy\Laravel;
use Console\App\Helpers\ConfigHelper;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use SplFileObject;
use Exception;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class DeployCommand extends Command
{
	/**
	 * @var string
	 */
	protected static $defaultName = 'deploy';

	const DEPLOYS = [
		'laravel' => Laravel::class,
	];


	/**
	 * @var array
	 */
	protected $config;

	protected $configHelper;

	/**
	 * @var bool
	 */
	protected $isNewApp = true;

	public function __construct(ClientInterface $httpClient, ConfigHelper $configHelper, $name = null)
	{
		parent::__construct($httpClient, $name);
		$this->configHelper = $configHelper;
	}

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('Deploy your app.')
			->addArgument('dir', InputArgument::OPTIONAL, 'Path to a directory of your application, default value current working directory', getcwd())
			->addOption('laravel', null, InputOption::VALUE_NONE, 'The ID of the app');
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
			$deployObject = $this->getDeployObject($input->getOptions(), $input->getArgument('dir'));
			$appPath = rtrim($input->getArgument('dir'), '/') . DIRECTORY_SEPARATOR;
//			$configFilePath = $appPath . self::LAMP_IO_CONFIG;
			var_export($this->configHelper->get('app_config', '32'));
			die();
//			$this->setUpConfig($configFilePath);
			$deployObject->isCorrectApp($appPath);
			$appId = $this->getAppId($output, $input);
//			$this->saveToConfig($configFilePath);
			$deployObject->deployApp($appId, $this->isNewApp);
			$deployObject->deployDb();
			$output->writeln('<info>Done, check it out at https://' . $appId . '.lamp.app/</info>');
		} catch (Exception $exception) {
			$output->writeln('<error>' . $exception->getMessage() . '</error>');
			exit(1);
		}
	}

	/**
	 * @param string $configFilePath
	 */
	protected function saveToConfig(string $configFilePath)
	{
		if (file_exists($configFilePath)) {
			return;
		}

		foreach ($this->config as $key => $value) {
			file_put_contents($configFilePath, $key . '=' . $value . PHP_EOL);
		}

	}

	/**
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return string
	 * @throws Exception
	 */
	protected function getAppId(OutputInterface $output, InputInterface $input): string
	{
		if (!empty($this->config['app_id'])) {
			$this->isNewApp = false;
			return $this->config['app_id'];
		}

		$questionHelper = $this->getHelper('question');
		$question = new ConfirmationQuestion('<info>This looks like a new app, shall we create a lamp.io app for it? (Y/n):</info>');
		if (!$questionHelper->ask($input, $output, $question)) {
			$output->writeln('<info>You must to create new app or select to which app your project should be deployed, in .lamp.io file inside of your project</info>');
			exit();
		}
		$appsNewCommand = $this->getApplication()->find(AppsNewCommand::getDefaultName());
		$args = [
			'command' => AppsNewCommand::getDefaultName(),
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		$appsNewCommand->run(new ArrayInput($args), $bufferOutput);
		/** @var Document $document */
		$document = Parser::parseResponseString($bufferOutput->fetch());
		$appId = $document->get('data.id');
		$output->writeln('<info>' . $appId . ' created!</info>');
		$this->setConfig('app_id', $appId);
		return $appId;
	}

	/**
	 * @param string $configPath
	 */
	protected function setUpConfig(string $configPath)
	{
		if (file_exists($configPath)) {
			$this->config = $this->parseConfigFile($configPath);
		} else {
			$this->config = [];
		}
	}

	/**
	 * @param string $key
	 * @return string
	 */
	protected function getConfig(string $key): string
	{
		return !empty($this->config[$key]) ? $this->config[$key] : '';
	}

	/**
	 * @param string $key
	 * @param string $value
	 */
	protected function setConfig(string $key, string $value)
	{
		$this->config[$key] = $value;
	}

	/**
	 * @param string $configPath
	 * @return array
	 */
	protected function parseConfigFile(string $configPath): array
	{
		$config = [];
		$fileObject = new SplFileObject($configPath);
		$fileObject->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);
		foreach ($fileObject as $row) {
			if (empty(trim($row))) {
				continue;
			}
			$line = explode('=', str_replace(' ', '', trim($row)));
			$config[$line[0]] = $line[1];

		}
		return $config;
	}

	/**
	 * @param array $options
	 * @param string $appDir
	 * @return DeployInterface
	 */
	protected function getDeployObject(array $options, string $appDir): DeployInterface
	{
		foreach ($options as $optionKey => $option) {
			if ($option && array_key_exists($optionKey, self::DEPLOYS)) {
				$deployClass = (self::DEPLOYS[$optionKey]);
				return new $deployClass(rtrim($appDir, '/'), $this->getApplication());
			}
		}
		throw new InvalidArgumentException('App type for deployment, not specified, apps allowed ' . implode(',', array_keys(self::DEPLOYS)));
	}

}