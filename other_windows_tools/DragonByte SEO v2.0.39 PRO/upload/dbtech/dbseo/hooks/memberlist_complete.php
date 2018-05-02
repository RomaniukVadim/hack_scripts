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
	
	if (!DBSEO::$config['dbtech_dbseo_rewrite_memberlist'])
	{
		// Not rewriting blog URLs
		break;
	}

	// Prepend canonical URL
	DBSEO_Url_Create::addCanonical($headinclude, (
		DBSEO::$config['dbtech_dbseo_rewrite_thread'] ? 
			DBSEO_Url_Create::create('MemberList_MemberList' . (isset($_REQUEST['ltr']) ? '_Letter' : ($vbulletin->GPC['page'] > 1 ? '_Page' : '')), $_REQUEST) : 
			'memberlist.php?' . (isset($_REQUEST['ltr']) ? '?ltr=' . $_REQUEST['ltr'] : '') . ($vbulletin->GPC['page'] > 1 ? ((isset($_REQUEST['ltr']) ? '&' : '?') . 'page=' . $vbulletin->GPC['page']) : '')
	));
}
while (false);
?>