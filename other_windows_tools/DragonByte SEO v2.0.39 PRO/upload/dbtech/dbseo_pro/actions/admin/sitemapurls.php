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

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

$frequencies = array(
	'always' 	=> $vbphrase['dbtech_dbseo_frequency_always'],
	'hourly' 	=> $vbphrase['dbtech_dbseo_frequency_hourly'],
	'daily' 	=> $vbphrase['dbtech_dbseo_frequency_daily'],
	'weekly' 	=> $vbphrase['dbtech_dbseo_frequency_weekly'],
	'monthly' 	=> $vbphrase['dbtech_dbseo_frequency_monthly'],
	'yearly' 	=> $vbphrase['dbtech_dbseo_frequency_yearly'],
	'never' 	=> $vbphrase['dbtech_dbseo_frequency_never'],
);

// #############################################################################
if ($_REQUEST['action'] == 'sitemapurls' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_dbseo_custom_sitemap_urls']);
	
	print_form_header('index', 'sitemapurls', true);
	construct_hidden_code('action', 'import');
	print_table_header($vbphrase['dbtech_dbseo_custom_sitemap_urls_import'], 2, 0);
	print_upload_row($vbphrase['dbtech_dbseo_custom_sitemap_urls_import_descr'], 	'upload');
	print_select_row($vbphrase['dbtech_dbseo_update_frequency'], 					'import[frequency]', 	$frequencies, $vbulletin->options['dbtech_dbseo_sitemap_frequency_autoimport']);
	print_input_row($vbphrase['dbtech_dbseo_priority_range'], 						'import[priority]', 	$vbulletin->options['dbtech_dbseo_sitemap_priority_autoimport']);
	print_time_row($vbphrase['dbtech_dbseo_last_updated'], 							'import[lastupdate]', 	TIMENOW);
	print_submit_row($vbphrase['import'], false);
	
	print_form_header('index', 'sitemapurls');
	construct_hidden_code('action', 'update');
	print_table_header($vbphrase['dbtech_dbseo_add_custom_sitemap_url'], 2, 0);
	print_input_row($vbphrase['dbtech_dbseo_sitemap_url'], 			'sitemapurl[url]', 			$vbulletin->options['bburl'] . '/example/page', 									true, 55);
	print_select_row($vbphrase['dbtech_dbseo_update_frequency'], 	'sitemapurl[frequency]', 	$frequencies, $vbulletin->options['dbtech_dbseo_sitemap_frequency_autoimport']);
	print_input_row($vbphrase['dbtech_dbseo_priority_range'], 		'sitemapurl[priority]', 	$vbulletin->options['dbtech_dbseo_sitemap_priority_autoimport'], 					true, 55);
	print_time_row($vbphrase['dbtech_dbseo_last_updated'], 			'sitemapurl[lastupdate]', 	TIMENOW);
	print_submit_row($vbphrase['dbtech_dbseo_add_custom_sitemap_url'], false);
	
	$frequencies = array('' => $vbphrase['all_log_entries']) + $frequencies;
	print_form_header('index', 'sitemapurls');
	construct_hidden_code('action', 'view');
	print_table_header($vbphrase['dbtech_dbseo_custom_sitemap_url_viewer']);
	print_input_row($vbphrase['log_entries_to_show_per_page'], 'perpage', 15);
	print_select_row($vbphrase['dbtech_dbseo_updatefrequency'], 'frequency', $frequencies);
	print_input_row($vbphrase['dbtech_dbseo_sitemapurl'], 'url');
	print_time_row($vbphrase['start_date'], 'startdate', 0, 0);
	print_time_row($vbphrase['end_date'], 'enddate', 0, 0);
	print_select_row($vbphrase['order_by'], 'orderby', array(
		'url' 			=> $vbphrase['dbtech_dbseo_sitemapurl'], 
		'frequency' 	=> $vbphrase['dbtech_dbseo_updatefrequency'],
		'priority' 		=> $vbphrase['dbtech_dbseo_priorityrange'],
		'lastupdate' 	=> $vbphrase['dbtech_dbseo_lastupdated'],
	), 'lastupdate');
	print_submit_row($vbphrase['view'], 0);

	print_form_header('index', 'sitemapurls');
	construct_hidden_code('action', 'prune');
	print_table_header($vbphrase['prune'], 2, 0);
	print_yes_no_row($vbphrase['dbtech_dbseo_are_you_sure_prune'], 'doprune', 0);
	print_submit_row($vbphrase['prune'], false);

	print_cp_footer();
}

