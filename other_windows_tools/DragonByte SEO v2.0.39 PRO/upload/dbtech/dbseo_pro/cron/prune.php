<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2013 Fillip Hannisdal AKA Revan/NeoRevan/Belazor 	  # ||
|| # All Rights Reserved. 											  # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # You are not allowed to use this on your server unless the files  # ||
|| # you downloaded were done so with permission.					  # ||
|| # ---------------------------------------------------------------- # ||
|| #################################################################### ||
\*======================================================================*/

if (!is_object($vbulletin->db))
{
	exit;
}

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
	exit;
}

// Ensure we can run for as long as we need to
@set_time_limit(0);

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

// Prune old entries
DBSEO::$db->modifyQuery('DELETE FROM $dbtech_dbseo_spiderlog WHERE dateline <= ' . (time() - (DBSEO::$config['dbtech_dbseo_spiderlog_prune'] * 86400)));
DBSEO::$db->modifyQuery('DELETE FROM $dbtech_dbseo_sitemaplog WHERE dateline <= ' . (time() - (DBSEO::$config['dbtech_dbseo_sitemaplog_prune'] * 86400)));

log_cron_action('Logs pruned', $nextitem, 1);