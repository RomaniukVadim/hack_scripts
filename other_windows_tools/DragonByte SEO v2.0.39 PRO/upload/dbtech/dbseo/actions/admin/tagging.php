<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2013 Fillip Hannisdal AKA Revan/NeoRevan/Belazor 	  # ||
|| # All Rights Reserved. 											  # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # You are not allowed to use this on your server unless the files  # ||
|| # you downloaded were done so with permission.					  # ||
|| # ---------------------------------------------------------------- # ||
|| #################################################################### ||
\*======================================================================*/

// #############################################################################
if ($_REQUEST['action'] == 'tagging' OR empty($_REQUEST['action']))
{
	if (!is_array($vbulletin->options['dbtech_dbseo_enable_tagging_thread_excludedforums']))
	{
		$vbulletin->options['dbtech_dbseo_enable_tagging_thread_excludedforums'] = @unserialize($vbulletin->options['dbtech_dbseo_enable_tagging_thread_excludedforums']);
		$vbulletin->options['dbtech_dbseo_enable_tagging_thread_excludedforums'] = is_array($vbulletin->options['dbtech_dbseo_enable_tagging_thread_excludedforums']) ? $vbulletin->options['dbtech_dbseo_enable_tagging_thread_excludedforums'] : array();
	}

	$array = construct_forum_chooser_options(-1, $vbphrase['all']);
	$size = sizeof($array);

	$vbphrase['forum_is_closed_for_posting'] = $vbphrase['closed'];

	print_cp_header($vbphrase['dbtech_dbseo_manage_content_tags']);

	print_form_header('index', 'tagging');
	construct_hidden_code('action', 'reset');
	print_table_header($vbphrase['dbtech_dbseo_reset_tags'], 2, 0);
	print_description_row($vbphrase['dbtech_dbseo_reset_tags_descr']);
	print_yes_no_row($vbphrase['dbtech_dbseo_are_you_sure_reset_tags'], 'doreset', 0);
	print_submit_row($vbphrase['dbtech_dbseo_reset_tags'], false);

	print_form_header('index', 'tagging');
	construct_hidden_code('action', 'add');
	print_table_header($vbphrase['dbtech_dbseo_add_tags']);
	print_input_row($vbphrase['dbtech_dbseo_content_items_per_page'], 'perpage', 250);
	print_time_row($vbphrase['start_date'], 'startdate', 0, false);
	print_time_row($vbphrase['end_date'], 'enddate', TIMENOW, false);
	print_select_row($vbphrase['excluded_forums'], 'excluded[]', $array, $vbulletin->options['dbtech_dbseo_enable_tagging_thread_excludedforums'], false, ($size > 10 ? 10 : $size), true);
	print_submit_row($vbphrase['submit'], 0);

	print_cp_footer();
}

// #############################################################################
if ($_REQUEST['action'] == 'reset')
{
	print_cp_header($vbphrase['dbtech_dbseo_resetting_tags']);

	$vbulletin->input->clean_array_gpc('r', array(
		'doreset' => TYPE_BOOL,
	));

	if (!$vbulletin->GPC['doreset'])
	{
		// Nothing to do
		print_stop_message('nothing_to_do');
	}

	if (intval($vbulletin->versionnumber) == 4)
	{
		// Remove tagcontent
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "tagcontent WHERE contenttypeid = '" . vB_Types::instance()->getContentTypeID('vBForum_Thread') . "'");
	}
	else
	{
		// Remove tagthread
		$db->query_write("DELETE FROM " . TABLE_PREFIX . "tagthread");
	}

	// Reset taglist
	$db->query_write("UPDATE " . TABLE_PREFIX . "thread SET taglist = NULL");

	define('CP_REDIRECT', 'index.php?do=home');
	print_stop_message('dbtech_dbseo_tags_reset');
}

