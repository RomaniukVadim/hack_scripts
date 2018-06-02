<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
require($root.'/inc/classes/backups.php');

echo $backups->redis_categories();
?>