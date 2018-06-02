<?php
class blog {
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
 
 public function set_reads($id = null) {
  global $db, $dbName, $redis, $ip_address;
  
  if($redis->hget('blog_id'.$id.'_ip'.$ip_address, 'read')) {
   return false;
  } elseif($db->query("UPDATE `$dbName`.`blog` SET  `reads` = `reads` + 1 WHERE  `blog`.`id` = '$id';")) {
   $redis->hset('blog_id'.$id.'_ip'.$ip_address, 'read', 1);
   $redis->expire('blog_id'.$id.'_ip'.$ip_address, 518400);
  }
 }
 
 public function entry_get() {
  global $db, $user_logged, $ugroup, $site_url;
  
  $id = (int) $_GET['id'];
  
  if($id) {
   $q = $db->query("
    SELECT blog.id, blog.title, blog.text, blog.time, blog.reads, blog.del, users.uid, users.uavatar, users.uname, users.ulast_name
    FROM `blog`
     INNER JOIN `users` ON blog.uid = users.uid
    WHERE blog.id = '$id'
   ");
  } else {
   $q = $db->query("
    SELECT blog.id, blog.title, blog.text, blog.time, blog.reads, blog.del, users.uid, users.uavatar, users.uname, users.ulast_name
    FROM `blog`
     INNER JOIN `users` ON blog.uid = users.uid
    WHERE blog.del = 0
    ORDER BY `id` DESC
    LIMIT 1
   ");
  }
  $d = $db->assoc($q);
  
  $id = $d['id'];
  $title = $d['title'];
  $text = $d['text'];
  $time = $d['time'];
  $reads = $d['reads'];
  $del = $d['del'];
  $authod_id = $d['uid'];
  $name = no_name($d['uname'].' '.$d['ulast_name']);
  $avatar = no_avatar($d['uavatar']);
  
  if($ugroup == 4 && $user_logged) {
   $author_name = '<a href="/admin/modules/users/?search='.$site_url.'id'.$authod_id.'" onclick="nav.go(this); return false;">'.$name.'</a>';
   $avatar_result = '<a href="/admin/modules/users/?search='.$site_url.'id'.$authod_id.'" onclick="nav.go(this); return false;"><img src="'.$avatar.'"></a>';
  } else {
   $author_name = '<a href="/" onclick="nav.go(this); return false;">Команда Piar.Name</a>';
   $avatar_result = '<a href="/" onclick="nav.go(this); return false;"><div id="blog_avatar_author"></div></a>';
  }
  
  blog::set_reads($id);
  
  $template = '
              <div id="blog_entry_overflow">
               <div id="blog_entry_overflow_avatar">
                '.$avatar_result.'
               </div>
               <div id="blog_entry_overflow_other">
                <div id="blog_entry_overflow_other_bg_title">
                 <div id="blog_entry_overflow_other_title">
                  <a href="/blog?id='.$id.'" onclick="nav.go(this); return false">'.fxss($title).'</a>
                 </div>
                 <div id="blog_entry_overflow_other_date">
                  '.$author_name.'
                  <span id="blog_entry_overflow_other_date_t">'.new_time($time).'</span>
                 </div>
                </div>
                <div id="blog_entry_overflow_other_text">
                 '.nl2br(blog::html_code(htmlspecialchars($text))).'
                </div>
               </div>
              </div>
              <div id="blog_menu_id_hr"></div>
              <span id="blog_menu_id_hr_reads"><b>'.$reads.'</b> '.declOfNum($reads, array('просмотр', 'просмотра', 'просмотров')).' '.($ugroup == 4 && $user_logged ? '| <a href="/blog/edit?id='.$id.'" onclick="nav.go(this); return false">Редактировать</a>' : '').' <span id="span_blog_id_del">'.($ugroup == 4 && !$del && $user_logged ? '| <a href="javascript://" onclick="admin_blog._delete('.$id.')">Удалить</a>' : '').'</span></span>
  ';
  return array('title' => $title, 'template' => $template, 'del' => $del);
 }
 
 public function entry_menu_all() {
  global $db, $ugroup, $page_name;
  
  $q = $db->query("SELECT `id`, `title` FROM `blog` WHERE `del` = 0 ORDER BY `id` DESC LIMIT 8");
  $i = 0;
  while($d = $db->fetch($q)) {
   $id = $d['id'];
   $title = $d['title'];
   
   $template .= '
               <a'.(($_GET['id'] == $id || !$_GET['id'] && $i == 0 && $page_name != 'blog_archive') ? ' class="active"' : '').' href="/blog?id='.$id.'" onclick="nav.go(this); return false">'.fxss($title).'</a>
   ';
   $i++;
  }
  return $template;
 }
 
 public function edit_info() {
  global $db;
  
  $id = (int) $_GET['id'];
  
  $q = $db->query("SELECT `id`, `title`, `text`, `del` FROM `blog` WHERE `id` = '$id'");
  $d = $db->fetch($q);
  
  return array('id' => $d['id'], 'title' => $d['title'], 'text' => $d['text'], 'del' => $d['del']);
 }
 
 public function add() {
  global $db, $dbName, $user_id, $ugroup, $user_logged, $time, $redis;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $title = $db->escape(trim($_POST['title']));
  $text = $db->escape(trim($_POST['text']));
  $check_allow_admin = $_POST['check_allow_admin'] ? 1 : 0;
  $check_left_menu = $_POST['check_left_menu'] ? 1 : 0;
  
  if(!$title) {
   $json = array('error_text' => 'Пожалуйста, введите заголовок новости.');
  } elseif(!$text) {
   $json = array('error_text' => 'Пожалуйста, введите текст новости.');
  } elseif($db->query("INSERT INTO `$dbName`.`blog` (`id`, `uid`, `title`, `text`, `time`, `del`, `reads`) VALUES (NULL, '$user_id', '$title', '$text', '$time', '$check_allow_admin', '0');")) {
   $last_id = $db->insert_id();
   if($check_left_menu) {
    $redis->hset('blog_menu_left', 'id', $last_id);
    $redis->hset('blog_menu_left', 'title', $title);
   }
   $json = array('success' => 1, 'id' => $last_id);
  } else {
   $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
  }
  
  return json_encode($json);
 }
 
 public function edit() {
  global $db, $dbName, $user_id, $ugroup, $user_logged, $time, $redis;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $id = (int) $_POST['id'];
  
  $title = $db->escape(trim($_POST['title']));
  $text = $db->escape(trim($_POST['text']));
  $check_allow_admin = $_POST['check_allow_admin'] ? 1 : 0;
  $check_left_menu = $_POST['check_left_menu'] ? 1 : 0;
  
  if(!$id) {
   $json = array('error_text' => 'Неизвестная ошибка.');
  } elseif(!$title) {
   $json = array('error_text' => 'Пожалуйста, введите заголовок новости.');
  } elseif(!$text) {
   $json = array('error_text' => 'Пожалуйста, введите текст новости.');
  } elseif($db->query("UPDATE `$dbName`.`blog` SET `title` = '$title', `text` = '$text', `del` = '$check_allow_admin' WHERE `blog`.`id` = '$id'")) {
   if($check_left_menu) {
    $redis->hset('blog_menu_left', 'id', $id);
    $redis->hset('blog_menu_left', 'title', stripslashes($title));
   } else {
    if($redis->hget('blog_menu_left', 'id') == $id) {
     $redis->hdel('blog_menu_left', 'id');
     $redis->hdel('blog_menu_left', 'title');
    }
   }
   $json = array('success' => 1);
  } else {
   $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
  }
  
  return json_encode($json);
 }
 
 public function delete() {
  global $db, $dbName, $user_id, $ugroup, $user_logged;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $id = (int) $_GET['id'];
  
  if(!$id) {
   $json = array('error_text' => 'Неизвестная ошибка.');
  } elseif($db->query("UPDATE  `$dbName`.`blog` SET  `del` =  '1' WHERE  `blog`.`id` = '$id';")) {
   $json = array('success' => 1);
  } else {
   $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
  }
  
  return json_encode($json);
 }
 
 public function archive_num() {
  global $db;
  
  $q = $db->query("SELECT `id` FROM `blog` WHERE `del` = 0");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function archive_all() {
  global $db;
  
  $q = $db->query("SELECT `id`, `title`, `time` FROM `blog` WHERE `del` = 0 ORDER BY `id` DESC");
  while($d = $db->fetch($q)) {
  
   $template .= '
              <div class="archive_blog_overflow">
               <div class="archive_blog_overflow_title">
                <a href="/blog?id='.$d['id'].'" onclick="nav.go(this); return false">'.fxss($d['title']).'</a>
               </div>
               <div class="archive_blog_overflow_footer">
                <a href="/" onclick="nav.go(this); return false">Команда MontyTool</a> | '.new_time($d['time']).'
               </div>
              </div>
   ';
  }
  return $template;
 }
}

$blog = new blog;
?>