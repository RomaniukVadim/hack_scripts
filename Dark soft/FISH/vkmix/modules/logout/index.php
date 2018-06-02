<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');

if($user_uhash == $_GET['hash']) {
 setCookie('user_id', '', 0);
 setCookie('user_hash', '', 0);
}

header('Location: /');
?>