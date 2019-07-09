<?php

namespace Console\App\Helpers;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Compiler
{
	const PHAR_NAME = 'lio.phar';

	protected $output;

	/**
	 * Compiler constructor.
	 */
	public function __construct()
	{
		$this->output = new ConsoleOutput();
	}

	/**
	 *
	 */
	public function compile()
	{
		$this->output->writeln('<info>Starting build phar package</info>');
		$pathToBuild =  $this->getAppRoot() . DIRECTORY_SEPARATOR . 'build' . DIRECTORY_SEPARATOR . self::PHAR_NAME;
		if (file_exists($pathToBuild)) {
			unlink($pathToBuild);
		}
		$phar = new \Phar(
			$pathToBuild,
			0,
			self::PHAR_NAME
		);
		$phar->setSignatureAlgorithm(\Phar::SHA256);

		$finderSort = function (\SplFileInfo $a, \SplFileInfo $b) {
			return strcmp(strtr($a->getRealPath(), '\\', '/'), strtr($b->getRealPath(), '\\', '/'));
		};

		$finder = new Finder();
		$finder
			->files()
			->ignoreVCS(true)
			->name('*.php')
			->notName('Compiler.php')
			->in($this->getAppRoot())
			->sort($finderSort);

		foreach ($finder as $file) {
			$this->addFile($phar, $file);
		}

		$this->addBin($phar);
		// Stubs
		$phar->setStub($this->getStub());
		$phar->stopBuffering();
		unset($phar);
		chmod(
			$pathToBuild,
			0755
		);
		$this->output->writeln('<info>Phar successfully built, path: ' . $pathToBuild . ' </info>');
	}


	/**
	 * @param \Phar $phar
	 */
	private function addBin(\Phar $phar)
	{
		$content = file_get_contents($this->getAppRoot() . '/bin/lio');
		$content = preg_replace('{^#!/usr/bin/env php\s*}', '', $content);
		$phar->addFromString('bin/lio', $content);
	}

	/**
	 * @param \Phar $phar
	 * @param SplFileInfo $file
	 */
	private function addFile(\Phar $phar, SplFileInfo $file)
	{
		$path = $file->getRelativePathname();
		$content = file_get_contents((string)$file);
		$phar->addFromString($path, $content);
	}


	/**
	 * @return string
	 */
	private function getAppRoot()
	{
		return dirname(__DIR__, 3);
	}

	/**
	 * @return string
	 */
	private function getStub()
	{
		$stub = <<<'EOF'
#!/usr/bin/env php
<?php Phar::mapPhar('lio.phar');
require 'phar://lio.phar/bin/lio';
__HALT_COMPILER();
EOF;
		return $stub;
	}
}