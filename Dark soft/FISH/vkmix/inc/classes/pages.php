<?php
class pages {
 public function html_code($text = null) {
  $text = preg_replace('/\[b\](.*?)\[\/b\]/s', '<b>$1</b>', $text);
  $text = preg_replace('/\[i\](.*?)\[\/i\]/s', '<i>$1</i>', $text);
  $text = preg_replace('/\[u\](.*?)\[\/u\]/s', '<u>$1</u>', $text);
  $text = preg_replace('/\[ul\]/s', '<ul>', $text);
  $text = preg_replace('/\[\/ul\]/s', '</ul>', $text);
  $text = preg_replace('/\[li\](.*?)\[\/li\]/s', '<li>$1</li>', $text);
  $text = preg_replace('/\[h1\](.*?)\[\/h1\]/s', '<h1>$1</h1>', $text);
  $text = preg_replace('/\[h2\](.*?)\[\/h2\]/s', '<h2>$1</h2>', $text);
  $text = preg_replace('/\[h3\](.*?)\[\/h3\]/s', '<h3>$1</h3>', $text);
  $text = preg_replace('/\[center\](.*?)\[\/center\]/s', '<div class="position_center">$1</div>', $text);
  $text = preg_replace('/\[left\](.*?)\[\/left\]/s', '<div class="position_left">$1</div>', $text);
  $text = preg_replace('/\[right\](.*?)\[\/right\]/s', '<div class="position_right">$1</div>', $text);
  $text = preg_replace('/\[blockquote\](.*?)\[\/blockquote\]/s', '<blockquote>$1</blockquote>', $text);
  $text = preg_replace('/\[url=(.*?)\](.*?)\[\/url\]/s', '<a href="$1" target="_blank">$2</a>', $text);
  $text = preg_replace('/\[img\](.*?)\[\/img\]/s', '<img src="$1">', $text);
  
  return $text;
 }
 
