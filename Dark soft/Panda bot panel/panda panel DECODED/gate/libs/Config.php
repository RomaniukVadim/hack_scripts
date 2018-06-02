<?php

class miae0a
{
	static public $__config;

	static protected function loadConfig()
	{
		require __DIR__ . '/../cfg/cfg.php';
		require __DIR__ . '/../../system/config.php';
		self::$__config = $config;
	}

	static public function config($name)
	{
		if (!self::$__config) {
			self::loadConfig();
		}

		return array_key_exists($name, self::$__config) ? self::$__config[$name] : NULL;
	}
}


?>
