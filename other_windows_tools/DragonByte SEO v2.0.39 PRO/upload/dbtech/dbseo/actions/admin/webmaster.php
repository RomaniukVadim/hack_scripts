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

@set_time_limit(0);
ignore_user_abort(1);

// Make sure we have this
require_once(DIR . '/dbtech/dbseo/includes/functions_chart.php');

// Grab ourselves the Google accounts integration
require_once(DIR . '/dbtech/dbseo/includes/3rdparty/Google/config.inc.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################

print_cp_header($vbphrase['dbtech_dbseo_gwt_reports']);

// #############################################################################
if ($_REQUEST['action'] == 'oauth')
{
	try
	{
		if (!isset($_REQUEST['code']))
		{
			?><script type="text/javascript">window.onload = function() { window.open('<?php echo $client->createAuthUrl(); ?>', "_blank"); } </script><?php

			print_form_header('index', 'webmaster');
			construct_hidden_code('action', 'oauth');
			print_table_header($vbphrase['dbtech_dbseo_google_authentication']);
			print_input_row($vbphrase['dbtech_dbseo_google_authentication_code'], 'code');
			print_submit_row($vbphrase['dbtech_dbseo_google_authenticate'], null);
			print_cp_footer();
		}

		// Get access token
		$vbulletin->dbtech_dbseo_oauth = $client->authenticate($_REQUEST['code']);
		build_datastore('dbtech_dbseo_oauth', trim($vbulletin->dbtech_dbseo_oauth), 0);
	}
	catch (Exception $exception)
	{
		print_cp_message($exception->getMessage() . ' on line ' . $exception->getLine() . ' in ' . $exception->getFile() . ' (code: ' . $exception->getCode() . ')');
	}

	// Blank this out so we go back to the main loop
	print_cp_redirect('index.php?do=webmaster');
}

try
{
	$client->setAccessToken($vbulletin->dbtech_dbseo_oauth);
}
catch (Exception $e)
{
	// Access token couldn't be set
	print_form_header('index', 'webmaster');
	construct_hidden_code('action', 'oauth');
	construct_hidden_code('param', 'login');
	print_table_header($vbphrase['dbtech_dbseo_google_authentication']);
	print_description_row($vbphrase['dbtech_dbseo_google_must_authenticate']);
	print_submit_row($vbphrase['dbtech_dbseo_google_authenticate'], null);
	print_cp_footer();
}

if ($client->isAccessTokenExpired())
{
	try
	{
		// Refresh access token
		$client->refreshToken($client->getRefreshToken());
	}
	catch (Exception $e)
	{
		// Access token couldn't be set
		print_form_header('index', 'webmaster');
		construct_hidden_code('action', 'oauth');
		construct_hidden_code('param', 'login');
		print_table_header($vbphrase['dbtech_dbseo_google_authentication']);
		print_description_row($vbphrase['dbtech_dbseo_google_must_authenticate']);
		print_submit_row($vbphrase['dbtech_dbseo_google_authenticate'], null);
		print_cp_footer();
	}

	// Update the access token
	$vbulletin->dbtech_dbseo_oauth = $client->getAccessToken();
	build_datastore('dbtech_dbseo_oauth', trim($vbulletin->dbtech_dbseo_oauth), 0);
}

// Set GWT object
$gwt = new Google_Service_Webmasters($client);

// #############################################################################
if ($_POST['action'] == 'savesite')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'site' => TYPE_STR,
	));

	if ($vbulletin->GPC['site'])
	{
		if (!isset($vbulletin->dbtech_dbseo_gwt_cache))
		{
			// Init this
			$vbulletin->dbtech_dbseo_gwt_cache = array('site' => '');
		}

		if ($vbulletin->GPC['site'] == 'n_a')
		{
			// Add the required URL
			$gwt->sites->add($vbulletin->options['bburl']);

			// Set GWT object
			$siteVerification = new Google_Service_SiteVerification($client);

			// Create the request
			$result = $siteVerification->webResource->getToken(new Google_Service_SiteVerification_SiteVerificationWebResourceGettokenRequest(array(
				'verificationMethod' => 'META',
				'site' => array(
					'type' => 'SITE',
					'identifier' => $vbulletin->options['bburl']
				)
			)));
			$token = $result->getToken();

			// We need this
			require_once(DIR . '/includes/adminfunctions_template.php');

			$styles = $db->query_read_slave("SELECT styleid, title FROM " . TABLE_PREFIX . "style WHERE parentid < 0");
			while ($style = $db->fetch_array($styles))
			{
				$doRebuild = false;

				if ($existing = $db->query_first("
					SELECT templateid, styleid, title, template
					FROM " . TABLE_PREFIX . "template
					WHERE styleid = " . $style['styleid'] . "
						AND templatetype = 'replacement'
						AND title = '" . $db->escape_string('</head>') . "'
				"))
				{
					if (strpos($existing['template'], $token) !== false)
					{
						// This style already had a valid replacement
						continue;
					}

					/*update query*/
					$db->query_write("
						UPDATE " . TABLE_PREFIX . "template
						SET template = '" . $db->escape_string($token . "\n" . $existing['template']) . "'
						WHERE templateid = '" . $existing['templateid'] . "'
					");

					// Force a rebuild
					$doRebuild = true;
				}
				else
				{
					/*insert query*/
					$db->query_write("
						INSERT INTO " . TABLE_PREFIX . "template
							(styleid, templatetype, title, template)
						VALUES
							(" . $style['styleid'] . ", 'replacement', '" . $db->escape_string('</head>') . "', '" . $db->escape_string($token . "\n</head>") . "')
					");

					// Force a rebuild
					$doRebuild = true;
				}

				if ($doRebuild)
				{
					// Rebuild this style
					print_rebuild_style($style['styleid'], $style['title'], false, false, true, false);
				}
			}

			// Submit verification request
			$siteVerification->webResource->insert('META', new Google_Service_SiteVerification_SiteVerificationWebResourceResource(array(
				'site' => array(
					'type' => 'SITE',
					'identifier' => $vbulletin->options['bburl']
				)
			)));

			// Set this
			$vbulletin->dbtech_dbseo_gwt_cache['site'] = $vbulletin->options['bburl'];
		}
		else
		{
			// Set this
			$vbulletin->dbtech_dbseo_gwt_cache['site'] = $vbulletin->GPC['site'];
		}

		// Now cache
		build_datastore('dbtech_dbseo_gwt_cache', trim(serialize($vbulletin->dbtech_dbseo_gwt_cache)), 1);
	}

	// Blank this out so we go back to the main loop
	print_cp_redirect('index.php?do=webmaster');
}

// #############################################################################
if ($_REQUEST['action'] != 'oauth')
{
	$updateCache = $preventDefaultCache = false;
	if (!isset($vbulletin->dbtech_dbseo_gwt_cache))
	{
		// Init this
		$vbulletin->dbtech_dbseo_gwt_cache = array('site' => '');
	}

	if (!$vbulletin->dbtech_dbseo_gwt_cache['site'])
	{
		$sites = array('' => $vbphrase['dbtech_dbseo_choose_site_profile']);

		$request = $gwt->sites->listSites();
		foreach ($request as $site)
		{
			$sites[$site->siteUrl] = $site->siteUrl;
			if (strpos($vbulletin->options['bburl'], $site->siteUrl) !== false)
			{
				// Set this
				$vbulletin->dbtech_dbseo_gwt_cache['site'] = $site->siteUrl;

				// Flag cache for update
				$updateCache = true;
				break;
			}
		}

		if (!$vbulletin->dbtech_dbseo_gwt_cache['site'])
		{
			// Add this at the bottom
			$sites['n_a'] = $vbphrase['dbtech_dbseo_none_add_new_site'];

			print_form_header('index', 'webmaster');
			construct_hidden_code('action', 'savesite');
			print_table_header($vbphrase['dbtech_dbseo_gwt_site']);
			print_select_row($vbphrase['dbtech_dbseo_gwt_choose_site'], 'site', $sites, '');
			print_submit_row($vbphrase['save'], null);
			print_cp_footer();
		}
	}

	// Set the keys
	$keys = array('startdate', 'enddate'/*DBTECH_PRO_START*/, 'comparisonstartdate', 'comparisonenddate', 'includecomparison'/*DBTECH_PRO_END*/);

	$defaults = array(
		'startdate' 			=> strtotime('midnight -1 month 1 day'),
		'enddate' 				=> strtotime('midnight -1 day'),
		/*DBTECH_PRO_START*/
		'comparisonstartdate' 	=> strtotime('midnight -2 months -1 day'),
		'comparisonenddate' 	=> strtotime('midnight -1 month -1 day'),
		'includecomparison' 	=> 1,
		/*DBTECH_PRO_END*/
	);

	if (!isset($vbulletin->dbtech_dbseo_gwt_cache['statdates']))
	{
		// Set defaults
		$vbulletin->dbtech_dbseo_gwt_cache['statdates'] = array();
	}

	foreach ($keys as $key)
	{
		if (!isset($_REQUEST[$key]))
		{
			// Set from cache
			$_REQUEST[$key] = $_POST[$key] = isset($vbulletin->dbtech_dbseo_gwt_cache['statdates'][$key]) ? intval($vbulletin->dbtech_dbseo_gwt_cache['statdates'][$key]) : $defaults[$key];
		}
	}

	$vbulletin->input->clean_array_gpc('r', array(
		'startdate'  			=> TYPE_UNIXTIME,
		'enddate'    			=> TYPE_UNIXTIME,
		/*DBTECH_PRO_START*/
		'includecomparison'  	=> TYPE_BOOL,
		'comparisonstartdate'  	=> TYPE_UNIXTIME,
		'comparisonenddate'    	=> TYPE_UNIXTIME,
		/*DBTECH_PRO_END*/
	));

	if ($vbulletin->GPC['enddate'] < $vbulletin->GPC['startdate'])
	{
		// Make sure end date is correct
		$vbulletin->GPC['enddate'] = $vbulletin->GPC['startdate'] + ($vbulletin->GPC['startdate'] - $vbulletin->GPC['enddate']);
	}

	/*DBTECH_PRO_START*/
	if ($vbulletin->GPC['comparisonenddate'] < $vbulletin->GPC['comparisonstartdate'])
	{
		// Make sure comparison end date is correct
		$vbulletin->GPC['comparisonenddate'] = $vbulletin->GPC['comparisonstartdate'] + ($vbulletin->GPC['comparisonstartdate'] - $vbulletin->GPC['comparisonenddate']);
	}
	/*DBTECH_PRO_END*/

	foreach ($keys as $key)
	{
		if (!isset($vbulletin->dbtech_dbseo_gwt_cache['statdates'][$key]) AND $vbulletin->GPC[$key] == $defaults[$key])
		{
			// Skip this
			continue;
		}

		// Write to cache
		$vbulletin->dbtech_dbseo_gwt_cache['statdates'][$key] = intval($vbulletin->GPC[$key]);

		// Flag cache for update
		$updateCache = true;
	}

	// Set start & end date variables
	$startDate = $vbulletin->GPC['startdate'];
	$endDate = $vbulletin->GPC['enddate'];

	/*DBTECH_PRO_START*/
	if ($vbulletin->GPC['includecomparison'])
	{
		// Comparison start & end dates
		$comparisonStartDate = $vbulletin->GPC['comparisonstartdate'];
		$comparisonEndDate = $vbulletin->GPC['comparisonenddate'];
	}
	/*DBTECH_PRO_END*/

	// Set hash key
	$hashKey = 'crawlerrors_web';

	if (!isset($vbulletin->dbtech_dbseo_gwt_cache[$hashKey]))
	{
		// Basic cache array
		$vbulletin->dbtech_dbseo_gwt_cache[$hashKey] = array(
			'data' => array(),
			'time' => 0
		);
	}

	if ($vbulletin->dbtech_dbseo_gwt_cache[$hashKey]['time'] <= (TIMENOW - 3600))
	{
		// Easier to handle this data
		$data = array();

		// Grab the request data
		$request = $gwt->urlcrawlerrorscounts->query($vbulletin->dbtech_dbseo_gwt_cache['site'], array('platform' => 'web'));

		foreach ($request->countPerTypes as $type)
		{
			$data[$type->category] = $type->entries[0]->count;
		}

		// Store cache object
		$vbulletin->dbtech_dbseo_gwt_cache[$hashKey] = array(
			'data' => $data,
			'time' => TIMENOW
		);

		// Schedule cache update
		$updateCache = true;
	}
	else
	{
		$data = $vbulletin->dbtech_dbseo_gwt_cache[$hashKey]['data'];
	}

	$cell = array();

	print_table_start();
	print_table_header($vbphrase['dbtech_dbseo_gwt_site_report'], 4);
	foreach ($data AS $category => $count)
	{
		$cellCount = count($cell);
		if ($cellCount AND $cellCount % 4 == 0)
		{
			print_cells_row($cell, 0, 0, -5, 'top', 1, 1);
			$cell = array();
		}

		// Add the cell row
		$cell[] = $vbphrase['dbtech_dbseo_gwt_crawlerrors_'. $category] ? $vbphrase['dbtech_dbseo_gwt_crawlerrors_'. $category] : 'dbtech_dbseo_gwt_crawlerrors_'. $category;
		$cell[] = vb_number_format($count);
	}

	if (count($cell))
	{
		// Add more data
		print_cells_row($cell, 0, 0, -5, 'top', 1, 1);
	}
	print_table_footer();

	// Set hash key
	$hashKey = 'sitemaps';

	if (!isset($vbulletin->dbtech_dbseo_gwt_cache[$hashKey]))
	{
		// Basic cache array
		$vbulletin->dbtech_dbseo_gwt_cache[$hashKey] = array(
			'data' => array(),
			'time' => 0
		);
	}

	if ($vbulletin->dbtech_dbseo_gwt_cache[$hashKey]['time'] <= (TIMENOW - 3600))
	{
		// Easier to handle this data
		$sitemapInfo = array();

		// Grab the request data
		$request = $gwt->sitemaps->listSitemaps($vbulletin->dbtech_dbseo_gwt_cache['site']);

		foreach ($request->sitemap as $sitemap)
		{
			if (strpos($sitemap->path, 'dbseositemap.php') !== false)
			{
				// We have a sitemap
				foreach ($sitemap->contents as $sitemapContent)
				{
					if ($sitemapContent->type == 'web')
					{
						// This is the sitemap info we need
						$sitemapInfo['submitted'] = $sitemapContent->submitted;
						$sitemapInfo['indexed'] = $sitemapContent->indexed;
						break 2;
					}
				}
				break;
			}
		}

		if (!$sitemapInfo)
		{
			// Submit the sitemap
			$gwt->sitemaps->submit($vbulletin->dbtech_dbseo_gwt_cache['site'], $vbulletin->options['bburl'] . '/dbseositemap.php');

			// This is the sitemap info we need
			$sitemapInfo['submitted'] = 0;
			$sitemapInfo['indexed'] = 0;
		}

		if ($sitemapInfo)
		{
			// Store cache object
			$vbulletin->dbtech_dbseo_gwt_cache[$hashKey] = array(
				'data' => $sitemapInfo,
				'time' => TIMENOW
			);

			// Schedule cache update
			$updateCache = true;
		}
	}
	else
	{
		// Fetch sitemap info from cache
		$sitemapInfo = $vbulletin->dbtech_dbseo_gwt_cache[$hashKey]['data'];
	}

	if ($sitemapInfo)
	{
		// We had a sitemap
		print_table_start();
		print_table_header($vbphrase['dbtech_dbseo_gwt_sitemap_report'], 4);
		print_cells_row(array(
			$vbphrase['dbtech_dbseo_gwt_sitemap_submitted'],
			vb_number_format($sitemapInfo['submitted']),

			$vbphrase['dbtech_dbseo_gwt_sitemap_indexed'],
			vb_number_format($sitemapInfo['indexed'])
		), 0, 0, -5, 'top', 1, 1);
		print_table_footer();
	}


	print_form_header('index', 'webmaster');
	print_table_header($vbphrase['dbtech_dbseo_gwt_reports'], 4);
	print_time_row($vbphrase['start_date'], 						'startdate', 			$vbulletin->GPC['startdate'], 			false);
	print_time_row($vbphrase['end_date'], 							'enddate', 				$vbulletin->GPC['enddate'], 			false);
	/*DBTECH_PRO_START*/
	print_yes_no_row($vbphrase['dbtech_dbseo_include_comparison'], 	'includecomparison', 	$vbulletin->GPC['includecomparison']);
	print_time_row($vbphrase['dbtech_dbseo_comparison_start_date'], 'comparisonstartdate', 	$vbulletin->GPC['comparisonstartdate'], false);
	print_time_row($vbphrase['dbtech_dbseo_comparison_end_date'], 	'comparisonenddate', 	$vbulletin->GPC['comparisonenddate'], 	false);
	/*DBTECH_PRO_END*/
	print_submit_row($vbphrase['update']);

	/*DBTECH_PRO_START*/
	do
	{
		$historicalData = array('current' => array(), 'comparison' => array());

		// Query the data
		$request = $db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapdata WHERE dateline BETWEEN " . intval($startDate) . " AND " . intval($endDate));

		while ($row = $db->fetch_array($request))
		{
			// Make sure this is an array
			$row['sitemapdata'] = @unserialize($row['sitemapdata']);
			$row['sitemapdata'] = is_array($row['sitemapdata']) ? $row['sitemapdata'] : array();

			// Store this
			$historicalData['current'][$row['dateline']] = $row['sitemapdata'];
		}

		if ($vbulletin->GPC['includecomparison'])
		{
			// Query the data
			$request = $db->query_read_slave("SELECT * FROM " . TABLE_PREFIX . "dbtech_dbseo_sitemapdata WHERE dateline BETWEEN " . intval($comparisonStartDate) . " AND " . intval($comparisonEndDate));

			while ($row = $db->fetch_array($request))
			{
				// Make sure this is an array
				$row['sitemapdata'] = @unserialize($row['sitemapdata']);
				$row['sitemapdata'] = is_array($row['sitemapdata']) ? $row['sitemapdata'] : array();

				// Store this
				$historicalData['comparison'][$row['dateline']] = $row['sitemapdata'];
			}
		}

		foreach (array(
			'authPermissions' 	=> 'crawlerrors_authPermissions',
			'notFollowed' 		=> 'crawlerrors_notFollowed',
			'notFound' 			=> 'crawlerrors_notFound',
			'other' 			=> 'crawlerrors_other',
			'serverError' 		=> 'crawlerrors_serverError',
			'soft404' 			=> 'crawlerrors_soft404',
			'submitted' 		=> 'sitemap_submitted',
			'indexed' 			=> 'sitemap_indexed',
		) as $label => $phraseKey)
		{
			// Set hash key
			$hashKey = $label . '-' . $startDate . '-' . $endDate . ($comparisonStartDate ? ('-' . $comparisonStartDate . '-' . $comparisonEndDate) : '');

			if (!isset($vbulletin->dbtech_dbseo_gwt_cache[$hashKey]))
			{
				// Basic cache array
				$vbulletin->dbtech_dbseo_gwt_cache[$hashKey] = array(
					'data' => array(
						'datasets' => array(),
						'labels' => array(),
					),
					'time' => 0
				);
			}

			if ($vbulletin->dbtech_dbseo_gwt_cache[$hashKey]['time'] <= (TIMENOW - 3600))
			{
				$labels = $datasets = array();
				foreach ($historicalData['current'] as $dateline => $row)
				{
					// Sort out the label
					$labels[] = date('M j', $dateline);

					if (!isset($datasets[0]))
					{
						// Store this
						$datasets[0] = array(
							'labels' => array(),
							'data' => array(),
						);
					}

					// Set the dataset var
					$datasets[0]['label'] = str_replace('"', '\"', $vbphrase['dbtech_dbseo_gwt_' . $phraseKey]);
					$datasets[0]['data'][] = $row[$label];
					$datasets[0]['labels'][] = date('M j Y', $dateline);
				}

				if ($vbulletin->GPC['includecomparison'])
				{
					foreach ($historicalData['comparison'] as $dateline => $row)
					{
						if (!isset($datasets[1]))
						{
							// Store this
							$datasets[1] = array(
								'labels' => array(),
								'data' => array(),
							);
						}

						// Set the dataset var
						$datasets[1]['label'] = str_replace('"', '\"', $vbphrase['dbtech_dbseo_gwt_' . $phraseKey]);
						$datasets[1]['data'][] = $row[$label];
						$datasets[1]['labels'][] = date('M j Y', $dateline);
					}
				}

				// Store cache object
				$vbulletin->dbtech_dbseo_gwt_cache[$hashKey] = array(
					'data' => array(
						'datasets' => $datasets,
						'labels' => $labels,
					),
					'time' => TIMENOW
				);

				// Schedule cache update
				$updateCache = true;
			}
			else
			{
				// Retrieve from cache
				$labels = $vbulletin->dbtech_dbseo_gwt_cache[$hashKey]['data']['labels'];
				$datasets = $vbulletin->dbtech_dbseo_gwt_cache[$hashKey]['data']['datasets'];
			}

			if (!count($labels))
			{
				// Skip this
				continue;
			}

			print_table_start();
			print_table_header($vbphrase['dbtech_dbseo_gwt_' . $phraseKey]);
			print_line_chart($labels, $datasets);
			print_table_footer();
		}
	}
	while (false);
	/*DBTECH_PRO_END*/

	if ($updateCache)
	{
		// Now update cache
		build_datastore('dbtech_dbseo_gwt_cache', trim(serialize($vbulletin->dbtech_dbseo_gwt_cache)), 1);
	}
}

print_cp_footer();