
<?php

function ThemeBegin($subtitle, $js_script, $popup_menu, $body_events, $filter = NULL, $showHeader = true, $cssUp = '', $toTop = false, $small = false, $pureOffset = false)
{
	$javascript = '';
	$body_js_events = ($body_events === 0 ? '' : $body_events);

	if (!empty($js_script)) {
		$javascript .= str_replace('{SCRIPT}', $js_script, THEME_JAVASCRIPT_BODY);
	}

	if (!empty($popup_menu)) {
		$javascript .= str_replace('{SCRIPT}', $popup_menu, THEME_JAVASCRIPT_BODY) . str_replace('{PATH}', THEME_PATH . '/popupmenu.js', THEME_JAVASCRIPT_EXTERNAL);
		$body_js_events .= ' onclick="jsmHideLastMenu()"';
	}

	$mainmenu = 1;
	header('Content-Type: ' . THEME_CONTENT_TYPE . '; charset=utf-8');
	httpNoCacheHeaders();
	$newBaseMenu = ($subtitle != 'login' ? file_get_contents(THEME_PATH . '/menu.html') : '');
	$newBaseMenu = str_replace(array('{NEWVERSION}', '{LASTLOGIN}'), array(PANDA_NEWVERSION, @$_SESSION['lastlogin']), $newBaseMenu);
	$module = @$_REQUEST['m'];
	$subModule = @$_REQUEST['botsaction'];

	if (in_array($module, array('sys_info', 'sys_options', 'update'))) {
		$replace = '{active_sys}';
	}
	else if ($subModule == 'port_socks') {
		$replace = (@$_REQUEST['type'] == 'vnc' ? '{active_port_vnc}' : '{active_port_socks}');
	}
	else if (in_array($module, array('stats_main', 'home'))) {
		$replace = '{active_stats_main}';
	}
	else {
		$replace = '{active_' . $module . '}';
	}

	$newBaseMenu = str_replace($replace, 'sidebar-new-active', $newBaseMenu);
	$newBaseMenu = str_replace('{header_sub_style}', $toTop ? 'header-no-middle' : '', $newBaseMenu);
	$newBaseMenu = str_replace('{menu_sub_style}', $small ? 'menu-hide' : '', $newBaseMenu);
	$stat = statSmall();
	$newBaseMenu = str_replace(array('{SMSTAT_TOTAL}', '{SMSTAT_TOTAL_PR}', '{SMSTAT_ONLINE}', '{SMSTAT_ONLINE_PR}', '{SMSTAT_NEW}', '{SMSTAT_NEW_PR}'), array($stat['total'], 100, $stat['online'], @round(($stat['online'] / $stat['total']) * 100), $stat['new'], @round(($stat['new'] / $stat['total']) * 100)), $newBaseMenu);
	$containerSubStyle = '';

	if ($toTop) {
		$containerSubStyle .= ' container-totop';
	}
	if ($small) {
		$containerSubStyle .= ' container-new-notmove';
	}

	echo @str_replace(array('{TITLE}', '{SUBTITLE}', '{THEME_HTTP_PATH}', '{JAVASCRIPT}', '{BODY_JS_EVENTS}', '{MAINMENU}', '{INFO_TITLE}', '{INFO_USERNAME_TITLE}', '{INFO_USERNAME}', '{INFO_DATE_TITLE}', '{INFO_DATE}', '{INFO_TIME_TITLE}', '{INFO_TIME}', '{NEW_BASE_MENU}', '{CSS_UP_PATH}', '{container_sub_style}'), array(LNG_TITLE, $subtitle, THEME_PATH, $javascript, $body_js_events, $mainmenu, LNG_INFO, LNG_INFO_USERNAME, htmlEntitiesEx($GLOBALS['userData']['name']), LNG_INFO_DATE, htmlEntitiesEx(gmdate(LNG_FORMAT_DATE, CURRENT_TIME)), LNG_INFO_TIME, htmlEntitiesEx(gmdate(LNG_FORMAT_TIME, CURRENT_TIME)), $newBaseMenu, $cssUp, $containerSubStyle), file_get_contents(THEME_PATH . '/header.html'));

	if (!$small) {
		echo '<script type="text/javascript" src="theme/stat.js"></script>';
	}

	if (!$filter) {
		echo '<div class="col-xs-12 main">' . "\r\n";

		if ($showHeader) {
			echo '<h1 class="page-header">' . $subtitle . '</h1>' . "\r\n\r\n";
		}
	}
	else {
		echo '<div class="sidebar">' . "\n" . '            ' . $filter . "\n" . '          </div>' . "\n" . '          <div class="main main-shift ' . ($pureOffset ? 'main-pure' : '') . '">';

		if ($showHeader) {
			echo '<h1 class="page-header">' . $subtitle . '</h1>' . "\r\n\r\n";
		}

	}

}

