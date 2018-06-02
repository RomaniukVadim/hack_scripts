<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'add.post_task';

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

$cat_id = $tasks->getCatNum($_GET['section']); // id категории

echo $tasks->add(array(
 'section' => $_POST['section'],
 'url' => $_POST['url'],
 'cat' => $_POST['cat'],
 'comments' => $_POST['comments'],
 'amount' => $_POST['amount'],
 'count' => $_POST['count'],
 'uid' => $user_id,
 'upoints' => $upoints,
 'captcha_code' => $_POST['captcha_code'],
 'captcha_key' => $_POST['captcha_key'],
 'ssid' => $_POST['ssid']
));
?>