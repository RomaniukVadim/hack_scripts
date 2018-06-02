<?php
require_once 'system/lib/guiutil.php';

class Plugin_Reports_DB_Connect {
	static function connection_picker(){
		echo str_replace('{WIDTH}', 'auto', THEME_DIALOG_BEGIN).
				str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(2, LNG_REPORTS_CONNECT_ANOTHERDB), THEME_DIALOG_TITLE),
			'<tr><td>';

		# select box
		echo '<form method=GET><input type="hidden" name="m" value="'.$_GET['m'].'" />';
		echo '<label>', LNG_REPORTS_CONNECT_DBNAME, ': ',
			'<SELECT name="dbConnect">',
			'<option value="">', LNG_REPORTS_CONNECT_THIS, '</option>';
		if (!empty($GLOBALS['config']['db-connect']))
			foreach ($GLOBALS['config']['db-connect'] as $name => $conn){
				$selected = !empty($_SESSION['db-connect']) && $_SESSION['db-connect'] == $name;
				echo '<option value="', htmlentities($name), '" '.($selected? 'selected' : '').'>', htmlentities($name), '</option>';
			}
		echo '</SELECT></label>';
		echo '<input type="submit" value="Connect" />';

		echo ' <a href="?m=ajax_config&action=dbConnect" class="ajax_colorbox" />[ '.LNG_REPORTS_CONNECT_SETUP.' ]</a> ';
		echo '</form>';

		echo THEME_DIALOG_END;
	}

	static function switch_connection($name){
		unset($_SESSION['db-connect']);
		$conn = &$GLOBALS['config']['db-connect'][  $name  ];
		if (!empty($conn))
			$_SESSION['db-connect'] = $name;
	}

	static function connect(){
		if (empty($_SESSION['db-connect']))
			return;
		$conn = &$GLOBALS['config']['db-connect'][  $_SESSION['db-connect']  ];
		if (empty($conn))
			return;

		# Close the previous connection
		@mysql_close();

		# Attempt to connect
		$purl = parse_url($conn);
		$l = mysql_connect($purl['host'].':'.$purl['port'], $purl['user'], $purl['pass'], true);
		$ok = $l !== FALSE;
		if ($ok) $ok = (bool)mysql_select_db(trim($purl['path'], '/'), $l);
		if ($ok) $ok = (bool)mysql_query('SET NAMES "'.MYSQL_CODEPAGE.'" COLLATE "'.MYSQL_COLLATE.'";', $l);

		if (!$ok){
			flashmsg('err', 'Citra Connect: ":conn" failed! Error: ":error". Using the default connection instead', array(':conn' => $conn, ':error' => mysql_error($l)));
			mysql_close($l);
			connectToDb();
			return;
		}

		# Warn
		flashmsg('info', 'Citra Connect: Using ":db"', array(':db' => $_SESSION['db-connect']));

        # Replace MySQL connection credentials in $config so dbPDO sees it
        $GLOBALS['config'] = array(
            'mysql_host' => $purl['host'],
            'mysql_user' => $purl['user'],
            'mysql_pass' => $purl['pass'],
            'mysql_db' => trim($purl['path'], '/'),
        ) + $GLOBALS['config'];
	}
}
