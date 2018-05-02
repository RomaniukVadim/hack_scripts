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

// Create this array
$priorities = array(
	0 => $vbphrase['dbtech_dbseo_low'],
	1 => $vbphrase['dbtech_dbseo_medium'],
	2 => $vbphrase['dbtech_dbseo_high'],
);

// #############################################################################
if ($_REQUEST['action'] == 'keyword' OR empty($_REQUEST['action']))
{
	$keywords = $keywordsToSort = array();
	$keywordQuery = $db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_dbseo_keyword");
	while ($keyword = $db->fetch_array($keywordQuery))
	{
		// Store this as a neat array
		$keywords[$keyword['keywordid']] = $keyword;
		$keywordsToSort[$keyword['keywordid']] = $keyword['keyword'];
	}
	unset($keyword);
	$db->free_result($keywordQuery);

	// Sort by highest prio first
	natcasesort($keywordsToSort);

	$keywords_by_priority = array();
	foreach ($keywordsToSort as $keywordid => $tmp)
	{
		// Index
		$keyword = $keywords[$keywordid];
		$keywords_by_priority[$keyword['priority']][$keyword['keywordid']] = $keyword;
	}

	// Sort by highest prio first
	krsort($keywords_by_priority);
	
	print_cp_header($vbphrase['dbtech_dbseo_keyword_management']);
	
	// Table header
	$headings = array();
	$headings[] = $vbphrase['dbtech_dbseo_keyword'];
	$headings[] = $vbphrase['active'];
	//$headings[] = $vbphrase['dbtech_dbseo_style'];
	$headings[] = $vbphrase['edit'];
	$headings[] = $vbphrase['delete'];
	
	print_form_header('index', 'keywords');
	construct_hidden_code('action', 'massadd');
	print_table_header($vbphrase['dbtech_dbseo_keyword_management'], count($headings));
	print_textarea_row($vbphrase['dbtech_dbseo_keywords_massadd'], 'keywords', '', 12, 80);
	print_submit_row($vbphrase['dbtech_dbseo_add_new_keyword'], false, count($headings));

	if (count($keywords_by_priority))
	{
		print_form_header('index', 'keywords');
		construct_hidden_code('action', 'massupdate');
		print_table_header($vbphrase['dbtech_dbseo_keyword_management'], count($headings));
		
		foreach ($keywords_by_priority as $priorityid => $priorityList)
		{
			print_description_row($vbphrase['dbtech_dbseo_priority_' . $priorityid], false, count($headings), 'optiontitle');
			print_cells_row($headings, 0, 'thead');			
		
			foreach ($priorityList as $keywordid => $keyword)
			{
				$styles = array();
				$styles = implode(', ', $styles);

				// Table data
				$cell = array();
				$cell[] = $keyword['keyword'];
				$cell[] = '
						<input type="hidden" name="keyword[' . $keywordid . '][active]" value="0" /> 
						<input type="checkbox" name="keyword[' . $keywordid . '][active]" value="1"' . ($keyword['active'] ? ' checked="checked"' : '') . ' />
				';
				//$cell[] = $styles ? $styles : $vbphrase['none'];
				$cell[] = construct_link_code($vbphrase['edit'], 'index.php?' . $vbulletin->session->vars['sessionurl'] . 'do=keywords&amp;action=modify&amp;keywordid=' . $keywordid);
				$cell[] = construct_link_code($vbphrase['delete'], 'index.php?' . $vbulletin->session->vars['sessionurl'] . 'do=keywords&amp;action=delete&amp;keywordid=' . $keywordid);
				
				// Print the data
				print_cells_row($cell, 0, 0, -5, 'middle', 0, 1);
			}
		}
		print_submit_row($vbphrase['save'], false, count($headings), false, '<input type="button" id="addnew" class="button" value="' . $vbphrase['dbtech_dbseo_add_new_keyword'] . '" tabindex="1" onclick="window.location=\'index.php?do=keywords&action=modify\'" />');	
	}
	else
	{
		print_form_header('index', 'keywords');
		construct_hidden_code('action', 'modify');
		print_table_header($vbphrase['dbtech_dbseo_keyword_management'], count($headings));
		print_description_row($vbphrase['dbtech_dbseo_no_keywords'], false, count($headings));
		print_submit_row($vbphrase['dbtech_dbseo_add_new_keyword'], false, count($headings));	
	}
}

