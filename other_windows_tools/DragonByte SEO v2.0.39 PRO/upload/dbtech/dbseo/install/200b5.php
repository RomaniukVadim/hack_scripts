<?php
self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_dbseo_sitemapdata` (
		`dateline` int(10) NOT NULL DEFAULT '0',
		`sitemapdata` MEDIUMBLOB,
		PRIMARY KEY (`dateline`)
	) ENGINE=MyISAM
");
self::report('Created Table', 'dbtech_dbseo_sitemapdata');