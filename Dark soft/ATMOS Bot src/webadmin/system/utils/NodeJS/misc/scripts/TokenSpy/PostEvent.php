<?php
$room = 'gate';
$event = 'test';
$msg = array(
    'a' => 1,
    'b' => 2,
);

require_once __DIR__.'/../../../../../config.php';
require_once __DIR__.'/../../../../../global.php';
require_once __DIR__.'/../../../../../lib/HttpJsonAPIclient.php';

$API = new HttpJsonAPIclient("http://localhost:8080/TokenSpy");
$API->nodejsAuthCookie('anybody');

$response = $API->callMethod("event/{$room}/{$event}", $msg);
var_export($response);
