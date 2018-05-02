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

	if (intval($vbulletin->versionnumber) == 4)
	{
		// Ensure this is done
		$vbulletin->shutdown->add(array(DBSEO::$db, 'close'));
	}
	else
	{
		// Ensure this is done
		$vbulletin->shutdown->add('DBSEO_DB_Shutdown');
	}

	if (!DBSEO::$config['dbtech_dbseo_active'])
	{
		// Mod is disabled
		break;
	}
	
	// Prepare the content
	$output = DBSEO::processContent($output, true);

	if (
		isset($_SERVER['SERVER_ADDR']) AND
		$_SERVER['SERVER_ADDR'] == '192.168.0.20' AND 
		$_SERVER['SERVER_NAME'] == 'development' AND
		function_exists('xhprof_enable') AND
		XHPROF_DEBUG === true
	)
	{
		// Disable debugging
		$xhprof_data = xhprof_disable();
		include_once('/usr/share/php/xhprof_lib/utils/xhprof_lib.php');
		include_once('/usr/share/php/xhprof_lib/utils/xhprof_runs.php');

		$xhprof_runs = new XHProfRuns_Default();
		$xhprof_runs->save_run($xhprof_data, '');
	}
}
while (false);
?>