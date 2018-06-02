<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'edit.form_task';

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/logs.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/vk.api.php');
require($root.'/inc/classes/sessions.php');
require($root.'/inc/classes/tasks.php');

echo $tasks->edit_form(array(
 'id' => $_GET['id'],
 'section' => $_GET['section'],
 'uid' => $user_id
));
?>