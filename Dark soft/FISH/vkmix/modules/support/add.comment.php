<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'add.comment_support';

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/logs.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/vk.api.php');
require($root.'/inc/classes/tasks.php');
require($root.'/inc/classes/support.php');

echo $support->add_comment();
?>