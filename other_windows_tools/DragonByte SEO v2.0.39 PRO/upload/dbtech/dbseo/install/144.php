<?php
if (self::$db_alter->fetch_table_info('dbtech_dbseo_urlhistory'))
{
	// Nuke it from orbit I say
	self::$db->query_write("TRUNCATE TABLE `" . TABLE_PREFIX  . "dbtech_dbseo_urlhistory`");

	self::$db_alter->add_field(array(
		'name'       => 'nonlatin',
		'type'       => 'tinyint',
		'length'     => '1',
		'attributes' => 'unsigned',
		'null'       => false,	// True = NULL, false = NOT NULL
		'default'    => '2'
	));
	self::$db->query_write("ALTER TABLE " . TABLE_PREFIX . "dbtech_dbseo_urlhistory DROP PRIMARY KEY");
	self::$db->query_write("ALTER TABLE " . TABLE_PREFIX . "dbtech_dbseo_urlhistory ADD PRIMARY KEY (setting, nonlatin, rawformat)");
	self::$db->query_write("ALTER TABLE " . TABLE_PREFIX . "dbtech_dbseo_urlhistory CHANGE regexpformat regexpformat MEDIUMBLOB");	
	self::report('Altered Table', 'thread');
}