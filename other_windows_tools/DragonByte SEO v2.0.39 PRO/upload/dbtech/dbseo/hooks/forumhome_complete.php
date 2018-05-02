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
	
	// Prepend canonical URL
	DBSEO_Url_Create::addCanonical($headinclude, DBSEO::$config['_bburl'] . '/' . (DBSEO::$config['dbtech_dbseo_force_directory_index'] ? '' : DBSEO::$config['forumhome'] . '.php'));
}
while (false);
?>