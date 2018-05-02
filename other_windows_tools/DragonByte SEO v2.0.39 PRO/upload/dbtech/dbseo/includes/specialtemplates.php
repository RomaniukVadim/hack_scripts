<?php
$extracache = array(
	'dbtech_dbseo_keyword',
	'dbtech_dbseo_gwt_cache',
	'dbtech_dbseo_oauth'
);
if (defined('DBSEO_ADMIN'))
{
	$extracache[] = 'dbtech_dbseo_adminnote';
	$extracache[] = 'dbtech_dbseo_ga_cache';
}
?>