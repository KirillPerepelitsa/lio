<?php

namespace Console\App\Helpers;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ConfigHelper
{
	const LAMP_IO_CONFIG = 'lamp.io.yaml';

	const YAML_INLINE_LEVEL = 4;

	/**
	 * @var array
	 */
	protected $config = [];

	protected $appPath;

	public function __construct(string $appPath)
	{
		try {
			$this->appPath = $appPath;
			$parsedYaml = Yaml::parseFile($this->appPath . self::LAMP_IO_CONFIG);
			$this->config = !empty($parsedYaml) ? $parsedYaml : [];
		} catch (ParseException $parseException) {
			file_put_contents($appPath . DIRECTORY_SEPARATOR . self::LAMP_IO_CONFIG, '');
		}
	}

	public function get(string $keys = '')
	{
		if (empty($keys)) {
			return $this->config;
		}
		$config = $this->config;
		$keys = explode('.', $keys);
		foreach ($keys as $arg) {
			if (isset($config[$arg])) {
				$config = $config[$arg];
			} else {
				return '';
			}
		}
		return $config;
	}

	public static function arraySet(array &$array, $key, $value)
	{
		if (is_null($key)) return $array = $value;
		$keys = explode('.', $key);
		while (count($keys) > 1) {
			$key = array_shift($keys);
			if (!isset($array[$key]) || !is_array($array[$key])) {
				$array[$key] = [];
			}
			$array =& $array[$key];
		}
		$array[array_shift($keys)] = $value;
		return $array;
	}

	public function set(string $keys, $value)
	{
		$keys = [$keys => $value];
		foreach ($keys as $key => $val) {
			self::arraySet($this->config, $key, $val);
		}
	}

	public function save()
	{
		$yaml = Yaml::dump($this->config, self::YAML_INLINE_LEVEL);
		file_put_contents($this->appPath . self::LAMP_IO_CONFIG, $yaml);
	}

	public static function yamlToArray(string $yamlString)
	{
		return Yaml::parse($yamlString);
	}

}