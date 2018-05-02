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
	DBSEO_Url_Create::addCanonical($headinclude, (
		DBSEO::$config['dbtech_dbseo_rewrite_forum'] ?
			DBSEO_Url_Create::create('Forum_Forum' . ($vbulletin->GPC['page'] > 1 ? '_Page' : ''), $foruminfo) :
			'forumdisplay.php?f=' . $vbulletin->GPC['f'] . ($vbulletin->GPC['page'] > 1 ? '&page=' . $vbulletin->GPC['page'] : '')
	));
}
while (false);
?>