<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'delete.categories_task';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/logs.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/vk.api.php');
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks.php');
require($root.'/inc/classes/tasks_categories.php');

echo $tasks_categories->delete(array(
 'id' => $_POST['id'],
 'uid' => $user_id,
 'ssid' => $_POST['ssid']
));
?>