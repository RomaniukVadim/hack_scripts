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
	
	if (!DBSEO::$config['dbtech_dbseo_rewrite_blog'])
	{
		// We're not rewriting blog URLs
		break;
	}

	if (!is_array($userinfo))
	{
		// Missing userinfo
		break;
	}

	foreach ($userinfo['custompages'] as $custompages)
	{
		foreach ($custompages as $custompage)
		{
			// Pre-cache this custom block
			DBSEO::$cache['blogcustomblock'][$custompage['i']] = array(
				'customblockid' => $custompage['i'],
				'title' 		=> $custompage['t'],
				'userid' 		=> $userinfo['userid']
			);
		}
	}
}
while (false);
?>