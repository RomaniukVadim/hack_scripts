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
	
	if ($thread['open'] == 10)
	{
		// Not a valid thread
		break;
	}

	if (!isset($thread['forumid']))
	{
		// Thread is lacking forum info
		break;
	}

	if (isset(DBSEO::$cache['thread_pre'][$thread['threadid']]))
	{
		// Already cached
		break;
	}

	// Pre-cache thread info
	DBSEO::$cache['thread_pre'][$thread['threadid']] = $thread;
}
while (false);
?>