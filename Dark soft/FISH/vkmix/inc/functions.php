<?php
// определение IP
function ip_address() {
 if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $ip_result = $_SERVER['HTTP_X_FORWARDED_FOR'];
 elseif(isset($_SERVER['HTTP_CLIENT_IP'])) $ip_result = $_SERVER['HTTP_CLIENT_IP']; 
 else $ip_result = $_SERVER['REMOTE_ADDR'];
 return $ip_result;
}

// определение браузера пользователя
function user_browser() { 
  $str = getenv('HTTP_USER_AGENT'); 
  if(strpos($str, 'Avant Browser', 0) !== false) return 'Avant Browser'; 
  elseif(strpos($str, 'Acoo Browser', 0) !== false) return 'Acoo Browser'; 
  elseif(@eregi('Iron/([0-9a-z\.]*)', $str, $pocket)) return 'SRWare Iron '.$pocket[1];
  elseif(@eregi('Chrome/([0-9a-z\.]*)', $str, $pocket)) return 'Google Chrome '.$pocket[1]; 
  elseif(@eregi('(Maxthon|NetCaptor)( [0-9a-z\.]*)?', $str, $pocket)) return $pocket[1].$pocket[2];
  elseif(@strpos($str, 'MyIE2', 0) !== false) return 'MyIE2'; 
  elseif(@eregi('(NetFront|K-Meleon|Netscape|Galeon|Epiphany|Konqueror|'. 'Safari|Opera Mini)/([0-9a-z\.]*)', $str, $pocket)) return $pocket[1].' '.$pocket[2]; 
  elseif(@eregi('Opera[/ ]([0-9a-z\.]*)', $str, $pocket)) return 'Opera '.$pocket[1]; 
  elseif(@eregi('Orca/([ 0-9a-z\.]*)', $str, $pocket)) return 'Orca Browser '.$pocket[1]; 
  elseif(@eregi('(SeaMonkey|Firefox|GranParadiso|Minefield|'.'Shiretoko)/([0-9a-z\.]*)', $str, $pocket)) return 'Mozilla '.$pocket[1].' '.$pocket[2]; 
  elseif(@eregi('rv:([0-9a-z\.]*)', $str, $pocket) && strpos($str, 'Mozilla/', 0) !== false) return 'Mozilla '.$pocket[1]; 
  elseif(@eregi('Lynx/([0-9a-z\.]*)', $str, $pocket)) return 'Lynx '.$pocket[1];
  elseif(@eregi('MSIE ([0-9a-z\.]*)', $str, $pocket)) return 'Internet Explorer '.$pocket[1];
  else return 'Unknown';
}

function new_time($a) { // преобразовываем время в нормальный вид
 date_default_timezone_set('Europe/Moscow');
 $ndate = date('d.m.Y', $a);
 $ndate_time = date('H:i', $a);
 $ndate_exp = explode('.', $ndate);
 $nmonth = array(
  1 => 'янв',
  2 => 'фев',
  3 => 'мар',
  4 => 'апр',
  5 => 'мая',
  6 => 'июн',
  7 => 'июл',
  8 => 'авг',
  9 => 'сен',
  10 => 'окт',
  11 => 'ноя',
  12 => 'дек'
 );
 
 foreach ($nmonth as $key => $value) {
  if($key == intval($ndate_exp[1])) $nmonth_name = $value;
 }
 
 if($ndate == date('d.m.Y')) return 'сегодня в '.$ndate_time;
 elseif($ndate == date('d.m.Y', strtotime('-1 day'))) return 'вчера в '.$ndate_time;
 else return $ndate_exp[0].' '.$nmonth_name.' '.$ndate_exp[2].' в '.$ndate_time;
}

// генерируем случайные символы
function rand_str($length, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890') {
 $chars_length = (strlen($chars) - 1);
 $string = $chars{rand(0, $chars_length)};
 for ($i = 1; $i < $length; $i = strlen($string)) {
  $r = $chars{rand(0, $chars_length)};
  if ($r != $string{$i - 1}) $string .= $r;
 }
 return $string;
}

// отправка письма на email
function send_email($to, $title, $message) {
 $message = ' 
 <html> 
     <head> 
         <title>'.$title.'</title> 
     </head> 
     <body> 
         <p>'.$message.'</p> 
     </body> 
 </html>'; 

 $headers  = "Content-type: text/html; charset=utf-8 \r\n"; 
 $headers .= "From: <robot@montytool.ru>\r\n"; 
 
 mail($to, $title, $message, $headers);
}

