<?php

namespace Console\App\Commands;

use Console\App\Helpers\AuthHelper;
use Console\App\Helpers\HttpHelper;
use GuzzleHttp\ClientInterface;
use Symfony\Component\Console\Command\Command as BaseCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class Command extends BaseCommand
{
	const DEFAULT_CLI_OPTIONS = [
		'help', 'quiet', 'verbose', 'version', 'ansi', 'no-ansi', 'no-interaction', 'json',
	];
	protected $httpHelper;

	public function __construct(ClientInterface $httpClient, $name = null)
	{
		parent::__construct($name);
		$this->httpHelper = new HttpHelper($httpClient);
	}

	protected function configure()
	{
		$this->addOption('json', 'j', InputOption::VALUE_NONE, 'Output as a raw json');
	}


	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws \Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->getFormatter()->setStyle('warning', new OutputFormatterStyle('black', 'yellow'));
		if (!AuthHelper::isTokenExist()) {
			$this->callAuthCommand();
		}

		$this->httpHelper->setHeader('Authorization', 'Bearer ' . AuthHelper::getToken());
	}

	/**
	 * @param string $message
	 * @param OutputInterface $output
	 * @return ProgressBar
	 */
	public static function getProgressBar(string $message, OutputInterface $output): ProgressBar
	{
		ProgressBar::setFormatDefinition('custom', $message . '%bar%');
		$progressBar = new ProgressBar($output);
		$progressBar->setFormat('custom');
		$progressBar->setProgressCharacter('.');
		$progressBar->setEmptyBarCharacter(' ');
		$progressBar->setBarCharacter('.');
		$progressBar->setBarWidth(30);

		return $progressBar;
	}

	/**
	 * @throws \Exception
	 */
	protected function callAuthCommand()
	{
		$authCommand = $this->getApplication()->find(AuthCommand::getDefaultName());
		$args = [
			'command' => AuthCommand::getDefaultName(),
		];
		$input = new ArrayInput($args);
		$authCommand->run($input, new ConsoleOutput());
	}

	/**
	 * @param string $questionText
	 * @param OutputInterface $output
	 * @param InputInterface $input
	 * @return bool
	 */
	protected function askConfirm(string $questionText, OutputInterface $output, InputInterface $input): bool
	{
		if (!empty($input->getOption('yes'))) {
			return true;
		}
		/** @var QuestionHelper $helper */
		$helper = $this->getHelper('question');
		$question = new ConfirmationQuestion($questionText, false);
		return $helper->ask($input, $output, $question);
	}

	/**
	 * @param array $data
	 * @param string $fieldName
	 * @return array
	 */
	protected function sortData(array $data, string $fieldName): array
	{
		uasort($data, function ($a, $b) use ($fieldName) {
			if (!isset($a['attributes'][$fieldName]) || !isset($b['attributes'][$fieldName])) {
				return $a;
			} else {
				return $a['attributes'][$fieldName] <=> $b['attributes'][$fieldName];
			}
		});

		return $data;
	}
}