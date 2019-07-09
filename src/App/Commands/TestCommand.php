<?php


namespace Console\App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class TestCommand extends Command
{
	protected static $defaultName = 'test';

	protected function configure()
	{
		$this->setDescription('Test command')
			->setHelp('try rebooting');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$output->writeln("<info>Test command executed</info>");
	}
}