// проверяем список id аттачей
function check_id_attaches($list = null) {
 if(!preg_match('/^(?:\d\,?)+\d?$/', $list)) {
  return 0;
 } else {
  return 1;
 }
}

// нормальный вид id'ов у аттачей
function normal_id_attaches($list = null) {
 return preg_replace('/\,$/', '', $list);
}

// создаем переключатели страниц
function pages($param) {
 $pages_go = '';
 $pages_back = '';
 $page_tmpl = ''; // объявляем переменную page_tmpl для использования return
 $full_url_replace = array('?page='.$_GET['page'], '&page='.$_GET['page'], 'page='.$_GET['page']);
 $full_url = str_replace($full_url_replace, '', $_SERVER['REQUEST_URI']);
 $full_string = str_replace($full_url_replace, '', $_SERVER['QUERY_STRING']);
 $que_url = $full_string ? '&' : '?';
 $pages_cicle_start = 1;
 $pages_count = ceil($param['ents_count'] / $param['ents_print']);
 
 if($pages_count > 3) {
  $pages_cicle = 3; 
  $pages_go = '<a class="page" href="'.$full_url.''.$que_url.'page='.$pages_count.'" onclick="nav.go(this); return false;">»</a>';
  if($_GET['page'] >= 2) {
   if($_GET['page'] + 1 >= $pages_count) {
    $pages_cicle = $pages_coun;
    $pages_go = '';
   } else $pages_cicle = $_GET['page'] + 2;
   $pages_cicle = $_GET['page'] + 1 >= $pages_count ? $pages_count : $_GET['page'] + 2;
   $pages_cicle_start = ($_GET['page'] == 1 || $_GET['page'] == 2) ? $_GET['page'] - 1 : $_GET['page'] - 2;
   if($_GET['page'] > 3) $pages_back = '<a class="page" href="'.$full_url.''.$que_url.'page=1" onclick="nav.go(this); return false;">«</a>';
  }
 }
 else {
  $pages_cicle = $pages_count;
  $pages_cicle_start = 1;
 }
 
 for($i = $pages_cicle_start; $i <= $pages_cicle; $i++) {
  if($i == $param['page'] || !$param['page'] && $i == 1) $page_tmpl .=  '<a class="page active" href="'.$full_url.''.$que_url.'page='.$i.'" onclick="nav.go(this); return false;">'.$i.'</a>';
  else $page_tmpl .= '<a class="page" href="'.$full_url.''.$que_url.'page='.$i.'" onclick="nav.go(this); return false;">'.$i.'</a>';
  if($i != $pages_count) $page_tmpl .= '';
 }
 return $pages_back.''.$page_tmpl.''.$pages_go;
}

// создаем переключатели страниц для боксов
function pages_ajax($param) {
 $pages_go = '';
 $pages_back = '';
 $page_tmpl = ''; // объявляем переменную page_tmpl для использования return
 $full_url_replace = array('?page='.$_GET['page'], '&page='.$_GET['page'], 'page='.$_GET['page']);
 $full_url = str_replace($full_url_replace, '', $_SERVER['REQUEST_URI']);
 $full_string = str_replace($full_url_replace, '', $_SERVER['QUERY_STRING']);
 $que_url = $full_string ? '&' : '?';
 $pages_cicle_start = 1;
 $pages_count = ceil($param['ents_count'] / $param['ents_print']);
 
 if($pages_count > 3) {
  $pages_cicle = 3; 
  $pages_go = '<a class="page" href="'.$full_url.''.$que_url.'page='.$pages_count.'" onclick="return false">»</a>';
  if($_GET['page'] >= 2) {
   if($_GET['page'] + 1 >= $pages_count) {
    $pages_cicle = $pages_coun;
    $pages_go = '';
   } else $pages_cicle = $_GET['page'] + 2;
   $pages_cicle = $_GET['page'] + 1 >= $pages_count ? $pages_count : $_GET['page'] + 2;
   $pages_cicle_start = ($_GET['page'] == 1 || $_GET['page'] == 2) ? $_GET['page'] - 1 : $_GET['page'] - 2;
   if($_GET['page'] > 3) $pages_back = '<a class="page" href="'.$full_url.''.$que_url.'page=1" onclick="return false">«</a>';
  }
 }
 else {
  $pages_cicle = $pages_count;
  $pages_cicle_start = 1;
 }
 
 for($i = $pages_cicle_start; $i <= $pages_cicle; $i++) {
  if($i == $param['page'] || !$param['page'] && $i == 1) $page_tmpl .=  '<a class="page active" href="'.$full_url.''.$que_url.'page='.$i.'" onclick="return false">'.$i.'</a>';
  else $page_tmpl .= '<a class="page" href="'.$full_url.''.$que_url.'page='.$i.'" onclick="return false">'.$i.'</a>';
  if($i != $pages_count) $page_tmpl .= '';
 }
 return $pages_back.''.$page_tmpl.''.$pages_go;
}

