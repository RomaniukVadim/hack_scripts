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

	if (!DBSEO::$config['dbtech_dbseo_meta_thread_custom'])
	{
		// Feature is disabled
		break;
	}

	/*DBTECH_PRO_START*/
	$vbulletin->input->clean_array_gpc('p', array(
		'keywords_custom' 		=> TYPE_NOHTML,
		'description_custom' 	=> TYPE_NOHTML,
	));

	// Update keywords
	$threadman->set('dbtech_dbseo_keywords_custom', 	str_replace(array('$','\\','"'), array('\$','\\\\','&quot;'), strip_tags($vbulletin->GPC['keywords_custom'])));
	$threadman->set('dbtech_dbseo_description_custom', 	str_replace(array('$','\\','"'), array('\$','\\\\','&quot;'), preg_replace('#[\s\"]+#s', ' ', strip_tags($vbulletin->GPC['description_custom']))));
	/*DBTECH_PRO_END*/

	if ($threadinfo['title'] == $vbulletin->GPC['title'])
	{
		// The titles are identical
		break;
	}

	// Update post titles
	$db->query_write("
		UPDATE " . TABLE_PREFIX . "post
		SET title = '" . $db->escape_string('re: ' . $vbulletin->GPC['title']) . "'
		WHERE threadid = " . intval($threadinfo['threadid']) . " 
			AND title = '" . $db->escape_string('re: ' . $threadinfo['title']) . "'
	");
}
while (false);
?>