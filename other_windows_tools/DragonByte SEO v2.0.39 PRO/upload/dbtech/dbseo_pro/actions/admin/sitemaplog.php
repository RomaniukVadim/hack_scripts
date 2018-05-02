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
if ($_REQUEST['action'] == 'sitemaplog' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_dbseo_sitemap_log']);
	
	// ###################### Start modify #######################
	$spiders = $db->query_read_slave("
		SELECT DISTINCT spider
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemaplog
		ORDER BY spider
	");
	$spiderlist = array('no_value' => $vbphrase['all_log_entries']);
	while ($spider = $db->fetch_array($spiders))
	{
		$spiderlist[$spider['spider']] = $spider['spider'];
	}
	
	// ###################### Start modify #######################
	$sitemaps = $db->query_read_slave("
		SELECT DISTINCT sitemap
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemaplog
		ORDER BY sitemap
	");
	$sitemaplist = array('no_value' => $vbphrase['all_log_entries']);
	while ($sitemap = $db->fetch_array($sitemaps))
	{
		$sitemaplist[$sitemap['sitemap']] = $sitemap['sitemap'];
	}
	
	print_form_header('index', 'sitemaplog');
	construct_hidden_code('action', 'view');
	print_table_header($vbphrase['dbtech_dbseo_sitemap_log_viewer']);
	print_input_row($vbphrase['log_entries_to_show_per_page'], 'perpage', 15);
	print_select_row($vbphrase['dbtech_dbseo_spider'], 'spider', $spiderlist);
	print_select_row($vbphrase['dbtech_dbseo_sitemap'], 'sitemap', $sitemaplist);
	print_time_row($vbphrase['start_date'], 'startdate', 0, 0);
	print_time_row($vbphrase['end_date'], 'enddate', 0, 0);
	print_select_row($vbphrase['order_by'], 'orderby', array('date' => $vbphrase['date'], 'spider' => $vbphrase['dbtech_dbseo_spider'], 'sitemap' => $vbphrase['dbtech_dbseo_sitemap']), 'date');
	print_submit_row($vbphrase['view'], 0);
}

// #############################################################################
if ($_REQUEST['action'] == 'view')
{
	print_cp_header($vbphrase['dbtech_dbseo_sitemap_log']);
	
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
		'spider'     => TYPE_STR,
		'sitemap'    => TYPE_STR,
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
	
	if ($vbulletin->GPC['spider'])
	{
		$sqlconds[] = "spider = '" . $vbulletin->GPC['spider'] . "'";
	}
	
	if ($vbulletin->GPC['sitemap'])
	{
		$sqlconds[] = "sitemap = '" . $vbulletin->GPC['sitemap'] . "'";
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
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemaplog
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
		case 'spider':
			$order = 'spider ASC, dateline DESC';
			break;
		case 'sitemap':
			$order = 'sitemap ASC, dateline DESC';
			break;
		case 'date':
		default:
			$order = 'dateline DESC';
	}
	
	$logs = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemaplog
		" . (!empty($sqlconds) ? "WHERE " . implode("\r\n\tAND ", $sqlconds) : "") . "
		ORDER BY $order
		LIMIT $startat, " . $vbulletin->GPC['perpage'] . "
	");
	
	if ($db->num_rows($logs))
	{
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemaplog&action=view&spider=" . urlencode($vbulletin->GPC['spider']) . "&sitemap=" . urlencode($vbulletin->GPC['sitemap']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=1'\">";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemaplog&action=view&spider=" . urlencode($vbulletin->GPC['spider']) . "&sitemap=" . urlencode($vbulletin->GPC['sitemap']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$prv'\">";
		}
	
		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemaplog&action=view&spider=" . urlencode($vbulletin->GPC['spider']) . "&sitemap=" . urlencode($vbulletin->GPC['sitemap']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$nxt'\">";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemaplog&action=view&spider=" . urlencode($vbulletin->GPC['spider']) . "&sitemap=" . urlencode($vbulletin->GPC['sitemap']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$totalpages'\">";
		}
		
		$headings = array();
		$headings[] = "<a href=\"index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemaplog&action=view&spider=" . urlencode($vbulletin->GPC['spider']) . "&sitemap=" . urlencode($vbulletin->GPC['sitemap']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=spider&page=" . $vbulletin->GPC['pagenumber'] . "\">" . str_replace(' ', '&nbsp;', $vbphrase['dbtech_dbseo_spider']) . "</a>";
		$headings[] = "<a href=\"index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemaplog&action=view&spider=" . urlencode($vbulletin->GPC['spider']) . "&sitemap=" . urlencode($vbulletin->GPC['sitemap']) . "&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=date&page=" . $vbulletin->GPC['pagenumber'] . "\">" . $vbphrase['date'] . "</a>";
		$headings[] = $vbphrase['dbtech_dbseo_sitemap'];
		$headings[] = $vbphrase['ip_address'];
	
		print_form_header('', '');
		print_description_row(construct_link_code($vbphrase['restart'], "index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemaplog"), false, count($headings), 'thead', 'right');
		print_table_header(construct_phrase($vbphrase['dbtech_dbseo_sitemap_log_viewer_page_x_y_there_are_z_total_log_entries'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), count($headings));
		print_cells_row($headings, 1);
	
		while ($log = $db->fetch_array($logs))
		{
			$cell = array();
			$cell[] = $log['spider'] . '<br /><span class="smallfont">' . $log['useragent'] . '</span>';
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $log['dateline']) . '</span>';
			$cell[] = $log['sitemap'];
			$cell[] = $log['ipaddress'];
	
			print_cells_row($cell, 0, 0, -4);
		}
	
		print_table_footer(count($headings), "$firstpage $prevpage &nbsp; $nextpage $lastpage");
	}
	else
	{
		print_stop_message('no_results_matched_your_query');
	}
}

print_cp_footer();