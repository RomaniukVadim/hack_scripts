<?php
require_once 'system/lib/dbpdo.php';

/** CronJobs concerning Scripts
 */
class cronjobs_reports implements ICronJobs {
    /** Remove old `botnet_activity` table entries
     * @cron period: 1d
     */
    function cronjob_cleanup_botnet_activity(){
        $db = dbPDO::singleton();

        $q = $db->query(
            'DELETE FROM `botnet_activity`
             WHERE `date` < FROM_UNIXTIME(:tm)
             ;', array(
            ':tm' => time() - 60*60*24 * 31 * 2,
        ));
        return array('removed' => $q->rowCount());
    }
}
