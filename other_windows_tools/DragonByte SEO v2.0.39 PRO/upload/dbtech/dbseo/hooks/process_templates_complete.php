<?php
do
{
	if (!class_exists('DBSEO'))
	{
		// Set important constants
		define('DBSEO_CWD', 	DIR);
		define('DBSEO_TIMENOW', TIMENOW);
		define('IN_DBSEO', 		true);

		// Make sure we nab this class
		require_once(DBSEO_CWD . '/dbtech/dbseo/includes/class_core.php');

		// Init DBSEO
		DBSEO::init(true);
	}

	if (!DBSEO::$config['dbtech_dbseo_active'])
	{
		// Mod is disabled
		break;
	}
	
	if (DBSEO::$config['_preprocessed'] OR (DBSEO_URL_SCHEME == 'https' AND strpos($vbulletin->options['bburl'], 'https:') === false))
	{
		// Clean up base
		$headinclude = preg_replace('#<base href[^>]*?>(\s*?<!--\[if IE\]><\/base><!\[endif\]-->)?#is', '', $headinclude);
	}

	if ($_REQUEST['do'] != 'doenterpwd' AND THIS_SCRIPT != 'vbcms')
	{
		// Prepend canonical URL
		DBSEO_Url_Create::addCanonical($headinclude, preg_replace('#\?.+#', '', $_SERVER['DBSEO_URI']), false);
	}

	if (!class_exists('vB_Template'))
	{
		// Ensure we have this
		require_once(DIR . '/dbtech/dbseo/includes/class_template.php');
	}

	// Make sure we do replacement variables
	$vbulletin->options['dbtech_dbseo_sitelinks_customurl'] = str_replace(array('{bburl}', '{homeurl}'), array(DBSEO::$config['_bburl'], $vbulletin->options['homeurl']), $vbulletin->options['dbtech_dbseo_sitelinks_customurl']);

	// Set the sitelinks template
	$headinclude .= vB_Template::create('dbtech_dbseo_sitelink_search')->render();
}
while (false);
?>