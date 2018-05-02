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

// ######################## SET PHP ENVIRONMENT ###########################
// for some reason adding the @ to the beginning of this line makes it not work
error_reporting(E_ALL & ~E_NOTICE);

// Config this
$forumspath = '/Users/filliph/WebServer/public_html/devboards/vb4';

if (!$forumspath OR !is_dir($forumspath))
{
	print ("$forumspath is not a valid directory.");
	die();
}

// ##################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'dbseo');
define('VB_AREA', 'Maintenance');
define('SKIP_SESSIONCREATE', 1);
define('VB_ENTRY', true);
define('NOCOOKIES', 1);
define('DISABLE_HOOKS', true);

chdir($forumspath);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('maintenance', 'cpglobal', 'dbtech_dbseo');

// ########################################################################
// ######################### START MAIN SCRIPT ############################
// ########################################################################

if (file_exists('./includes/class_bootstrap.php'))
{
	require('./includes/class_bootstrap.php');
	$bootstrap = new vB_Bootstrap();
	$bootstrap->init();
}
else
{
	require_once('./global.php');
}

$vbphrase = init_language();

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

$vbulletin->pluginlist = array();
$plugins = $vbulletin->db->query_read("
	SELECT plugin.*,
		IF(product.productid IS NULL, 0, 1) AS foundproduct,
		IF(plugin.product = 'vbulletin', 1, product.active) AS productactive
	FROM " . TABLE_PREFIX . "plugin AS plugin
	LEFT JOIN " . TABLE_PREFIX . "product AS product ON(product.productid = plugin.product)
	WHERE plugin.active = 1
		AND plugin." . "phpcode <> ''
		AND plugin.hookname LIKE 'dbtech_dbseo_sitemap_%'
	ORDER BY plugin.executionorder ASC
");
while ($plugin = $vbulletin->db->fetch_array($plugins))
{
	if ($plugin['foundproduct'] AND !$plugin['productactive'])
	{
		continue;
	}
	else if (!empty($adminlocations["$plugin[hookname]"]))
	{
		$vbulletin->pluginlist["$plugin[hookname]"] .= "$plugin[phpcode]\r\n";
	}
	else
	{
		$vbulletin->pluginlist["$plugin[hookname]"] .= "$plugin[phpcode]\r\n";
	}
}
$vbulletin->db->free_result($plugins);

require_once(DIR . '/dbtech/dbseo/includes/class_sitemap.php');

$runner = new DBSEO_SiteMapRunner_Cron($vbulletin);

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

$log_text = $runner->written_filename;
while (!$runner->is_finished)
{
	$runner->generate();
	$log_text .= ', ' . $runner->written_filename;
}

$log_text .= ', dbseo_sitemap_index.xml';

echo "$log_text\n";

//run any registered shutdown functions
if (intval($vbulletin->versionnumber) > 3)
{
	$GLOBALS['vbulletin']->shutdown->shutdown();
}
exec_shut_down();
$vbulletin->db->close();