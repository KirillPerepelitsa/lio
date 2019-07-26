<?php

namespace Console\App\Helpers;

use InvalidArgumentException;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ConfigHelper
{
	/** TODO update it  */
	const LAMP_IO_CONFIG = 'lamp.io.yaml';

	protected $yamlParser;

	/**
	 * @var array
	 */
	protected $config = [];

	public function __construct(string $pwd)
	{
		try {
			$this->config = Yaml::parseFile($pwd . DIRECTORY_SEPARATOR . self::LAMP_IO_CONFIG);
		} catch (ParseException $parseException) {
			echo $parseException->getMessage();
			file_put_contents($pwd . DIRECTORY_SEPARATOR . self::LAMP_IO_CONFIG, '');
		}
	}

	public function get(string ... $args)
	{
		$config = $this->config;
		foreach ($args as $arg) {
			if (isset($config[$arg])) {
				$config = $config[$arg];
			} else {
				throw new InvalidArgumentException('Key not exists, ' . $arg);
			}
		}
		return $config;
	}

	public function set(string $value, string ... $args)
	{
		$config = $this->config;
		foreach ($args as $arg) {
			$this->config[$arg];
		}
		$config = $value;

	}

	public function save()
	{

	}


}