// #############################################################################
if ($_REQUEST['action'] == 'add')
{

	print_cp_header($vbphrase['dbtech_dbseo_manage_content_tags']);

	$vbulletin->input->clean_array_gpc('r', array(
		'perpage' 		=> TYPE_UINT,
		'startat' 		=> TYPE_UINT,
		'startdate' 	=> TYPE_UNIXTIME,
		'enddate' 		=> TYPE_UNIXTIME,
		'excluded' 		=> TYPE_ARRAY_UINT,
	));

	if (empty($vbulletin->GPC['perpage']))
	{
		$vbulletin->GPC['perpage'] = 250;
	}

	echo '<p>' . $vbphrase['dbtech_dbseo_adding_content_tags'] . '...</p>';

	$entries = $db->query_read_slave("
		SELECT post.threadid, post.title, post.pagetext, thread.*
		FROM " . TABLE_PREFIX . "thread AS thread
		LEFT JOIN " . TABLE_PREFIX . "post AS post ON(post.postid = thread.firstpostid)
		WHERE thread.threadid >= " . $vbulletin->GPC['startat'] . "
			AND thread.dateline >= " . $vbulletin->GPC['startdate'] . "
			AND thread.dateline <= " . $vbulletin->GPC['enddate'] . "
		ORDER BY thread.threadid, thread.forumid
		LIMIT " . $vbulletin->GPC['perpage']
	);
	$finishat = ($vbulletin->GPC['startat'] + $vbulletin->GPC['perpage']);

	while ($entry = $db->fetch_array($entries))
	{
		echo construct_phrase($vbphrase['processing_x'], $entry['threadid']) . "<br />\n";
		vbflush();

		if (in_array($entry['forumid'], $vbulletin->GPC['excluded']))
		{
			// Excluded forum
			continue;
		}

		if (in_array(-1, $vbulletin->GPC['excluded']))
		{
			// Excluded forum
			continue;
		}

		// Get current tags
		$keywords = preg_split('#\s*,\s*#s', $entry['taglist'], -1, PREG_SPLIT_NO_EMPTY);

		// Shorthand
		$maxTags = ($vbulletin->options['tagmaxstarter'] > 0 ? $vbulletin->options['tagmaxstarter'] : $vbulletin->options['tagmaxthread']);

		if (sizeof($keywords) < $maxTags)
		{
			// Preparations
			$prefix = '';
			$prefixId = $entry['prefixid'];

			if (!empty($prefixId))
			{
				// We had a thread prefix
				global $vbphrase;
				$prefix = trim($vbphrase["prefix_{$prefixId}_title_plain"]);
			}

			// Do the vB Content filtering
			DBSEO_Filter::vBContentFilter(
				$keywords,
				$prefix . ' ' . unhtmlspecialchars($entry['title']) . ' ' . ($vbulletin->options['dbtech_dbseo_enable_tagging_thread_onlytitle'] ? '' : $entry['pagetext']),
				$maxTags,
				$vbulletin->options['tagminlen'],
				$vbulletin->options['tagmaxlen'],
				$vbulletin->options['tagforcelower'],
				preg_split('/\s+/s', vbstrtolower($vbulletin->options['tagbadwords']), -1, PREG_SPLIT_NO_EMPTY),
				preg_split('/\s+/s', vbstrtolower($vbulletin->options['taggoodwords']), -1, PREG_SPLIT_NO_EMPTY)
			);
		}

		if (intval($vbulletin->versionnumber) == 4)
		{
			require_once(DIR . '/includes/class_taggablecontent.php');
			$content = vB_Taggable_Content_Item::create($vbulletin, 'vBForum_Thread', $entry['threadid'], $entry);

			$limits = $content->fetch_tag_limits();
			$content->add_tags_to_content($keywords, $limits);
		}
		else
		{
			require_once(DIR . '/includes/functions_newpost.php');
			add_tags_to_thread($entry, $keywords);
		}
	}

	$finishat++;

	if ($checkmore = $db->query_first_slave("SELECT threadid FROM " . TABLE_PREFIX . "thread WHERE threadid >= $finishat AND dateline >= " . $vbulletin->GPC['startdate'] . " LIMIT 1"))
	{
		print_cp_redirect("index.php?" . $vbulletin->session->vars['sessionurl'] . "do=tagging&action=add&startat=$finishat&pp=" . $vbulletin->GPC['perpage'] . "&startdate=" . $vbulletin->GPC['startdate'] . "&enddate=" . $vbulletin->GPC['enddate'] . "&excluded[]=" . implode('&excluded[]=', $vbulletin->GPC['excluded']));
		echo "<p><a href=\"index.php?" . $vbulletin->session->vars['sessionurl'] . "do=tagging&amp;action=add&amp;startat=$finishat&amp;pp=" . $vbulletin->GPC['perpage'] . "&startdate=" . $vbulletin->GPC['startdate'] . "&enddate=" . $vbulletin->GPC['enddate'] . "&amp;excluded[]=" . implode('&amp;excluded[]=', $vbulletin->GPC['excluded']) . "\">" . $vbphrase['click_here_to_continue_processing'] . "</a></p>";
	}
	else
	{
		print_cp_message($vbphrase['dbtech_dbseo_tags_added']);
	}
}