function statSmall()
{
	global $config;
	$result = array('total' => 0, 'new' => 0, 'online' => 0);
	$sql = 'select ' . "\n" . '    count(0) as total, ' . "\n" . '    count(if(rtime_last>=' . (time() - $config['botnet_timeout']) . ', 1, null)) as online,' . "\n" . '    count(if(flag_new=1, 1, null)) as new' . "\n" . '    from botnet_list';

	if ($dataset = mysqlQueryEx('botnet_list', $sql)) {
		if ($row = mysql_fetch_array($dataset)) {
			$result = $row;
		}
	}

	return $result;
}

function statLow()
{
	$result = 0;
	$sql = 'select count(0) from low_stat';

	if ($dataset = mysqlQueryEx('low_stat', $sql)) {
		if ($row = mysql_fetch_array($dataset)) {
			$result = $row[0];
		}
	}

	return $result;
}

function statTodayReports()
{
	$result = 0;
	$table = 'botnet_reports_' . gmdate('ymd', time());
	$sql = 'select count(0) from ' . $table;

	if ($dataset = mysqlQueryEx($table, $sql)) {
		if ($row = mysql_fetch_array($dataset)) {
			$result = $row[0];
		}
	}

	return $result;
}

function ThemeEnd()
{
	echo file_get_contents(THEME_PATH . '/footer.html');
}

function themeSmall($subtitle, $data, $js_script, $popup_menu, $body_events, $showHeader = true, $cssUp = '')
{
	themebegin($subtitle, $js_script, $popup_menu, $body_events, NULL, $showHeader, $cssUp, false, true);
	echo $data;
	themeend();
	return NULL;
	$javascript = '';
	$body_js_events = ($body_events === 0 ? '' : $body_events);

	if ($js_script != '') {
		$javascript .= str_replace('{SCRIPT}', $js_script, THEME_JAVASCRIPT_BODY);
	}

	if ($popup_menu != '') {
		$javascript .= str_replace('{SCRIPT}', $popup_menu, THEME_JAVASCRIPT_BODY) . str_replace('{PATH}', THEME_PATH . '/popupmenu.js', THEME_JAVASCRIPT_EXTERNAL);
		$body_js_events .= ' onclick="jsmHideLastMenu()"';
	}

	header('Content-Type: ' . THEME_CONTENT_TYPE . '; charset=utf-8');
	httpNoCacheHeaders();
	echo str_replace(array('{SUBTITLE}', '{THEME_HTTP_PATH}', '{JAVASCRIPT}', '{BODY_JS_EVENTS}', '{BODY}'), array($subtitle, THEME_PATH, $javascript, $body_js_events, $data), file_get_contents(THEME_PATH . '/small.html'));
}

function ThemeMySQLError()
{
	themesmall('MySQL error', mysqlErrorEx(), 0, 0, 0);
	exit();
}

function ThemeFatalError($string)
{
	themesmall('Fatal error', $string, 0, 0, 0);
	exit();
}

function themePageList($list, $js_to_first, $js_to_prev, $js_to_last, $js_to_next)
{
	$data = '<table class="table_frame" style="width:100%; margin-bottom: 10px"><tr><td>' . LNG_PAGELIST_TITLE . '&#160;';

	if ($js_to_first !== 0) {
		$data .= '[<a href="#" onclick="' . $js_to_first . '">' . LNG_PAGELIST_FIRST . '</a>]';
	}

	if ($js_to_prev !== 0) {
		if ($js_to_first !== 0) {
			$data .= '&#160;';
		}

		$data .= '[<a href="#" onclick="' . $js_to_prev . '">' . LNG_PAGELIST_PREV . '</a>]';
	}

	foreach ($list as $v) {
		if ($v[0] === 0) {
			$data .= '&#160;..&#160;';
		}
		else if ($v[1] === 0) {
			$data .= '&#160;<b>[' . $v[0] . ']</b>&#160;';
		}
		else {
			$data .= '&#160;<a href="#" onclick="' . $v[1] . '">' . $v[0] . '</a>&#160;';
		}
	}

	if ($js_to_next !== 0) {
		$data .= '[<a href="#" onclick="' . $js_to_next . '">' . LNG_PAGELIST_NEXT . '</a>]';
	}

	if ($js_to_last !== 0) {
		if ($js_to_next !== 0) {
			$data .= '&#160;';
		}

		$data .= '[<a href="#" onclick="' . $js_to_last . '">' . LNG_PAGELIST_LAST . '</a>]';
	}

	$data .= '</td></tr></table>';
	return $data;
}

