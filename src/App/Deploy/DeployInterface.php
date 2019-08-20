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

	/**
	 * @param string $currentRelease
	 * @param string $previousRelease
	 * @return void
	 */
	public function revert(string $currentRelease, string $previousRelease);

	public function deleteOldReleases();
}