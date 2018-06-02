<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'go_task';

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

$go = json_decode($tasks->go(), true);
$go_error_text = $go['error_text'];
$go_success = $go['success'];
$go_url = $go['url'];

if($go_error_text) {
 echo '<script type="text/javascript">window.task_error = \''.$go_error_text.'\'; window.close();</script>';
 exit;
} else {
 header('Location: '.$sites_list_rand.'go.html?url=http://vk.com/'.$go_url);
 exit;
}
?>