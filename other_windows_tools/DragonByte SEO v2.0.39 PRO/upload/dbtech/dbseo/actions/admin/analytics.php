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

print_cp_header($vbphrase['dbtech_dbseo_ga_reports']);

// #############################################################################
if ($_REQUEST['action'] == 'oauth')
{
	try
	{
		if (!isset($_REQUEST['code']))
		{
			?><script type="text/javascript">window.onload = function() { window.open('<?php echo $client->createAuthUrl(); ?>', "_blank"); } </script><?php

			print_form_header('index', 'analytics');
			construct_hidden_code('action', 'oauth');
			print_table_header($vbphrase['dbtech_dbseo_google_authentication']);
			print_input_row($vbphrase['dbtech_dbseo_google_authentication_code'], 'code');
			print_submit_row($vbphrase['dbtech_dbseo_google_authenticate'], null);
			print_cp_footer();
		}

		// Get access token
		$client->authenticate($_REQUEST['code']);

		// Store the oauth token
		$vbulletin->dbtech_dbseo_oauth = $client->getAccessToken();
		build_datastore('dbtech_dbseo_oauth', trim($vbulletin->dbtech_dbseo_oauth), 0);
	}
	catch (Exception $exception)
	{
		print_cp_message($exception->getMessage() . ' on line ' . $exception->getLine() . ' in ' . $exception->getFile() . ' (code: ' . $exception->getCode() . ')');
	}

	// Blank this out so we go back to the main loop
	print_cp_redirect('index.php?do=analytics');
}

try
{
	$client->setAccessToken($vbulletin->dbtech_dbseo_oauth);
}
catch (Exception $e)
{
	// Access token couldn't be set
	print_form_header('index', 'analytics');
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
		$newAccessToken = $client->refreshToken($client->getRefreshToken());
	}
	catch (Exception $e)
	{
		// Access token couldn't be set
		print_form_header('index', 'analytics');
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
$ga = new Google_Service_Analytics($client);

// #############################################################################
if ($_POST['action'] == 'saveaccount')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'accountid' => TYPE_STR,
	));

	if ($vbulletin->GPC['accountid'] != -1)
	{
		if (!$vbulletin->GPC['accountid'])
		{
			// We need this
			print_stop_message('dbtech_dbseo_google_analytics_account_required');
		}
		else
		{
			// Set this
			require_once(DIR . '/includes/adminfunctions_options.php');
			save_settings(array('dbtech_dbseo_analytics_account' => $vbulletin->GPC['accountid']));
		}
	}

	// Blank this out so we go back to the main loop
	print_cp_redirect('index.php?do=analytics');
}

// #############################################################################
if ($_POST['action'] == 'saveprofile')
{
	$vbulletin->input->clean_array_gpc('p', array(
		'profileid' => TYPE_INT,
	));

	if ($vbulletin->GPC['profileid'] > -1)
	{
		if (!$vbulletin->GPC['profileid'])
		{
			// Make sure this is a readable array
			$tmp = explode('-', $vbulletin->options['dbtech_dbseo_analytics_account']);

			// Set account ID
			$accountId = $tmp[1];

			// Useless now
			unset($tmp);

			// Set the full property ID
			$webPropertyId = $vbulletin->options['dbtech_dbseo_analytics_account'];

			// Create profile object
			$profile = new Google_Service_Analytics_Profile();
				$profile->setName($vbulletin->options['bbtitle']);
				$profile->setWebsiteUrl($vbulletin->options['bburl']);
			$profileResult = $ga->management_profiles->insert($accountId, $webPropertyId, $profile);

			// Set this
			require_once(DIR . '/includes/adminfunctions_options.php');
			save_settings(array('dbtech_dbseo_analytics_profile' => $profileResult->id));
		}
		else
		{
			// Set this
			require_once(DIR . '/includes/adminfunctions_options.php');
			save_settings(array('dbtech_dbseo_analytics_profile' => $vbulletin->GPC['profileid']));
		}
	}

	// Blank this out so we go back to the main loop
	print_cp_redirect('index.php?do=analytics');
}

