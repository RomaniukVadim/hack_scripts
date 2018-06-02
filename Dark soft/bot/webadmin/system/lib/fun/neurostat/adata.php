<?php namespace lib\fun\NeuroStat\AData;

use lib\fun\NeuroStat;
use lib\fun\NeuroStat\Criteria;
use Citadel\Models;

/** Hierarchical data storage for the analysis
 */
class AnalysisData {
    /** The analysis
     * @var Models\NeurostatAnalysis
     */
    protected $analysis;

    /**
     * @param Models\NeurostatAnalysis $analysis
     */
    function __construct(Models\NeurostatAnalysis $analysis){
        $this->analysis = $analysis;
        $this->_buffer = $this->_buffer_default;
    }

    /** Hierarchical data: [bid][yymmdd][cid] = array(reports)
     * @var array
     */
    protected $_data = array();

    const B_BID = 0;
    const B_YYMMDD = 1;
    const B_CID = 2;
    const B_REPORTS = 3;

    /** Commit buffer
     * @var array
     */
    protected $_buffer = null;
    protected $_buffer_default = array(0 => null, 1 => null, 2 => null, 3 => array());

    /** Reset the buffer
     */
    function reset(){
        $this->_buffer = $this->_buffer_default;
        return $this;
    }

    /** Reset the reports buffer only
     */
    function resetReports(){
        $this->_buffer[static::B_REPORTS] = $this->_buffer_default[static::B_REPORTS];
        return $this;
    }

    /** Commit the buffer
     */
    function commit(){
        # Initialize
        $ref = &$this->_data
            [ $this->_buffer[static::B_BID] ]
            [ $this->_buffer[static::B_YYMMDD] ]
            [ $this->_buffer[static::B_CID] ]
        ;
        if (!isset($ref)) $ref = array();
        # Push reports
        $ref = array_merge($ref, $this->_buffer[static::B_REPORTS]);
        return $this;
    }

    /** Accumulate: Bot Id
     * @param int $bid
     */
    function setBot($bid){
        $this->_buffer[static::B_BID] = $bid;
        return $this;
    }

    /** Accumulate: Date
     * @param int $yymmdd
     */
    function setDate($yymmdd){
        $this->_buffer[static::B_YYMMDD] = $yymmdd;
        return $this;
    }

    /** Accumulate: matched Criterion
     * @param Criteria\ACriterion $criterion
     */
    function setCriterion(Criteria\ACriterion $criterion){
        $this->_buffer[static::B_CID] = $criterion->c->cid;
        return $this;
    }

    /** Accumulate: report reference
     * @param string $report
     */
    function addReport($report){
        array_push($this->_buffer[static::B_REPORTS], $report);
        return $this;
    }

    /** Flush the collected data to the database
     */
    function save(\dbPDO $db){
        # Prepare the query
        $q = $db->prepare(
            'INSERT INTO `neurostat_analysis_data`
             VALUES(:aid, :bid, :cid, :date, :reports, GREATEST(1, :reports_count))
             ON DUPLICATE KEY UPDATE
                `reports` = IF(  LENGTH(:reports)  , `reports`, CONCAT_WS("\n", `reports`, :reports)),
                `reports_count` = GREATEST(1, `reports_count` + :reports_count)
            ;');
        $q_data = array(
            ':aid' => $this->analysis->aid,
            ':bid' => null,
            ':cid' => null,
            ':date' => 0,
            ':reports' => '',
            ':reports_count' => 0,
        );

        # Store records
        $db->beginTransaction();
        foreach ($this->_data as $bid => $data1){
            $q_data[':bid'] = $bid;

            foreach ($data1 as $yymmdd => $data2){
                $q_data[':date'] = $yymmdd?:'00000000'; # mysql takes '120131' just ok

                foreach ($data2 as $cid => $reports){
                    $q_data[':cid'] = $cid;
                    $q_data[':reports'] = implode("\n" ,$reports);
                    $q_data[':reports_count'] = count($reports);
                    $q_data[':hits'] = $q_data[':reports_count'];
                    $q->execute($q_data);
                }
            }
        }
        $db->commit();

        # Reset
        $this->_data = array();
    }
}
