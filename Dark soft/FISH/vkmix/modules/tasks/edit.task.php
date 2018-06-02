<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'edit_task';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/logs.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/vk.api.php');
require($root.'/inc/classes/sessions.php');
require($root.'/inc/classes/tasks.php');

echo $tasks->edit_task(array(
 'id' => $_POST['id'],
 'uid' => $user_id,
 'cat' => $_POST['cat'],
 'count' => $_POST['count'],
 'upoints' => $upoints,
 'captcha_code' => $_POST['captcha_code'],
 'captcha_key' => $_POST['captcha_key'],
 'ssid' => $_POST['ssid']
));
?>