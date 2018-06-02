<?php namespace lib\fun\TokenSpy;

use Citadel\Models\TokenSpy\BotState;
use Citadel\Models\TokenSpy\Rule;
use lib\fun\TokenSpy\gate\BotInfo;

require_once 'system/lib/datfile.php';

/** TokenSpy state file handler
 */
class TsState {
    const PATH = 'system/data/TokenSpy';

    /** TokenSpy state file
     * @var \DatFileLoader
     */
    protected $_state_datfile;

    /** TokenSpy state
     * @var object
     */
    protected $_state;

    function __construct(){
        $this->_state_datfile = new \DatFileLoader(static::PATH.'/state');
    }

    /** Load the state file data
     * @return $this
     */
    function load(){
        $this->_state = $this->_state_datfile->load();
        if (is_null($this->_state))
            $this->_state = (object)array(
                'enabled' => 0,
                'paused' => 0,
            );
        return $this;
    }

    /** Save the new state file data
     */
    function save(){
        $this->_state_datfile->save($this->_state);
    }

    /** Get/Set the TokenSpy 'enabled' state.
     * Additionally, this does always unpause it
     * @param bool $set
     * @return bool The current state
     */
    function ts_enabled($set = null){
        if (!is_null($set)){
            $this->_state->enabled = (bool)$set;
            $this->ts_paused(false); # Always unpause
        }
        return $this->_state->enabled;
    }

    /** Get/Set the TokenSpy 'paused' state
     * @param bool $set
     * @return bool The current state
     */
    function ts_paused($set = null){
        if (!is_null($set))
            $this->_state->paused = (bool)$set;
        return $this->_state->paused;
    }

    /** Check whether TS is enabled for this very bot
     * @param \Amiss\Manager $man
     *      Amiss Manager to update botState when necessary
     * @param BotInfo $botInfo
     *      BotInfo from the request
     * @param BotState|null $bs
     *      The BotState to check the TS state against
     * @param string $for
     *      'getState', 'enter', 'page'
     * @param &string $reason
     *      TS disabled reason
     * @return bool
     */
    function isEnabledFor(\Amiss\Manager $man, BotInfo $botInfo, $bs, &$reason){
        # Enabled?
        if (!$this->ts_enabled()){
            $reason = 'TS switched off';
            return false; # not enabled. period.
        }

        # Paused?
        if ($this->ts_paused() && (!$bs || !$bs->id || !$bs->page)){ # unknown bot || new bot || no page is set
            $reason = 'TS paused && new bot';
            return false; # enabled,paused, but the bot is not known
        }

        # Check rule restrictions
        $rule = $man->get('\Citadel\Models\TokenSpy\Rule', 'name=? AND enabled=1', $botInfo->rule_name); /** @var Rule $rule */
        if ($rule){
            # Bots whitelist?
            if ($rule->bots_wl && !in_array($botInfo->botId, $rule->bots_wl)){
                $reason = 'Not in whitelist';
                return false;
            }
            # Rule disabled?
            if (isset($bs->info['disabled_rules'][$botInfo->rule_name])){
                if (time() < $bs->info['disabled_rules'][$botInfo->rule_name]){
                    $reason = 'Rule disabled for this bot';
                    return false;
                }
            }
        }

        # Check whether TS is enabled for this bot individually
        if ($bs){
            switch ($bs->istate){
                case $bs::ISTATE_ON:
                    return true; # Always on
                case $bs::ISTATE_IGN:
                    $reason = 'Bot in ignore mode';
                    return false; # Always off
                case $bs::ISTATE_SKIP:
                    /* SKIP should ignore the current rule
                     * Thus, we let the bot in if it's requesting another rule, or a timeout has expired
                     */
                    $is_new_rule = $botInfo->rule_name != $bs->rule_name; # bot is requesting another rule
                    $is_timeout = $bs->atime && (time() - $bs->atime->format('U')) > (60*60); # skip expired

                    if ($is_new_rule || $is_timeout){
                        $bs->istate = $bs::ISTATE_ON;
                        $man->save($bs); # update the changed state
                        return true;
                    }

                    $reason = 'Bot in skip mode';
                    return false;
            }
        }

        return true;
    }

    /** Get the state object
     * @return object
     */
    function getState(){
        return $this->_state;
    }
}
