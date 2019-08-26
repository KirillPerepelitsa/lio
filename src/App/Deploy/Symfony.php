<?php

namespace Console\App\Deploy;

class Symfony extends DeployAbstract
{
	/**
	 *
	 */
	public function revertProcess()
	{
		$this->consoleOutput->writeln('<comment>Starting revert</comment>');
		foreach (array_reverse($this->steps) as $step) {
			if ($step['status'] == 'success') {
				$step['revertFunction']();
			}
		}
	}

	public function deployApp(string $appPath, bool $isFirstDeploy)
	{
		parent::deployApp($appPath, $isFirstDeploy);
	}

}