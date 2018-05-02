<?php
do
{
	if (!class_exists('DBSEO'))
	{
		// Set important constants
		define('DBSEO_CWD', 	DIR);
		define('DBSEO_TIMENOW', TIMENOW);
		define('IN_DBSEO', 		true);

		// Make sure we nab this class
		require_once(DBSEO_CWD . '/dbtech/dbseo/includes/class_core.php');

		// Init DBSEO
		DBSEO::init(true);
	}

	if (!DBSEO::$config['dbtech_dbseo_active'])
	{
		// Mod is disabled
		break;
	}

	if (DBSEO::$config['dbtech_dbseo_rewrite_memberprofile'])
	{
		// Pre-cache user info
		DBSEO::$cache['_objectIds']['userinfo'][$moderator['userid']] = array(
			'userid' => $moderator['userid'],
			'username' => $moderator['username']
		);
	}
}
while (false);
?>