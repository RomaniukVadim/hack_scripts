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
function print_line_chart($labels, $datasets, $isBytes = false)
{
	$uniqueid = fetch_uniqueid_counter();

	if (!isset($GLOBALS['_dbseo_included_chartjs']))
	{
		?>
		<script type="text/javascript" src="../dbtech/dbseo/clientscript/3rdparty/chart/Chart.min.js"></script>
		<script type="text/javascript" src="../dbtech/dbseo/clientscript/3rdparty/chart/Chart.Helper.js"></script>
		<?php
		$GLOBALS['_dbseo_included_chartjs'] = true;
	}

	$colours = array(
		'151,187,205',
		'220,220,220',
		'205,170,152',
		'152,161,205',
		'205,196,152',
		'187,205,152',
		'170,152,205',
		'158,104,76',
		'76,131,158',
		'205,152,161',
		'152,205,170',
		'196,152,205',
		'152,205,196',
		'185,135,110',
		'110,160,185'
	);

	$dataSetsJS = array();
	foreach ($datasets as $key => $data)
	{
		if (!isset($colours[$key]))
		{
			// Skip this
			continue;
		}

		// Store the data set JS code
		$dataSetsJS[] = '
			{
				label: "' . $data['label'] . '",
				fill: false,
				backgroundColor: "rgba(' . $colours[$key] . ',0.2)",
				borderColor: "rgba(' . $colours[$key] . ',1)",
				data : ["' . implode('", "', $data['data']) . '"]
			}
		';
	}

	print_description_row('
		<div>
			<div>
				<canvas id="canvas_line_' . $uniqueid . '" height="200" width="600"></canvas>
			</div>
		</div>

		<script>
			var lineChartData = {
				labels : ["' . implode('", "', $labels) . '"],
				datasets : [
					' . implode(', ', $dataSetsJS) . '
				]
			}

			var ctx = document.getElementById("canvas_line_' . $uniqueid . '").getContext("2d");
			new Chart(ctx, {
				type: \'line\',
				data: lineChartData,
				options: {

					tooltips: {
						callbacks: {
							label: function(tooltipItem, data) {
								var datasetLabel = data.datasets[tooltipItem.datasetIndex].label || \'\';
								return datasetLabel + \': \' + number_format(parseInt(tooltipItem.yLabel), 0, \'' . $GLOBALS['vbulletin']->userinfo['lang_decimalsep'] . '\', \'' . $GLOBALS['vbulletin']->userinfo['lang_thousandsep'] . '\');
							},
						}
					},
					elements: {
						point: {
							hitRadius: 10
						}
					},
					scales: {
						yAxes:[{
							ticks: {
								userCallback: function(value, index, values) { return number_format(parseInt(value), 0, \'' . $GLOBALS['vbulletin']->userinfo['lang_decimalsep'] . '\', \'' . $GLOBALS['vbulletin']->userinfo['lang_thousandsep'] . '\'); }
							}
						}]
					}
				}
			});
		</script>
	', false, 2, '" style="background-color:white;');
}

// #############################################################################
function print_pie_chart($data)
{
	$uniqueid = fetch_uniqueid_counter();

	$pieData = array();
	$colours = array(
		array(
			'colour' 	=> '#46BFBD',
			'highlight' => '#5AD3D1'
		),
		array(
			'colour' 	=> '#F7464A',
			'highlight' => '#FF5A5E',
		),
	);

	$i = 0;
	$backgroundColors = $hoverBackgroundColors = array();
	foreach ($data as $label => $value)
	{
		$backgroundColors[] = $colours[$i]['colour'];
		$hoverBackgroundColors[] = $colours[$i]['highlight'];

		$i++;
	}

	if (!isset($GLOBALS['_dbseo_included_chartjs']))
	{
		?>
		<script type="text/javascript" src="../dbtech/dbseo/clientscript/3rdparty/chart/Chart.min.js"></script>
		<script type="text/javascript" src="../dbtech/dbseo/clientscript/3rdparty/chart/Chart.Helper.js"></script>
		<?php
		$GLOBALS['_dbseo_included_chartjs'] = true;
	}

	print_description_row('
		<div>
			<div>
				<canvas id="canvas_pie_' . $uniqueid . '" height="200" width="600"></canvas>
			</div>
		</div>

		<script>
			var pieData = {
				labels : ["' . implode('", "', array_keys($data)) . '"],
				datasets : [
					{
						data : ["' . implode('", "', $data) . '"],
						backgroundColor: ["' . implode('", "', $backgroundColors) . '"],
						hoverBackgroundColor: ["' . implode('", "', $hoverBackgroundColors) . '"],
					}
				]
			}

			var ctx = document.getElementById("canvas_pie_' . $uniqueid . '").getContext("2d");
			new Chart(ctx, {
				type: \'pie\',
				data: pieData
			});
		</script>
	', false, 2, '" style="background-color:white;');
}