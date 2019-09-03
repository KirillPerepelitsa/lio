<?php

namespace Console\App\Deploy;

interface DeployInterface
{

	/**
	 * @param string $appPath
	 * @param bool $isFirstDeploy
	 * @return void
	 */
	public function deployApp(string $appPath, bool $isFirstDeploy);

	/**
	 * @return void
	 */
	public function revertProcess();
}