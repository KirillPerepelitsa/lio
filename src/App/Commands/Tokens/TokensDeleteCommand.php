<?php


namespace Console\App\Commands\Tokens;


use Art4\JsonApiClient\Exception\ValidationException;
use Console\App\Commands\Command;
use Exception;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TokensDeleteCommand extends Command
{
	const API_ENDPOINT = 'https://api.lamp.io/tokens/%s';

	/**
	 * @var string
	 */
	protected static $defaultName = 'tokens:delete';

	/**
	 *
	 */
	protected function configure()
	{
		parent::configure();
		$this->setDescription('Delete a token')
			->setHelp('https://www.lamp.io/api#/tokens/tokensDelete')
			->addArgument('token_id', InputArgument::REQUIRED, 'The ID of the token.')
			->addOption('yes', 'y', InputOption::VALUE_NONE, 'Skip confirm delete question');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws Exception
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		parent::execute($input, $output);

		try {
			if (!$this->askConfirm('<info>Are you sure you want to token? (y/N)</info>', $output, $input)) {
				return 0;
			}
			$response = $this->httpHelper->getClient()->request(
				'DELETE',
				sprintf(
					self::API_ENDPOINT,
					$input->getArgument('token_id')
				),
				[
					'headers' => $this->httpHelper->getHeaders(),
				]
			);
			if (!empty($input->getOption('json'))) {
				$output->writeln($response->getBody()->getContents());
			} else {
				$output->writeln(
					'<info>Token ' . $input->getArgument('token_id') . ' deleted</info>'
				);
			}
		} catch (GuzzleException $guzzleException) {
			$output->writeln($guzzleException->getMessage());
			return 1;
		} catch (ValidationException $e) {
			$output->writeln($e->getMessage());
			return 1;
		}
	}
}