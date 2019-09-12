<?php

namespace Console\App\Commands;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\Serializer\ArraySerializer;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\AppRuns\AppRunsNewCommand;
use Console\App\Commands\Apps\AppsDescribeCommand;
use Console\App\Commands\Apps\AppsListCommand;
use Console\App\Commands\Apps\AppsNewCommand;
use Console\App\Commands\Databases\DatabasesListCommand;
use Console\App\Deployers\DeployInterface;
use Console\App\Deployers\Laravel;
use Console\App\Deployers\Symfony;
use Console\App\Helpers\ConfigHelper;
use Console\App\Helpers\DeployHelper;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use Exception;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Console\App\Commands\Databases\DatabasesNewCommand;

class DeployCommand extends Command
{
	/**
	 * @var string
	 */
	protected static $defaultName = 'deploy';

	const DEPLOYS = [
		'laravel' => Laravel::class,
		'symfony' => Symfony::class,
	];

	const DEFAULT_RELEASE_RETAIN = 10;

	/**
	 * @var ConfigHelper
	 */
	protected $configHelper;

	/**
	 * @var bool
	 */
	protected $isAppAlreadyExists;

	/**
	 * @var ClientInterface
	 */
	protected $httpClient;

	/**
	 * @var bool
	 */
	protected $isNewDbInstance = false;

	public function __construct(ClientInterface $httpClient, $name = null)
	{
		parent::__construct($httpClient, $name);
		$this->httpClient = $httpClient;
	}

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('Deploy your app to lamp.io')
			->addArgument('dir', InputArgument::OPTIONAL, 'Path to a directory of your application, default value current working directory', getcwd())
			->addOption('laravel', null, InputOption::VALUE_NONE, 'Deploy laravel app')
			->addOption('symfony', null, InputOption::VALUE_NONE, 'Deploy symfony app');
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
			if ($input->getArgument('dir') == '.') {
				$input->setArgument('dir', getcwd());
			}
			$appPath = rtrim($input->getArgument('dir'), '/') . DIRECTORY_SEPARATOR;
			$this->configHelper = new ConfigHelper($appPath);
			if (empty($this->configHelper->get('type')) || !array_key_exists($this->configHelper->get('type'), self::DEPLOYS)) {
				$this->setAppType($input->getOptions());
			}
			$releaseId = date('YmdHis', time());
			$this->configHelper->set('release', $releaseId);
			if (empty($this->configHelper->get('retain'))) {
				$this->configHelper->set('retain', self::DEFAULT_RELEASE_RETAIN);
			}
			if (!DeployHelper::isCorrectApp($this->configHelper->get('type'), $appPath)) {
				throw new Exception(ucfirst($this->configHelper->get('type')) . ' has not been found found on your directory');
			}
			$appId = $this->getAppId($output, $input);
			/** Need to remove this condition after mysql support will be added for symfony deploy */
			if ($this->configHelper->get('type') == 'symfony') {
				$this->configHelper->set('database.system', 'sqlite');
				$this->configHelper->set('database.type', 'internal');
			} else {
				$this->createDatabase($output, $input, $appId);
			}

