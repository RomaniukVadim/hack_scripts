<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/vk.api.php');
require($root.'/inc/classes/vk_info_update.php');

echo $vk_info_update->update();
?>