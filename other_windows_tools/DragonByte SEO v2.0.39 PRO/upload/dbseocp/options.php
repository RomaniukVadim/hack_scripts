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

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'dbseoadmin');
define('IN_DBSEO', true);
define('DBSEO_ADMIN', true);
if ($_REQUEST['do'] != 'nav')
{
	define('VB_AREA', 'AdminCP');
}
define('VB_ENTRY', 1);
define('IN_CONTROL_PANEL', true);

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array(
	'dbtech_dbseo', 'cpglobal', 'cphome', 'logging', 'threadmanage',
	'banning', 'cpuser', 'cpoption', 'cppermission', 'user'
);

// Step down a notch
chdir('./../');

// get special data templates from the datastore
require('./dbtech/dbseo/includes/specialtemplates.php');
$specialtemplates = $extracache;
$specialtemplates[] = 'pluginlistadmin';

// ######################### REQUIRE BACK-END ############################
require_once('global.php');
require_once(DIR . '/includes/adminfunctions_options.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

// #############################################################################
// ajax setting value validation
if ($_POST['do'] == 'validate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'varname' => TYPE_STR,
		'setting' => TYPE_ARRAY
	));

	$varname = convert_urlencoded_unicode($vbulletin->GPC['varname']);
	$value = convert_urlencoded_unicode($vbulletin->GPC['setting']["$varname"]);

	require_once(DIR . '/includes/class_xml.php');

	$xml = new vB_AJAX_XML_Builder($vbulletin, 'text/xml');
	$xml->add_group('setting');
	$xml->add_tag('varname', $varname);

	if ($setting = $db->query_first_slave("SELECT * FROM " . TABLE_PREFIX . "setting WHERE varname = '" . $db->escape_string($varname) . "'"))
	{
		$raw_value = $value;

		$value = validate_setting_value($value, $setting['datatype']);

		$valid = exec_setting_validation_code($setting['varname'], $value, $setting['validationcode'], $raw_value);
	}
	else
	{
		$valid = 1;
	}

	$xml->add_tag('valid', $valid);
	$xml->close_group();
	$xml->print_xml();
}