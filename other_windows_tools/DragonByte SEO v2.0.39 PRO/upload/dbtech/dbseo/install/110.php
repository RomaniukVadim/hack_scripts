<?php
self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_dbseo_sitemapbuildlog` (
		`sitemapbuildlogid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`dateline` int(10) NOT NULL DEFAULT '0',
		`builddetails` MEDIUMBLOB,
		`sitemaphits` int(10) NOT NULL DEFAULT '0',
		`spiderhits` int(10) NOT NULL DEFAULT '0',
		PRIMARY KEY (`sitemapbuildlogid`)
	) ENGINE=MyISAM
");
self::report('Created Table', 'dbtech_dbseo_sitemapbuildlog');