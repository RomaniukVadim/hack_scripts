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
	
	// Prepare replacements
	$blog['message'] = DBSEO::replaceIds($blog['message']);

	if (!DBSEO::$config['dbtech_dbseo_rewrite_blog'])
	{
		// Not rewriting blog URLs
		break;
	}

	if (!DBSEO::$config['dbtech_dbseo_rewrite_blogentry'])
	{
		// Not rewriting blog entry URLs
		break;
	}

	if ($_REQUEST['do'] != 'blog')
	{
		// Wrong action
		break;
	}

	if ($_REQUEST['page'])
	{
		// We don't do pages
		break;
	}

	$show['dbtech_dbseo_url'] = $vbulletin->options['bburl'] . '/' . (DBSEO::$config['dbtech_dbseo_rewrite_blogentry'] ? 
		DBSEO_Url_Create::create('Blog_BlogEntry', $blog) : 
		'blog.php?b=' . $blog['blogid']
	);

	// Prepend canonical URL
	DBSEO_Url_Create::addCanonical($headinclude, $show['dbtech_dbseo_url']);

	if (
		DBSEO::$config['dbtech_dbseo_enable_socialsharing'] AND 
		(!count(DBSEO::$config['dbtech_dbseo_socialshare_usergroups']) OR is_member_of($vbulletin->userinfo, DBSEO::$config['dbtech_dbseo_socialshare_usergroups'])) AND (
			DBSEO::$config['dbtech_dbseo_socialshare_blog_postlist_above'] != 'none' OR
			DBSEO::$config['dbtech_dbseo_socialshare_blog_postlist_below'] != 'none' OR
			DBSEO::$config['dbtech_dbseo_socialshare_blog_postcontent'] != 'none'
		)
	)
	{
		if (!class_exists('vB_Template'))
		{
			// Ensure we have this
			require_once(DIR . '/dbtech/dbseo/includes/class_template.php');
		}

		if (intval($vbulletin->versionnumber) == 3)
		{
			if (DBSEO::$config['dbtech_dbseo_socialshare_blog_postlist_above'] != 'none')
			{
				// Above post list
				eval('$ad_location[\'ad_blog_entry_before\'] .= "' . fetch_template('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_blog_postlist_above']) . '";');
			}

			if (DBSEO::$config['dbtech_dbseo_socialshare_blog_postlist_below'] != 'none')
			{
				// Above post list
				eval('$ad_location[\'ad_blog_entry_after\'] .= "' . fetch_template('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_blog_postlist_below']) . '";');
			}
		}
		else
		{
			if (DBSEO::$config['dbtech_dbseo_socialshare_blog_postlist_above'] != 'none')
			{
				// Above post list
				$ad_location['blogshowentry_before'] .= vB_Template::create('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_blog_postlist_above'])->render();
			}

			if (DBSEO::$config['dbtech_dbseo_socialshare_blog_postlist_below'] != 'none')
			{
				// Above post list
				$ad_location['blogshowentry_after'] .= vB_Template::create('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_blog_postlist_below'])->render();
			}
		}

		// Add social sharing widget thingy
		$footer = '<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js' . ($vbulletin->options['dbtech_dbseo_socialshare_pubid'] ? '#pubid=' . $vbulletin->options['dbtech_dbseo_socialshare_pubid'] : '') . '"></script>' . $footer;
	}
}
while (false);
?>