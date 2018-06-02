<?php namespace lib\helper;

/** Progress object: keeps track while you're performing a bunch of tasks, where each processes a number of items
 */
abstract class Progress {
    /** State interval update, seconds
     * Once this number of seconds has passed since last update - the state is persisted with a user callback
     * @var int
     */
    protected $_update_interval;

    /** Automatically update the progress on significant changes, like action switch
     * @var bool
     */
    protected $_autoupdate = false;

    /** Lifesign threshold, seconds
     * Once this number of seconds has passed, the process is treated dead
     * @var int
     */
    protected $_timeout_interval;

    /** Initialize the state
     * @param int $update_interval
     *      State interval update, seconds
     * @param int $alive_threshold
     *      Lifesign threshold, seconds
     */
    function __construct($update_interval, $alive_threshold, $autoupdate = false){
        $this->_update_interval = $update_interval;
        $this->_timeout_interval = $alive_threshold;
        $this->_autoupdate = $autoupdate;
    }

    /** Method to update the state
     * Once $_update_interval has passed - this method is used to update the state
     */
    abstract protected function _updateProgress();

    /** Last lifesign timestamp
     * @var int
     */
    protected $lifesign = 0;

    /** Update the state if the time has come
     * @param bool $now Whether to update immediately
     */
    function update($now = false){
        if (!$now)
            $now = (time() - $this->lifesign) > $this->_update_interval;
        if ($now){
            $this->updateLifesign();
            $this->_updateProgress();
        }
    }

    /** Update the lifesign property to now
     */
    function updateLifesign(){
        $this->lifesign = time();
        return $this;
    }

    /** Using the lifesign timestamp, determine whether the analysis is really alive
     * @return bool
     */
    function isAlive(){
        return (time() - $this->lifesign) < $this->_timeout_interval;
    }

    /** The list of actions we're going to perform
     * Each action is just a title indexed by its numeric code
     * @var string[]
     */
    protected $actions = array();

    /** Specify the list of action titles, mapped from action codes
     * @var array (int => string)
     */
    function setActions(array $actions){
        $this->actions = $actions;
        return $this;
    }

    /** Numeric id of the current action
     * @var int
     */
    protected $action_id = null;

    /** Switch to another action
     * @param int $id
     *      Action id from $actions
     * @param int $total
     *      The number of items to be processed by this action
     * @throws \InvalidArgumentException
     */
    function setAction($id, $total = 1){
        # Update the action
        if (!isset($this->actions[$id]))
            throw new \InvalidArgumentException('There\'s no action with the specified id');
        $this->action_id = $id;
        # Reset the progress
        $this->progress = array(0, $total);
        # Autoupdate?
        if ($this->_autoupdate) $this->update(true); # Action change: autoupdate!
        return $this;
    }

    /** Get the current action id
     * @return int|null
     */
    function getAction(){
        return $this->action_id;
    }

    /** Get the current action title
     * @return string
     */
    function getActionTitle(){
        return $this->actions[$this->action_id];
    }

    /** Get the current action ordinal number
     * [0,1,...,count($actions))
     * @return int
     */
    function getActionOrdinal(){
        return array_search($this->action_id, array_keys($this->actions));
    }

    /** What it the process currently doing: Human-readable string
     * @var string
     */
    protected $doing;

    /** Specify a human-readable string which tells what the process is currently doing
     * @param string $doing
     */
    function setDoing($doing){
        $this->doing = $doing;
        return $this;
    }

    /** Tell what the process is doing
     * @return string
     */
    function getDoing(){
        return $this->doing;
    }

    /** Progress indication for the current action: array( processed-items , total-items )
     * @var int[2]
     */
    protected $progress = array(0,0);

    /** Specify the number of currently processed items for the current action
     * WARNING: it's not adviced to call this method too fast, keep calm!
     * @param int $position
     */
    function setCurrentProgress($position){
        # Autoupdate?
        $update_now = false;
        if ($this->_autoupdate){ # Significant percentage change: autoupdate!
            if ( floor(20*$position/$this->progress[1]) - floor(20*$this->progress[0]/$this->progress[1]) >= 1 ) # progress change >5%
                $update_now = true;
        }
        # Update the progress
        $this->progress[0] = $position;

        # Update
        if ($update_now)
            $this->update(true);
        return $this;
    }

    /** Get the progress percentage of the current action
     * @return float [0,1]
     */
    function getCurrentProgress(){
        if ($this->progress[1] == 0)
            return 0;
        return $this->progress[0] / $this->progress[1];
    }

    /** Get the progress of this action in the context of all actions
     * @return float [0,1]
     */
    function getActionProgress(){
        return $this->getActionOrdinal() / count($this->actions);
    }

    /** Get the total progress percentage
     * @return float [0,1]
     */
    function getProgress(){
        $progress = $this->getActionProgress(); # position of this action in the total progress
        $perc_per_action = 1 / count($this->actions); # Precentage per action
        $progress += $perc_per_action * $this->getCurrentProgress();
        return $progress;
    }

    /** Get a nice HTML5 <progress> tag
     * @return string
     */
    function toHtmlProgress(){
        $p = round(100*$this->getProgress(),2);
        return sprintf(
            "<progress title='%s' value='%0.2f' max='%0.2f'>%d %%</progress>",
            htmlspecialchars($this->getActionTitle().': '.$this->getDoing()),
            $p, 100, $p
        );
    }

    /** Get an HTML representation
     * @return string
     */
    function toHtml(){
        $ret = "<ul class='progress'>";
        if (count($this->actions) > 1)
            $ret .= sprintf(
                "<li class='action'>%s (%d/%d)</li>",
                $this->getActionTitle(),
                $this->getActionOrdinal()+1,
                count($this->actions)
            );
        if (!empty($this->doing))
            $ret .= sprintf(
                "<li class='doing'>%s</li>",
                $this->doing
            );
        $ret .= sprintf(
            "<li class='progress'>%s</li>",
            $this->toHtmlProgress()
        );
        $ret .= "</ul>";
        return $ret;
    }
}



/** Progress which is updated by a lambda callback
 */
class LambdaProgress extends Progress {
    protected $_update_callback;

    function __sleep() {
        return array_diff(array_keys(get_object_vars($this)), array('_update_callback'));
    }

    /** Specify the update callback
     * @param callable $update_callback
     */
    function setUpdateCallback($update_callback){
        $this->_update_callback = $update_callback;
        return $this;
    }

    protected function _updateProgress() {
        call_user_func($this->_update_callback);
    }
}
