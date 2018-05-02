<?php

// Revert
/*
if (self::$db_alter->fetch_table_info('administrator'))
{
	self::$db_alter->drop_field('dbtech_dbseoadminperms');
	self::report('Reverted Table', 'administrator');
}
*/

// Clean up
self::$db->query_write("DELETE FROM `" . TABLE_PREFIX . "datastore` WHERE `title` LIKE 'dbtech_dbseo_%'");
self::report('Reverted Table', 'datastore');

// Revert
if (self::$db_alter->fetch_table_info('thread'))
{
	self::$db_alter->drop_field(array(
		'dbtech_dbseo_keywords',
		'dbtech_dbseo_keywords_custom',
		'dbtech_dbseo_description',
		'dbtech_dbseo_description_custom',
	));
	self::report('Reverted Table', 'thread');
}

// Revert
if (self::$db_alter->fetch_table_info('usergroup'))
{
	self::$db_alter->drop_field('dbtech_dbseopermissions');
	self::report('Reverted Table', 'usergroup');
}

// Drop
$tables = array(
	'adminmessage',
	'keyword',
	'resolvedurl',
	'sitemapbuildlog',
	'sitemapdata',
	'sitemaplog',
	'sitemapurl',
	'spiderlog',
	'urlhistory',
);
foreach ($tables as $table)
{
	self::$db->query_write("DROP TABLE IF EXISTS `" . TABLE_PREFIX . "dbtech_dbseo_{$table}`");
	self::report('Deleted Table', 'dbtech_dbseo_' . $table);
}