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
if ($_REQUEST['action'] == 'save' OR empty($_REQUEST['action']))
{
	require_once(DIR . '/includes/adminfunctions_options.php');

	$vbulletin->input->clean_array_gpc('p', array(
		'filedo' 		=> TYPE_STR,
		'actionname' 	=> TYPE_STR,
		'actionparam' 	=> TYPE_STR,
		'dogroup' 		=> TYPE_STR,
		'setting'  		=> TYPE_ARRAY,
	));

	// Shorthand
	$settinggroup = 'dbtech_dbseo_' . $vbulletin->GPC['dogroup'];

	// query settings phrases
	$settings = array();
	$settings_q = $db->query("
		SELECT setting.*, settinggroup.grouptitle
		FROM " . TABLE_PREFIX . "settinggroup AS settinggroup
		LEFT JOIN " . TABLE_PREFIX . "setting AS setting USING(grouptitle)
		WHERE setting.grouptitle = '" . $settinggroup . "'
	");
	while ($setting = $db->fetch_array($settings_q))
	{
		$settings[$setting['varname']] = $setting['varname'];
	}
	$db->free_result($settings_q);
	unset($setting);

	// query settings phrases
	$settingphrase = array();
	$settingphrase_q = $db->query("
		SELECT varname, text
		FROM " . TABLE_PREFIX . "phrase
		WHERE fieldname = 'vbsettings' AND
			languageid IN(-1, 0, " . LANGUAGEID . ")
		ORDER BY languageid ASC
	");
	while ($settingphrase_r = $db->fetch_array($settingphrase_q))
	{
		$settingphrase[$settingphrase_r['varname']] = $settingphrase_r['text'];
	}
	$db->free_result($settingphrase_q);
	unset($settingphrase_r);

	// Ensure we don't have any trickery going on here
	$vbulletin->GPC['setting'] = array_intersect_key($vbulletin->GPC['setting'], $settings);

	if (!empty($vbulletin->GPC['setting']))
	{
		foreach (DBSEO_Url::$libraries as $optionGroup => $options)
		{
			foreach ($options as $option => $optionInfo)
			{
				// Store the raw URLs
				DBSEO::$cache['librarysettings']['dbtech_dbseo_rewrite_rule_' . strtolower($option)] = $optionGroup . '_' . $option;
			}
		}

		save_settings($vbulletin->GPC['setting']);

		// Nuke all the resolved URLs juuuust to be sure
		$vbulletin->db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "dbtech_dbseo_resolvedurl");

		// Ensure we can get rid of the cache
		DBSEO::$datastore->flush();

		define('CP_REDIRECT', 'index.php?do=' . $vbulletin->GPC['filedo'] . ($vbulletin->GPC['actionname'] ? '&' . $vbulletin->GPC['actionname'] . '=' . $vbulletin->GPC['actionparam'] : ''));
		print_stop_message('saved_settings_successfully');
	}
	else
	{
		print_stop_message('nothing_to_do');
	}
}