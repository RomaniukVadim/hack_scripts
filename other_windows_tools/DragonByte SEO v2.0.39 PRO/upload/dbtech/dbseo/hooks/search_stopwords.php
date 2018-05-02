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
	
	if (!is_array(DBSEO::$cache['keywords']))
	{
		DBSEO::$cache['keywords'] = array();
		
		$keywordList = DBSEO::$db->generalQuery('
			SELECT *
			FROM $dbtech_dbseo_keyword
			ORDER BY priority DESC, keyword ASC
		', false);

		foreach ($keywordList as $keyWord)
		{
			if (!$keyWord['active'])
			{
				// Inactive keyword
				continue;
			}

			// Index
			DBSEO::$cache['keywords'][] = $keyWord;
		}
	}

	foreach (DBSEO::$cache['keywords'] as $keyWord)
	{
		// Add to goodwords list
		$goodwords[] = strtolower($keyWord['keyword']);
	}
}
while (false);
?>