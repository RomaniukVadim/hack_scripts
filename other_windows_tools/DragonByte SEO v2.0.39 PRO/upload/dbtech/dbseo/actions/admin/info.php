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

// #############################################################################
if ($_REQUEST['action'] == 'main' OR empty($_REQUEST['action']))
{
	// Calculate next effectve run
	$nextRun = mktime($vbulletin->options['dbtech_dbseo_sitemap_runtime'], 0, 0);
	$nextRun = $nextRun < TIMENOW ? strtotime('tomorrow' . $vbulletin->options['dbtech_dbseo_sitemap_runtime'] . ':00') : $nextRun;

	$cronLog = $db->query_first_slave("
		SELECT dateline
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapbuildlog
		ORDER BY sitemapbuildlogid DESC
		LIMIT 1
	");
	/*DBTECH_PRO_START*/
	$sitemapHits = $db->query_first_slave("
		SELECT SUM(sitemaphits) AS count
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapbuildlog
	");
	$spiderHits = $db->query_first_slave("
		SELECT SUM(spiderhits) AS count
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapbuildlog
	");
	/*DBTECH_PRO_END*/

	// Set cache type
	$cacheType = (
		(isset($vbulletin->config['Datastore']['dbseoclass']) AND !empty($vbulletin->config['Datastore']['dbseoclass'])) ?
		substr($vbulletin->config['Datastore']['dbseoclass'], 16) :
		(
			(isset($vbulletin->config['Datastore']['class']) AND !empty($vbulletin->config['Datastore']['class']) AND strtolower($vbulletin->config['Datastore']['class']) != 'vb_datastore_filecache') ?
			substr($vbulletin->config['Datastore']['class'], 13) :
			$vbphrase['n_a']
		)
	);

	if (!isset($isIncluded))
	{
		print_cp_header($vbphrase['dbtech_dbseo_system_info']);
	}
	print_table_start();
	print_table_header($vbphrase['dbtech_dbseo_system_info'], 4);

	print_cells_row(array(
		$vbphrase['dbtech_dbseo_system_status'],
		$vbulletin->options['dbtech_dbseo_active'] == true ? $vbphrase['dbtech_dbseo_system_online'] : $vbphrase['dbtech_dbseo_system_offline'],

		$vbphrase['dbtech_dbseo_system_version'],
		'<span style="font-weight: bold;">2.0.39<span>'
	), 0, 0, -5, 'top', 1, 1);

	print_cells_row(array(
		$vbphrase['dbtech_dbseo_cache_status'],
		$cacheType != $vbphrase['n_a'] ? $vbphrase['dbtech_dbseo_system_online'] : $vbphrase['n_a'],

		$vbphrase['dbtech_dbseo_cache_type'],
		$cacheType
	), 0, 0, -5, 'top', 1, 1);

	print_cells_row(array(
		$vbphrase['dbtech_dbseo_sitemap_generation'],
		$vbulletin->options['dbtech_dbseo_sitemap_cron_enable'] == true ? $vbphrase['dbtech_dbseo_system_online'] : $vbphrase['dbtech_dbseo_system_offline'],

		$vbphrase['dbtech_dbseo_sitemap_generation_frequency'],
		construct_phrase($vbphrase['dbtech_dbseo_every_x_days'], $vbulletin->options['dbtech_dbseo_sitemap_cron_frequency'])
	), 0, 0, -5, 'top', 1, 1);

	print_cells_row(array(
		$vbphrase['dbtech_dbseo_last_sitemap_generated'],
		vbdate($vbulletin->options['logdateformat'], $cronLog['dateline']),

		$vbphrase['dbtech_dbseo_next_sitemap_generated'],
		vbdate($vbulletin->options['logdateformat'], $nextRun),
	), 0, 0, -5, 'top', 1, 1);

	/*DBTECH_PRO_START*/
	print_cells_row(array(
		$vbphrase['dbtech_dbseo_logged_sitemap_hits'],
		vb_number_format($sitemapHits['count']),

		$vbphrase['dbtech_dbseo_logged_spider_hits'],
		vb_number_format($spiderHits['count']),
	), 0, 0, -5, 'top', 1, 1);
	/*DBTECH_PRO_END*/

	print_table_footer();
}

if (!isset($isIncluded))
{
	print_cp_footer();
}