			$this->configHelper->save();
			if (!$this->isFirstDeploy()) {
				$this->deleteOldReleases(
					DeployHelper::getReleases($appId, $this->getApplication()),
					$output
				);
			}
			$deployObject = $this->getDeployObject();
			$deployObject->deployApp($appPath, $this->isFirstDeploy(), $this->isNewDbInstance);
			if (!empty($this->configHelper->get('app.attributes.hostname'))) {
				$url = 'https://' . $this->configHelper->get('app.attributes.hostname') . '/';
			} else {
				$url = 'https://' . $appId . '.lamp.app/';
			}
			$output->writeln('<info>Done, check it out at ' . $url . '</info>');
		} catch (Exception $exception) {
			$output->writeln('<error>' . trim($exception->getMessage()) . '</error>');
			$this->configHelper->save();
			if (!empty($deployObject)) {
				$deployObject->revertProcess();
				$output->writeln(PHP_EOL . '<comment>Revert completed</comment>');
			}
			return 1;
		}
	}

	/**
	 * @param array $releases
	 * @param OutputInterface $output
	 * @throws Exception
	 */
	protected function deleteOldReleases(array $releases, OutputInterface $output)
	{
		if (count($releases) + 1 < $this->configHelper->get('retain') || $this->configHelper->get('retain') <= '0') {
			return;
		}
		foreach ($releases as $key => $release) {
			if ($key <= (count($releases) - $this->configHelper->get('retain'))) {
				DeployHelper::deleteRelease($this->configHelper->get('app.id'), $release['id'], $this->getApplication(), $output);
			}
		}
	}

	/**
	 * @param string $appId
	 * @return bool
	 * @throws Exception
	 */
	protected function isAppExists(string $appId)
	{
		$appsDescribe = $this->getApplication()->find(AppsDescribeCommand::getDefaultName());
		$args = [
			'command' => AppsDescribeCommand::getDefaultName(),
			'app_id'  => $appId,
			'--json'  => true,
		];
		return $appsDescribe->run(new ArrayInput($args), new NullOutput()) === 0;
	}


	/**
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @param string $appId
	 * @return void|string
	 * @throws Exception
	 */
	protected function createDatabase(OutputInterface $output, InputInterface $input, string $appId)
	{
		if ($this->configHelper->get('database.type') == 'external') {
			if (!$this->isDbCredentialsSet($this->configHelper->get('database.connection'))) {
				throw new Exception('Please set connection credentials for external database in a lamp.io.yaml');
			}
			$this->configHelper->set('database.system', 'mysql');
			return;
		}
		if ($this->configHelper->get('database.system') == 'sqlite') {
			$this->configHelper->set('database.type', 'internal');
			return;
		}
		$dbId = $this->getLampIoDatabaseId($appId);
		if (empty($dbId)) {
			$dbId = $this->createLampIoDatabase($output, $input, $appId);
			$this->isNewDbInstance = true;
		}
		$this->configHelper->set('database.id', $dbId);
		$this->configHelper->set('database.system', 'mysql');
		$this->configHelper->set('database.type', 'internal');
	}

	/**
	 * @param array $credentials
	 * @return bool
	 */
	protected function isDbCredentialsSet(array $credentials): bool
	{
		return (!empty($credentials['host']) || !empty($credentials['user']) || !empty($credentials['password']));
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	protected function getDbList()
	{
		$appRunsNewCommand = $this->getApplication()->find(DatabasesListCommand::getDefaultName());
		$args = [
			'command' => DatabasesListCommand::getDefaultName(),
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($appRunsNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			return (new ArraySerializer(['recursive' => true]))->serialize($document->get('data'));
		} else {
			throw new Exception('Cant get apps list. ' . $bufferOutput->fetch());
		}
	}

	/**
	 * @param string $appId
	 * @return mixed|string
	 * @throws Exception
	 */
	protected function getLampIoDatabaseId(string $appId)
	{
		$dbList = $this->getDbList();
		foreach ($dbList as $db) {
			if (strpos($db['attributes']['description'], 'app_id:<' . $appId . '>') !== false) {
				$dbId = $db['id'];
				break;
			}
		}
		return $dbId ?? '';
	}

	/**
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @param string $appId
	 * @throws Exception
	 * @return string
	 */
	protected function createLampIoDatabase(OutputInterface $output, InputInterface $input, string $appId): string
	{
		$questionHelper = $this->getHelper('question');
		$question = new ConfirmationQuestion('<info>This looks like a new app, shall we create a lamp.io database for it? (Y/n):</info>');
		if (!$questionHelper->ask($input, $output, $question)) {
			throw new Exception('You must to create new database or select to which database your project should use, in lamp.io.yaml file inside of your project');
		}

		$databasesNewCommand = $this->getApplication()->find(DatabasesNewCommand::getDefaultName());
		$args = [
			'command' => DatabasesNewCommand::getDefaultName(),
			'--json'  => true,
		];
		if (!empty($this->configHelper->get('database.attributes'))) {
			$attributes = [];
			foreach ($this->configHelper->get('database.attributes') as $key => $appAttribute) {
				$attributes['--' . $key] = $appAttribute;
			}
			$args = array_merge($args, $attributes);
		}
		$descriptionPostfix = ' app_id:<' . $appId . '>';
		$args['--description'] = !empty($args['--description']) ? $args['--description'] . $descriptionPostfix : $descriptionPostfix;
		$bufferOutput = new BufferedOutput();
		if ($databasesNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			$databaseId = $document->get('data.id');
			$output->writeln('<info>' . $databaseId . ' created!</info>');
			$this->configHelper->set('database.root_password', $document->get('data.attributes.mysql_root_password'));
			return $databaseId;
		} else {
			throw new Exception($bufferOutput->fetch());
		}
	}


	/**
	 * @return bool
	 * @throws Exception
	 */
	protected function isFirstDeploy(): bool
	{
		return !($this->isAppAlreadyExists && DeployHelper::isReleasesFolderExists($this->configHelper->get('app.id'), $this->getApplication()));
	}

	/**
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return string
	 * @throws Exception
	 */
	protected function getAppId(OutputInterface $output, InputInterface $input): string
	{
		if (!empty($this->configHelper->get('app.id'))) {
			if (!$this->isAppExists($this->configHelper->get('app.id'))) {
				throw new Exception('app-id(<app_id>) specified in lamp.io.yaml does not exist');
			}
			$this->isAppAlreadyExists = true;
			$appId = $this->configHelper->get('app.id');
		} elseif (DeployHelper::isRemoteDeploy()) {
			$pattern = 'autodeploy:<' . basename($input->getArgument('dir')) . '>:<' . $this->getGitBranchName() . '>';
			$appId = $this->getAutoDeployAppId($pattern);
			$this->isAppAlreadyExists = true;
			if (empty($appId)) {
				$appId = $this->createNewApp($output, $input, $pattern);
				$this->isAppAlreadyExists = false;
			}
		} else {
			$appId = $this->createNewApp($output, $input);
		}
		$this->configHelper->set('app.id', $appId);
		$this->configHelper->set('app.url', 'https://' . $appId . '.lamp.app');
		return $appId;
	}

	/**
	 * @param string $descriptionPattern
	 * @return string
	 * @throws Exception
	 */
	protected function getAutoDeployAppId(string $descriptionPattern): string
	{
		$appsList = $this->getAppsList();
		foreach ($appsList as $app) {
			if (strpos($app['attributes']['description'], $descriptionPattern) !== false) {
				$appId = $app['id'];
				break;
			}
		}
		return $appId ?? '';
	}

	/**
	 * @return string
	 */
	protected function getGitBranchName(): string
	{
		return getenv('TRAVIS_BRANCH') . getenv('CIRCLE_BRANCH') . getenv('GIT_BRANCH') .
			getenv('teamcity.build.branch') . getenv('CI_COMMIT_REF_NAME') . getenv('GITHUB_REF');
	}

	/**
	 * @return array
	 * @throws Exception
	 */
	protected function getAppsList(): array
	{
		$appRunsNewCommand = $this->getApplication()->find(AppsListCommand::getDefaultName());
		$args = [
			'command' => AppRunsNewCommand::getDefaultName(),
			'--json'  => true,
		];
		$bufferOutput = new BufferedOutput();
		if ($appRunsNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			return (new ArraySerializer(['recursive' => true]))->serialize($document->get('data'));
		} else {
			throw new Exception('Cant get apps list. ' . $bufferOutput->fetch());
		}
	}


	/**
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @param string $autoDeployDescription
	 * @return string
	 * @throws Exception
	 */
	protected function createNewApp(OutputInterface $output, InputInterface $input, string $autoDeployDescription = ''): string
	{
		if (!empty($this->configHelper->get('app.id'))) {
			if (!$this->isAppExists($this->configHelper->get('app.id'))) {
				throw new Exception('app-id(<app_id>) specified in lamp.io.yaml does not exist');
			}
			$this->isAppAlreadyExists = true;
			return $this->configHelper->get('app.id');
		}

		$questionHelper = $this->getHelper('question');
		$question = new ConfirmationQuestion('<info>This looks like a new app, shall we create a lamp.io app for it? (Y/n):</info>');
		if (!$questionHelper->ask($input, $output, $question)) {
			throw new Exception('You must to create new app or select to which app your project should be deployed, in lamp.io.yaml file inside of your project');
		}
		$appsNewCommand = $this->getApplication()->find(AppsNewCommand::getDefaultName());
		$args = [
			'command'       => AppsNewCommand::getDefaultName(),
			'--json'        => true,
		];
		if (!empty($this->configHelper->get('app.attributes'))) {
			$attributes = [];
			foreach ($this->configHelper->get('app.attributes') as $key => $appAttribute) {
				$attributes['--' . $key] = $appAttribute;
			}
			$args = array_merge($args, $attributes);
		}
		if (!empty($autoDeployDescription)) {
			$description = !empty($args['--description']) ? $args['--description'] : '';
			$args['--description'] = $description . ' ' . $autoDeployDescription;
		} elseif (empty($args['--description'])) {
			$args['--description'] = basename($input->getArgument('dir'));
		}
		if (empty($this->configHelper->get('app.attributes.description'))) {
			$this->configHelper->set('app.attributes.description', basename($input->getArgument('dir')));
		}
		$bufferOutput = new BufferedOutput();
		if ($appsNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			$appId = $document->get('data.id');
			$output->writeln('<info>' . $appId . ' created!</info>');
			return $appId;
		} else {
			throw new Exception($bufferOutput->fetch());
		}
	}

	/**
	 * @param array $options
	 */
	protected function setAppType(array $options)
	{
		foreach ($options as $optionKey => $option) {
			if ($option && array_key_exists($optionKey, self::DEPLOYS)) {
				$this->configHelper->set('type', $optionKey);
				return;
			}
		}
		throw new InvalidArgumentException('App type for deployment, not specified, apps allowed ' . implode(',', array_keys(self::DEPLOYS)));
	}


	/**
	 * @return DeployInterface
	 * @throws Exception
	 */
	protected function getDeployObject(): DeployInterface
	{
		$deployClass = (self::DEPLOYS[$this->configHelper->get('type')]);
		return new $deployClass($this->getApplication(), $this->configHelper->get(), $this->httpClient);
	}

}
