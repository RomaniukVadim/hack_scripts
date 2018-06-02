<?php
/** Feed a form using JS */
function js_form_feeder($form_selector, $form_data){
	return '<script>(function(){ var f = function(){ window.js_form_feeder('.json_encode($form_selector).', '.json_encode($form_data).'); }; $(f); $(document).bind("cbox_complete", f); })();</script>';
	}

/** Set arbitrary JSON variables
 * Example: echo jsonset(array('window.data' => $data));
 */
function jsonset(array $variables){
	$ret  = '<script type="text/javascript">'."\n";
	foreach ($variables as $name => $value)
		$ret .= $name.' = '.json_encode($value).';'."\n";
	$ret .= '</script>'."\n";
	return $ret;
	}

/** Print datetime in a short format */
function date_short($timestamp, $fmt_tm = 'H:i:s', $fmt_dm = 'd.m', $fmt_dmy = 'd.m.Y'){
	if ((time() - $timestamp) < (60*60*20))
		return date($fmt_tm, $timestamp);
	if ((time() - $timestamp) < (60*60*24*30))
		return date($fmt_dm, $timestamp);
	return date($fmt_dmy, $timestamp);
	}

/** Print datetime in 'timeago' format.
 * @param int|null $delta
 * 	Time delta, seconds
 * @param string|null $lang
 * 	Language code. NULL looks into $GLOBALS['userData']['language']
 * @return string
 */
function timeago($seconds, $lang=NULL, $period = false){
	if (is_null($lang))
		$lang = $GLOBALS['userData']['language'];

	# Language
	$pot = array(
		'en' => array(
				0 => 'sec', 1 => 'min', 2 => 'h',
				3 => 'days', 4 => 'months', 5 => 'years', 6 => 'cent', 7 => 'eras',
				'ago' => 'ago',
				'future' => 'in the future',
				'now' => 'just now',
                'instantly' => 'instantly',
				'never' => 'never',
				),
		'ru' => array(
				0 => 'сек', 1 => 'мин', 2 => 'ч',
				3 => 'дн', 4 => 'мес', 5 => 'лет', 6 => 'столетий', 7 => 'эр',
				'ago' => 'назад',
                'future' => 'в будущем',
				'now' => 'только что',
                'instantly' => 'мгновенно',
				'never' => 'никогда',
				),
		);
	$l = isset($pot[$lang]) ? $pot[$lang] : $pot['en'];

	# Never?
	if (is_null($seconds))
		return $l['never'];

    # Negative?
    $negative = false;
    if ($seconds < 0){
        $seconds *= -1;
        $negative = true;
    }

	# Units
	$K = array(1, 60, 60, 24, 30, 12, 365, 100, 1000); # coeff
	$N = count($K); # count

	# Calc
	$d = array(0 => $seconds);
	for ($i=1; $i<$N; $i++){
		$d[$i] = floor($d[$i-1]/$K[$i]);
		$d[$i-1] -= $d[$i]*$K[$i];
		}

	# Output
    $ago = $negative? $l['future'] : $l['ago'];
    if ($period)
        $ago = null;

	for ($i=$N-1; $i>0; $i--)
		if ($d[$i] > 2 || ($d[$i]>0 && $d[$i-1]==0))
			return "{$d[$i]} {$l[$i]} {$ago}";
		elseif ($d[$i] > 0)
			return "{$d[$i]} {$l[$i]} {$d[$i-1]} {$l[$i-1]} {$ago}";

	# Seconds left
	if ($d[0] > 10)
		return "{$d[0]} {$l[0]} {$ago}";

    if ($period)
        return $l['instantly'];
	return $l['now'];
	}

/** Recursive urlencode
 * Supports arrays & even streams!
 */
function urlenc(array $args, $_pre = '', $_post = '') {
	$ret = '';
	foreach ($args as $k => $v)
		if (is_array($v))
			$ret .= urlenc($v, $_pre.$k.$_post.'[', ']');
		else {
			if (is_resource($v))
				$v = stream_get_contents($v);
			$ret .= $_pre.$k.$_post .'='. urlencode($v) .'&';
			}
	return $ret;
	}

