<?php

if (!defined('__CP__')) {
	exit();
}

include_once __DIR__ . '/../gate/libs/Api.php';
ThemeBegin('Url', 0, 0, 0);
echo '<form method="post" class="form-inline form-group-sm">' . "\n" . '  <input type="text" name="url" placeholder="Enter url to extract bot id" style="width: 200px" class="form-control" /> <input type="submit" value="Extract" class="btn btn-primary btn-sm" />' . "\n" . '</form><hr/>' . "\n";

if (isset($_POST['url'])) {
	$botId = Api::extractId($_POST['url']);

	if ($botId) {
		print('BotId: ' . $botId . ' <a href="cp.php?bots[]=' . htmlspecialchars($botId) . '&amp;botsaction=fullinfo" target="_blank">View bot</a>');
	}
	else {
		print('Wrong url');
	}
}

ThemeEnd();

?>
