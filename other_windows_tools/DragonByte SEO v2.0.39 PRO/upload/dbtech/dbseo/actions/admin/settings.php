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
if ($_REQUEST['action'] == 'options' OR empty($_REQUEST['action']))
{
	require_once(DIR . '/includes/adminfunctions_options.php');
	
	$vbulletin->input->clean_array_gpc('r', array(
		'dogroup' => TYPE_STR,
	));	

	// Shorthand
	$settinggroup = 'dbtech_dbseo_' . $vbulletin->GPC['dogroup'];

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
	
	print_cp_header($vbphrase['dbtech_dbseo_settings']);
	
	echo '<script type="text/javascript" src="../clientscript/vbulletin_cpoptions_scripts.js?v=' . SIMPLE_VERSION . '"></script>';
	
	// display links to settinggroups and create settingscache
	$settingscache = array();
	
	// query settings phrases
	$settings = $db->query("
		SELECT setting.*, settinggroup.grouptitle
		FROM " . TABLE_PREFIX . "settinggroup AS settinggroup
		LEFT JOIN " . TABLE_PREFIX . "setting AS setting USING(grouptitle)
		WHERE setting.grouptitle = '" . $settinggroup . "'
		ORDER BY settinggroup.displayorder, setting.displayorder
	");
	while ($setting = $db->fetch_array($settings))
	{
		$settingscache[$setting['grouptitle']][$setting['varname']] = $setting;
		$grouptitlecache[$setting['grouptitle']] = $setting['grouptitle'];
	}
	$db->free_result($settings);
	unset($setting);
	
	$scriptpath = $vbulletin->scriptpath;
	$debug = $vbulletin->debug;
	$vbulletin->scriptpath = 'options.php';
	$vbulletin->debug = false;

	// show selected settings
	print_form_header('index', 'save', false, true, 'optionsform', '90%', '', true, 'post" onsubmit="return count_errors()');
	construct_hidden_code('filedo', 		'settings');
	construct_hidden_code('actionname', 	'dogroup');
	construct_hidden_code('actionparam', 	$vbulletin->GPC['dogroup']);
	construct_hidden_code('dogroup', 		$vbulletin->GPC['dogroup']);

	print_setting_group($settinggroup);

	print_submit_row($vbphrase['save']);

	?>
	<div id="error_output" style="font: 10pt courier new"></div>
	<script type="text/javascript">
	<!--
	var error_confirmation_phrase = "<?php echo $vbphrase['error_confirmation_phrase']; ?>";
	//-->
	</script>
	<script type="text/javascript" src="../clientscript/vbulletin_settings_validate.js?v=<?php echo SIMPLE_VERSION; ?>"></script>
	<?php
	
	$vbulletin->scriptpath = $scriptpath;
	$vbulletin->debug = $debug;
	
	print_cp_footer();
}