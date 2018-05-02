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
if ($_REQUEST['action'] == 'reset' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_dbseo_vbseo_import']);
	
	print_form_header('index', 'reset');
	construct_hidden_code('action', 'reset');
	print_table_header($vbphrase['dbtech_dbseo_reset_settings'], 2, 0);
	print_description_row($vbphrase['dbtech_dbseo_reset_settings_descr']);
	print_yes_no_row($vbphrase['dbtech_dbseo_are_you_sure_reset'], 'doreset', 0);
	print_submit_row($vbphrase['dbtech_dbseo_reset_settings'], false);
		
	print_cp_footer();
}

// #############################################################################
if ($_REQUEST['action'] == 'reset')
{
	print_cp_header($vbphrase['importing_settings']);

	$vbulletin->input->clean_array_gpc('r', array(
		'doreset' => TYPE_BOOL,
	));
	
	if (!$vbulletin->GPC['doreset'])
	{
		// Nothing to do
		print_stop_message('nothing_to_do');
	}

	require_once(DIR . '/includes/adminfunctions_options.php');

	// display links to settinggroups and create settingscache
	$settingscache = array();
	
	// query settings phrases
	$settings = $db->query("
		SELECT setting.*
		FROM " . TABLE_PREFIX . "setting AS setting
		LEFT JOIN " . TABLE_PREFIX . "settinggroup AS settinggroup USING(grouptitle)
		WHERE setting.varname LIKE 'dbtech_dbseo%'
			AND settinggroup.displayorder = 0
	");
	while ($setting = $db->fetch_array($settings))
	{
		$settingscache[$setting['varname']] = $setting;
	}
	$db->free_result($settings);
	unset($setting);

	$settings = array();
	foreach ($settingscache as $settingName => $settingInfo)
	{
		if ($settingInfo['value'] != $settingInfo['defaultvalue'])
		{
			// Reset this setting
			$settings[$settingName] = $settingInfo['defaultvalue'];
		}
	}
	
	if (!empty($settings))
	{
		// Save the settings
		save_settings($settings);
	}

	define('CP_REDIRECT', 'index.php?do=home');
	print_stop_message('dbtech_dbseo_settings_reset');
}