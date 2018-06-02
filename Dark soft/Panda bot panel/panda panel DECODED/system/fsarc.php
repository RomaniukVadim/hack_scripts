<?php

function fsarcCreate($archive, $files)
{
	error_reporting(32767);

	if (strcasecmp(substr(php_uname('s'), 0, 7), 'windows') === 0) {
		$archive = str_replace('/', '\\', $archive);

		foreach ($files as $v => ) {
			$k = php_uname('s');
			$files[$k] = str_replace('/', '\\', $v);
		}
	}

	$archive .= '.zip';
	$cli = 'zip -r -9 -q -S "' . $archive . '" "' . implode('" "', $files) . '"';
	exec($cli, &$e, &$r);

	if ($r != 0) {
		echo '(error: ' . $r . ') ' . $cli . '<br/>';
	}
	return $r ? false : $archive;
}

function newZipCreate($archive, $files)
{
	$base = __DIR__ . '/../';
	$zip = new ZipArchive();

	if ($zip->open($archive, ZipArchive::CREATE) !== true) {
		return NULL;
	}

	foreach ($files as $file) {
		if (!is_file($base . $file)) {
			continue;
		}

		$zip->addFile($base . $file, $file);
	}

	$zip->close();
	return file_exists($archive) ? $archive : NULL;
}


?>
