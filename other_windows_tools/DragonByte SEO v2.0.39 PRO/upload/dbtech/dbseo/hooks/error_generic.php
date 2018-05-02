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

	if (!in_array(DBSEO::$cache['_error'], array('noid', 'invalidid')))
	{
		// Wrong error type
		break;
	}

	// Set 404 header
	http_response_code(isset(DBSEO::$config['dbtech_dbseo_generic_http']) ? DBSEO::$config['dbtech_dbseo_generic_http'] : 404);
	DBSEO::sendResponseCode(isset(DBSEO::$config['dbtech_dbseo_generic_http']) ? DBSEO::$config['dbtech_dbseo_generic_http'] : 404);
}
while (false);
?>