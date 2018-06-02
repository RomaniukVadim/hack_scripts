<?php

class rby
{
	static public $link;

	static public function connect($params = NULL)
	{
		Log::message('SQL::connect()');
		$host = ($params ? $params['host'] : self::config('mysql_host'));
		$user = ($params ? $params['user'] : self::config('mysql_user'));
		$pass = ($params ? $params['pass'] : self::config('mysql_pass'));
		$db = ($params ? $params['db'] : self::config('mysql_db'));

		if (self::$link = @mysql_connect($host, $user, $pass)) {
			if (mysql_select_db($db, self::$link)) {
				self::query('set names \'utf8\'');
				return true;
			}
		}

		return false;
	}

	static public function query($sql, $notice = true)
	{
		$st = mysql_query($sql, self::$link);
		if (!$st && self) {
			Log::error(self::error());
		}

		return $st;
	}

	static public function error()
	{
		return mysql_error(self::$link);
	}

	static public function fetch($dataset)
	{
		return mysql_fetch_array($dataset);
	}

	static public function fetchAll($dataset)
	{
		$result = array();

		while ($row = self::fetch($dataset)) {
			$result[] = $row;
		}

		return $result;
	}

	static public function affected()
	{
		return mysql_affected_rows(self::$link);
	}
}

include_once __DIR__ . '/Config.php';
include_once __DIR__ . '/Log.php';

?>
