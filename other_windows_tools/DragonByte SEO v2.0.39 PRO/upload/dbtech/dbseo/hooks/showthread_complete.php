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

	if (!$threadinfo['dbtech_dbseo_keywords'])
	{
		// Rebuild keywords
		$keywords = preg_split('#\s*,\s*#s', $threadinfo['taglist'], -1, PREG_SPLIT_NO_EMPTY);

		// Shorthand
		$maxTags = DBSEO::$config['dbtech_dbseo_metakeyword_length'];

		if (sizeof($keywords) < $maxTags)
		{
			// Preparations
			$prefix = '';
			$prefixId = $threadinfo['prefixid'];

			if (!empty($prefixId))
			{
				// We had a thread prefix
				$prefix = trim($vbphrase["prefix_{$prefixId}_title_plain"]);
			}

			// Do the vB Content filtering
			DBSEO_Filter::vBContentFilter(
				$keywords,
				$prefix . ' ' . unhtmlspecialchars($threadinfo['title']) . ' ' . $pagetext,
				$maxTags,
				$vbulletin->options['minsearchlength'],
				0,
				true,
				preg_split('/\s+/s', vbstrtolower($vbulletin->options['tagbadwords']), -1, PREG_SPLIT_NO_EMPTY),
				preg_split('/\s+/s', vbstrtolower($vbulletin->options['taggoodwords']), -1, PREG_SPLIT_NO_EMPTY)
			);
		}

		// Now set the tag list
		$threadinfo['dbtech_dbseo_keywords'] = implode(', ', $keywords);

		// Write to DB
		$db->query_write("UPDATE " . TABLE_PREFIX . "thread SET dbtech_dbseo_keywords = " . $db->sql_prepare($threadinfo['dbtech_dbseo_keywords']) . " WHERE threadid = " . intval($threadinfo['threadid']));
	}

	if (DBSEO::$config['dbtech_dbseo_metakeyword_threads'])
	{
		// Set the keywords
		$threadinfo['keywords'] = $threadinfo['dbtech_dbseo_keywords_custom'] ? $threadinfo['dbtech_dbseo_keywords_custom'] : $threadinfo['dbtech_dbseo_keywords'];
	}

	if (!$threadinfo['dbtech_dbseo_description'])
	{
		// Rebuild description
		preg_match('#<!--\s*message\s*-->(.*?)<!--\s*/\s*message\s*-->#s', $postbits, $matches);
		if (!$matches)
		{
			$_searchVal = intval(DBSEO::$config['templateversion']) == 4 ? '</blockquote>' : '</div>';
			if (strpos($postbits, $_searchVal) !== false)
			{
				// Try another match
				preg_match('#post_message_[^>]*?\>(.*?)' . $_searchVal . '#s', $postbits, $matches);
			}
		}

		// Extract the description
		$threadinfo['dbtech_dbseo_description'] = trim(preg_replace(array(
			'#<!--.*?-->#s',
			'#<div>Originally Posted by.*?</div>#',
			'#<script.*?\>.*?</script>#is',
			'#(<.*?\>)+#s'
		), '', str_replace('>' . $vbphrase['quote'] . ':<', '', $matches[1])));

		if ($threadinfo['dbtech_dbseo_description'])
		{
			// We had a description!
			$threadinfo['dbtech_dbseo_description'] = preg_replace('#[\s\"]+#s', ' ', strip_tags($threadinfo['dbtech_dbseo_description']));

			if (strlen($threadinfo['dbtech_dbseo_description']) > DBSEO::$config['dbtech_dbseo_metadescription_length'])
			{
				// Shorten the description
				$threadinfo['dbtech_dbseo_description'] = DBSEO::subStr($threadinfo['dbtech_dbseo_description'], DBSEO::$config['dbtech_dbseo_metadescription_length']);
			}

			// Ensure this is quoted properly
			$threadinfo['dbtech_dbseo_description'] = str_replace(array('$','\\','"'), array('\$','\\\\','&quot;'), $threadinfo['dbtech_dbseo_description']);
		}

		// Write to DB
		$db->query_write("UPDATE " . TABLE_PREFIX . "thread SET dbtech_dbseo_description = " . $db->sql_prepare($threadinfo['dbtech_dbseo_description']) . " WHERE threadid = " . intval($threadinfo['threadid']));
	}

	if (DBSEO::$config['dbtech_dbseo_metadescription_threads'])
	{
		// Set the description
		$threadinfo['meta_description'] = $threadinfo['dbtech_dbseo_description_custom'] ? $threadinfo['dbtech_dbseo_description_custom'] : $threadinfo['dbtech_dbseo_description'];
	}

	// Set thread page
	$threadinfo['page'] = $vbulletin->GPC['pagenumber'];

	// Cache this info
	DBSEO::$cache['thread'][$threadinfo['threadid']] = $threadinfo;

	$show['dbtech_dbseo_url'] = $vbulletin->options['bburl'] . '/' . (DBSEO::$config['dbtech_dbseo_rewrite_thread'] ?
		DBSEO_Url_Create::create('Thread_Thread' . ($threadinfo['page'] > 1 ? '_Page' : ''), $threadinfo) :
		'showthread.php?t=' . $threadinfo['threadid'] . ($threadinfo['page'] > 1 ? '&page=' . $threadinfo['page'] : '')
	);

	// Prepend canonical URL
	DBSEO_Url_Create::addCanonical($headinclude, $show['dbtech_dbseo_url']);

	if (!DBSEO::$config['dbtech_dbseo_enable_socialsharing'])
	{
		// Social Sharing is disabled
		break;
	}

	if (!is_array($vbulletin->options['dbtech_dbseo_socialshare_excludedforums']))
	{
		$vbulletin->options['dbtech_dbseo_socialshare_excludedforums'] = @unserialize($vbulletin->options['dbtech_dbseo_socialshare_excludedforums']);
		$vbulletin->options['dbtech_dbseo_socialshare_excludedforums'] = is_array($vbulletin->options['dbtech_dbseo_socialshare_excludedforums']) ? $vbulletin->options['dbtech_dbseo_socialshare_excludedforums'] : array();
	}

	if (in_array($threadinfo['forumid'], $vbulletin->options['dbtech_dbseo_socialshare_excludedforums']))
	{
		// Skip this
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
		DBSEO::$config['dbtech_dbseo_socialshare_thread_postlist_above'] == 'none' AND
		DBSEO::$config['dbtech_dbseo_socialshare_thread_postlist_below'] == 'none' AND
		DBSEO::$config['dbtech_dbseo_socialshare_thread_postcontent'] == 'none' AND (
			DBSEO::$config['dbtech_dbseo_socialshare_thread_firstpost'] == 'none' AND
			$threadinfo['page'] <= 1
		)
	)
	{
		// Social Sharing is disabled
		break;
	}

	if (!class_exists('vB_Template'))
	{
		// Ensure we have this
		require_once(DIR . '/dbtech/dbseo/includes/class_template.php');
	}

	if (intval($vbulletin->versionnumber) == 3)
	{
		if (DBSEO::$config['dbtech_dbseo_socialshare_thread_postlist_above'] != 'none')
		{
			// Above post list
			eval('$poll .= "' . fetch_template('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_thread_postlist_above']) . '";');
		}

		if (DBSEO::$config['dbtech_dbseo_socialshare_thread_postlist_below'] != 'none')
		{
			// Above post list
			eval('$ad_location[\'ad_showthread_beforeqr\'] .= "' . fetch_template('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_thread_postlist_below']) . '";');
		}
	}
	else
	{
		if (DBSEO::$config['dbtech_dbseo_socialshare_thread_postlist_above'] != 'none')
		{
			// Above post list
			$template_hook['showthread_above_posts'] .= vB_Template::create('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_thread_postlist_above'])->render();
		}

		if (DBSEO::$config['dbtech_dbseo_socialshare_thread_postlist_below'] != 'none')
		{
			// Above post list
			$forumjump .= vB_Template::create('dbtech_dbseo_socialshare_' . DBSEO::$config['dbtech_dbseo_socialshare_thread_postlist_below'])->render();
		}
	}

	// Add social sharing widget thingy
	$footer = '<script type="text/javascript" src="//s7.addthis.com/js/300/addthis_widget.js' . ($vbulletin->options['dbtech_dbseo_socialshare_pubid'] ? '#pubid=' . $vbulletin->options['dbtech_dbseo_socialshare_pubid'] : '') . '"></script>' . $footer;
}
while (false);
?>