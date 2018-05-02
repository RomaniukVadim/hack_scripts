<?php
self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_dbseo_adminmessage` (
		`adminmessageid` int(10) unsigned NOT NULL AUTO_INCREMENT,
		`varname` varchar(250) NOT NULL DEFAULT '',
		`dismissable` smallint(5) unsigned NOT NULL DEFAULT '0',
		`script` varchar(50) NOT NULL DEFAULT '',
		`action` varchar(20) NOT NULL DEFAULT '',
		`execurl` mediumtext,
		`method` enum('get','post') NOT NULL DEFAULT 'post',
		`dateline` int(10) unsigned NOT NULL DEFAULT '0',
		`status` enum('undone','done','dismissed') NOT NULL DEFAULT 'undone',
		`statususerid` int(10) unsigned NOT NULL DEFAULT '0',
		`args` mediumtext,
		PRIMARY KEY (`adminmessageid`),
		KEY `script_action` (`script`,`action`),
		KEY `varname` (`varname`)
	) ENGINE=MyISAM;
");
self::report('Created Table', 'dbtech_dbseo_adminmessage');

self::$db->query_write("
	REPLACE INTO `" . TABLE_PREFIX  . "dbtech_dbseo_adminmessage`
		(`adminmessageid`, `varname`, `dismissable`, `script`, `action`, `execurl`, `method`, `dateline`)
	VALUES
		(1, 'dbtech_dbseo_xcache_auth_descr', 0, 'validate', 'xcache', NULL, 'get', UNIX_TIMESTAMP()),
		(2, 'dbtech_dbseo_sitemap_path_unwritable', 0, 'validate', 'sitemappath', NULL, 'get', UNIX_TIMESTAMP()),
		(3, 'dbtech_dbseo_sitemap_cron_disabled', 1, 'validate', 'cron', 'index.php?do=save&filedo=home&dogroup=sitemap_general&setting[dbtech_dbseo_sitemap_cron_enable]=1', 'post', UNIX_TIMESTAMP())
");
self::report('Populated Table', 'dbtech_dbseo_adminmessage');