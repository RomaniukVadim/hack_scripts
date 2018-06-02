<?php
$site_url = 'http://piar.name/';

$ip_address = ip_address();
$browser = user_browser();
$time = time();

$user_id = (int) $_COOKIE['user_id'];
$user_hash = $_COOKIE['user_hash'] ? $db->escape($_COOKIE['user_hash']) : 2;

$cat_name = $_GET['section'] ? $_GET['section'] : 'likes';
$query_string = $_SERVER['QUERY_STRING'];

$dbName = 'piar';
$online_limit = time() - (15 * 60);

$noavatar = '/images/camera_b.gif';
$tokens = array(
	'd8a5c9b7ac128f9b3f53a2683d8ddf5f7f30b7bc76a7c22bc02c9b73a9a70bb3e53dac67ed3b682365756'
);

/*
Токены для ВК брать по ссылке
https://oauth.vk.com/token?grant_type=password&scope=notify,friends,photos,audio,video,docs,notes,pages,status,offers,questions,wall,groups,messages,notifications,stats,ads,offline&client_id=3140623&client_secret=VeWdmVclDCtn6ihuP1nt&username=ВАШЛОГИН&password=ВАШПАРОЛЬ
ВАШЛОГИН и ВАШПАРОЛЬ замените на логин, и пароль соответственно
*/

$token = $tokens[rand(0, count($tokens) - 1)];

$tokens_complaints = array(
	'd8a5c9b7ac128f9b3f53a2683d8ddf5f7f30b7bc76a7c22bc02c9b73a9a70bb3e53dac67ed3b682365756'
); // токены для жалоб

$token_complaints = $tokens_complaints[rand(0, count($tokens_complaints) - 1)];

$sites_list = array('http://piar.name/'); //сайты с go.html
$sites_list_rand = $sites_list[rand(0, count($sites_list) - 1)];

$ref_points = 15; // кол-во баллов за реферала
?>