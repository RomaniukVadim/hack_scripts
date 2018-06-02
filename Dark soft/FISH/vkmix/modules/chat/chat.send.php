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

$message = $_POST['message'];
$lasttime = $_SESSION['chat']['lasttime'];
$nowtime = time();
if(mb_strlen($message) > 300){
	$error = 'Сообщение длиннее 300 символов.';
}
if($nowtime - $lasttime < 3){
	$error = 'Вы рассылаете сообщения слишком часто!';
}

if ($uvk_id==0) {
	$error = 'Вы еще не привязяли страницу ВК!';
}

if(!$error){
	if($chat->Send($message)){
		echo json_encode(array(
		'result' => 'Сообщение отправлено'
		));
		$_SESSION['chat']['lasttime'] = $nowtime;
	} else {
		echo json_encode(array(
		'error' => 'Вы заблокированы в чате'
		));
	}
} else {
	echo json_encode(array(
		'error' => $error
		));
}