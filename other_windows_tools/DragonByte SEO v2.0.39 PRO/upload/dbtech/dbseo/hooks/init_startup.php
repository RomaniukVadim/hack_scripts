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

	// The extra bits to cache
	require(DIR . '/dbtech/dbseo/includes/specialtemplates.php');
	$extrafetch = array();

	foreach ($extracache as $varname)
	{
		// datastore_fetch uses a different syntax
		$extrafetch[] = "'$varname'";
	}

	// Now merge the prepared entries
	$datastore_fetch = array_merge($datastore_fetch, $extrafetch);

	if (isset($this) AND is_object($this))
	{
		// Forum inits within a class
		$this->datastore_entries = array_merge((array)$this->datastore_entries, $extracache);
	}
	else
	{
		// AdminCP / ModCP inits normally
		$specialtemplates = array_merge((array)$specialtemplates, $extracache);
	}

	if (defined('DBSEO_URL_SCHEME') AND DBSEO_URL_SCHEME)
	{
		// Set the backup bburl
		$vbulletin->options['bburl'] = str_replace('http://', DBSEO_URL_SCHEME . '://', preg_replace('#/+$#', '', $vbulletin->options['bburl']));
	}

	if ($_POST)
	{
		// Don't redirect post requests
		break;
	}

	if (VB_AREA !== 'Forum')
	{
		// We don't want this to run in the AdminCP
		break;
	}

	if (!DBSEO::$config['dbtech_dbseo_add_canonical'])
	{
		// We're not doing redirects
		break;
	}

	// Always force standard URLs
	$vbulletin->options['friendlyurl'] = 0;

	if (THIS_SCRIPT == 'vbcms')
	{
		// These need to be turned off for the vB CMS
		$vbulletin->options['friendlyurl_canonical'] = 0;
		$vbulletin->options['friendlyurl_canonical_registered'] = 0;
	}

	$_SERVER['DBSEO_VALID_URL'] = true;
	if (!isset($_SERVER['DBSEO_FILE']) OR !$_SERVER['DBSEO_FILE'])
	{
		// We need to grab the stuff for DBSEO_FILE
		preg_match('#([^/]+)$#', $_SERVER['PHP_SELF'], $matches);

		// This is used in certain URL lookups
		$_SERVER['DBSEO_FILE'] = isset($matches[1]) ? (@file_exists($matches[1]) ? $matches[1] : '') : '';

		if (!$_SERVER['DBSEO_FILE'])
		{
			$fileinfo = pathinfo($_SERVER['PHP_SELF']);
			if (@file_exists($_SERVER['DOCUMENT_ROOT'] . $fileinfo['dirname']))
			{
				// This is a funky vB URL, let's force vB to deal with it
				$vbulletin->options['friendlyurl_canonical'] = 2;
				$vbulletin->options['friendlyurl_canonical_registered'] = 2;

				// We don't need to check canonical as we're gonna redirect anyway
				break;
			}
		}

		// Finally set this
		$_SERVER['DBSEO_VALID_URL'] = false;
	}

	// Checks for a canonical URL
	DBSEO_Url_Check::checkCanonical();

	if (DBSEO::$config['dbtech_dbseo_force_directory_index'] AND $_SERVER['DBSEO_FILE'] == DBSEO::$config['homePage'] AND DBSEO_REQURL == DBSEO::$config['homePage'])
	{
		// Redirect to the index
		DBSEO::safeRedirect(DBSEO::$config['_bburl'] . '/');
	}
}
while (false);
?>