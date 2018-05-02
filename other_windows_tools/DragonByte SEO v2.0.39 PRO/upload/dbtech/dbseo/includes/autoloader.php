<?php

abstract class DBSEO_Autoloader
{
	protected static $_prefix = 'DBSEO_';
	protected static $_paths = array();

	public static function register($path)
	{
		self::$_paths[] = (string) $path . '/dbtech/dbseo/includes/'; // includes

		spl_autoload_register(array(__CLASS__, '_autoload'));
	}

	/**
	 * Extremely primitive autoloader
	 */
	public static function _autoload($class)
	{
		if (preg_match('/[^a-z0-9_]/i', $class))
		{
			return false;
		}

		if (stripos($class, self::$_prefix) !== 0)
		{
			return false;
		}

		$class = substr($class, strlen(self::$_prefix));

		$fname = str_replace('_', '/', strtolower($class)) . '.php';

		foreach (self::$_paths AS $path)
		{
			if (file_exists($path . $fname))
			{
				include($path . $fname);
				if (class_exists(self::$_prefix . $class, false))
				{
					if (method_exists(self::$_prefix . $class, '__init'))
					{
						call_user_func(array(self::$_prefix . $class, '__init'));
					}

					return true;
				}
			}
		}

		return class_exists(self::$_prefix . $class, false);
	}
}