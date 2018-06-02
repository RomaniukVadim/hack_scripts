<?php
header('Content-Type: text/html; charset=utf-8');
session_start();
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'chat';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/chat.php');

$id_com = $_POST['id'];
if ($ugroup!=4) {
	$error = 'Access denied';
}


if(!$error){
	if($chat->Del($id_com)){
		echo json_encode(array(
		'result' => 'Сообщение удаленно.'
		));
		$_SESSION['chat']['lasttime'] = $nowtime;
	} else {
		echo json_encode(array(
		'error' => 'Не удалось удалить.'
		));
	}
} else {
	echo json_encode(array(
		'error' => $error
		));
}