// #############################################################################
if ($_POST['action'] == 'import')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'import' => TYPE_ARRAY
	));

	$vbulletin->input->clean_array_gpc('f', array(
		'upload' => TYPE_FILE
	));
	
	if (file_exists($vbulletin->GPC['upload']['tmp_name']))
	{
		// got an uploaded file?
		$urls = preg_split('#[\r\n]+#', file_read($vbulletin->GPC['upload']['tmp_name']));
	}
	else
	{
		print_stop_message('no_file_uploaded_and_no_local_file_found');
	}

	$urlsToCheck = $urlsToImport = array();
	foreach ($urls as $url)
	{
		// Store some arrays
		$urlsToCheck[$url] = "'" . $db->escape_string($url) . "'";
		$urlsToImport[$url] = "(
			'" . $db->escape_string($url) . "',
			'" . $db->escape_string($vbulletin->GPC['import']['frequency']) . "',
			'" . $db->escape_string($vbulletin->GPC['import']['priority']) . "',
			'" . $vbulletin->input->clean($vbulletin->GPC['import']['lastupdate'], TYPE_UNIXTIME) . "'
		)";
	}

	if (count($urlsToCheck))
	{
		// Check for existing urls
		$foundUrls = $db->query_read_slave("
			SELECT url
			FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
			WHERE url IN(" . implode(',', $urlsToCheck) . ")
		");
		while ($foundUrl = $db->fetch_array($foundUrls))
		{
			// Already had this URL
			unset($urlsToImport[$foundUrl['url']]);
		}
	}

	if (count($urlsToImport))
	{
		// We had some new ones to add
		$db->query_write("
			INSERT INTO " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
				(url, frequency, priority, lastupdate)
			VALUES " . implode(',', $urlsToImport) . "
		");
	}

	define('CP_REDIRECT', 'index.php?do=sitemapurls');
	print_stop_message('dbtech_dbseo_urls_imported');
}

// #############################################################################
if ($_REQUEST['action'] == 'modify')
{
	$vbulletin->input->clean_gpc('r', 'sitemapurlid', TYPE_UINT);
	
	if (!$existing = $db->query_first_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
		WHERE sitemapurlid = '" . $vbulletin->GPC['sitemapurlid'] . "'
	"))
	{
		// Couldn't find the sitemapurl
		print_stop_message('dbtech_dbseo_invalid_x', $vbphrase['dbtech_dbseo_sitemapurl'], $vbulletin->GPC['sitemapurlid']);
	}	

	print_cp_header($vbphrase['dbtech_dbseo_custom_sitemap_urls']);
	print_form_header('index', 'sitemapurls');
	construct_hidden_code('action', 'update');
	construct_hidden_code('sitemapurlid', $existing['sitemapurlid']);
	print_table_header($vbphrase['dbtech_dbseo_add_custom_sitemap_url'], 2, 0);
	print_input_row($vbphrase['dbtech_dbseo_sitemap_url'], 			'sitemapurl[url]', 							$existing['url'], 			true, 55);
	print_select_row($vbphrase['dbtech_dbseo_update_frequency'], 	'sitemapurl[frequency]', 	$frequencies, 	$existing['frequency']);
	print_input_row($vbphrase['dbtech_dbseo_priority_range'], 		'sitemapurl[priority]', 					$existing['priority'], 		true, 55);
	print_time_row($vbphrase['dbtech_dbseo_last_updated'], 			'sitemapurl[lastupdate]', 					$existing['lastupdate']);
	print_submit_row($vbphrase['dbtech_dbseo_add_custom_sitemap_url'], false);
}

// #############################################################################
if ($_POST['action'] == 'update')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'sitemapurlid' 	=> TYPE_UINT,
		'sitemapurl' 	=> TYPE_ARRAY
	));
	
	if (!$vbulletin->GPC['sitemapurlid'])
	{
		if (!$foundUrl = $db->query_first_slave("
			SELECT url
			FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
			WHERE url = '" . $db->escape_string($vbulletin->GPC['sitemapurl']['url']) . "'
		"))
		{
			// We had some new ones to add
			$db->query_write("
				INSERT INTO " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
					(url, frequency, priority, lastupdate)
				VALUES (
					'" . $db->escape_string($vbulletin->GPC['sitemapurl']['url']) . "',
					'" . $db->escape_string($vbulletin->GPC['sitemapurl']['frequency']) . "',
					'" . $db->escape_string($vbulletin->GPC['sitemapurl']['priority']) . "',
					'" . $vbulletin->input->clean($vbulletin->GPC['sitemapurl']['lastupdate'], TYPE_UNIXTIME) . "'
				)
			");
		}
	}
	else
	{
		if ($foundUrl = $db->query_first_slave("
			SELECT sitemapurlid
			FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
			WHERE sitemapurlid = '" . $vbulletin->GPC['sitemapurlid'] . "'
		"))
		{
			// We had some new ones to add
			$db->query_write("
				UPDATE " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
				SET
					url = '" . $db->escape_string($vbulletin->GPC['sitemapurl']['url']) . "', 
					frequency = '" . $db->escape_string($vbulletin->GPC['sitemapurl']['frequency']) . "', 
					priority = '" . $db->escape_string($vbulletin->GPC['sitemapurl']['priority']) . "', 
					lastupdate = '" . $vbulletin->input->clean($vbulletin->GPC['sitemapurl']['lastupdate'], TYPE_UNIXTIME) . "'
				WHERE sitemapurlid = '" . $vbulletin->GPC['sitemapurlid'] . "'
			");
		}
	}

	define('CP_REDIRECT', 'index.php?do=sitemapurls');
	print_stop_message('dbtech_dbseo_urllist_modified');
}

// #############################################################################
if ($_REQUEST['action'] == 'delete')
{
	$vbulletin->input->clean_gpc('r', 'sitemapurlid', TYPE_UINT);
	
	if (!$existing = $db->query_first_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
		WHERE sitemapurlid = '" . $vbulletin->GPC['sitemapurlid'] . "'
	"))
	{
		// Couldn't find the sitemapurl
		print_stop_message('dbtech_dbseo_invalid_x', $vbphrase['dbtech_dbseo_sitemapurl'], $vbulletin->GPC['sitemapurlid']);
	}

	print_cp_header(construct_phrase($vbphrase['dbtech_dbseo_delete_x'], $vbphrase['dbtech_dbseo_sitemapurl']));
	
	echo "<p>&nbsp;</p><p>&nbsp;</p>";
	print_form_header('index', 'sitemapurls', 0, 1, '', '75%');
	construct_hidden_code('sitemapurlid', $vbulletin->GPC['sitemapurlid']);
	construct_hidden_code('action', 'kill');
	print_table_header(construct_phrase($vbphrase['confirm_deletion_x'], $existing['sitemapurl']));
	print_description_row("
		<blockquote><br />
		" . construct_phrase($vbphrase['are_you_sure_want_to_delete_dbtech_dbseo_sitemapurl_x'], $existing['url'],
			'sitemapurlid', $vbulletin->GPC['sitemapurlid'], '') . "
		<br /></blockquote>\n\t");
	print_submit_row($vbphrase['yes'], 0, 2, $vbphrase['no']);

	print_cp_footer();
}

// #############################################################################
if ($_POST['action'] == 'kill')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'sitemapurlid' => TYPE_UINT,
		'kill' 		 => TYPE_BOOL
	));
	
	if (!$existing = $db->query_first_slave("
		SELECT sitemapurlid
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
		WHERE sitemapurlid = '" . $vbulletin->GPC['sitemapurlid'] . "'
	"))
	{
		// Couldn't find the sitemapurl
		print_stop_message('dbtech_dbseo_invalid_x', $vbphrase['dbtech_dbseo_sitemapurl'], $vbulletin->GPC['sitemapurlid']);
	}
	
	// init data manager
	$db->query_write("DELETE FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl WHERE sitemapurlid = '" . $vbulletin->GPC['sitemapurlid'] . "'");
	
	define('CP_REDIRECT', 'index.php?do=sitemapurls');
	print_stop_message('dbtech_dbseo_x_y', $vbphrase['dbtech_dbseo_sitemapurl'], $vbphrase['dbtech_dbseo_deleted']);	
}

// #############################################################################
if ($_REQUEST['action'] == 'view')
{
	print_cp_header($vbphrase['dbtech_dbseo_sitemap_log']);
	
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'frequency'  => TYPE_STR,
		'url'  		 => TYPE_STR,
		'orderby'    => TYPE_NOHTML,
		'startdate'  => TYPE_UNIXTIME,
		'enddate'    => TYPE_UNIXTIME,
	));
	
	$sqlconds = array();
	$hook_query_fields = $hook_query_joins = '';
	
	if ($vbulletin->GPC['perpage'] < 1)
	{
		$vbulletin->GPC['perpage'] = 15;
	}
	
	if ($vbulletin->GPC['frequency'])
	{
		$sqlconds[] = "frequency = '" . $vbulletin->GPC['frequency'] . "'";
	}
	
	if ($vbulletin->GPC['url'])
	{
		$sqlconds[] = "url LIKE '%" . $db->escape_string_like($vbulletin->GPC['url']) . "%'";
	}
	
	if ($vbulletin->GPC['startdate'])
	{
		$sqlconds[] = "dateline >= " . $vbulletin->GPC['startdate'];
	}
	
	if ($vbulletin->GPC['enddate'])
	{
		$sqlconds[] = "dateline <= " . $vbulletin->GPC['enddate'];
	}
	
	$counter = $db->query_first_slave("
		SELECT COUNT(*) AS total
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
		" . (!empty($sqlconds) ? "WHERE " . implode("\r\n\tAND ", $sqlconds) : "") . "
	");
	$totalpages = ceil($counter['total'] / $vbulletin->GPC['perpage']);
	
	if ($vbulletin->GPC['pagenumber'] < 1)
	{
		$vbulletin->GPC['pagenumber'] = 1;
	}
	$startat = ($vbulletin->GPC['pagenumber'] - 1) * $vbulletin->GPC['perpage'];
	
	switch($vbulletin->GPC['orderby'])
	{
		case 'url':
			$order = 'url ASC, lastupdate DESC';
			break;
		case 'frequency':
			$order = 'frequency ASC, lastupdate DESC';
			break;
		case 'priority':
			$order = 'priority ASC, lastupdate DESC';
			break;
		case 'lastupdate':
		default:
			$order = 'lastupdate DESC';
	}
	
	$logs = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl
		" . (!empty($sqlconds) ? "WHERE " . implode("\r\n\tAND ", $sqlconds) : "") . "
		ORDER BY $order
		LIMIT $startat, " . $vbulletin->GPC['perpage'] . "
	");
	
	if ($db->num_rows($logs))
	{
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapurls&action=view&frequency=" . urlencode($vbulletin->GPC['frequency']) . "&url=" . urlencode($vbulletin->GPC['url']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=1'\">";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapurls&action=view&frequency=" . urlencode($vbulletin->GPC['frequency']) . "&url=" . urlencode($vbulletin->GPC['url']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$prv'\">";
		}
	
		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapurls&action=view&frequency=" . urlencode($vbulletin->GPC['frequency']) . "&url=" . urlencode($vbulletin->GPC['url']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$nxt'\">";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapurls&action=view&frequency=" . urlencode($vbulletin->GPC['frequency']) . "&url=" . urlencode($vbulletin->GPC['url']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$totalpages'\">";
		}
		
		$headings = array();
		$headings[] = "<a href=\"index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapurls&action=view&frequency=" . urlencode($vbulletin->GPC['frequency']) . "&url=" . urlencode($vbulletin->GPC['url']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=url&page=" . $vbulletin->GPC['pagenumber'] . "\">" . str_replace(' ', '&nbsp;', $vbphrase['dbtech_dbseo_sitemapurl']) . "</a>";
		$headings[] = "<a href=\"index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapurls&action=view&frequency=" . urlencode($vbulletin->GPC['frequency']) . "&url=" . urlencode($vbulletin->GPC['url']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=frequency&page=" . $vbulletin->GPC['pagenumber'] . "\">" . str_replace(' ', '&nbsp;', $vbphrase['dbtech_dbseo_updatefrequency']) . "</a>";
		$headings[] = "<a href=\"index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapurls&action=view&frequency=" . urlencode($vbulletin->GPC['frequency']) . "&url=" . urlencode($vbulletin->GPC['url']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=priority&page=" . $vbulletin->GPC['pagenumber'] . "\">" . str_replace(' ', '&nbsp;', $vbphrase['dbtech_dbseo_priorityrange']) . "</a>";
		$headings[] = "<a href=\"index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapurls&action=view&frequency=" . urlencode($vbulletin->GPC['frequency']) . "&url=" . urlencode($vbulletin->GPC['url']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=lastupdate&page=" . $vbulletin->GPC['pagenumber'] . "\">" . $vbphrase['dbtech_dbseo_lastupdated'] . "</a>";
		$headings[] = $vbphrase['edit'];
		$headings[] = $vbphrase['delete'];
	
		print_form_header('', '');
		print_description_row(construct_link_code($vbphrase['restart'], "index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapurls"), false, count($headings), 'thead', 'right');
		print_table_header(construct_phrase($vbphrase['dbtech_dbseo_custom_sitemap_url_viewer_page_x_y_there_are_z_total_log_entries'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), count($headings));
		print_cells_row($headings, 1);
	
		while ($log = $db->fetch_array($logs))
		{
			$cell = array();
			$cell[] = construct_link_code($log['url'], $log['url']);
			$cell[] = $frequencies[$log['frequency']];
			$cell[] = $log['priority'];
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $log['lastupdate']) . '</span>';
			$cell[] = construct_link_code($vbphrase['edit'], 'index.php?' . $vbulletin->session->vars['sessionurl'] . 'do=sitemapurls&amp;action=modify&amp;sitemapurlid=' . $log['sitemapurlid']);
			$cell[] = construct_link_code($vbphrase['delete'], 'index.php?' . $vbulletin->session->vars['sessionurl'] . 'do=sitemapurls&amp;action=delete&amp;sitemapurlid=' . $log['sitemapurlid']);
	
			print_cells_row($cell, 0, 0, -4);
		}
	
		print_table_footer(count($headings), "$firstpage $prevpage &nbsp; $nextpage $lastpage");
	}
	else
	{
		print_stop_message('no_results_matched_your_query');
	}
}



function printTimeCell($name = 'date', $unixtime = '', $showtime = true, $birthday = false, $valign = 'middle')
{
	global $vbphrase, $vbulletin;
	static $datepicker_output = false;

	if (!$datepicker_output)
	{
		echo '
			<script type="text/javascript" src="../clientscript/vbulletin_date_picker.js?v=' . SIMPLE_VERSION . '"></script>
			<script type="text/javascript">
			<!--
				vbphrase["sunday"]    = "' . $vbphrase['sunday'] . '";
				vbphrase["monday"]    = "' . $vbphrase['monday'] . '";
				vbphrase["tuesday"]   = "' . $vbphrase['tuesday'] . '";
				vbphrase["wednesday"] = "' . $vbphrase['wednesday'] . '";
				vbphrase["thursday"]  = "' . $vbphrase['thursday'] . '";
				vbphrase["friday"]    = "' . $vbphrase['friday'] . '";
				vbphrase["saturday"]  = "' . $vbphrase['saturday'] . '";
			-->
			</script>
		';
		$datepicker_output = true;
	}

	$monthnames = array(
		0  => '- - - -',
		1  => $vbphrase['january'],
		2  => $vbphrase['february'],
		3  => $vbphrase['march'],
		4  => $vbphrase['april'],
		5  => $vbphrase['may'],
		6  => $vbphrase['june'],
		7  => $vbphrase['july'],
		8  => $vbphrase['august'],
		9  => $vbphrase['september'],
		10 => $vbphrase['october'],
		11 => $vbphrase['november'],
		12 => $vbphrase['december'],
	);

	if (is_array($unixtime))
	{
		require_once(DIR . '/includes/functions_misc.php');
		$unixtime = vbmktime(0, 0, 0, $unixtime['month'], $unixtime['day'], $unixtime['year']);
	}

	if ($birthday)
	{ // mktime() on win32 doesn't support dates before 1970 so we can't fool with a negative timestamp
		if ($unixtime == '')
		{
			$month = 0;
			$day = '';
			$year = '';
		}
		else
		{
			$temp = explode('-', $unixtime);
			$month = intval($temp[0]);
			$day = intval($temp[1]);
			if ($temp[2] == '0000')
			{
				$year = '';
			}
			else
			{
				$year = intval($temp[2]);
			}
		}
	}
	else
	{
		if ($unixtime)
		{
			$month = vbdate('n', $unixtime, false, false);
			$day = vbdate('j', $unixtime, false, false);
			$year = vbdate('Y', $unixtime, false, false);
			$hour = vbdate('G', $unixtime, false, false);
			$minute = vbdate('i', $unixtime, false, false);
		}
	}

	$cell = array();
	$cell[] = "<label for=\"{$name}_month\">$vbphrase[month]</label><br /><select name=\"{$name}[month]\" id=\"{$name}_month\" tabindex=\"1\" class=\"bginput\"" . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[month]&quot;\"") . ">\n" . construct_select_options($monthnames, $month) . "\t\t</select>";
	$cell[] = "<label for=\"{$name}_date\">$vbphrase[day]</label><br /><input type=\"text\" class=\"bginput\" name=\"{$name}[day]\" id=\"{$name}_date\" value=\"$day\" size=\"4\" maxlength=\"2\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[day]&quot;\"") . ' />';
	$cell[] = "<label for=\"{$name}_year\">$vbphrase[year]</label><br /><input type=\"text\" class=\"bginput\" name=\"{$name}[year]\" id=\"{$name}_year\" value=\"$year\" size=\"4\" maxlength=\"4\" tabindex=\"1\"" . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[year]&quot;\"") . ' />';
	if ($showtime)
	{
		$cell[] = $vbphrase['hour'] . '<br /><input type="text" tabindex="1" class="bginput" name="' . $name . '[hour]" value="' . $hour . '" size="4"' . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[hour]&quot;\"") . ' />';
		$cell[] = $vbphrase['minute'] . '<br /><input type="text" tabindex="1" class="bginput" name="' . $name . '[minute]" value="' . $minute . '" size="4"' . iif($vbulletin->debug, " title=\"name=&quot;$name" . "[minute]&quot;\"") . ' />';
	}
	$inputs = '';
	foreach($cell AS $html)
	{
		$inputs .= "\t\t<td><span class=\"smallfont\">$html</span></td>\n";
	}

	return "
		<div id=\"ctrl_$name\"><table cellpadding=\"0\" cellspacing=\"2\" border=\"0\"><tr>\n$inputs\t\n</tr></table></div>
		<script type=\"text/javascript\"> new vB_DatePicker(\"{$name}_year\", \"{$name}_\", \"" . $vbulletin->userinfo['startofweek']  . "\"); </script>\r\n
	";
}

// #############################################################################
if ($_POST['action'] == 'prune')
{
	$vbulletin->input->clean_array_gpc('r', array(
		'doprune' 		 => TYPE_BOOL
	));
	
	if (!$vbulletin->GPC['doprune'])
	{
		// Nothing to do
		print_stop_message('nothing_to_do');
	}
	
	// init data manager
	$db->query_write("TRUNCATE TABLE " . TABLE_PREFIX . "dbtech_dbseo_sitemapurl");
	
	define('CP_REDIRECT', 'index.php?do=sitemapurls');
	print_stop_message('dbtech_dbseo_x_y', $vbphrase['dbtech_dbseo_sitemapurl'], $vbphrase['dbtech_dbseo_deleted']);
}