<?php

namespace Console\App\Helpers;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

class ConfigHelper
{
	/** TODO update it  */
	const LAMP_IO_CONFIG = '/Users/kirillperepelitsa/work/lio/lamp.io.yaml';

	protected $yamlParser;

	/**
	 * @var array
	 */
	protected $config = [];

	public function __construct()
	{
		try {
			$this->config = Yaml::parseFile(self::LAMP_IO_CONFIG);
		} catch (ParseException $parseException) {
			file_put_contents(self::LAMP_IO_CONFIG, '');
		}
	}

	public function get(string ... $args)
	{
		return call_user_func(function (){}, '');
//		return $this->config[''];
	}

	public function set()
	{

	}

	public function save()
	{

	}


}