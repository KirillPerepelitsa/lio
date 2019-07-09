<?php

namespace Console\App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Humbug\SelfUpdate\Updater;
use Humbug\SelfUpdate\Strategy\GithubStrategy;

class SelfUpdateCommand extends Command
{
	const PACKAGE_NAME = 'lamp-io/lio';
	const FILE_NAME = 'lio.phar';

	protected static $defaultName = 'self-update';

	/**
	 * @var OutputInterface
	 */
	protected $output;
	/**
	 * @var string
	 */
	protected $version;

	/**
	 * Execute the command.
	 *
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (!\Phar::running()) {
			$output->writeln('<error>Cant execute command. This command works only under phar build</error>');
			exit(1);
		}
		$this->version = $this->getApplication()->getVersion();
		$updater = new Updater(null, false);
		$updater->setStrategy(Updater::STRATEGY_GITHUB);
		$this->update($this->getGithubReleasesUpdater($updater), $output);
	}


	/**
	 * @param Updater $updater
	 * @return Updater
	 */
	protected function getGithubReleasesUpdater(Updater $updater)
	{
		/** @var GithubStrategy $strategy */
		$strategy = $updater->getStrategy();
		$strategy->setPackageName(self::PACKAGE_NAME);
		$strategy->setPharName(self::FILE_NAME);
		$strategy->setCurrentLocalVersion($this->version);
		return $updater;
	}


	/**
	 * @param Updater $updater
	 */
	protected function update(Updater $updater, OutputInterface $output)
	{
		$output->writeln('Updating...' . PHP_EOL);
		try {
			$result = $updater->update();
			$newVersion = $updater->getNewVersion();
			$oldVersion = $updater->getOldVersion();
			if (strlen($newVersion) == 40) {
				$newVersion = 'dev-' . $newVersion;
			}
			if (strlen($oldVersion) == 40) {
				$oldVersion = 'dev-' . $oldVersion;
			}

			if ($result) {
				$output->writeln('<info>Lio cli has been updated.</info>>');
				$output->writeln(sprintf(
					'<info>Current version is:</info> <options=bold>%s</options=bold>.',
					$newVersion
				));
				$output->writeln(sprintf(
					'<info>Previous version was:</info> <options=bold>%s</options=bold>.',
					$oldVersion
				));
			} else {
				$output->writeln('<info>Lio cli currently up to date.</info>');
				$output->writeln(sprintf(
					'<info>Current version is:</info> <options=bold>%s</options=bold>.',
					$oldVersion
				));
			}
		} catch (\Exception $e) {
			$output->writeln(sprintf('Error: <error>%s</error>', $e->getMessage()));
			exit(1);
		}
	}

	/**
	 *
	 */
	protected function configure()
	{
		$this
			->setName('self-update')
			->setDescription('Update lio.phar to most recent stable release');
	}
}