// #############################################################################
if ($_REQUEST['action'] == 'modify')
{
	$keywordid = $vbulletin->input->clean_gpc('r', 'keywordid', TYPE_UINT);
	$keyword = ($keywordid ? $db->query_first_slave("SELECT *	FROM " . TABLE_PREFIX . "dbtech_dbseo_keyword WHERE keywordid = '" . $vbulletin->GPC['keywordid'] . "'") : false);
	
	if (!is_array($keyword))
	{
		// Non-existing keyword
		$keywordid = 0;
	}
	
	$defaults = array(
		'keyword' 		=> 'keyword',
		'active' 		=> 1,
		'priority' 		=> 1,
	);
	
	if ($keywordid)
	{
		// Edit
		print_cp_header(strip_tags(construct_phrase($vbphrase['dbtech_dbseo_editing_x_y'], $vbphrase['dbtech_dbseo_keyword'], $keyword['keyword'])));
		print_form_header('index', 'keywords');
		construct_hidden_code('action', 'update');
		construct_hidden_code('keywordid', $keywordid);
		print_table_header(construct_phrase($vbphrase['dbtech_dbseo_editing_x_y'], $vbphrase['dbtech_dbseo_keyword'], $keyword['keyword']));
	}
	else
	{
		// Add
		print_cp_header($vbphrase['dbtech_dbseo_add_new_keyword']);
		print_form_header('index', 'keywords');
		construct_hidden_code('action', 'update');
		print_table_header($vbphrase['dbtech_dbseo_add_new_keyword']);
		
		$keyword = $defaults;
	}
	
	print_description_row($vbphrase['dbtech_dbseo_main_settings'], false, 2, 'optiontitle');	
	print_input_row($vbphrase['dbtech_dbseo_keyword'], 				'keyword[keyword]', 					$keyword['keyword']);
	print_yes_no_row($vbphrase['active'],							'keyword[active]',						$keyword['active']);
	print_select_row($vbphrase['dbtech_dbseo_keyword_priority'],	'keyword[priority]',	$priorities,	$keyword['priority']);
	print_submit_row(($keywordid ? $vbphrase['save'] : $vbphrase['dbtech_dbseo_add_new_keyword']));
}

// #############################################################################
if ($_POST['action'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'keywordid' 	=> TYPE_UINT,
		'keyword' 		=> TYPE_ARRAY,
	));
	
	// init data manager
	$dm =& DBSEO::initDataManager('Keyword', $vbulletin, ERRTYPE_CP);
	
	// set existing info if this is an update
	if ($vbulletin->GPC['keywordid'])
	{
		if (!$existing = $db->query_first_slave("SELECT *	FROM " . TABLE_PREFIX . "dbtech_dbseo_keyword WHERE keywordid = '" . $vbulletin->GPC['keywordid'] . "'"))
		{
			// Couldn't find the keyword
			print_stop_message('dbtech_dbseo_invalid_x', $vbphrase['dbtech_dbseo_keyword'], $vbulletin->GPC['keywordid']);
		}
		
		// Set existing
		$dm->set_existing($existing);
		
		// Added
		$phrase = $vbphrase['dbtech_dbseo_edited'];
	}
	else
	{
		// Added
		$phrase = $vbphrase['dbtech_dbseo_added'];
	}
	
	// keyword fields
	foreach ($vbulletin->GPC['keyword'] AS $key => $val)
	{
		if (!$vbulletin->GPC['keywordid'] OR $existing[$key] != $val)
		{
			// Only set changed values
			$dm->set($key, $val);
		}
	}
	
	// Save! Hopefully.
	$dm->save();
	
	define('CP_REDIRECT', 'index.php?do=keywords');
	print_stop_message('dbtech_dbseo_x_y', $vbphrase['dbtech_dbseo_keyword'], $phrase);	
}

