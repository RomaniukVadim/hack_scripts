<?php namespace lib\fun\TokenSpy\gate;

/** BotInfo object
 */
class BotInfo {
    /** The name of the Rule
     * @var string
     */
    public $rule_name;

    /** The id of the Rule pattern
     * @var int
     */
    public $pattern_id;

    /** BotId
     * @var string
     */
    public $botId;

    /** The original URL
     * @var string
     */
    public $url;

    /** The original domain
     * @var string
     */
    public $domain;

    /** The current session
     * @var string|null
     */
    public $session_id;

    /** Create a dummy BotInfo for testing 'TestBot'
     * @return BotInfo
     */
    static function makeTestBotInfo($url){
        $botInfo = new static;
        $botInfo->rule_name = 'example';
        $botInfo->pattern_id = 0;
        $botInfo->botId = 'TestBot';
        $botInfo->url = $url;
        $botInfo->domain = parse_url($url, PHP_URL_HOST);
        $botInfo->session_id = 'TestBot';
        return $botInfo;
    }

    /** Get the info from /.ts/enter format
     * { "url: "<full query URL>", "buid": "<BotId>", "ruid": "<RuleId>", "puid": <PatternId>}
     */
    static function fromTsEnter($jsonBody){
        $botInfo = new self();
        # Map
        foreach (array(
                'url' => 'url',
                'botId' => 'buid',
                'rule_name' => 'ruid',
                'pattern_id' => 'puid',
            ) as $prop => $key)
            $botInfo->{$prop} = isset($jsonBody[$key])? $jsonBody[$key] : null;
        # Emulate
        $botInfo->domain = parse_url($botInfo->url, PHP_URL_HOST);
        # Finish
        return $botInfo;
    }

    /** Get the info from the Proxy headers
     *  X-TS-Rule-Name          The name of the rule that's currently active
     *  X-TS-Rule-PatternID     The Rule pattern that matched
     *  X-TS-BotID              BotId
     *  X-TS-Domain             The original domain name
     *  X-TS-SessionID          The id of the session given by ts.php/.ts/enter
     */
    static function fromProxyHeaders(){
        $botInfo = new self();
        # Map
        foreach (array(
            'rule_name' => 'RULE_NAME',
            'pattern_id' => 'RULE_PATTERNID',
            'botId' => 'BOTID',
            'domain' => 'DOMAIN',
            'session_id' => 'SESSIONID',
            ) as $prop => $header){
            $botInfo->$prop = null;
            if (isset($_GET[$header]))
                $botInfo->$prop = $_GET[$header];
            if (isset($_SERVER["HTTP_X_TS_{$header}"]))
                $botInfo->$prop = $_SERVER["HTTP_X_TS_{$header}"];
        }

        # Fix: USERAGENT header is wrapped with an X-TS extension
        if (isset($_SERVER['HTTP_X_TS_HEADER_USERAGENT']))
            $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_X_TS_HEADER_USERAGENT']; // move to place

        # Emulate
        $botInfo->url = empty($botInfo->domain)? null : "http://{$botInfo->domain}";
        $botInfo->url .= $_SERVER['PATH_INFO'];

        # Finish
        return $botInfo;
    }

    /** Start a session for this bot
     */
    function session_start(){
        # Start the session
        ini_set('session.use_cookies', '0');
        session_name('TokenSpy');
        session_id($this->session_id);
        session_start();
    }
}
