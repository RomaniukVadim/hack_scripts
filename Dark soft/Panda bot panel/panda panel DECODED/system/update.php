<?php

if (!defined('__CP__')) {
	exit();
}

ini_set('max_execution_time', 600);
include_once __DIR__ . '/Updater.php';
ThemeHeader();
echo "\n";
$updater = new Updater(@$config['repository']);
$updater->license = hash('sha256', @file_get_contents(__DIR__ . '/../license.dat'));

if (isset($_POST['update'])) {
	if ($updater->update($error)) {
		echo '<div class="panel panel-success" style="width: 500px"><div class="panel-heading"><h3 class="panel-title">Success</h3></div>' . "\n" . '  ' . "\t" . '                                 <div class="panel-body">Update success</div></div>';
	}
	else {
		echo '<div class="panel panel-danger" style="width: 500px"><div class="panel-heading"><h3 class="panel-title">Error</h3></div>' . "\n" . '  ' . "\t" . '         <div class="panel-body">Update error, detail: ' . $error . '</div></div>';
	}
}
else {
	$response = $updater->check(false);

	if (!$response) {
		echo 'Repository unavailable.';
	}
	else if ($response->new == 1) {
		echo 'A new version is available.<br><br><form method=post><input type=submit name=update value=Update class="btn btn-sm btn-primary"></form>';
	}
	else {
		echo 'You already have the latest version.';
	}
}

?>
