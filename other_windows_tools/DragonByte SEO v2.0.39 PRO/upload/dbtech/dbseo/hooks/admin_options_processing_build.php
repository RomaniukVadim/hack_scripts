<?php
do
{
	if (!class_exists('DBSEO'))
	{
		// We're not dealing with this
		break;
	}

	if (!isset(DBSEO::$cache['librarysettings']))
	{
		// We're not dealing with this
		break;
	}

	if (strpos($oldsetting['varname'], 'dbtech_dbseo_rewrite_rule_') === false)
	{
		// We're not dealing with this
		break;
	}

	// Default values
	$_setting = $oldsetting['varname'];
	$optionValue = $oldsetting['value'];

	if (substr($_setting, -7) == '_custom')
	{
		// Drop the custom suffix
		$_setting = substr($_setting, 0, -7);

		// A custom URL has changed
		if ($settings[$_setting] != 'custom' OR $vbulletin->options[$_setting] != 'custom')
		{
			// The original setting has been modified in some way
			break;
		}

		// We need to do some replacement trickery for custom ones
		$optionValue = str_replace(array('[', ']'), '%', $optionValue);
	}
	else
	{
		// This is not a custom URL
		if ($newvalue == 'custom')
		{
			// We switched to custom

			// Do nothing
		}
		else if ($oldsetting['value'] == 'custom')
		{
			// We switched away from custom

			// We need to do some replacement trickery for custom ones
			$optionValue = str_replace(array('[', ']'), '%', $vbulletin->options[$_setting . '_custom']);
		}
	}

	if ($_setting == 'forumpath')
	{
		// We don't need to store this
		break;
	}

	switch ($vbulletin->options['dbtech_dbseo_filter_nonlatin_chars'])
	{
		case 0:
			$chars = '\S';
			$set = '[^/]';
			break;

		case 1:
			$chars = 'a-z\._';
			$set = '[' . $chars . 'A-Z\d-]';
			break;

		default:
			$chars = 'a-z\._\\' . $vbulletin->options['dbtech_dbseo_rewrite_separator'] . 'ְֱֲֳִֵַָֹÊֻּֽ־ֿׁׂ׃װױײ״ÙÚÛÜÝאבגדהוחטיךכלםמןסעףפץצרשתûü‎ÿµ';
			$set = '[' . $chars . 'A-Z\d-]';
			break;
	}

	$replace = array(
		'#%attachment_id%#' 		=> '([dt\d]+)',
		'#%picture_id%#' 			=> '([dt\d]+)',
		'#%[a-z_]+_id%#' 			=> '(\d+)',
		'#%year%#' 					=> '(\d+)',
		'#%month%#' 				=> '(\d+)',
		'#%day%#' 					=> '(\d+)',
		'#%[a-z_]+_path%#' 			=> '([' . $chars . 'A-Z\d/-]+)',
		'#%[a-z_]+_filename%#' 		=> '(.+)',
		'#%tag%#' 					=> '(.+)',
		'#%(album|group)_title%#' 	=> '([^/]+)',
		'#%[a-z_]+_name%#' 			=> '([^/]+)',
		'#%[a-z_]+_title%#' 		=> '(' . $set . '+)',
		'#%[a-z_]+_ext%#' 			=> '([^/]+)', 
		'#%post_count%#' 			=> '(\d*?)',
		'#%letter%#' 				=> '([a-z]|0|all)',
		'#%[a-z_]*page%#' 			=> '(\d+)',
		'#%[a-z_]+%#' 				=> '(' . $set . ')+',
	);

	// Insert the history
	$vbulletin->db->query_write("
		INSERT IGNORE INTO " . TABLE_PREFIX . "dbtech_dbseo_urlhistory 
			(setting, rawformat, nonlatin, regexpformat) 
		VALUES(
			'" . $vbulletin->db->escape_string(DBSEO::$cache['librarysettings'][$_setting]) . "',
			'" . $vbulletin->db->escape_string($optionValue) . "',
			'" . intval($vbulletin->options['dbtech_dbseo_filter_nonlatin_chars']) . "',
			'" . $vbulletin->db->escape_string(preg_replace(array_keys($replace), $replace, preg_quote($optionValue, '#'))) . "'
		)
	");
}
while (false);
?>