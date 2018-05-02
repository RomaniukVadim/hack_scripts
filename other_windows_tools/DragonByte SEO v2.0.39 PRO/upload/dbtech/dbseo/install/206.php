<?php
self::$db->query_write("
	REPLACE INTO `" . TABLE_PREFIX  . "dbtech_dbseo_adminmessage`
		(`adminmessageid`, `varname`, `dismissable`, `script`, `action`, `execurl`, `method`, `dateline`)
	VALUES
		(4, 'dbtech_dbseo_analytics_disabled', 1, 'validate', 'gaenabled', 'index.php?do=save&filedo=home&dogroup=analytics&setting[dbtech_dbseo_analytics_active]=1', 'post', UNIX_TIMESTAMP()),
		(5, 'dbtech_dbseo_analytics_account_missing', 1, 'validate', 'gaaccount', 'index.php?do=analytics', 'get', UNIX_TIMESTAMP()),
		(6, 'dbtech_dbseo_analytics_profile_missing', 1, 'validate', 'gaprofile', 'index.php?do=analytics', 'get', UNIX_TIMESTAMP())
");
self::report('Populated Table', 'dbtech_dbseo_adminmessage');