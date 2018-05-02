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
if ($_REQUEST['action'] == 'impex' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_dbseo_backup_restore']);
	
	print_form_header('index', 'impex');
	construct_hidden_code('action', 'backup');
	print_table_header($vbphrase['dbtech_dbseo_settings_backup'], 2, 0);
	print_description_row($vbphrase['dbtech_dbseo_settings_backup_descr']);
	print_submit_row($vbphrase['backup'], false);
	
	print_form_header('index', 'impex', 1, 1, 'uploadform');
	construct_hidden_code('action', 'restore');
	print_table_header($vbphrase['dbtech_dbseo_settings_restore'], 2, 0);
	print_description_row($vbphrase['dbtech_dbseo_settings_restore_descr']);
	print_upload_row($vbphrase['dbtech_dbseo_upload_xml'], 'productfile', 999999999);
	print_submit_row($vbphrase['restore'], false);
		
	print_cp_footer();
}

// #############################################################################
if ($_REQUEST['action'] == 'backup')
{
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

	require_once(DIR . '/includes/class_xml.php');
	$xml = new vB_XML_Builder($vbulletin);
	
	// Parent for features
	$xml->add_group('settings');

	foreach ($settingscache as $settingName => $settingInfo)
	{
		$xml->add_tag('setting', $settingInfo['value'], array('varname' => $settingName));
	}

	// Close off the table group
	$xml->close_group();

	$doc = "<?xml version=\"1.0\" encoding=\"ISO-8859-1\"?>\r\n\r\n" . $xml->output();
	unset($xml);
	
	require_once(DIR . '/includes/functions_file.php');
	file_download($doc, 'dbseo-export.xml', 'text/xml');
}

// #############################################################################
if ($_REQUEST['action'] == 'restore')
{
	print_cp_header($vbphrase['importing_settings']);

	require_once(DIR . '/includes/adminfunctions_options.php');
	require_once(DIR . '/includes/class_xml.php');

	print_dots_start('<b>' . $vbphrase['importing_settings'] . "</b>, $vbphrase[please_wait]", ':', 'dspan');

	$vbulletin->input->clean_array_gpc('f', array(
		'productfile' => TYPE_FILE
	));
	
	if (file_exists($vbulletin->GPC['productfile']['tmp_name']))
	{
		// got an uploaded file?
		$xml = file_read($vbulletin->GPC['productfile']['tmp_name']);
	}
	else
	{
		print_dots_stop();
		print_stop_message('no_file_uploaded_and_no_local_file_found');
	}
		
	$xmlobj = new vB_XML_Parser($xml);
	if ($xmlobj->error_no == 1)
	{
		print_dots_stop();
		print_stop_message('no_xml_and_no_path');
	}
	
	if (!$arr = $xmlobj->parse())
	{
		print_dots_stop();
		print_stop_message('xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line());
	}

	if (!isset($arr['setting']) OR !is_array($arr['setting']) OR !count($arr['setting']))
	{
		print_dots_stop();
		print_stop_message('dbtech_dbseo_vbseo_import_config_file_unexpectedcontent', DIR);
	}

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
	foreach ($arr['setting'] as $settingInfo)
	{
		if (!isset($vbulletin->options[$settingInfo['varname']]))
		{
			// Skip this setting
			continue;
		}

		if (strval($vbulletin->options[$settingInfo['varname']]) === strval($settingInfo['value']))
		{
			// This is already identical
			continue;
		}
		
		// Store the setting array
		$settings[$settingInfo['varname']] = $settingInfo['value'];
	}

	if (!empty($settings))
	{
		save_settings($settings);
	}

	print_dots_stop();	

	define('CP_REDIRECT', 'index.php?do=home');
	print_stop_message('dbtech_dbseo_settings_imported');
}