<?php

namespace Console\App\Helpers;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

class PasswordHelper
{
	/**
	 * @param string $questionOutput
	 * @param mixed $default
	 * @param OutputInterface $output
	 * @return Question
	 */
	public static function getPasswordQuestion(string $questionOutput, $default, OutputInterface $output): Question
	{
		$question = new Question($questionOutput, $default);
		$question->setHiddenFallback(false);
		$question->setHidden(true);
		$question->setValidator(function ($value) use($output) {
			if (is_null($value)) {
				$output->writeln('<error>Error: Refusing to set empty password</error>');
				exit(1);
			}
			return $value;
		});
		$question->setMaxAttempts(10);

		return $question;
	}
}