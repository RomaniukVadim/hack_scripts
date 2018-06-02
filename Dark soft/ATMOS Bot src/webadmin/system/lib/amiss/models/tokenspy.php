<?php namespace Citadel\Models\TokenSpy;

use Amiss;
use lib\fun\TokenSpy\Page;
use lib\fun\TokenSpy\Skeleton;

require_once 'system/lib/fun/TokenSpy/resources/Template.php';
require_once 'system/lib/fun/TokenSpy/resources/Page.php';

/** TokenSpy Bot State
 * @table tokenspy_rules
 */
class Rule {
    /** Rule ID, PK
     * @primary
     * @type autoinc
     * @var int
     */
    public $id;

    /** Modification timestamp
     * @field
     * @type timestamp not null
     * @var \DateTime
     */
    public $mtime;

    /** Rule name
     * @field
     * @type varchar(64) not null
     * @var string
     */
    public $name;

    /** Triggering patterns for the rule |serialized
     * {{ uid: Number, mask: String, post: Array.<String> }}
     * @field
     * @type serialized not null
     * @var \lib\fun\TokenSpy\RulePattern[]
     */
    public $patterns;

    /** Proxy masks |serialized
     * @field
     * @type serialized not null
     * @var string[]
     */
    public $pmasks = array();

    /** Is the rule enabled?
     * @field
     * @type int not null
     * @var bool
     */
    public $enabled;

    /** The template name to use
     * For skeleton-based templates, the template name is (".%d", $this->id)
     * @field
     * @type varchar(255) not null
     * @var string
     */
    public $template;

    /** The skeleton to use for building a template
     * @field
     * @type serialized null
     * @var Skeleton
     */
    public $skeleton;

    /** The page to use
     * @field
     * @type serialized not null
     * @var Page
     */
    public $page;

    /** White list of bots allowed to trigger this rule
     * @field
     * @type serialized not null
     * @var string[]
     */
    public $bots_wl;
}



/** TokenSpy Bot State
 * @table tokenspy_bots_state
 */
class BotState {
    /** Create a dummy BotState for testing 'TestBot'
     * @param string|null $template
     * @param Page|null $page
     * @param Page|null $page2
     * @return BotState
     */
    static function makeTestBotState($template = null, $page = null, $page2 = null){
        $bs = new static; /** @var BotState $bs */
        $bs->botId = 'TestBot';
        $bs->rule_id = null;
        $bs->rule_name = 'test';
        $bs->pattern_id = 0;
        $bs->ctime = $bs->atime = $bs->mtime = new \DateTime;
        $bs->browser = 'FakeBrowser 1.0';
        $bs->url = 'http://example.com/';
        $bs->template = $template;
        $bs->session_id = 'TestBot';
        $bs->hits = 1;
        $bs->page = $page;
        $bs->page2 = $page2;
        return $bs;
    }

    /** State ID, PK
     * @primary
     * @type autoinc
     * @var int
     */
    public $id;

    /** Bot Id
     * @field
     * @type varchar(100) not null
     * @var string
     */
    public $botId;

    /** Individual bot TS state
     * self::ISTATE_* const
     * @field
     * @var string
     */
    public $istate = self::ISTATE_ON;

    /** istate: "on", TS is enabled for this bot.
     * Default state.
     * @const
     */
    const ISTATE_ON = 'on';

    /** istate: "skip", disable execution of the current rule
     * TS will be enabled again when a new rule matches.
     * @const
     */
    const ISTATE_SKIP = 'skip';

    /** istate: "ign", TS is disabled for this bot, forever
     * @const
     */
    const ISTATE_IGN = 'ign';

    /** Rule id (FK)
     * @field
     * @type int not null
     * @var string
     */
    public $rule_id;

    /** Rule name (FK)
     * @field
     * @type varchar(64) not null
     * @var string
     */
    public $rule_name;

    /** Rule Object (FK)
     * @has one of=\Citadel\Models\TokenSpy\Rule; on=rule_id
     * @var Rule
     */
    public $Rule;

    /** Rule Pattern ID (FK)
     * @field
     * @type int unsigned not null
     * @var int
     */
    public $pattern_id;

    /** Creation timestamp
     * @field
     * @type timestamp not null
     * @var \DateTime
     */
    public $ctime;

    /** Modification (master) timestamp
     * @field
     * @type timestamp not null
     * @var \DateTime
     */
    public $mtime;

    /** Access (lifesign) timestamp
     * @field
     * @type timestamp not null
     * @var \DateTime
     */
    public $atime;

    /** Bot browser string
     * @field
     * @type varchar(255) not null
     * @var string
     */
    public $browser;