function ThemeHeader()
{
	$data = file_get_contents(__DIR__ . '/header.html');
	$data = substr($data, 0, strpos($data, '<body')) . '<body style="padding: 0px">';
	$data = str_replace('{CSS_UP_PATH}', '', $data);
	echo $data;
}

if (!defined('__CP__') && defined('__CP__')) {
	exit();
}

define('THEME_CONTENT_TYPE', 'text/html');
define('THEME_JAVASCRIPT_BODY', '<script type="text/javascript">' . "\r\n" . '{SCRIPT}' . "\r\n" . '</script>');
define('THEME_JAVASCRIPT_EXTERNAL', '<script type="text/javascript" src="{PATH}"></script>');
define('THEME_MAINMENU_SEPARATOR', '<div class="menu_separator"></div>');
define('THEME_MAINMENU_SUBHEADER', THEME_MAINMENU_SEPARATOR . '<div class="menu_header">{TITLE}</div>');
define('THEME_MAINMENU_ITEM', '<a href="{URL}">{TEXT}</a>');
define('THEME_MAINMENU_ITEM_CURRENT', '<a href="{URL}">&#8594;&#160;{TEXT}</a>');
define('THEME_STRING_FORM_ERROR_1_BEGIN', '<div class="panel panel-danger"><div class="panel-heading"><h3 class="panel-title">Errors</h3></div><div class="panel-body">');
define('THEME_STRING_FORM_ERROR_1_END', '</div></div>');
define('THEME_STRING_FORM_SUCCESS_1_BEGIN', '<div class="panel panel-success"><div class="panel-heading"><h3 class="panel-title">Messages</h3></div><div class="panel-body">');
define('THEME_STRING_FORM_SUCCESS_1_END', '</div></div>');
define('THEME_STRING_BOLD_BEGIN', '<b>');
define('THEME_STRING_BOLD_END', '</b>');
define('THEME_STRING_NEWLINE', '<br/>');
define('THEME_STRING_ID_BEGIN', '<div id="{ID}">');
define('THEME_STRING_ID_END', '</div>');
define('THEME_STRING_SPACE', '&#160;');
define('THEME_STRING_ERROR', '<strong class="error">{TEXT}</strong>');
define('THEME_STRING_SUCCESS', '<strong class="success">{TEXT}</strong>');
define('THEME_STRING_HELP_ANCHOR', '<a href="{URL}" onclick="this.target=\'_blank\'">[?]</a>');
define('THEME_VSPACE', '<div><br/><br/></div>');
define('THEME_STRING_REPORTPREVIEW', '<input type="checkbox" class="checkIds" value="{ID}">&#160;{TEXT}');
define('THEME_STRING_REPORTPREVIEW_FTP', '<a onclick="this.target=\'_blank\'" href="{URL}">{TEXT}</a>');
define('THEME_SCREENSHOT', '<img class="screenshot" src="{URL}" alt="screenshot" />');
define('THEME_IMG_WAIT', '<img src="' . THEME_PATH . '/throbber.gif" alt="throbber" />');
define('THEME_POPUPMENU_BOT', '<a class="bot_a" href="#" onclick="return jsmShowMenu({ID}, {MENU_NAME}, Array(), Array(\'{BOTID_FOR_URL}\'), \'{SUBVAL}\')">{BOTID}</a><div style="display:none;text-align:left" id="popupmenu{ID}"></div>');
define('THEME_FORMPOST_BEGIN', '<form autocomplete="off" class="form-group-sm" method="post" id="{NAME}" action="{URL}"{JS_EVENTS}>');
define('THEME_FORMPOST_TO_NEW_BEGIN', '<form class="form-group-sm" method="post" id="{NAME}" action="{URL}"{JS_EVENTS}><script type="text/javascript">' . "\r\n" . 'document.getElementById(\'{NAME}\').target=\'_blank\'' . "\r\n" . '</script>');
define('THEME_FORMPOST_MP_BEGIN', '<form class="form-group-sm" method="post" id="{NAME}" enctype="multipart/form-data" action="{URL}"{JS_EVENTS}>');
define('THEME_FORMPOST_END', '</form>');
define('THEME_FORMGET_BEGIN', '<form class="form-group-sm" method="get" id="{NAME}" action="{URL}"{JS_EVENTS}>');
define('THEME_FORMGET_TO_NEW_BEGIN', '<form class="form-group-sm" method="get" id="{NAME}" action="{URL}"{JS_EVENTS}><script type="text/javascript">' . "\r\n" . 'document.getElementById(\'{NAME}\').target=\'_blank\'' . "\r\n" . '</script>');
define('THEME_FORMGET_END', '</form>');
define('THEME_FORMGET_TO_NEW_BEGIN_POST', '<form class="form-group-sm" method="post" target="_blank" id="{NAME}" action="{URL}"{JS_EVENTS}>');
define('THEME_FORM_VALUE', '<div style="display:none"><input type="hidden" name="{NAME}" value="{VALUE}" /></div>');
define('THEME_DIALOG_BEGIN', '<table style="width: 100%">');
define('THEME_DIALOG_END', '</table>');
define('THEME_DIALOG_TITLE', '<tr><td colspan="{COLUMNS_COUNT}" class="td_header" align="left">{TEXT}</td></tr>');
define('THEME_DIALOG_TITLE_REP', '<tr><td colspan="{COLUMNS_COUNT}" class="repHeader" align="left">{TEXT}</td></tr>');
define('THEME_DIALOG_GROUP_BEGIN', '<td colspan="{COLUMNS_COUNT}" valign="top"><table class="table_frame" width="100%">');
define('THEME_DIALOG_GROUP_END', '</table></td>');
define('THEME_DIALOG_GROUP_TITLE', '<tr><td colspan="{COLUMNS_COUNT}" class="td_header" align="left">{TEXT}</td></tr>');
define('THEME_DIALOG_ROW_BEGIN', '<tr>');
define('THEME_DIALOG_ROW_END', '</tr>');
define('THEME_DIALOG_ITEM_LISTBOX_BEGIN', '<select id="{NAME}" name="{NAME}" class="form-control input-sm" style="width:{WIDTH}">');
define('THEME_DIALOG_ITEM_LISTBOX_BEGIN_RO', '<select id="{NAME}" name="{NAME}" class="form-control input-sm" style="width:{WIDTH}" disabled="disabled">');
define('THEME_DIALOG_ITEM_LISTBOX_END', '</select>');
define('THEME_DIALOG_ITEM_LISTBOX_END_RO', '</select>');
define('THEME_DIALOG_ITEM_LISTBOX_ITEM', '<option value="{VALUE}">{TEXT}</option>');
define('THEME_DIALOG_ITEM_LISTBOX_ITEM_CUR', '<option value="{VALUE}" selected="selected">{TEXT}</option>');
define('THEME_DIALOG_ITEM_TEXT', '<td align="left">{TEXT}</td>');
define('THEME_DIALOG_ITEM_TEXT_TOP', '<td align="left" valign="top">{TEXT}</td>');
define('THEME_DIALOG_ITEM_WRAPTEXT', '<td align="left" style="white-space:normal">{TEXT}</td>');
define('THEME_DIALOG_ITEM_MAXSPACE', '<td align="left" style="width:100%">&#160;</td>');
define('THEME_DIALOG_ITEM_CHILD_BEGIN', '<td colspan="{COLUMNS_COUNT}" valign="top" align="left">');
define('THEME_DIALOG_ITEM_CHILD_END', '</td>');
define('THEME_DIALOG_ITEM_ERROR', '<td align="left" class="error">{TEXT}</td>');
define('THEME_DIALOG_ITEM_SUCCESSED', '<td align="left" class="success">{TEXT}</td>');
define('THEME_DIALOG_ITEM_EMPTY', '<td>&#160;</td>');
define('THEME_DIALOG_ITEM_INPUT_PASSWORD', '<input type="password" name="{NAME}" value="{VALUE}" maxlength="{MAX}" class="form-control" />');
define('THEME_DIALOG_ITEM_INPUT_TEXT', '<input type="text" name="{NAME}" value="{VALUE}" maxlength="{MAX}" class="form-control" />');
define('THEME_DIALOG_ITEM_INPUT_TEXT_DISABLED', '<input type="text" name="{NAME}" value="{VALUE}" maxlength="{MAX}" class="form-control" disabled />');
define('THEME_DIALOG_ITEM_INPUT_TEXT_RO', '<input type="text" name="{NAME}" value="{VALUE}" maxlength="{MAX}" class="form-control" readonly="readonly" />');
define('THEME_DIALOG_ITEM_INPUT_TEXTAREA', '<textarea name="{NAME}" rows="{ROWS}" cols="{COLS}" class="form-control">{TEXT}</textarea>');
define('THEME_DIALOG_ITEM_INPUT_TEXTAREA_RO', '<textarea name="{NAME}" rows="{ROWS}" cols="{COLS}" class="form-control" readonly="readonly">{TEXT}</textarea>');
define('THEME_DIALOG_ITEM_INPUT_PASS', '<input type="password" name="{NAME}" value="{VALUE}" maxlength="{MAX}" class="form-control" />');
define('THEME_DIALOG_ITEM_INPUT_FILE', '<td><input type="file" name="{NAME}" style="width:{WIDTH}" /></td>');
define('THEME_DIALOG_ITEM_INPUT_RADIO_1', '<td align="left" colspan="{COLUMNS_COUNT}"><input type="radio" name="{NAME}" value="{VALUE}"{JS_EVENTS} /></td>');
define('THEME_DIALOG_ITEM_INPUT_RADIO_ON_1', '<td align="left" colspan="{COLUMNS_COUNT}"><input type="radio" name="{NAME}" value="{VALUE}" checked="checked"{JS_EVENTS} /></td>');
define('THEME_DIALOG_ITEM_INPUT_RADIO_2', '<td align="left" colspan="{COLUMNS_COUNT}"><input type="radio" name="{NAME}" value="{VALUE}"{JS_EVENTS} />&#160;{TEXT}</td>');
define('THEME_DIALOG_ITEM_INPUT_RADIO_ON_2', '<td align="left" colspan="{COLUMNS_COUNT}"><input type="radio" name="{NAME}" value="{VALUE}" checked="checked"{JS_EVENTS} />&#160;{TEXT}</td>');
define('THEME_DIALOG_ITEM_INPUT_RADIO_3', '<input type="radio" name="{NAME}" value="{VALUE}"{JS_EVENTS} />');
define('THEME_DIALOG_ITEM_INPUT_RADIO_ON_3', '<input type="radio" name="{NAME}" value="{VALUE}" checked="checked"{JS_EVENTS} />');
define('THEME_DIALOG_ITEM_INPUT_CHECKBOX_1', '<td align="left" colspan="{COLUMNS_COUNT}"><input type="checkbox" name="{NAME}" value="{VALUE}"{JS_EVENTS} /></td>');
define('THEME_DIALOG_ITEM_INPUT_CHECKBOX_ON_1', '<td align="left" colspan="{COLUMNS_COUNT}"><input type="checkbox" name="{NAME}" value="{VALUE}" checked="checked"{JS_EVENTS} /></td>');
define('THEME_DIALOG_ITEM_INPUT_CHECKBOX_2', '<td align="left" colspan="{COLUMNS_COUNT}"><input type="checkbox" name="{NAME}" value="{VALUE}"{JS_EVENTS} />&#160;{TEXT}</td>');
define('THEME_DIALOG_ITEM_INPUT_CHECKBOX_ON_2', '<td align="left" colspan="{COLUMNS_COUNT}"><input type="checkbox" name="{NAME}" value="{VALUE}" checked="checked"{JS_EVENTS} />&#160;{TEXT}</td>');
define('THEME_DIALOG_ITEM_INPUT_CHECKBOX_3', '<input type="checkbox" name="{NAME}" value="{VALUE}"{JS_EVENTS} />');
define('THEME_DIALOG_ITEM_INPUT_CHECKBOX_ON_3', '<input type="checkbox" name="{NAME}" value="{VALUE}" checked="checked"{JS_EVENTS} />');
define('THEME_DIALOG_ITEM_ACTION_SUBMIT', '<input type="submit" value="{TEXT}"{JS_EVENTS} class="btn btn-primary btn-sm" />');
define('THEME_DIALOG_ITEM_ACTION_SUBMIT_SUC', '<input type="submit" value="{TEXT}"{JS_EVENTS} class="btn btn-success btn-sm" />');
define('THEME_DIALOG_ITEM_ACTION_RESET', '<input type="button" value="{TEXT}"{JS_EVENTS} class="btn btn-danger btn-sm" />');
define('THEME_DIALOG_ITEM_ACTION_RESET_WARN', '<input type="reset" value="{TEXT}"{JS_EVENTS} class="btn btn-warning btn-sm" />');
define('THEME_DIALOG_ITEM_ACTION', '<input type="button" value="{TEXT}"{JS_EVENTS} class="btn btn-primary btn-sm" />');
define('THEME_DIALOG_ITEM_ACTION_DANG', '<input type="button" value="{TEXT}"{JS_EVENTS} class="btn btn-danger btn-sm" />');
define('THEME_DIALOG_ITEM_ACTION_SUC', '<input type="button" value="{TEXT}"{JS_EVENTS} class="btn btn-success btn-sm" />');
define('THEME_DIALOG_ITEM_ACTION_SEPARATOR', '&#160;|&#160;');
define('THEME_DIALOG_ACTIONLIST_BEGIN', '<tr><td colspan="{COLUMNS_COUNT}" align="right">');
define('THEME_DIALOG_ACTIONLIST_END', '</td></tr>');
define('THEME_DIALOG_ANCHOR', '<a href="{URL}">{TEXT}</a>');
define('THEME_DIALOG_ANCHOR_BLANK', '<a href="{URL}" onclick="this.target=\'_blank\'">{TEXT}</a>');
define('THEME_LIST_BEGIN', '<table width="100%" class="table table-striped table-bordered table-hover">');
define('THEME_LIST_END', '</table>');
define('THEME_LIST_TITLE', '<tr><td colspan="{COLUMNS_COUNT}" align="left" class="td_header">{TEXT}</td></tr>');
define('THEME_LIST_HEADER_L', '<td colspan="{COLUMNS_COUNT}" class="td_header" style="width:{WIDTH}">{TEXT}</td>');
define('THEME_LIST_HEADER_L_SORT', '<td colspan="{COLUMNS_COUNT}" class="td_header" style="width:{WIDTH}"><a href="{URL}"{JS_EVENTS}>{TEXT}</a></td>');
define('THEME_LIST_HEADER_L_SORT_CUR_ASC', '<td colspan="{COLUMNS_COUNT}" class="td_header" style="width:{WIDTH}">&#160;&#8593;&#160;<a href="{URL}"{JS_EVENTS}>{TEXT}</a></td>');
define('THEME_LIST_HEADER_L_SORT_CUR_DESC', '<td colspan="{COLUMNS_COUNT}" class="td_header" style="width:{WIDTH}">&#160;&#8595;&#160;<a href="{URL}"{JS_EVENTS}>{TEXT}</a></td>');
define('THEME_LIST_HEADER_R', '<td colspan="{COLUMNS_COUNT}" class="td_header" style="width:{WIDTH}">{TEXT}</td>');
define('THEME_LIST_HEADER_R_SORT', '<td colspan="{COLUMNS_COUNT}" class="td_header" style="width:{WIDTH}"><a href="{URL}"{JS_EVENTS}>{TEXT}</a></td>');
define('THEME_LIST_HEADER_R_SORT_CUR_ASC', '<td colspan="{COLUMNS_COUNT}" class="td_header" style="width:{WIDTH}">&#8593;&#160;<a href="{URL}"{JS_EVENTS}>{TEXT}</a></td>');
define('THEME_LIST_HEADER_R_SORT_CUR_DESC', '<td colspan="{COLUMNS_COUNT}" class="td_header" style="width:{WIDTH}">&#8595;&#160;<a href="{URL}"{JS_EVENTS}>{TEXT}</a></td>');
define('THEME_LIST_HEADER_CHECKBOX_1', '<td colspan="{COLUMNS_COUNT}" class="td_header" style="width:{WIDTH}"><input type="checkbox" name="{NAME}" value="{VALUE}"{JS_EVENTS} /></td>');
define('THEME_LIST_HEADER_CHECKBOX_ON_1', '<td colspan="{COLUMNS_COUNT}" class="td_header" style="width:{WIDTH}"><input type="checkbox" name="{NAME}" value="{VALUE}" checked="checked" {JS_EVENTS} /></td>');
define('THEME_LIST_ROW_BEGIN', '<tr>');
define('THEME_LIST_ROW_END', '</tr>');
define('THEME_LIST_ITEM_EMPTY_1', '<td colspan="{COLUMNS_COUNT}" align="center" class="td_c1"><i>{TEXT}</i></td>');
define('THEME_LIST_ITEM_EMPTY_2', '<td>&#160;</td>');
define('THEME_LIST_ITEM_PLAIN_U1', '<td colspan="{COLUMNS_COUNT}" class="td_c1" align="left" style="width:{WIDTH}"><pre>{TEXT}</pre></td>');
define('THEME_LIST_ITEM_PLAIN_U2', '<td colspan="{COLUMNS_COUNT}" class="td_c2" align="left" style="width:{WIDTH}"><pre>{TEXT}</pre></td>');
define('THEME_LIST_ITEM_LTEXT_U1', '<td class="td_c1">{TEXT}</td>');
define('THEME_LIST_ITEM_LTEXT_U2', '<td class="td_c2">{TEXT}</td>');
define('THEME_LIST_ITEM_RTEXT_U1', '<td class="td_c1">{TEXT}</td>');
define('THEME_LIST_ITEM_RTEXT_U2', '<td class="td_c2">{TEXT}</td>');
define('THEME_LIST_ITEM_INPUT_CHECKBOX_1_U1', '<td class="td_c1" align="left" style="width:1%"><input type="checkbox" name="{NAME}" value="{VALUE}"{JS_EVENTS} /></td>');
define('THEME_LIST_ITEM_INPUT_CHECKBOX_ON_1_U1', '<td class="td_c1" align="left" style="width:1%"><input type="checkbox" name="{NAME}" value="{VALUE}" checked="checked" {JS_EVENTS} /></td>');
define('THEME_LIST_ITEM_INPUT_CHECKBOX_1_U2', '<td class="td_c2" align="left" style="width:1%"><input type="checkbox" name="{NAME}" value="{VALUE}"{JS_EVENTS} /></td>');
define('THEME_LIST_ITEM_INPUT_CHECKBOX_ON_1_U2', '<td class="td_c2" align="left" style="width:1%"><input type="checkbox" name="{NAME}" value="{VALUE}" checked="checked" {JS_EVENTS} /></td>');
define('THEME_LIST_ITEM_LISTBOX_U1_BEGIN', '<td class="td_c1" align="left"><select name="{NAME}" class="form-control input-sm">');
define('THEME_LIST_ITEM_LISTBOX_U1_END', '</select></td>');
define('THEME_LIST_ITEM_LISTBOX_U2_BEGIN', '<td class="td_c2" align="left"><select name="{NAME}" class="form-control input-sm">');
define('THEME_LIST_ITEM_LISTBOX_U2_END', '</select></td>');
define('THEME_LIST_ITEM_LISTBOX_ITEM', '<option value="{VALUE}">{TEXT}</option>');
define('THEME_LIST_ITEM_LISTBOX_ITEM_CUR', '<option value="{VALUE}" selected="selected">{TEXT}</option>');
define('THEME_LIST_ITEM_INPUT_TEXT_U1', '<td class="td_c1" align="left"><input type="text" name="{NAME}" value="{VALUE}" maxlength="{MAX}" class="form-control" /></td>');
define('THEME_LIST_ITEM_INPUT_TEXT_U2', '<td class="td_c1" align="left"><input type="text" name="{NAME}" value="{VALUE}" maxlength="{MAX}" class="form-control" /></td>');
define('THEME_LIST_ANCHOR', '<a href="{URL}">{TEXT}</a>');
define('THEME_LIST_ANCHOR_BLANK', '<a href="{URL}" onclick="this.target=\'_blank\'">{TEXT}</a>');

?>
