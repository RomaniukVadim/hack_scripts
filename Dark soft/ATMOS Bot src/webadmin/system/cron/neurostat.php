<?php
require_once 'system/lib/dbpdo.php';
require_once 'system/lib/util.php';

/** CronJobs concerning Neurostat
 */
class cronjobs_neurostat implements ICronJobs {
    /** Remove old neuroanalysis results for analysis of specific bots
     * @cron period: 1d
     */
    function cronjob_remove_old_singlebot_analyses(){
        $db = dbPDO::singleton();

        # Remove analysis
        $q = $db->query(
            'DELETE FROM `neurostat_analyses`
             WHERE
                `single_botid` IS NOT NULL AND
                `launched` <= NOW() - INTERVAL 3 DAY
             ;'
        );

        # Remove orphaned analysis bots
        $db->query(
            'DELETE `neurostat_analysis_bots`
             FROM `neurostat_analysis_bots`
                LEFT JOIN `neurostat_analyses` USING(`aid`)
             WHERE `neurostat_analyses`.`aid` IS NULL
             ;'
        );

        # Remove orphaned analysis data
        $db->query(
            'DELETE `neurostat_analysis_data`
             FROM `neurostat_analysis_data`
                LEFT JOIN `neurostat_analyses` USING(`aid`)
             WHERE `neurostat_analyses`.`aid` IS NULL
             ;'
        );

        return array('cleansed' => $q->rowCount());
    }
}
