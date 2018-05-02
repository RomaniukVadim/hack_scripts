<?php

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_dbseo_resolvedurl` (
		`resolvedurlid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`forumurl` MEDIUMBLOB,
		`seourl` MEDIUMBLOB,
		`urldata` MEDIUMBLOB,
		`format` VARCHAR(100) NOT NULL DEFAULT '',
		PRIMARY KEY (`resolvedurlid`)
	) ENGINE=MyISAM
");
self::report('Created Table', 'dbtech_dbseo_resolvedurl');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_dbseo_sitemapurl` (
		`sitemapurlid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`url` MEDIUMBLOB,
		`frequency` VARCHAR(10) NOT NULL DEFAULT '',
		`priority` VARCHAR(10) NOT NULL DEFAULT '',
		`lastupdate` INT(10) NOT NULL DEFAULT '0',
		PRIMARY KEY (`sitemapurlid`)
	) ENGINE=MyISAM
");
self::report('Created Table', 'dbtech_dbseo_sitemapurl');