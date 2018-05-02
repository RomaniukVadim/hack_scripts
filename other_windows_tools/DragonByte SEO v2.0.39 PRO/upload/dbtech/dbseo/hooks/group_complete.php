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
	
	if (!$vbulletin->GPC['groupid'])
	{
		// Missing group ID
		break;
	}

	if (!is_array($group))
	{
		// Missing group info
		break;
	}

	if (!$group['groupid'])
	{
		// Missing group ID
		break;
	}

	switch ($_REQUEST['do'])
	{
		case 'view':
			// Cache this info
			DBSEO::$cache['groups'][$group['groupid']] = $group;

			// Prepend canonical URL
			DBSEO_Url_Create::addCanonical($headinclude, (
				DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'] ? 
					DBSEO_Url_Create::create('SocialGroup_SocialGroup' . ($vbulletin->GPC['page'] > 1 ? '_Page' : ''), $group) : 
					'group.php?groupid=' . $vbulletin->GPC['groupid'] . ($vbulletin->GPC['page'] > 1 ? '&page=' . $vbulletin->GPC['page'] : '')
			));
			break;

		case 'discuss':
			if (!DBSEO::$config['dbtech_dbseo_enable_socialsharing'])
			{
				// Social Sharing is disabled
				break;
			}

			if (
				count(DBSEO::$config['dbtech_dbseo_socialshare_usergroups']) AND
				!is_member_of($vbulletin->userinfo, DBSEO::$config['dbtech_dbseo_socialshare_usergroups'])
			)
			{
				// Social Sharing is disabled
				break;
			}

			if (
				DBSEO::$config['dbtech_dbseo_socialshare_sg_postlist_above'] == 'none' AND
				DBSEO::$config['dbtech_dbseo_socialshare_sg_postlist_below'] == 'none' AND
				DBSEO::$config['dbtech_dbseo_socialshare_sg_postcontent'] == 'none'
			)
			{
				// Social Sharing is disabled
				break;
			}

			// Set URL
			$show['dbtech_dbseo_url'] = $vbulletin->options['bburl'] . '/' . (DBSEO::$config['dbtech_dbseo_rewrite_socialgroup'] ? 
				DBSEO_Url_Create::create('SocialGroup_SocialGroupDiscussion' . ($vbulletin->GPC['page'] > 1 ? '_Page' : ''), $discussion) : 
				'group.php?discussionid=' . $discussion['discussionid'] . ($vbulletin->GPC['page'] > 1 ? '&page=' . $vbulletin->GPC['page'] : '')
			);
			
			if (!class_exists('vB_Template'))
			{
				// Ensure we have this
				require_once(DIR . '/dbtech/dbseo/includes/class_template.php');
			}

			if (intval($vbulletin->versionnumber) == 3)
			{
				if (DBSEO::$config['dbtech_dbseo_socialshare_sg_postlist_above'] != 'none')
				{
					// Above post list
					eval('$template_hook[\'group_discuss_before_messages\'] .= "' . fetch_template('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_sg_postlist_above']) . '";');
				}

				if (DBSEO::$config['dbtech_dbseo_socialshare_sg_postlist_below'] != 'none')
				{
					// Above post list
					eval('$template_hook[\'group_discuss_after_messages\'] .= "' . fetch_template('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_sg_postlist_below']) . '";');
				}
			}
			else
			{
				if (DBSEO::$config['dbtech_dbseo_socialshare_sg_postlist_above'] != 'none')
				{
					// Above post list
					$template_hook['group_discuss_before_messages'] .= vB_Template::create('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_sg_postlist_above'])->render();
				}

				if (DBSEO::$config['dbtech_dbseo_socialshare_sg_postlist_below'] != 'none')
				{
					// Above post list
					$template_hook['group_discuss_after_messages'] .= vB_Template::create('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_sg_postlist_below'])->render();
				}

				// Gah.
				$page_templater->register('template_hook', $template_hook, true);
			}

			// Add social sharing widget thingy
			$footer = '<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js' . ($vbulletin->options['dbtech_dbseo_socialshare_pubid'] ? '#pubid=' . $vbulletin->options['dbtech_dbseo_socialshare_pubid'] : '') . '"></script>' . $footer;
			break;
	}

}
while (false);
?>