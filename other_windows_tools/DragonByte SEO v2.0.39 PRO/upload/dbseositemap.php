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

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);
@ini_set('zlib.output_compression', 'Off');
@set_time_limit(0);
if (@ini_get('output_handler') == 'ob_gzhandler' AND @ob_get_length() !== false)
{	// if output_handler = ob_gzhandler, turn it off and remove the header sent by PHP
	@ob_end_clean();
	header('Content-Encoding:');
}

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'xmlsitemap');
define('BYPASS_FORUM_DISABLED', true);
define('CSRF_PROTECTION', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array();

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array();

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$vbulletin->input->clean_array_gpc('r', array(
	'fn' => TYPE_STR
));

($hook = vBulletinHook::fetch_hook('dbtech_dbseo_sitemap_view_start')) ? eval($hook) : false;

if ($vbulletin->GPC['fn'])
{
	$sitemap_filename = preg_replace('#[^a-z0-9_.]#i', '', $vbulletin->GPC['fn']);
	$sitemap_filename = preg_replace('#\.{2,}#', '.', $sitemap_filename);

	if (substr($sitemap_filename, -4) != '.xml' AND substr($sitemap_filename, -7) != '.xml.gz')
	{
		$sitemap_filename = '';
	}
}
else if (file_exists($vbulletin->options['dbtech_dbseo_sitemap_path'] . '/dbseo_sitemap_index.xml.gz'))
{
	$sitemap_filename = 'dbseo_sitemap_index.xml.gz';
}
else if (file_exists($vbulletin->options['dbtech_dbseo_sitemap_path'] . '/dbseo_sitemap_index.xml'))
{
	$sitemap_filename = 'dbseo_sitemap_index.xml';
}
else
{
	$sitemap_filename = '';
}

if ($sitemap_filename AND file_exists($vbulletin->options['dbtech_dbseo_sitemap_path'] . "/$sitemap_filename"))
{
	/*DBTECH_PRO_START*/
	if ($vbulletin->options['dbtech_dbseo_enable_sitemaplog'])
	{
		if ($latestBuildLog = $db->query_first_slave("
			SELECT sitemapbuildlogid
			FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapbuildlog
			ORDER BY sitemapbuildlogid DESC LIMIT 1
		"))
		{
			// Update last row
			$db->query_write("
				UPDATE `" . TABLE_PREFIX . "dbtech_dbseo_sitemapbuildlog` 
				SET sitemaphits = sitemaphits + 1
				WHERE sitemapbuildlogid = " . intval($latestBuildLog['sitemapbuildlogid'])
			);
		}

		if ($vbulletin->options['dbtech_dbseo_sitemaplog_prune'])
		{
			// Insert sitemap hit log
			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "dbtech_dbseo_sitemaplog
					(dateline, spider, useragent, ipaddress, sitemap)
				VALUES (
					'" . TIMENOW . "',
					'" . $db->escape_string(DBSEO_SPIDER ? DBSEO_SPIDER : $vbphrase['n_a']) . "',
					'" . $db->escape_string($_SERVER['HTTP_USER_AGENT']) . "',
					'" . $db->escape_string(IPADDRESS) . "',
					'" . $db->escape_string($sitemap_filename) . "'
				)
			");
		}
	}
	/*DBTECH_PRO_END*/

	$gzipped = (substr($sitemap_filename, -3) == '.gz');

	if ($gzipped)
	{
		header('Content-Transfer-Encoding: binary');
		header('Content-Encoding: gzip');
		$output_filename = substr($sitemap_filename, 0, -3);
	}
	else
	{
		$output_filename = $sitemap_filename;
	}

	header('Accept-Ranges: bytes');

	$filesize = sprintf('%u', filesize($vbulletin->options['dbtech_dbseo_sitemap_path'] . "/$sitemap_filename"));
	header("Content-Length: $filesize");

	header('Content-Type: text/xml');
	header('Content-Disposition: attachment; filename="' . rawurlencode($output_filename) . '"');

	readfile($vbulletin->options['dbtech_dbseo_sitemap_path'] . "/$sitemap_filename");
}