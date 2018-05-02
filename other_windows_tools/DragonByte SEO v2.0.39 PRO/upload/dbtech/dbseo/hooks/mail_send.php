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

	if (!DBSEO::$config['dbtech_dbseo_rewrite_emails'])
	{
		// We're not rewriting emails
		break;
	}

	// Force text URL rewrite
	DBSEO::$config['dbtech_dbseo_rewrite_texturls'] = true;

	// Process the content
	$message = DBSEO::processContent($message);

	// Revert this
	DBSEO::$config['dbtech_dbseo_rewrite_texturls'] = false;
}
while (false);
?>