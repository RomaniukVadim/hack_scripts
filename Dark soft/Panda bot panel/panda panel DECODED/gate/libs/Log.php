<?php

class t_0
{
	static public $saveBadData = true;
	static protected $filePointer;
	static protected $id;
	static public $try;
	static public $subId;
	static public $pdata;
	static public $logStatus;

	static protected function open()
	{
		if (self::$try) {
			return NULL;
		}

		self::$id = md5(microtime(true) . rand() . rand());
		$file = self::config('logDir') . 'log.' . date('d.m.Y H') . '.log';

		if (self::$filePointer = fopen($file, 'ab')) {
			flock(self::$filePointer, LOCK_UN);
		}

		self::$try = true;
		return self::$filePointer;
	}

	static protected function write($data)
	{
		if (!self::$filePointer) {
			self::open();
		}

		if (flock(self::$filePointer, LOCK_EX)) {
			fwrite(self::$filePointer, self::$id . ' ' . $data);
		}

		flock(self::$filePointer, LOCK_UN);
		return true;
	}

	static public function message($msg, $error = false)
	{
		if (is_null(self::$logStatus)) {
			self::$logStatus = self::config('logStatus');
		}

		if (self::$logStatus < 1) {
			return NULL;
		}

		if (!$error && self) {
			return NULL;
		}

		$sub = (self::$subId ? ' ' . self::$subId : '');
		$data = Api::getIp() . $sub . ' ' . date('H:i:s') . ' ' . ($error ? 'ERROR: ' : '') . $msg . "\n";
		return self::write($data);
	}

	static public function error($msg)
	{
		self::message($msg, true);

		if (self::$saveBadData) {
			self::saveBadData();
			self::$saveBadData = false;
			self::message('Request data saved to requests/' . self::$id . '.dat');
		}

		return true;
	}

	static protected function saveBadData()
	{
		$file = self::config('logDir') . '/requests/' . self::$id . '.dat';
		return file_put_contents($file, self::$pdata);
	}
}

include_once __DIR__ . '/Config.php';
include_once __DIR__ . '/Api.php';

?>
