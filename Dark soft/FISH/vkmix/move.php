<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
require($root.'/inc/classes/vk.api.php');
include($root.'/inc/system/profile.php');

echo time();

/*
$wall_info = json_decode($vk->wall_poll_check(-29534144, 30106702, 3, $token), true);

echo $wall_info;*/
?>