<?php

/*
// Add the administrator field
if (self::$db_alter->fetch_table_info('administrator'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_dbseoadminperms',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'administrator');
}
*/

if (self::$db_alter->fetch_table_info('usergroup'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_dbseopermissions',
		'type'       => 'int',
		'length'     => '10',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '0'
	));
	self::report('Altered Table', 'usergroup');
}

self::$db->query_write("
	REPLACE INTO " . TABLE_PREFIX . "datastore
		(title, data, unserialize)
	VALUES (
		'dbtech_dbseo_adminnote',
		'This is a global note that all DragonByte SEO Managers can see and edit.',
		0
	)
");
self::report('Populated Table', 'datastore');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_dbseo_keyword` (
		`keywordid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`keyword` varchar(250) NOT NULL DEFAULT '',
		`active` tinyint(1) NOT NULL DEFAULT '1',
		`priority` tinyint(1) NOT NULL DEFAULT '1',
		PRIMARY KEY (`keywordid`)
	) ENGINE=MyISAM
");
self::report('Created Table', 'dbtech_dbseo_keyword');