// #############################################################################
if ($_REQUEST['action'] != 'oauth')
{
	if (!$vbulletin->options['dbtech_dbseo_analytics_account'])
	{
		$accounts = array(-1 => $vbphrase['dbtech_dbseo_choose_account_property']);

		// Grab all profiles
		$managementAccounts = $ga->management_accounts->listManagementAccounts();

		foreach ($managementAccounts->items as $item)
		{
			// Grab all profiles
			$managementWebProperties = $ga->management_webproperties->listManagementWebproperties($item->id);

			if (!$managementWebProperties->items)
			{
				// Skip this
				continue;
			}

			$accounts[$item->name] = array();
			foreach ($managementWebProperties->items as $item2)
			{
				if ($item2->kind != 'analytics#webproperty')
				{
					// Skip this
					continue;
				}

				// Store this property
				$accounts[$item->name][$item2->id] = $item2->name;
			}
		}

		print_form_header('index', 'analytics');
		construct_hidden_code('action', 'saveaccount');
		print_table_header($vbphrase['dbtech_dbseo_ga_account']);
		print_select_row($vbphrase['dbtech_dbseo_ga_choose_account'], 'accountid', $accounts, -1);
		print_submit_row($vbphrase['save'], null);
		print_cp_footer();
	}

	// Make sure this is a readable array
	$tmp = explode('-', $vbulletin->options['dbtech_dbseo_analytics_account']);

	// Set account ID
	$accountId = $tmp[1];

	// Useless now
	unset($tmp);

	// Set the full property ID
	$webPropertyId = $vbulletin->options['dbtech_dbseo_analytics_account'];

	if (!$vbulletin->options['dbtech_dbseo_analytics_profile'])
	{
		// Grab all profiles
		$managementProfiles = $ga->management_profiles->listManagementProfiles($accountId, $webPropertyId);

		$profiles = array(-1 => $vbphrase['dbtech_dbseo_choose_management_profile']);
		foreach ($managementProfiles->items as $item)
		{
			if ($item->kind != 'analytics#profile')
			{
				// Skip this
				continue;
			}

			$profiles[$item->id] = $item->name;
			if (strpos($vbulletin->options['bburl'], $item->websiteUrl) !== false OR $item->name == $vbulletin->options['bburl'])
			{
				// Set this
				require_once(DIR . '/includes/adminfunctions_options.php');
				save_settings(array('dbtech_dbseo_analytics_profile' => $item->id));

				break;
			}
		}

		if (!$vbulletin->options['dbtech_dbseo_analytics_profile'])
		{
			// Add this at the bottom
			$profiles[0] = $vbphrase['dbtech_dbseo_none_add_new_profile'];

			print_form_header('index', 'analytics');
			construct_hidden_code('action', 'saveprofile');
			print_table_header($vbphrase['dbtech_dbseo_ga_profile']);
			print_select_row($vbphrase['dbtech_dbseo_ga_choose_profile'], 'profileid', $profiles, -1);
			print_submit_row($vbphrase['save'], null);
			print_cp_footer();
		}
	}

	$updateCache = $preventDefaultCache = false;
	if (!isset($vbulletin->dbtech_dbseo_ga_cache))
	{
		// Init this
		$vbulletin->dbtech_dbseo_ga_cache = array();
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

	if (!isset($vbulletin->dbtech_dbseo_ga_cache['statdates']))
	{
		// Set defaults
		$vbulletin->dbtech_dbseo_ga_cache['statdates'] = array();
	}

	foreach ($keys as $key)
	{
		if (!isset($_REQUEST[$key]))
		{
			// Set from cache
			$_REQUEST[$key] = $_POST[$key] = isset($vbulletin->dbtech_dbseo_ga_cache['statdates'][$key]) ? intval($vbulletin->dbtech_dbseo_ga_cache['statdates'][$key]) : $defaults[$key];
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
	}/*DBTECH_PRO_END*/

	foreach ($keys as $key)
	{
		if (!isset($vbulletin->dbtech_dbseo_ga_cache['statdates'][$key]) AND $vbulletin->GPC[$key] == $defaults[$key])
		{
			// Skip this
			continue;
		}

		// Write to cache
		$vbulletin->dbtech_dbseo_ga_cache['statdates'][$key] = intval($vbulletin->GPC[$key]);

		// Flag cache for update
		$updateCache = true;
	}

	// Set start & end date variables
	$startDate = date('Y-m-d', $vbulletin->GPC['startdate']);
	$endDate = date('Y-m-d', $vbulletin->GPC['enddate']);

	/*DBTECH_PRO_START*/
	if ($vbulletin->GPC['includecomparison'])
	{
		// Comparison start & end dates
		$comparisonStartDate = date('Y-m-d', $vbulletin->GPC['comparisonstartdate']);
		$comparisonEndDate = date('Y-m-d', $vbulletin->GPC['comparisonenddate']);
	}
	/*DBTECH_PRO_END*/

	$systemInfo = array();
	foreach (array(
		'visitors_total_count' 		=> array(
			'metrics' 					=> 'ga:visits',
			'optParams' 				=> array(),
		),
		'visitors_direct_count' 	=> array(
			'metrics' 					=> 'ga:visits',
			'optParams' 				=> array(
				'segment' 					=> 'sessions::condition::ga:source==(direct)'
			),
		),
		'visitors_referral_count' 	=> array(
			'metrics' 					=> 'ga:visits',
			'optParams' 				=> array(
				'segment' 					=> 'sessions::condition::ga:source!=(direct)'
			),
		),
	) as $label => $queryParams)
	{
		// Set hash key
		$hashKey = $label . '-' . $startDate . '-' . $endDate;

		if (!isset($vbulletin->dbtech_dbseo_ga_cache[$hashKey]))
		{
			// Basic cache array
			$vbulletin->dbtech_dbseo_ga_cache[$hashKey] = array(
				'data' => 0,
				'time' => 0
			);
		}

		if ($vbulletin->dbtech_dbseo_ga_cache[$hashKey]['time'] <= (TIMENOW - 3600))
		{
			// Query the data
			$request = $ga->data_ga->get('ga:' . $vbulletin->options['dbtech_dbseo_analytics_profile'], $startDate, $endDate, $queryParams['metrics'], $queryParams['optParams']);

			// Store cache object
			$vbulletin->dbtech_dbseo_ga_cache[$hashKey] = array(
				'data' => $request->rows[0][0],
				'time' => TIMENOW
			);

			// Schedule cache update
			$updateCache = true;

			// Set system info
			$systemInfo[$label] = $request->rows[0][0];
		}
		else
		{
			// Get from cache
			$systemInfo[$label] = $vbulletin->dbtech_dbseo_ga_cache[$hashKey]['data'];
		}

		/*DBTECH_PRO_START*/
		if ($vbulletin->GPC['includecomparison'])
		{
			// Include comparison
			$label .= '_comparison';

			// Set hash key
			$hashKey = $label . '-' . $comparisonStartDate . '-' . $comparisonEndDate;

			if (!isset($vbulletin->dbtech_dbseo_ga_cache[$hashKey]))
			{
				// Basic cache array
				$vbulletin->dbtech_dbseo_ga_cache[$hashKey] = array(
					'data' => 0,
					'time' => 0
				);
			}

			if ($vbulletin->dbtech_dbseo_ga_cache[$hashKey]['time'] <= (TIMENOW - 3600))
			{
				// Query the data
				$request = $ga->data_ga->get('ga:' . $vbulletin->options['dbtech_dbseo_analytics_profile'], $comparisonStartDate, $comparisonEndDate, $queryParams['metrics'], $queryParams['optParams']);

				// Store cache object
				$vbulletin->dbtech_dbseo_ga_cache[$hashKey] = array(
					'data' => $request->rows[0][0],
					'time' => TIMENOW
				);

				// Schedule cache update
				$updateCache = true;

				// Set system info
				$systemInfo[$label] = $request->rows[0][0];
			}
			else
			{
				// Get from cache
				$systemInfo[$label] = $vbulletin->dbtech_dbseo_ga_cache[$hashKey]['data'];
			}
		}
		/*DBTECH_PRO_END*/
	}

	print_form_header('index', 'analytics');
	print_table_header($vbphrase['dbtech_dbseo_ga_reports'], 4);
	print_time_row($vbphrase['start_date'], 						'startdate', 			$vbulletin->GPC['startdate'], 			false);
	print_time_row($vbphrase['end_date'], 							'enddate', 				$vbulletin->GPC['enddate'], 			false);
	/*DBTECH_PRO_START*/
	print_yes_no_row($vbphrase['dbtech_dbseo_include_comparison'], 	'includecomparison', 	$vbulletin->GPC['includecomparison']);
	print_time_row($vbphrase['dbtech_dbseo_comparison_start_date'], 'comparisonstartdate', 	$vbulletin->GPC['comparisonstartdate'], false);
	print_time_row($vbphrase['dbtech_dbseo_comparison_end_date'], 	'comparisonenddate', 	$vbulletin->GPC['comparisonenddate'], 	false);
	/*DBTECH_PRO_END*/
	print_submit_row($vbphrase['update']);

	print_table_start();
	print_table_header($vbphrase['dbtech_dbseo_system_info'], 4);
	print_cells_row(array(
		$vbphrase['dbtech_dbseo_ga_account'],
		$vbulletin->options['dbtech_dbseo_analytics_account'],

		$vbphrase['dbtech_dbseo_visitors_total'],
		vb_number_format($systemInfo['visitors_total_count'])
	), 0, 0, -5, 'top', 1, 1);
	print_cells_row(array(
		$vbphrase['dbtech_dbseo_visitors_direct'],
		vb_number_format($systemInfo['visitors_direct_count']),

		$vbphrase['dbtech_dbseo_visitors_referral'],
		vb_number_format($systemInfo['visitors_referral_count'])
	), 0, 0, -5, 'top', 1, 1);
	print_table_footer();

	/*DBTECH_PRO_START*/
	foreach (array(
		'visitors_total' 	=> array(
			'metrics' 			=> 'ga:visits',
			'optParams' 		=> array(
				'dimensions' 		=> 'ga:date',
			),
		),
		'visitors_direct' 	=> array(
			'metrics' 			=> 'ga:visits',
			'optParams' 		=> array(
				'dimensions' 		=> 'ga:date',
				'segment' 			=> 'sessions::condition::ga:source==(direct)',
			),
		),
		'visitors_referral' => array(
			'metrics'    		=> 'ga:visits',
			'optParams' 		=> array(
				'dimensions' 		=> 'ga:date',
				'segment' 	 		=> 'sessions::condition::ga:source!=(direct)',
			),
		),
	) as $label => $queryParams)
	{
		// Set hash key
		$hashKey = $label . '-' . $startDate . '-' . $endDate . ($comparisonStartDate ? ('-' . $comparisonStartDate . '-' . $comparisonEndDate) : '');

		if (!isset($vbulletin->dbtech_dbseo_ga_cache[$hashKey]))
		{
			// Basic cache array
			$vbulletin->dbtech_dbseo_ga_cache[$hashKey] = array(
				'data' => array(
					'datasets' => array(),
					'labels' => array(),
				),
				'time' => 0
			);
		}

		if ($vbulletin->dbtech_dbseo_ga_cache[$hashKey]['time'] <= (TIMENOW - 3600))
		{
			// Query the data
			$request = $ga->data_ga->get('ga:' . $vbulletin->options['dbtech_dbseo_analytics_profile'], $startDate, $endDate, $queryParams['metrics'], $queryParams['optParams']);

			$labels = $datasets = array();
			foreach ($request->rows as $row)
			{
				// Sort out the label
				$dateTime = DateTime::createFromFormat('Ymd', $row[0]);
				$labels[] = date('M j', $dateTime->getTimestamp());

				if (!isset($datasets[0]))
				{
					// Store this
					$datasets[0] = array(
						'labels' => array(),
						'data' => array(),
					);
				}

				// Set the dataset var
				$datasets[0]['label'] = $vbphrase['dbtech_dbseo_' . $label];
				$datasets[0]['data'][] = $row[1];
				$datasets[0]['labels'][] = date('M j Y', $dateTime->getTimestamp());
			}

			if ($vbulletin->GPC['includecomparison'])
			{
				// Query the data
				$request = $ga->data_ga->get('ga:' . $vbulletin->options['dbtech_dbseo_analytics_profile'], $comparisonStartDate, $comparisonEndDate, $queryParams['metrics'], $queryParams['optParams']);

				foreach ($request->rows as $row)
				{
					// Sort out the label
					$dateTime = DateTime::createFromFormat('Ymd', $row[0]);

					if (!isset($datasets[1]))
					{
						// Store this
						$datasets[1] = array(
							'labels' => array(),
							'data' => array(),
						);
					}

					// Set the dataset var
					$datasets[1]['label'] = $vbphrase['dbtech_dbseo_' . $label];
					$datasets[1]['data'][] = $row[1];
					$datasets[1]['labels'][] = date('M j Y', $dateTime->getTimestamp());
				}
			}

			// Store cache object
			$vbulletin->dbtech_dbseo_ga_cache[$hashKey] = array(
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
			$labels = $vbulletin->dbtech_dbseo_ga_cache[$hashKey]['data']['labels'];
			$datasets = $vbulletin->dbtech_dbseo_ga_cache[$hashKey]['data']['datasets'];
		}

		print_table_start();
		print_table_header($vbphrase['dbtech_dbseo_' . $label]);
		print_line_chart($labels, $datasets);
		print_table_footer();
	}

	print_table_start();
	print_table_header($vbphrase['dbtech_dbseo_visitor_comparison']);
	print_pie_chart(array(
		$vbphrase['dbtech_dbseo_direct_visitors'] 	=> $systemInfo['visitors_direct_count'],
		$vbphrase['dbtech_dbseo_referred_visitors'] => $systemInfo['visitors_referral_count']
	));
	print_table_footer();

	if ($vbulletin->GPC['includecomparison'])
	{
		print_table_start();
		print_table_header($vbphrase['dbtech_dbseo_visitor_comparison_previous_period']);
		print_pie_chart(array(
			$vbphrase['dbtech_dbseo_direct_visitors'] 	=> $systemInfo['visitors_direct_count_comparison'],
			$vbphrase['dbtech_dbseo_referred_visitors'] => $systemInfo['visitors_referral_count_comparison']
		));
		print_table_footer();
	}
	/*DBTECH_PRO_END*/

	if ($updateCache)
	{
		// Now cache
		build_datastore('dbtech_dbseo_ga_cache', trim(serialize($vbulletin->dbtech_dbseo_ga_cache)), 1);
	}
}

print_cp_footer();