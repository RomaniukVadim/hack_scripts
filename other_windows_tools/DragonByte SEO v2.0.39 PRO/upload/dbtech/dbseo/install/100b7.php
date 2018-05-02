<?php

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_dbseo_sitemaplog` (
		`sitemaplogid` INT(10) NOT NULL AUTO_INCREMENT,
		`dateline` INT(10) UNSIGNED NOT NULL DEFAULT '0',
		`spider` VARCHAR(100) NOT NULL DEFAULT '',
		`useragent` VARCHAR(256) NOT NULL DEFAULT '',
		`ipaddress` VARCHAR(64) NOT NULL DEFAULT '',
		`sitemap` VARCHAR(100) NOT NULL DEFAULT '',
		PRIMARY KEY (`sitemaplogid`)
	) ENGINE=MyISAM
");
self::report('Created Table', 'dbtech_dbseo_sitemaplog');

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_dbseo_spiderlog` (
		`dateline` INT(10) UNSIGNED NOT NULL,
		`spider` VARCHAR(100) NOT NULL,
		`script` VARCHAR(100) NOT NULL,
		`hits` INT(10) UNSIGNED NOT NULL DEFAULT '0',
		PRIMARY KEY (`dateline`, `spider`, `script`)
	) ENGINE=MyISAM
");
self::report('Created Table', 'dbtech_dbseo_spiderlog');