<?php
if (!defined('IS_MOBILE_STYLE') OR !IS_MOBILE_STYLE)
{
	if (!class_exists('vB_Template'))
	{
		// Ensure we have this
		require_once(DIR . '/dbtech/dbseo/includes/class_template.php');
	}

	if (intval($vbulletin->versionnumber) == 3)
	{
		eval('$code["$elementtype"] .= "' . fetch_template('dbtech_dbseo_navbit_link') . '";');
	}
	else
	{
		$templater = vB_Template::create('dbtech_dbseo_navbit_link');
			$templater->register('nav_title', $nav_title);
			$templater->register('nav_url', $nav_url);
		$code["$elementtype"] .= $templater->render();
	}
	$skip_nav_entry = true;
}
?>