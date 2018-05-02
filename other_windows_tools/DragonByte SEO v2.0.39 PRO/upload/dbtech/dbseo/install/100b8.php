<?php

self::$db->query_write("
	CREATE TABLE IF NOT EXISTS `" . TABLE_PREFIX  . "dbtech_dbseo_urlhistory` (
		`setting` VARCHAR(100) NOT NULL DEFAULT '',
		`regexpformat` VARCHAR(100) NOT NULL DEFAULT '',
		`rawformat` VARCHAR(100) NOT NULL DEFAULT '',
		PRIMARY KEY (`setting`, `regexpformat`, `rawformat`)
	) ENGINE=MyISAM
");
self::report('Created Table', 'dbtech_dbseo_urlhistory');