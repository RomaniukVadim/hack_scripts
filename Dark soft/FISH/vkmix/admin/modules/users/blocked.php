<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/logs.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');

if($_GET['type'] == 2) {
 echo $user->unblocked();
} elseif($_GET['history'] == 1) {
 echo $user->blocked_history();
} else {
 echo $user->blocked();
}
?>