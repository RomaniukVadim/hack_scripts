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
if ($_REQUEST['action'] == 'sitemapbuildlog' OR empty($_REQUEST['action']))
{
	print_cp_header($vbphrase['dbtech_dbseo_sitemap_build_log']);
	
	// ###################### Start modify #######################
	print_form_header('index', 'sitemapbuildlog');
	construct_hidden_code('action', 'view');
	print_table_header($vbphrase['dbtech_dbseo_sitemap_build_log_viewer']);
	print_input_row($vbphrase['log_entries_to_show_per_page'], 'perpage', 15);
	print_time_row($vbphrase['start_date'], 'startdate', 0, false);
	print_time_row($vbphrase['end_date'], 'enddate', 0, false);
	print_select_row($vbphrase['order_by'], 'orderby', array('date' => $vbphrase['date'], 'spiderhits' => $vbphrase['dbtech_dbseo_spider_hits'], 'sitemaphits' => $vbphrase['dbtech_dbseo_sitemap_hits']), 'date');
	print_submit_row($vbphrase['view'], 0);
}

// #############################################################################
if ($_REQUEST['action'] == 'view')
{
	print_cp_header($vbphrase['dbtech_dbseo_sitemap_build_log']);
	
	$vbulletin->input->clean_array_gpc('r', array(
		'perpage'    => TYPE_UINT,
		'pagenumber' => TYPE_UINT,
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
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapbuildlog
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
		case 'spiderhits':
			$order = 'spiderhits ASC, dateline DESC';
			break;
		case 'sitemaphits':
			$order = 'sitemaphits ASC, dateline DESC';
			break;
		case 'date':
		default:
			$order = 'dateline DESC';
	}
	
	$logs = $db->query_read_slave("
		SELECT *
		FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapbuildlog
		" . (!empty($sqlconds) ? "WHERE " . implode("\r\n\tAND ", $sqlconds) : "") . "
		ORDER BY $order
		LIMIT $startat, " . $vbulletin->GPC['perpage'] . "
	");
	
	if ($db->num_rows($logs))
	{
		if ($vbulletin->GPC['pagenumber'] != 1)
		{
			$prv = $vbulletin->GPC['pagenumber'] - 1;
			$firstpage = "<input type=\"button\" class=\"button\" value=\"&laquo; " . $vbphrase['first_page'] . "\" tabindex=\"1\" onclick=\"window.location='index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapbuildlog&action=view&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=1'\">";
			$prevpage = "<input type=\"button\" class=\"button\" value=\"&lt; " . $vbphrase['prev_page'] . "\" tabindex=\"1\" onclick=\"window.location='index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapbuildlog&action=view&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$prv'\">";
		}
	
		if ($vbulletin->GPC['pagenumber'] != $totalpages)
		{
			$nxt = $vbulletin->GPC['pagenumber'] + 1;
			$nextpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['next_page'] . " &gt;\" tabindex=\"1\" onclick=\"window.location='index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapbuildlog&action=view&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$nxt'\">";
			$lastpage = "<input type=\"button\" class=\"button\" value=\"" . $vbphrase['last_page'] . " &raquo;\" tabindex=\"1\" onclick=\"window.location='index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapbuildlog&action=view&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=" . $vbulletin->GPC['orderby'] . "&page=$totalpages'\">";
		}
		
		$headings = array();
		$headings[] = $vbphrase['dbtech_dbseo_sitemap_build_id'];
		$headings[] = "<a href=\"index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapbuildlog&action=view&startdate=" . urlencode($vbulletin->GPC['startdate']) . "&enddate=" . urlencode($vbulletin->GPC['enddate']) . "&pp=" . $vbulletin->GPC['perpage'] . "&orderby=date&page=" . $vbulletin->GPC['pagenumber'] . "\">" . $vbphrase['date'] . "</a>";
		$headings[] = $vbphrase['dbtech_dbseo_build_details'];
		/*DBTECH_PRO_START*/
		$headings[] = $vbphrase['dbtech_dbseo_logged_sitemap_hits'];
		$headings[] = $vbphrase['dbtech_dbseo_logged_spider_hits'];
		/*DBTECH_PRO_END*/
	
		print_form_header('', '');
		print_description_row(construct_link_code($vbphrase['restart'], "index.php?" . $vbulletin->session->vars['sessionurl'] . "do=sitemapbuildlog"), false, count($headings), 'thead', 'right');
		print_table_header(construct_phrase($vbphrase['dbtech_dbseo_sitemap_build_log_viewer_page_x_y_there_are_z_total_log_entries'], vb_number_format($vbulletin->GPC['pagenumber']), vb_number_format($totalpages), vb_number_format($counter['total'])), count($headings));
		print_cells_row($headings, 1);
	
		while ($log = $db->fetch_array($logs))
		{
			$buildDetails = '';
			$log['builddetails'] = @unserialize($log['builddetails']);

			if ($log['prevbuilddetails'] == NULL)
			{
				// This means the upgrade script has ran, but the details weren't filled in
				if (!$prevBuild = $db->query_first_slave("
					SELECT builddetails
					FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapbuildlog
					WHERE sitemapbuildlogid < $log[sitemapbuildlogid]
					ORDER BY sitemapbuildlogid DESC
					LIMIT 1
				"))
				{
					$log['prevbuilddetails'] = $prevBuild = array();
					foreach ($log['builddetails'] as $contenttype => $numUrls)
					{
						// Default to 0
						$log['prevbuilddetails'][$contenttype] = 0;
					}

					// Prepare for SQL insert
					$prevBuild['builddetails'] = trim(serialize($log['prevbuilddetails']));
				}
				else
				{
					// Make sure this is an array
					$log['prevbuilddetails'] = @unserialize($prevBuild['builddetails']);
					$log['prevbuilddetails'] = is_array($log['prevbuilddetails']) ? $log['prevbuilddetails'] : array();
				}

				// Update
				$db->query_write("
					UPDATE " . TABLE_PREFIX . "dbtech_dbseo_sitemapbuildlog 
					SET prevbuilddetails = " . $db->sql_prepare($prevBuild['builddetails']) . " 
					WHERE sitemapbuildlogid = $log[sitemapbuildlogid]
				");
			}
			else
			{
				// Make sure this is an array
				$log['prevbuilddetails'] = @unserialize($log['prevbuilddetails']);
				$log['prevbuilddetails'] = is_array($log['prevbuilddetails']) ? $log['prevbuilddetails'] : array();
			}

			$buildDetails = array();
			if (!is_array($log['builddetails']))
			{
				// Didn't have this for some reason
				$buildDetails[] = '<tr><td>' . $vbphrase['n_a'] . '</td></tr>';
			}
			else
			{
				$totals = array('current' => 0, 'prev' => 0);

				// Do the proper array
				foreach ($log['builddetails'] as $contenttype => $numUrls)
				{
					// Make sure this is set
					$log['prevbuilddetails'][$contenttype] = isset($log['prevbuilddetails'][$contenttype]) ? $log['prevbuilddetails'][$contenttype] : 0;

					// Calculate difference
					$difference = $numUrls - $log['prevbuilddetails'][$contenttype];

					$colour = ($difference < 0 ? 'color:red;' : ($difference > 0 ? 'color:green;' : '')); 
					$buildDetails[$contenttype] = '<tr>
						<td class="smallfont" style="padding-' . (intval($vbulletin->versionnumber) == 3 ? $stylevar['right'] : vB_Template_Runtime::fetchStyleVar('right')) . ':6px;">
							<strong>' . (
								$vbphrase['dbtech_dbseo_contenttype_' . $contenttype] ? 
								$vbphrase['dbtech_dbseo_contenttype_' . $contenttype] : 
								'dbtech_dbseo_contenttype_' . $contenttype) . 
							':</strong>
						</td>
						<td class="smallfont" style="padding-' . (intval($vbulletin->versionnumber) == 3 ? $stylevar['right'] : vB_Template_Runtime::fetchStyleVar('right')) . ':24px;">' . vb_number_format($numUrls, 0) . '</td>
						<td class="smallfont" style="padding-' . (intval($vbulletin->versionnumber) == 3 ? $stylevar['right'] : vB_Template_Runtime::fetchStyleVar('right')) . ':24px;' . $colour . '">' . ($difference >= 0 ? '+' : '') . vb_number_format($difference, 0) . '</td>
					</tr>';

					$totals['current'] += $numUrls;
					$totals['prev'] += $log['prevbuilddetails'][$contenttype];
				}

				foreach ($log['prevbuilddetails'] as $contenttype => $numUrls)
				{
					if (isset($buildDetails[$contenttype]))
					{
						// Skip this
						continue;
					}

					$buildDetails[$contenttype] = '<tr>
						<td class="smallfont" style="padding-' . (intval($vbulletin->versionnumber) == 3 ? $stylevar['right'] : vB_Template_Runtime::fetchStyleVar('right')) . ':6px;">
							<strong>' . (
								$vbphrase['dbtech_dbseo_contenttype_' . $contenttype] ? 
								$vbphrase['dbtech_dbseo_contenttype_' . $contenttype] : 
								'dbtech_dbseo_contenttype_' . $contenttype) . 
							':</strong>
						</td>
						<td class="smallfont" style="padding-' . (intval($vbulletin->versionnumber) == 3 ? $stylevar['right'] : vB_Template_Runtime::fetchStyleVar('right')) . ':24px;">0</td>
						<td class="smallfont" style="padding-' . (intval($vbulletin->versionnumber) == 3 ? $stylevar['right'] : vB_Template_Runtime::fetchStyleVar('right')) . ':24px; color:red;">-' . vb_number_format($numUrls, 0) . '</td>
					</tr>';

					$totals['prev'] += $numUrls;
				}

				// Calculate difference
				$difference = $totals['current'] - $totals['prev'];

				$colour = ($difference < 0 ? 'color:red;' : ($difference > 0 ? 'color:green;' : '')); 

				$buildDetails[] = '<tr>
					<td style="padding-top:24px; padding-' . (intval($vbulletin->versionnumber) == 3 ? $stylevar['right'] : vB_Template_Runtime::fetchStyleVar('right')) . ':6px;">
						<strong>' . $vbphrase['total'] . '</strong>
					</td>
					<td style="padding-top:24px; padding-' . (intval($vbulletin->versionnumber) == 3 ? $stylevar['right'] : vB_Template_Runtime::fetchStyleVar('right')) . ':24px;">
						<strong>' . vb_number_format($totals['current'], 0) . '</strong>
					</td>
					<td style="padding-top:24px; padding-' . (intval($vbulletin->versionnumber) == 3 ? $stylevar['right'] : vB_Template_Runtime::fetchStyleVar('right')) . ':24px;' . $colour . '">
						<strong>' . ($difference >= 0 ? '+' : '') . vb_number_format($difference, 0) . '</strong>
					</td>
				</tr>';
			}

			$cell = array();
			$cell[] = $log['sitemapbuildlogid'];
			$cell[] = '<span class="smallfont">' . vbdate($vbulletin->options['logdateformat'], $log['dateline']) . '</span>';
			$cell[] = '<table>' . implode('', $buildDetails) . '</table>';
			/*DBTECH_PRO_START*/
			$cell[] = vb_number_format($log['sitemaphits']);
			$cell[] = vb_number_format($log['spiderhits']);
			/*DBTECH_PRO_END*/
	
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