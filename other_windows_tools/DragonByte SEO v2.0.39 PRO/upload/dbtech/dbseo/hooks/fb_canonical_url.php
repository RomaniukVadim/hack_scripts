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

	if (THIS_SCRIPT == 'showthread')
	{
		global $threadinfo;
		if (DBSEO::$config['dbtech_dbseo_rewrite_thread'])
		{
			$fbcanonicalurl = DBSEO_Url_Create::create('Thread_Thread', $threadinfo);
		}
	}
	else if (THIS_SCRIPT == 'entry')
	{
		global $bloginfo;
		if (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'])
		{
			$fbcanonicalurl = DBSEO_Url_Create::create('Blog_BlogEntry', $bloginfo);
		}
	}
}
while (false);
?>