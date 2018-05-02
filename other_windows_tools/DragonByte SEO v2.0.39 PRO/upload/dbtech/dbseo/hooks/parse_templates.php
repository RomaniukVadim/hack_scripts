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

	if (($vbulletin->userinfo['permissions']['dbtech_dbseopermissions'] & $vbulletin->bf_ugp_dbtech_dbseopermissions['canadmindbseo']) AND $vbulletin->options['dbtech_dbseo_enable_dbseocp_link'])
	{
		if (intval($vbulletin->versionnumber) == 3)
		{
			$template_hook['footer_links'] .= '<a href="' . $vbulletin->options['dbtech_dbseo_cp_folder'] . '/' . $vbulletin->session->vars['sessionurl_q'] . '">' . $vbphrase['dbtech_dbseo_dbseo_admincp'] . '</a>';
		}
		else
		{
			$template_hook['footer_links'] .= '<li><a href="' . $vbulletin->options['dbtech_dbseo_cp_folder'] . '/' . $vbulletin->session->vars['sessionurl_q'] . '">' . $vbphrase['dbtech_dbseo_dbseo_admincp'] . '</a></li>';
		}
	}

	// Add various template code
	DBSEO::addTemplateCode('socialgroups_grouplist_bit', 		'DBSEO::$cache[\'groups\'][$group[\'groupid\']] = $group');
	DBSEO::addTemplateCode('socialgroups_categorylist_bit', 	'DBSEO::$cache[\'groupscat\'][$category[\'categoryid\']] = $category');
	DBSEO::addTemplateCode('search_results_socialgroup', 		'DBSEO::$cache[\'groupscat\'][$group[\'categoryid\']] = $group');
	DBSEO::addTemplateCode('socialgroups_discussion', 			'DBSEO::$cache[\'groupsdis\'][$discussion[\'discussionid\']] = $discussion');
	DBSEO::addTemplateCode('memberinfo_socialgroupbit', 		'DBSEO::$cache[\'groups\'][$socialgroup[\'groupid\']] = $socialgroup');
	DBSEO::addTemplateCode('blog_entry_profile', 				'DBSEO::$cache[\'blog\'][$this->blog[\'blogid\']] = $this->blog');
	DBSEO::addTemplateCode('blog_entry_profile', 				'$GLOBALS[\'vblog_categories\'] = $this->categories');
	DBSEO::addTemplateCode('albumbit', 							'DBSEO::$cache[\'album\'][$album[\'albumid\']] = $album');
	DBSEO::addTemplateCode('memberinfo_albumbit', 				'DBSEO::$cache[\'album\'][$album[\'albumid\']] = $album');

	if (intval(DBSEO::$config['templateversion']) < 4)
	{
		// vB3 only!
		DBSEO::addTemplateCode('socialgroups_picturebit', 		'DBSEO::$cache[\'' . DBSEO::$config['_picturestorage'] . '\'][$picture[\'' . DBSEO::$config['_pictureid'] . '\']] = $picture');
		DBSEO::addTemplateCode('album_picturebit', 				'DBSEO::$cache[\'' . DBSEO::$config['_picturestorage'] . '\'][$picture[\'' . DBSEO::$config['_pictureid'] . '\']] = $picture');
	}

	if (!isset($_REQUEST['ajax']))
	{
		// Only if we're not in AJAX
		DBSEO::addTemplateCode('blog_comment', 					'DBSEO::$cache[\'blogcom\'][$response[\'blogtextid\']] = array(\'cpage\' => true)');
	}
}
while (false);
?>