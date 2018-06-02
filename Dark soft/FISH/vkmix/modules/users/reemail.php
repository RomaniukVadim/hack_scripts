<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/system/profile.php');
require($root.'/inc/classes/users.php');

echo $user->reemail();
?>