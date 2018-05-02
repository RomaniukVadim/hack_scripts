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

$memoryLimit = ini_get('memory_limit');
$last = strtolower($memoryLimit[strlen(trim($memoryLimit)) - 1]);
switch($last)
{
	// The 'G' modifier is available since PHP 5.1.0
	case 'g':
		$memoryLimit *= 1024;
	case 'm':
		$memoryLimit *= 1024;
	case 'k':
		$memoryLimit *= 1024;
}

if ($memoryLimit < 134217728)
{
	// Set memory limit to 128M
	@ini_set('memory_limit', '128M');
}

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

require_once(DIR . '/dbtech/dbseo/includes/class_sitemap.php');

$runner = new DBSEO_SiteMapRunner_Cron($vbulletin);
$runner->set_cron_item($nextitem);

$status = $runner->check_environment();
if ($status['error'])
{
	// if an error has happened, display/log it if necessary and die

	if (VB_AREA == 'AdminCP')
	{
		print_stop_message($status['error'], $vbulletin->options['dbtech_dbseo_cp_folder']);
	}
	else if ($status['loggable'])
	{
		$rows = $vbulletin->db->query_first_slave("
			SELECT COUNT(*) AS count
			FROM " . TABLE_PREFIX . "adminmessage
			WHERE varname = '" . $vbulletin->db->escape_string($status['error']) . "'
				AND status = 'undone'
		");
		if ($rows['count'] == 0)
		{
			$vbulletin->db->query_write("
				INSERT INTO " . TABLE_PREFIX . "dbtech_dbseo_adminmessage
					(varname, dismissable, execurl, method, dateline, status)
				VALUES
					('" . $vbulletin->db->escape_string($status['error']) . "',
					1,
					'index.php?do=buildsitemap',
					'get',
					" . TIMENOW . ",
					'undone')
			");
		}
	}

	exit;
}

$runner->generate();

if ($runner->is_finished)
{
	$log_text = $runner->written_filename . ', dbseo_sitemap_index.xml';
}
else
{
	$log_text = $runner->written_filename;
}

log_cron_action($log_text, $nextitem, 1);

if (defined('IN_CONTROL_PANEL'))
{
	echo "<p>$log_text</p>";
}