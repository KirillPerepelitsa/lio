<?php

namespace Console\App\Deployers;

interface DeployInterface
{

	/**
	 * @param string $appPath
	 * @param bool $isFirstDeploy
	 * @param bool $isNewDbInstance
	 * @return void
	 */
	public function deployApp(string $appPath, bool $isFirstDeploy, bool $isNewDbInstance);

	/**
	 * @return void
	 */
	public function revertProcess();
}