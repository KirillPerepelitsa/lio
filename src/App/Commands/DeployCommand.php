<?php

namespace Console\App\Commands;

use Art4\JsonApiClient\Helper\Parser;
use Art4\JsonApiClient\V1\Document;
use Console\App\Commands\Apps\AppsDescribeCommand;
use Console\App\Commands\Apps\AppsNewCommand;
use Console\App\Commands\Databases\DatabasesDescribeCommand;
use Console\App\Deploy\DeployInterface;
use Console\App\Deploy\Laravel;
use Console\App\Helpers\ConfigHelper;
use Console\App\Helpers\DeployHelper;
use Console\App\Helpers\PasswordHelper;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;
use Exception;
use RuntimeException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Console\App\Commands\Databases\DatabasesNewCommand;
use Symfony\Component\Console\Question\Question;

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
	 * @var ConfigHelper
	 */
	protected $configHelper;

	/**
	 * @var bool
	 */
	protected $isAppAlreadyExists = true;

	public function __construct(ClientInterface $httpClient, $name = null)
	{
		parent::__construct($httpClient, $name);
	}

	/**
	 *
	 */
	protected function configure()
	{
		$this->setDescription('Deploy your app.')
			->addArgument('dir', InputArgument::OPTIONAL, 'Path to a directory of your application, default value current working directory', getcwd())
			->addOption('laravel', null, InputOption::VALUE_NONE, 'Deploy laravel app');
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
			if (!DeployHelper::isCorrectApp($this->configHelper->get('type'), $appPath)) {
				throw new Exception(ucfirst($this->configHelper->get('type')) . ' has not been found found on your directory');
			}
			$appId = $this->createApp($output, $input);
			$this->createDatabase($output, $input);
			$this->configHelper->save();
			$deployObject = $this->getDeployObject();
			$deployObject->deployApp($appPath, $this->isFirstDeploy());
			$deployObject->deleteOldReleases();
			$output->writeln('<info>Done, check it out at https://' . $appId . '.lamp.app/</info>');
		} catch (Exception $exception) {
			$output->writeln('<error>' . trim($exception->getMessage()) . '</error>');
			if (!empty($deployObject)) {
				$deployObject->revertProcess();
				$output->writeln(PHP_EOL . '<comment>Revert completed</comment>');
			}
			return 1;
		}
	}

	/**
	 * @param string $dbId
	 * @return bool
	 * @throws Exception
	 */
	protected function isDatabaseExists(string $dbId)
	{
		$databasesDescribe = $this->getApplication()->find(DatabasesDescribeCommand::getDefaultName());
		$args = [
			'command'     => DatabasesDescribeCommand::getDefaultName(),
			'database_id' => $dbId,
			'--json'      => true,
		];
		return $databasesDescribe->run(new ArrayInput($args), new NullOutput()) === 0;
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
	 * @return string
	 * @throws Exception
	 */
	protected function createDatabase(OutputInterface $output, InputInterface $input): string
	{
		if (!empty($this->configHelper->get('database.id'))) {
			if (!$this->isDatabaseExists($this->configHelper->get('database.id'))) {
				$output->writeln('<error>db-id(<db_id>) specified in lamp.io.yaml does not exist</error>');
				exit(1);
			}
			return $this->configHelper->get('database.id');
		}

		$questionHelper = $this->getHelper('question');
		$question = new ConfirmationQuestion('<info>This looks like a new app, shall we create a lamp.io database for it? (Y/n):</info>');
		if (!$questionHelper->ask($input, $output, $question)) {
			$output->writeln('<info>You must to create new database or select to which database your project should use, in lamp.io.yaml file inside of your project</info>');
			exit(1);
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
		$bufferOutput = new BufferedOutput();
		if ($databasesNewCommand->run(new ArrayInput($args), $bufferOutput) == '0') {
			/** @var Document $document */
			$document = Parser::parseResponseString($bufferOutput->fetch());
			$databaseId = $document->get('data.id');
			$output->writeln('<info>' . $databaseId . ' created!</info>');
			$this->configHelper->set('database.id', $databaseId);
			$this->configHelper->set('database.connection.host', $this->configHelper->get('database.id'));
			$this->configHelper->set('database.root_password', $document->get('data.attributes.mysql_root_password'));
			$this->setDatabaseCredentials($input, $output);
			return $databaseId;
		} else {
			throw new Exception($bufferOutput->fetch());
		}
	}

	/**
	 * @throws Exception
	 * @return bool
	 */
	protected function isFirstDeploy(): bool
	{
		return !($this->isAppAlreadyExists && DeployHelper::isReleasesFolderExists($this->configHelper->get('app.id'), $this->getApplication()));
	}

	protected function setDatabaseCredentials(InputInterface $input, OutputInterface $output)
	{
		if (empty($this->configHelper->get('database.connection.user'))) {
			$question = new Question('<info>Please enter database user name that will be created for your application:</info>');
			$question->setValidator(function ($value) {
				if (empty($value)) {
					throw new RuntimeException('User name can not be empty');
				}
				return $value;
			});
			$user = $this->getHelper('question')->ask($input, $output, $question);
			$this->configHelper->set('database.connection.user', $user);
			$question = PasswordHelper::getPasswordQuestion(
				'<info>Please enter database password for <above_user>:</info>',
				'',
				$output
			);
			$question->setValidator(function ($value) {
				if (empty($value)) {
					throw new RuntimeException('Password can not be empty');
				}
				return $value;
			});
			$password = $this->getHelper('question')->ask($input, $output, $question);
			$this->configHelper->set('database.connection.password', $password);
		}
	}


	/**
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return string
	 * @throws Exception
	 */
	protected function createApp(OutputInterface $output, InputInterface $input): string
	{
		if (!empty($this->configHelper->get('app.id'))) {
			if (!$this->isAppExists($this->configHelper->get('app.id'))) {
				$output->writeln('<error>app-id(<app_id>) specified in lamp.io.yaml does not exist</error>');
				exit(1);
			}
			$this->isAppAlreadyExists = true;
			return $this->configHelper->get('app.id');
		}

		$questionHelper = $this->getHelper('question');
		$question = new ConfirmationQuestion('<info>This looks like a new app, shall we create a lamp.io app for it? (Y/n):</info>');
		if (!$questionHelper->ask($input, $output, $question)) {
			$output->writeln('<info>You must to create new app or select to which app your project should be deployed, in lamp.io.yaml file inside of your project</info>');
			exit(1);
		}
		$appsNewCommand = $this->getApplication()->find(AppsNewCommand::getDefaultName());
		$args = [
			'command'       => AppsNewCommand::getDefaultName(),
			'--json'        => true,
			'--description' => basename($input->getArgument('dir')),
		];
		if (!empty($this->configHelper->get('app.attributes'))) {
			$attributes = [];
			foreach ($this->configHelper->get('app.attributes') as $key => $appAttribute) {
				$attributes['--' . $key] = $appAttribute;
			}
			$args = array_merge($args, $attributes);
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
			$this->configHelper->set('app.id', $appId);
			$this->configHelper->set('app.url', 'https://' . $appId . '.lamp.app');
			return $appId;
		} else {
			throw new Exception($bufferOutput->fetch());
		}
	}

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
		return new $deployClass($this->getApplication(), $this->configHelper->get());
	}

}