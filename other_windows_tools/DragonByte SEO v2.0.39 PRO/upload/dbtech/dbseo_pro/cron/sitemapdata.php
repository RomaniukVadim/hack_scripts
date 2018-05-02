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

try
{
	if (!$vbulletin->dbtech_dbseo_gwt_cache['site'])
	{
		// Halt execution
		throw new Exception('Missing site.');
	}

	// Grab ourselves the Google accounts integration
	require_once(DIR . '/dbtech/dbseo/includes/3rdparty/Google/config.inc.php');

	$client->setAccessToken($vbulletin->dbtech_dbseo_oauth);

	if ($client->isAccessTokenExpired())
	{
		// Refresh access token
		$client->refreshToken($client->getRefreshToken());

		// Update the access token
		$vbulletin->dbtech_dbseo_oauth = $client->getAccessToken();
		build_datastore('dbtech_dbseo_oauth', trim($vbulletin->dbtech_dbseo_oauth), 0);
	}

	// Set GWT object
	$gwt = new Google_Service_Webmasters($client);

	// Easier to handle this data
	$data = array();

	// Grab the request data
	$request = $gwt->urlcrawlerrorscounts->query($vbulletin->dbtech_dbseo_gwt_cache['site'], array('platform' => 'web'));

	foreach ($request->countPerTypes as $type)
	{
		$data[$type->category] = $type->entries[0]->count;
	}

	// Easier to handle this data
	$sitemapInfo = array();

	// Grab the request data
	$request = $gwt->sitemaps->listSitemaps($vbulletin->dbtech_dbseo_gwt_cache['site']);

	foreach ($request->sitemap as $sitemap)
	{
		if (strpos($sitemap->path, 'dbseositemap.php') !== false)
		{
			// We have a sitemap
			foreach ($sitemap->contents as $sitemapContent)
			{
				if ($sitemapContent->type == 'web')
				{
					// This is the sitemap info we need
					$sitemapInfo['submitted'] = $sitemapContent->submitted;
					$sitemapInfo['indexed'] = $sitemapContent->indexed;
					break 2;
				}
			}
			break;
		}
	}
	
	if (!$sitemapInfo)
	{
		// Submit the sitemap
		$gwt->sitemaps->submit($vbulletin->dbtech_dbseo_gwt_cache['site'], $vbulletin->options['bburl'] . '/dbseositemap.php');

		// This is the sitemap info we need
		$sitemapInfo['submitted'] = 0;
		$sitemapInfo['indexed'] = 0;
	}

	// Append the additional info
	$data = array_merge($data, $sitemapInfo);

	// Update our bot info (spider)
	$vbulletin->db->query_write("
		REPLACE INTO " . TABLE_PREFIX . "dbtech_dbseo_sitemapdata
			(dateline, sitemapdata)
		VALUES (
			'" . TIMENOW . "',
			'" . $vbulletin->db->escape_string(trim(serialize($data))) . "'
		)
	");
}
catch (Exception $e)
{
	if (defined('IN_CONTROL_PANEL'))
	{
		print_cp_message($e->getMessage() . ' on line ' . $e->getLine() . ' in ' . $e->getFile() . ' (code: ' . $e->getCode() . ')');
	}
}

log_cron_action('Sitemap Data Gathered', $nextitem, 1);