// #############################################################################
if ($_POST['action'] == 'massupdate')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'keyword' 		=> TYPE_ARRAY,
	));
	
	foreach ($vbulletin->GPC['keyword'] as $keywordid => $keyword)
	{
		if (!$existing = $db->query_first_slave("SELECT *	FROM " . TABLE_PREFIX . "dbtech_dbseo_keyword WHERE keywordid = '" . $keywordid . "'"))
		{
			// Couldn't find the keyword
			continue;
		}

		// init data manager
		$dm =& DBSEO::initDataManager('Keyword', $vbulletin, ERRTYPE_CP);
			$dm->set_existing($existing);
			foreach ($keyword AS $key => $val)
			{
				if ($existing[$key] != $val)
				{
					// Only set changed values
					$dm->set($key, $val);
				}
			}
		$dm->save();
	}
	
	define('CP_REDIRECT', 'index.php?do=keywords');
	print_stop_message('dbtech_dbseo_x_y', $vbphrase['dbtech_dbseo_keyword'], $vbphrase['dbtech_dbseo_edited']);	
}

// #############################################################################
if ($_POST['action'] == 'massadd')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'keywords' 		=> TYPE_STR,
	));

	$keywords = preg_split('#\r?\n#s', $vbulletin->GPC['keywords'], -1, PREG_SPLIT_NO_EMPTY);
	foreach ($keywords as $keyword)
	{
		// Check priority
		$keyword = explode(';', $keyword);

		if (!isset($keyword[1]) OR !in_array($keyword[1], array(0, 1, 2)))
		{
			// Default to medium
			$keyword[1] = 1;
		}

		// init data manager
		$dm =& DBSEO::initDataManager('Keyword', $vbulletin, ERRTYPE_SILENT);
			$dm->set('keyword', 	$keyword[0]);
			$dm->set('active', 		1);
			$dm->set('priority', 	$keyword[1]);
		$dm->save();
	}
	
	define('CP_REDIRECT', 'index.php?do=keywords');
	print_stop_message('dbtech_dbseo_x_y', $vbphrase['dbtech_dbseo_keyword'], $vbphrase['dbtech_dbseo_added']);
}

// #############################################################################
if ($_REQUEST['action'] == 'delete')
{
	$vbulletin->input->clean_gpc('r', 'keywordid', TYPE_UINT);
	
	if (!$existing = $db->query_first_slave("SELECT *	FROM " . TABLE_PREFIX . "dbtech_dbseo_keyword WHERE keywordid = '" . $vbulletin->GPC['keywordid'] . "'"))
	{
		// Couldn't find the keyword
		print_stop_message('dbtech_dbseo_invalid_x', $vbphrase['dbtech_dbseo_keyword'], $vbulletin->GPC['keywordid']);
	}	

	print_cp_header(construct_phrase($vbphrase['dbtech_dbseo_delete_x'], $vbphrase['dbtech_dbseo_keyword']));
	
	echo "<p>&nbsp;</p><p>&nbsp;</p>";
	print_form_header('index', 'keywords', 0, 1, '', '75%');
	construct_hidden_code('keywordid', $vbulletin->GPC['keywordid']);
	construct_hidden_code('action', 'kill');
	print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], $existing['keyword']));
	print_description_row("
		<blockquote><br />
		" . construct_phrase($vbphrase['are_you_sure_want_to_delete_dbtech_dbseo_keyword_x'], $existing['keyword'],
			'keywordid', $vbulletin->GPC['keywordid'], '') . "
		<br /></blockquote>\n\t");
	print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);

	print_cp_footer();
}

// #############################################################################
if ($_POST['action'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'keywordid' => TYPE_UINT,
		'kill' 		 => TYPE_BOOL
	));
	
	if (!$existing = $db->query_first_slave("SELECT *	FROM " . TABLE_PREFIX . "dbtech_dbseo_keyword WHERE keywordid = '" . $vbulletin->GPC['keywordid'] . "'"))
	{
		// Couldn't find the keyword
		print_stop_message('dbtech_dbseo_invalid_x', $vbphrase['dbtech_dbseo_keyword'], $vbulletin->GPC['keywordid']);
	}
	
	// init data manager
	$dm =& DBSEO::initDataManager('Keyword', $vbulletin, ERRTYPE_CP);
		$dm->set_existing($existing);
	$dm->delete();
	
	define('CP_REDIRECT', 'index.php?do=keywords');
	print_stop_message('dbtech_dbseo_x_y', $vbphrase['dbtech_dbseo_keyword'], $vbphrase['dbtech_dbseo_deleted']);	
}


print_cp_footer();