<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/logs.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');

if($_POST['edit_post'] == 1) {
 echo $user->edit_info_post();
 exit;
}

if($_GET['edit_history'] == 1) {
 echo $user->edit_info_history();
 exit;
}

echo $user->edit_info();
?>