/** A caching arguments generator
 * It takes the current arguments array, and applies the action:
 * @param int $do	= 0 : Kill mode: kill arguments, others are saved
 * 				= 1 : Save mode: save arguments, others are deleted
 * @param string $args..	List of $_GET varnames to apply the $do to
 * @return string The generated arguments list, startting from '?' and with trailing '&'
 * The results are cached on 1st invoke: it's CPU-safe to reinvoke.
 * @example GET: "a=1&b=2&c=3&d=4"
 * 		mkuri(0,'a','b'); // "c=3&d=4&" // kill a,b
 * 		mkuri(1,'a','b'); // "a=1&b=2&" // save a,b
 * 		mkuri(0) = "kill nothing" // "a=1&b=2&c=3&d=4&"
 */
function mkuri($do=0 /* , args... */) {
	static $getk = NULL;
	static $kcache = array();

    // Reset the cache?
    if (is_null($do)){
        $getk = NULL;
        $kcache = array();
    }

	/* func args */
	$A = func_get_args();
	unset($A[0]);
	/* GET keys */
	if (is_null($getk))
		$getk = array_keys($_GET);
	/* Apply filter */
	$keys = ($do == 1) ? array_intersect($A, $getk) : array_diff($getk, $A);
	/* Finish */
	$ret = '';
	foreach ($keys as $k){
		if (!isset($kcache[$k]))
			$kcache[$k] = urlenc(array($k => $_GET[$k]));#urlencode($k).'='.urlencode($_GET[$k]).'&';
		$ret .= $kcache[$k];
		}
	return $ret;
	}


/** Format information size, given in bytes, to larger units
 */
function bytesz($sz, $decimals = 3){
	$szsi = ' KMGTPEZY';
	# Format
	$rem = 0; // remainder
	$i=0; // the current suffix
	while ($sz >= 1024 && $i++ < 8) {
		$rem = (($sz & 0x3ff) + $rem) / 1024;
		$sz = $sz >> 10; # /1024
		}
	$sz += $rem;
	# Print
	return round($sz, $decimals).$szsi[$i].'b';
	}

/** Display a flash message with the given severity
 * @param string $severity Severity + CSS class: info, warn, err
 * @param string $message The message with :placeholders
 * @param string[] $data
 *      Named data placeholders:
 *      array( ':name' => value ) - HTML escaped
 *      array( '!name' => value ) - written as is
 */
function flashmsg($severity, $message, array $data = array()){
    # Escape
    foreach ($data as $k => $v)
        switch ($k[0]){
            case ':':
                $v = htmlspecialchars($v);
                break;
            default:
            case '!':
                break;
        }

	# Prepare
	$msg = str_replace(array_keys($data), array_values($data), $message);

	# Store
	if (empty($_SESSION['flashmsg'][$severity]))
		$_SESSION['flashmsg'][$severity] = array($msg);
	elseif (!in_array($msg, $_SESSION['flashmsg'][$severity]))
		$_SESSION['flashmsg'][$severity][] = $msg;
}

/** Convert 2-letter country code to <img> HTML tag
 * @param string $cnt Country name
 * @return string HTML image
 */
function countryFlag($cnt, $with_text = false){
    if ($cnt == '--' || $cnt == '??' || $cnt === '')
        return htmlentities($cnt);
    $c = htmlentities(strtolower($cnt));
    $C = htmlentities(strtoupper($cnt));
    return sprintf('<img class="flag" src="theme/images/flags/gif/%s.gif" alt="[ ? ]" title="%s" />%s', $c, $C, $with_text? " $C" : '');
}

/** Get an URL of the report viewer
 * @param string $report Report reference: 'yymmdd:id'
 * @param string $viewmode The view mode
 * @return null|string
 */
function report_url($report, $viewmode='brief'){
    if (empty($report))
        return null;
    return '?m=reports_db&t='.str_replace(':', '&id=', $report)."&viewmode={$viewmode}";
}

/** Generate a link that opens the 'brief' viewmode of a report
 * @param string $report Report ref
 * @param null $title Custom link title
 * @return string
 */
function report_link_brief($report, $title = null){
    $url = report_url($report, 'brief');
    if (empty($title))
        $title = $report;
    return "<a href='$url' class='report-view-brief'>{$title}</a>";
}
