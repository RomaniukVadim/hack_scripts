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

if (!file_exists(DIR . '/vbseo/resources/xml/config.xml') AND !file_exists(DIR . '/includes/config_vbseo.php'))
{
	print_stop_message('dbtech_dbseo_vbseo_import_config_file_noexist', DIR);
}

// #############################################################################
if ($_REQUEST['action'] == 'vbseoimport' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_dbseo_vbseo_import']);
	
	print_form_header('index', 'vbseoimport');
	construct_hidden_code('action', 'import');
	print_table_header($vbphrase['dbtech_dbseo_vbseo_import'], 2, 0);
	print_description_row($vbphrase['dbtech_dbseo_vbseo_import_descr']);
	print_yes_no_row($vbphrase['dbtech_dbseo_are_you_sure_import'], 'doimport', 0);
	print_submit_row($vbphrase['import'], false);
		
	print_cp_footer();
}

// #############################################################################
if ($_REQUEST['action'] == 'import')
{
	print_cp_header($vbphrase['importing_settings']);

	$vbulletin->input->clean_array_gpc('r', array(
		'doimport' => TYPE_BOOL,
	));
	
	if (!$vbulletin->GPC['doimport'])
	{
		// Nothing to do
		print_stop_message('nothing_to_do');
	}

	require_once(DIR . '/includes/adminfunctions_options.php');
	require(DIR . '/dbtech/dbseo/includes/vbseolookup.php');

	print_dots_start('<b>' . $vbphrase['importing_settings'] . "</b>, $vbphrase[please_wait]", ':', 'dspan');

	if (file_exists(DIR . '/vbseo/resources/xml/config.xml'))
	{
		if (!is_readable(DIR . '/vbseo/resources/xml/config.xml'))
		{
			print_dots_stop();
			print_stop_message('dbtech_dbseo_vbseo_import_config_file_noread', DIR);
		}

		require_once(DIR . '/includes/class_xml.php');

		$xmlobj = new vB_XML_Parser(false, DIR . '/vbseo/resources/xml/config.xml');
		if ($xmlobj->error_no == 1)
		{
			print_dots_stop();
			print_stop_message('no_xml_and_no_path');
		}

		if(!$arr = $xmlobj->parse())
		{
			print_dots_stop();
			print_stop_message('xml_error_x_at_line_y', $xmlobj->error_string(), $xmlobj->error_line());
		}

		if (!isset($arr['setting']) OR !is_array($arr['setting']) OR !count($arr['setting']))
		{
			print_dots_stop();
			print_stop_message('dbtech_dbseo_vbseo_import_config_file_unexpectedcontent', DIR);
		}
	}
	else if (file_exists(DIR . '/includes/config_vbseo.php'))
	{
		// Init this
		$arr = array('setting' => array());

		// Grab our config
		require_once(DIR . '/includes/config_vbseo.php');

		$char_repl = array();
		foreach ($vbseo_custom_char_replacement as $key => $val)
		{
			$char_repl[] = "'$key' => '$val'";
		}

		// Compatibility layer
		define('custom_301_text', 	$vbseo_custom_301_text);
		define('custom_rules_text', $vbseo_custom_rules_text);
		define('char_repl', 		implode("\n", $char_repl));

		foreach ($lookupTable as $vbseoKey => $dbseoVal)
		{
			if (!defined($vbseoKey))
			{
				continue;
			}

			// Store this
			$arr['setting'][] = array(
				'name' => $vbseoKey,
				'value' => constant($vbseoKey)
			);
		}
	}
	else
	{
		print_dots_stop();
		print_stop_message('no_xml_and_no_path');
	}

	// display links to settinggroups and create settingscache
	$settingscache = array();
	
	// query settings phrases
	$settings = $db->query("
		SELECT *
		FROM " . TABLE_PREFIX . "setting AS setting
		WHERE product LIKE 'dbtech_dbseo%'
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
		if (!isset($lookupTable[$settingInfo['name']]))
		{
			// Setting could not be imported
			continue;
		}

		// Shorthand
		$settingName = $lookupTable[$settingInfo['name']];

		switch ($settingName)
		{
			case 'dbtech_dbseo_stopwordlist':
				$settingInfo['value'] = str_replace('|', "\n", $settingInfo['value']);
				break;
		}

		if (preg_match('#^(select|selectmulti|radio):(piped|eval)(\r\n|\n|\r)(.*)$#siU', $settingscache[$settingName]['optioncode'], $matches))
		{
			$settingscache[$settingName]['optioncode'] = "$matches[1]:$matches[2]";
			$settingscache[$settingName]['optiondata'] = trim($matches[4]);
			$optionsArray = fetch_piped_options($settingscache[$settingName]['optiondata']);

			if (!array_key_exists($settingInfo['value'], $optionsArray))
			{
				if (!array_key_exists('custom', $optionsArray))
				{
					// This is not supported
					continue;
				}

				// Ensure this is in the right format
				$settingInfo['value'] = preg_replace('#%(\w+)%#i', '[$1]', $settingInfo['value']);

				if (
					strval($vbulletin->options[$settingName]) === 'custom' AND
					strval($vbulletin->options[$settingName . '_custom']) === strval($settingInfo['value'])
				)
				{
					// This is already identical
					continue;
				}

				// Store the setting array
				$settings[$settingName] = 'custom';
				$settings[$settingName . '_custom'] = $settingInfo['value'];				
			}
			else
			{
				if (strval($vbulletin->options[$settingName]) === strval($settingInfo['value']))
				{
					// This is already identical
					continue;
				}

				// Store the setting array
				$settings[$settingName] = $settingInfo['value'];
			}
		}
		else
		{
			if (strval($vbulletin->options[$settingName]) === strval($settingInfo['value']))
			{
				// This is already identical
				continue;
			}

			// Store the setting array
			$settings[$settingName] = $settingInfo['value'];
		}
	}
	
	if (!empty($settings))
	{
		// Save the settings
		save_settings($settings);
	}

	print_dots_stop();	

	define('CP_REDIRECT', 'index.php?do=home');
	print_stop_message('dbtech_dbseo_vbseo_imported');
}