    /** The URL the bot is currently at
     * @field
     * @type varchar(255) not null
     * @var string
     */
    public $url;

    /** Visits count
     * @field
     * @type int unsigned not null
     * @var int
     */
    public $hits = 0;

    /** The template name currently in use
     * @field
     * @type varchar(255) not null
     * @var string
     */
    public $template;

    /** Session Id the bot is using
     * @field
     * @type varchar(255) not null
     * @var string
     */
    public $session_id;

    /** The page in use
     * @field
     * @type serialized null
     * @var Page|null
     */
    public $page;

    /** The next page
     * @field
     * @type serialized null
     * @var Page|null
     */
    public $page2;

    /** Check the page transition conditions
     * @param bool|null $post
     *      Is the POST data available?
     */
    function needPageTransition($post = null){
        # Transit the page when there's POST data available
        if ($post) return true;

        # Transit the page when a timeout is reached
        if ($this->page && $this->page->timeout)
            if (time() > ($this->mtime->format('U') + $this->page->timeout))
                return true;

        # Stay here otherwise
        return false;
    }

    /** Do the page transition.
     * @param Amiss\Manager $man
     *      Amiss entity manager
     */
    function doPageTransition(Amiss\Manager $man){
        # Move the current page to history
        $log = BotHistoryLine::fromBotState($this);
        $man->save($log);

        # Page transition
        $this->page = $this->page2;
        $this->page2 = null;
        $this->mtime = new \DateTime();
        $this->atime = new \DateTime();
        $this->hits = 0;

        # When the next page is not set - reset to the initial
        if (!$this->page){
            //$man->assignRelated($this, 'Rule'); // FIXME: "Class \\Citadel\\Models\\TokenSpy\\Rule does not exist"
            if ($this->rule_id)
                $this->Rule = $man->getByPk('Citadel\Models\TokenSpy\Rule', $this->rule_id);

            if ($this->Rule)
                $this->page = $this->Rule->page;
        }
    }

    /** Misc info about the bot.
     * Keys:
     * 'disabled_rules':
     *      array(rule_name => timestamp)
     *      As each rule can be disabled, this gives a timestamp when the rule is enabled back again.
     *      Used in: manually disable a rule for the bot
     * @field
     * @type serialized not null
     * @var array
     */
    public $info;
}



/** TokenSpy Bot History Line
 * @table tokenspy_bots_history
 */
class BotHistoryLine {
    /** Create from BotState
     * @param BotState $state
     * @return BotHistoryLine
     */
    static function fromBotState(BotState $state){
        $log = new static();
        $log->botId = $state->botId;
        $log->rule_name = $state->rule_name;
        $log->ctime = $state->mtime;
        $log->url = $state->url;
        $log->hits = $state->hits;
        $log->page = $state->page;
        return $log;
    }

    /** State ID, PK
     * @primary
     * @type autoinc
     * @var int
     */
    public $id;

    /** Bot Id
     * @field
     * @type varchar(100) not null
     * @var string
     */
    public $botId;

    /** Rule name (FK)
     * @field
     * @type varchar(64) not null
     * @var string
     */
    public $rule_name;

    /** Rule Object (FK)
     * @has one of=\Citadel\Models\TokenSpy\Rule; on=rule_name
     * @var Rule
     */
    public $Rule;

    /** Creation timestamp
     * @field
     * @type timestamp not null
     * @var \DateTime
     */
    public $ctime;

    /** The URL the bot is currently at
     * @field
     * @type varchar(255) not null
     * @var string
     */
    public $url;

    /** Visits count
     * @field
     * @type int unsigned not null
     * @var int
     */
    public $hits;

    /** Displayed page
     * @field
     * @type serialized not null
     * @var Page
     */
    public $page;
}



/** TokenSpy Bot POST Data
 * @table tokenspy_bots_posted
 */
class BotPosted {
    /** State ID, PK
     * @primary
     * @type autoinc
     * @var int
     */
    public $id;

    /** Bot Id
     * @field
     * @type varchar(100) not null
     * @var string
     */
    public $botId;

    /** Creation timestamp
     * @field
     * @type timestamp not null
     * @var \DateTime
     */
    public $ctime;

    /** Data posted |serialized
     * @field
     * @type serialized not null
     * @var array
     */
    public $data;
}

/** TokenSpy Page Preset
 * @table tokenspy_page_presets
 */
class PagePreset {
    /** Page preset ID, PK
     * @primary
     * @type autoinc
     * @var int
     */
    public $id;

    /** Preset name
     * @field
     * @type varchar(64) not null
     * @var string
     */
    public $name;

    /** The page preset
     * @field
     * @type serialized not null
     * @var Page
     */
    public $page;
}
