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

if(!defined('IN_DBSEO')) die('Access denied.');

// #############################################################################
switch ($adminmessage['action'])
{
	case 'xcache':
		$doContinue = !(
			$vbulletin->config['Datastore']['class'] == 'vB_Datastore_XCache' AND
			@ini_get('xcache.admin.enable_auth') == 'On' AND (
				!$vbulletin->config['xcache']['user'] OR
				!$vbulletin->config['xcache']['pass']
			)
		);
		break;

	case 'sitemappath':
		// Skip this message if we can write to sitemap path
		$doContinue = is_writable($vbulletin->options['dbtech_dbseo_sitemap_path']);

		// But do this just in case
		$adminmessage['args'] = serialize(array($vbulletin->options['dbtech_dbseo_sitemap_path']));
		break;

	case 'cron':
		$doContinue = $vbulletin->options['dbtech_dbseo_sitemap_cron_enable'];
		break;

	case 'gaenabled':
		$doContinue = $vbulletin->options['dbtech_dbseo_analytics_active'];
		break;

	case 'gaaccount':
		$doContinue = $vbulletin->options['dbtech_dbseo_analytics_account'];
		break;

	case 'gaprofile':
		$doContinue = $vbulletin->options['dbtech_dbseo_analytics_profile'];
		break;
}
?>