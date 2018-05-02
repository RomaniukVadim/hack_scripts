<?php
if (self::$db_alter->fetch_table_info('dbtech_dbseo_sitemapbuildlog'))
{
	self::$db_alter->add_field(array(
		'name'       => 'prevbuilddetails',
		'type'       => 'mediumblob',
	));
	self::report('Altered Table', 'thread');
}