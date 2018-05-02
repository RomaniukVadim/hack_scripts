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

require_once(DIR . '/dbtech/dbseo/includes/class_sitemap.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

print_cp_header($vbphrase['dbtech_dbseo_xml_sitemap_manager']);

// #######################################################################
if ($_REQUEST['action'] == 'removesession' OR empty($_REQUEST['action']))
{
	print_form_header('index', 'removesession');
	construct_hidden_code('action', 'doremovesession');
	print_table_header($vbphrase['dbtech_dbseo_remove_sitemap_session']);
	print_description_row($vbphrase['dbtech_dbseo_are_you_sure_remove_sitemap_session']);
	print_submit_row($vbphrase['dbtech_dbseo_remove_sitemap_session'], null);
}

// ########################################################################
if ($_POST['action'] == 'doremovesession')
{
	// reset the build time to be the next time the cron is supposed to run based on schedule (in case we're in the middle of running it)
	require_once(DIR . '/includes/functions_cron.php');
	$cron = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "cron WHERE filename = './dbtech/dbseo/cron/sitemap.php'");
	if ($cron)
	{
		build_cron_item($cron['cronid'], $cron);
	}

	$vbulletin->db->query("DELETE FROM " . TABLE_PREFIX . "adminutil WHERE title = 'dbtech_dbseo_sitemapsession'");

	print_cp_redirect('index.php?do=buildsitemap');
}

print_cp_footer();