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

	if ($vbulletin->GPC['threadedmode'] == $vbulletin->userinfo['threadedmode'])
	{
		// Nothing's changed
		break;
	}

	// Ensure we can get rid of the cache
	DBSEO::$datastore->flush();
}
while (false);
?>