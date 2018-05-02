<?php
if ($_REQUEST['do'] == 'editthread' AND $vbulletin->options['dbtech_dbseo_meta_thread_custom'])
{
	if (!class_exists('vB_Template'))
	{
		// Ensure we have this
		require_once(DIR . '/dbtech/dbseo/includes/class_template.php');
	}

	if (intval($vbulletin->versionnumber) == 3)
	{
		eval('$posticons .= "' . fetch_template('dbtech_dbseo_perthread') . '";');
	}
	else
	{
		$templater = vB_Template::create('dbtech_dbseo_perthread');
			$templater->register('threadinfo', $threadinfo);
		$posticons .= $templater->render();

		// Overwrite registration
		$page_templater->register('posticons', $posticons, true);
	}
}
?>