// склонение числительных
function declOfNum($number, $titles) {
 $cases = array(2, 0, 1, 1, 1, 2);
 return $titles[($number%100 > 4 && $number %100 < 20) ? 2 : $cases[min($number%10, 5)]];
}

// CURL
function curl($url, $post = false) {
	$ch = curl_init($url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/23.0.1229.94 Safari/537.4 AlexaToolbar/alxg-3.1');
	if($post) {
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	}
	curl_setopt($ch, CURLOPT_COOKIEJAR, dirname(__FILE__).'/cookie.txt');
	curl_setopt($ch, CURLOPT_COOKIEFILE, dirname(__FILE__).'/cookie.txt');
	$response = curl_exec($ch);
	curl_close($ch);
	return $response;
}

// xss
function fxss($text) {
 return htmlspecialchars($text);
}

// параметры к URL
function querys($url, $param) {
 global $query_string;
 
 if($query_string) {
  return $url.'?'.preg_replace('/'.$param.'=(.*?)&/', '', $query_string.'&'.$param.'=');
 } else {
  return $param ? $url.'?'.$param.'=' : '';
 }
}

// русские символы в json
function jdecoder($json_str) {
 return $json_str;
}

// количество вопросов в поддержке(левое меню)
function my_support_new($admin = null) {
 global $db, $user_id, $ugroup;
 
 if($admin) {
  $query = "SELECT `id` FROM `support_questions` WHERE `uid` != '$user_id' AND `status` = '0' AND `del` = '0'";
 } else {
  $query = "SELECT `id` FROM `support_questions` WHERE `uid` = '$user_id' AND `status` = '1' AND `del` = '0'";
 }
 
 $q = $db->query($query);
 $n = $db->num($q);
 
 return $n;
}

// если нет имени
function no_name($name = null) {
 return trim($name) ? $name : 'Безымянный';
}

// если нет аватара
function no_avatar($avatar = null) {
 global $noavatar;
 
 return $avatar ? $avatar : $noavatar;
}

function showDate( $date ) // $date --> время в формате Unix time
{
    $stf      = 0;
    $cur_time = time();
    $diff     = $cur_time - $date;
 
    $seconds = array( 'секунда', 'секунды', 'секунд' );
    $minutes = array( 'минута', 'минуты', 'минут' );
    $hours   = array( 'час', 'часа', 'часов' );
    $days    = array( 'день', 'дня', 'дней' );
    $weeks   = array( 'неделя', 'недели', 'недель' );
    $months  = array( 'месяц', 'месяца', 'месяцев' );
    $years   = array( 'год', 'года', 'лет' );
    $decades = array( 'десятилетие', 'десятилетия', 'десятилетий' );
 
    $phrase = array( $seconds, $minutes, $hours, $days, $weeks, $months, $years, $decades );
    $length = array( 1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600 );
 
    for ( $i = sizeof( $length ) - 1; ( $i >= 0 ) && ( ( $no = $diff / $length[ $i ] ) <= 1 ); $i -- ) {
        ;
    }
    if ( $i < 0 ) {
        $i = 0;
    }
    $_time = $cur_time - ( $diff % $length[ $i ] );
    $no    = floor( $no );
    $value = sprintf( "%d %s ", $no, getPhrase( $no, $phrase[ $i ] ) );
 
    if ( ( $stf == 1 ) && ( $i >= 1 ) && ( ( $cur_time - $_time ) > 0 ) ) {
        $value .= time_ago( $_time );
    }
 
    return $value . ' назад';
}
 
function getPhrase( $number, $titles ) {
    $cases = array( 2, 0, 1, 1, 1, 2 );
 
    return $titles[ ( $number % 100 > 4 && $number % 100 < 20 ) ? 2 : $cases[ min( $number % 10, 5 ) ] ];
}

function utf8_urldecode($str) {
    $str = preg_replace("/%u([0-9a-f]{3,4})/i","&#x\\1;",urldecode($str));
    return html_entity_decode($str,null,'UTF-8');;
  }
?>