 public function pcheck_short($short_url = null) {
  global $db;
  
  $q = $db->query("SELECT `id` FROM `pages` WHERE `short_url` = '$short_url'");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function pages_list_num() {
  global $db;
  
  if($_GET['act'] == 'del') {
   $status_delete = '1';
  } else {
   $status_delete = '0';
  }
  
  $q = $db->query("SELECT `id` FROM `pages` WHERE `del` = '$status_delete'");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function pages_list() {
  global $db;
  
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 10;
  
  if($_GET['act'] == 'del') {
   $status_delete = '1';
  } else {
   $status_delete = '0';
  }
  
  $q = $db->query("SELECT `id`, `name`, `short_url`, `time`, `del` FROM `pages` WHERE `del` = '$status_delete' ORDER BY `id` DESC LIMIT $start_limit, 10");
  while($d = $db->fetch($q)) {
   $id = $d['id'];
   $name = fxss($d['name']);
   $short_url = $d['short_url'];
   $time = $d['time'];
   $del = $d['del'];
   
   $template .= '
         <div id="admin_page_overflow'.$id.'" class="admin_page_overflow">
          <div class="admin_page_overflow_id">
           #'.$id.'
          </div>
          <div class="admin_page_overflow_name">
           <a href="/page/'.$short_url.'" onclick="nav.go(this); return false">'.$name.'</a>
          </div>
          <div class="admin_page_overflow_date">'.new_time($time).'</div>
          <div class="admin_page_overflow_control">
           '.(($del) ? '<a href="javascript://" onclick="admin_pages._return('.$id.')">Восстановить</a>' : '<a href="/admin/modules/pages/edit.php?short_url='.$short_url.'" onclick="nav.go(this); return false"><div class="icon_edit_s"></div></a><div onclick="admin_pages._delete('.$id.')" class="icons_tab icons_tab_del1"></div>').'
          </div>
         </div>
   ';
  }
  return $template;
 }
 
 public function pedit_info() {
  global $db;
  
  $short_url = $db->escape($_GET['short_url']);
  
  $q = $db->query("SELECT `id`, `name`, `text` FROM `pages` WHERE `short_url` = '$short_url'");
  $d = $db->fetch($q);
  
  return array('id' => $d['id'], 'name' => fxss($d['name']), 'text' => $d['text']);
 }
 
 public function pinfo($short_url = null) {
  global $db, $user_id;
  
  $page_name = $db->escape($short_url);
  
  $q = $db->query("SELECT `name`, `text`, `del` FROM `pages` WHERE `short_url` = '$page_name'");
  $d = $db->fetch($q);
  
  $name = fxss($d['name']);
  $text = pages::html_code(nl2br(fxss($d['text'])));
  $del = $d['del'];
  
  if($del) {
   return array('title' => '404 Not found', 'del' => 1, 'template' => '<div id="site_page_content_none">Страница удалена, либо еще не создана.</div>');
  } elseif($name) {
   return array('response' => 1, 'title' => $name, 'template' => $text);
  } else {
   return array('title' => '404 Not found', 'template' => '<div id="site_page_content_none">Страница удалена, либо еще не создана.</div>');
  }
 }
 
 public function pedit() {
  global $db, $dbName, $ip_address, $browser, $time, $user_logged, $ugroup, $logs;
 
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $id = (int) $_POST['id'];
  $name = $db->escape(trim($_POST['name']));
  $text = $db->escape(trim($_POST['text']));
  
  $q = $db->query("SELECT `id`, `text` FROM `pages` WHERE `id` = '$id'");
  $d = $db->fetch($q);
  
  if(!$d['id']) {
   $json = array('error_text' => 'Ошибка доступа.');
  } elseif(!$name) {
   $json = array('error_text' => 'Пожалуйста, введите название страницы.');
  } elseif(!$text) {
   $json = array('error_text' => 'Пожалуйста, напишите текст.');
  } else {
   if($db->query("UPDATE  `$dbName`.`pages` SET  `name` =  '$name', `text` =  '$text' WHERE  `pages`.`id` = '$id' LIMIT 1 ;")) {
    $logs->site_page_edit($user_id, $id, $d['text']);
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с сервером. Попробуйте позже.');
   }
  }
  return json_encode($json);
 }
 
 public function padd() {
  global $db, $dbName, $ip_address, $browser, $time, $user_logged, $ugroup;
 
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $short_url = $db->escape(trim($_POST['short_url']));
  $name = $db->escape(trim($_POST['name']));
  $text = $db->escape(trim($_POST['text']));
  
  if(!$name) {
   $json = array('error_text' => 'Пожалуйста, введите название страницы.');
  } elseif(!$text) {
   $json = array('error_text' => 'Пожалуйста, напишите текст.');
  } elseif(!$short_url) {
   $json = array('error_text' => 'Пожалуйста, придумайте короткий адрес страницы.');
  } elseif(pages::pcheck_short($short_url)) {
   $json = array('error_text' => 'Этот адрес уже занят.');
  } elseif(!preg_match('/^([-_.@a-zA-Z0-9]){1,100}$/i', $short_url)) {
   $json = array('error_text' => 'Неправильный короткий адрес страницы.');
  } elseif($db->query("INSERT INTO `$dbName`.`pages` (`id`, `uid`, `name`, `short_url`, `time`, `ip`, `browser`, `text`, `del`) VALUES (NULL, '$user_id', '$name', '$short_url', '$time', '$ip_address', '$browser', '$text', '');")) {
   $json = array('success' => 1, 'short_url' => $short_url);
  } else {
   $json = array('error_text' => 'Ошибка соединения с сервером. Попробуйте позже.');
  }
  return json_encode($json);
 }
 
 public function pdel() {
  global $db, $dbName, $ip_address, $browser, $time, $user_logged, $ugroup, $user_id, $logs;
 
  $id = (int) $_GET['id'];
 
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $q = $db->query("SELECT `id`, `del` FROM `pages` WHERE `id` = '$id'");
  $d = $db->fetch($q);
  
  if($d['id']) {
   if($d['del'] == 1) {
    $json = array('error_text' => 'Эта страница уже удалена.');
   } else {
    if($db->query("UPDATE  `$dbName`.`pages` SET  `del` =  '1' WHERE  `pages`.`id` = '$id' LIMIT 1 ;")) {
     $logs->site_page_delete($user_id, $id);
     $json = array('success' => 1);
    } else {
     $json = array('error_text' => 'Ошибка соединения с сервером. Попробуйте позже.');
    }
   }
  } else {
   $json = array('error_text' => 'Ошибка доступа.');
  }
  return json_encode($json);
 }
 
 public function preturn() {
  global $db, $dbName, $ip_address, $browser, $time, $user_logged, $ugroup, $user_id, $logs;
 
  $id = (int) $_GET['id'];
 
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $q = $db->query("SELECT `id`, `del` FROM `pages` WHERE `id` = '$id'");
  $d = $db->fetch($q);
  
  if($d['id']) {
   if(!$d['del']) {
    $json = array('error_text' => 'Ошибка доступа.');
   } else {
    if($db->query("UPDATE  `$dbName`.`pages` SET  `del` =  '0' WHERE  `pages`.`id` = '$id' LIMIT 1 ;")) {
     $logs->site_page_return($user_id, $id);
     $json = array('success' => 1);
    } else {
     $json = array('error_text' => 'Ошибка соединения с сервером. Попробуйте позже.');
    }
   }
  } else {
   $json = array('error_text' => 'Ошибка доступа.');
  }
  return json_encode($json);
 }
 
 public function upload_img() {
  global $user_logged, $ugroup, $root;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $__dir = $root.'/images/uploads/';
  $__format_explode = explode('.', $_FILES['file']['name']);
  $__format = $__format_explode[count($__format_explode) - 1]; // получаем формат изображения
  $__format_type = array('jpeg', 'JPEG', 'jpg', 'JPG', 'png', 'PNG', 'gif', 'GIF', 'bmp', 'BMP');
  $__file_rand_name = rand_str(15);
  $__file_name = $__dir.''.$__file_rand_name.'.jpg';
  
  if(!in_array($__format, $__format_type)) {
   $json = array('error_text' => 'Неизвестный формат изображения.');
  } else {
   if(copy($_FILES['file']['tmp_name'], $__file_name)) {
    $json = array('success' => 1, 'result_big_file' => $__file_rand_name.'.jpg');
   } else {
    $json = array('error_text' => 'Неизвестная ошибка.');
   }
  }
  
  return json_encode($json);
 }
}

$pages = new pages;
?>