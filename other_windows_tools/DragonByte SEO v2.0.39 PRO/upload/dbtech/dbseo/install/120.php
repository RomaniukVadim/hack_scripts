<?php
if (self::$db_alter->fetch_table_info('thread'))
{
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_dbseo_keywords',
		'type'       => 'varchar',
		'length'     => '255',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => ''
	));
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_dbseo_keywords_custom',
		'type'       => 'varchar',
		'length'     => '255',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => ''
	));
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_dbseo_description',
		'type'       => 'varchar',
		'length'     => '255',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => ''
	));
	self::$db_alter->add_field(array(
		'name'       => 'dbtech_dbseo_description_custom',
		'type'       => 'varchar',
		'length'     => '255',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => ''
	));
	self::report('Altered Table', 'thread');
}