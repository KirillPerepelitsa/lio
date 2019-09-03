<?php


namespace Console\App\Commands\AppRuns;


use Art4\JsonApiClient\Exception\ValidationException;
use Console\App\Commands\Command;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AppRunsDeleteCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/app_runs/%s';

	protected static $defaultName = 'app_runs:delete';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete runned command')
			->setHelp('https://www.lamp.io/api#/app_runs/appRunsDelete')
			->addArgument('app_run_id', InputArgument::REQUIRED, 'ID of runned command');
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
			$this->httpHelper->getClient()->request(
				'DELETE',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('app_run_id')
					),
				[
					'headers' => $this->httpHelper->getHeaders(),
				]
			);
			if (empty($input->getOption('json'))) {
				$output->writeln('<info>Command with id . ' . $input->getArgument('app_run_id') . ' successfully deleted</info>');
			}
		} catch (ValidationException $validationException) {
			$output->writeln($validationException->getMessage());
			return 1;
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			return 1;
		}
	}
}