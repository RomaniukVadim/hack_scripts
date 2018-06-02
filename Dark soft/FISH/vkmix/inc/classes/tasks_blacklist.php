<?php
class tasks_blacklist { 
 public function list_table_num($del_flag = null, $status_flag = null) { // количество в таблице
  global $db;
  
  $list_section = $_GET['section'];
  
  // управление флагами
  if($list_section == 'deleted') {
   $delete_sql = 1;
   $status_sql = '';
  } elseif($list_section == 'new') {
   $status_sql = 'AND `status` = "1"';
  } elseif($list_section == 'considered') {
   $delete_sql = 0;
   $status_sql = 'AND `status` = "3"';
  } else {
   $delete_sql = 0;
   $status_sql = 'AND `status` = "0" OR `status` = "3"';
  }
  
  $q = $db->query("SELECT `id` FROM `tasks_blacklist` WHERE `del` = '$delete_sql' ".$status_sql."");
  $n = $db->num($q);

  return $n;
 }
 
 public function _new() { // новые заявки на жалобы
  global $db;
  
  $q = $db->query("SELECT `id` FROM `tasks_blacklist` WHERE `status` = '1'");
  $n = $db->num($q);

  return $n; 
 }
 
 public function list_table() { // строим таблицу
  global $db, $ugroup, $site_url;
  
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 10;
  $list_section = $_GET['section'];
  
  // управление флагами
  if($list_section == 'deleted') {
   $delete_sql = 1;
   $status_sql = '';
  } elseif($list_section == 'new') {
   $status_sql = 'AND tasks_blacklist.status = "1"';
  } elseif($list_section == 'considered') {
   $delete_sql = 0;
   $status_sql = 'AND tasks_blacklist.status = "3"';
  } else {
   $delete_sql = 0;
   $status_sql = 'AND tasks_blacklist.status = "0" OR tasks_blacklist.status = "3"';
  }

  if($ugroup != 4 && $ugroup != 3) {
   return '';
   exit;
  }
  
  $q = $db->query("
   SELECT tasks_blacklist.id, tasks_blacklist.url, tasks_blacklist.time, tasks_blacklist.moder_time, tasks_blacklist.last_time, tasks_blacklist.type, tasks_blacklist.browser, tasks_blacklist.ip_address, tasks_blacklist.moder_browser, tasks_blacklist.moder_ip_address, tasks_blacklist.text, tasks_blacklist.del_time, tasks_blacklist.del_browser, tasks_blacklist.del_browser, tasks_blacklist.del_ip_address, users1.uid AS uid_from, users1.uname AS uname_from, users1.ulast_name AS ulast_name_from, users2.uid AS uid_del, users2.uname AS uname_del, users2.ulast_name, users2.ulast_name AS ulast_name_del, users3.uid AS uid_moder, users3.uname AS uname_moder, users3.ulast_name AS ulast_name_moder
   FROM tasks_blacklist
    INNER JOIN users AS users1 ON tasks_blacklist.from_uid = users1.uid
    LEFT JOIN users AS users2 ON tasks_blacklist.del_uid = users2.uid
    LEFT JOIN users AS users3 ON tasks_blacklist.moder_uid = users3.uid
   WHERE tasks_blacklist.del = '$delete_sql' ".$status_sql."
   ORDER BY tasks_blacklist.last_time DESC
   LIMIT $start_limit, 10
  ");
  while($d = $db->assoc($q)) {
   $id = $d['id'];
   $type = $d['type'];
   $url = $d['url'];
   $time = $d['time'];
   $last_time = $d['last_time'];
   $browser = $d['browser'];
   $ip_address = $d['ip_address'];
   $text = nl2br(fxss($d['text']));
   $del_browser = $d['del_browser'];
   $del_ip_address = $d['del_ip_address'];
   $uid_from = $d['uid_from'];
   $uname_from = $d['uname_from'];
   $ulast_name_from = $d['ulast_name_from'];
   $uresult_name_from = !$uname_from ? 'Безымянный' : $uname_from.' '.$ulast_name_from;
   $uid_del = $d['uid_del'];
   $uname_del = $d['uname_del'];
   $ulast_name_del = $d['ulast_name_del'];
   $uid_moder = $d['uid_moder'];
   $uname_moder = $d['uname_moder'];
   $ulast_name_moder = $d['ulast_name_moder'];
   $del_time = $d['del_time'];
   $moder_time = $d['moder_time'];
   $moder_browser = $d['moder_browser'];
   $moder_ip_address = $d['moder_ip_address'];
   $now_blacklist = ($last_time > time() - (0.3 * 60)) ? 'now_blacklist' : '';
   
   if($type == 'group') {
    $url_result = 'public'.str_replace('-', '', $url);
   } elseif($type == 'user') {
    $url_result = 'id'.$url;
   } else {
    $url_result = $type.''.$url;
   }
   
   if($list_section == 'deleted') {
    $template .= '
            <tr id="blacklist_tr'.$id.'">
             <td class="column_content column_content_url">
              <a href="http://vk.com/'.$url_result.'" target="_blank">http://vk.com/'.$url_result.'</a>
              <div class="admin_tasks_blacklist_description">
               '.$text.'
              </div>
             </td>
             <td class="column_content column_content_author">
              '.(($ugroup == 4) ? '<a href="/admin/modules/users/?search='.$site_url.'id'.$uid_from.'" onclick="nav.go(this); return false">'.$uresult_name_from.'</a>' : $uresult_name_from).'
              <div class="admin_tasks_blacklist_author_info">
               <span>IP:</span> '.$ip_address.'
               <br />
               <span>Браузер:</span> '.$browser.'
              </div>
             </td>
             <td class="column_content column_content_author_deleted">
              '.(($ugroup == 4) ? '<a href="/admin/modules/users/?search='.$site_url.'id'.$uid_del.'" onclick="nav.go(this); return false">'.$uname_del.' '.$ulast_name_del.'</a>' : $uname_del.' '.$ulast_name_del).'
              <div class="admin_tasks_blacklist_author_info">
               <span>IP:</span> '.$del_ip_address.'
               <br />
               <span>Браузер:</span> '.$del_browser.'
              </div>
             </td>
             <td class="column_content column_content_date">'.new_time($del_time).'</td>
            </tr>
    ';
   } elseif($list_section == 'new') {
    $template .= '
            <tr id="blacklist_tr'.$id.'">
             <td class="column_content column_content_url">
              <a href="http://vk.com/'.$url_result.'" target="_blank">http://vk.com/'.$url_result.'</a>
              <div class="admin_tasks_blacklist_description">
               '.$text.'
              </div>
             </td>
             <td class="column_content column_content_author">
              '.(($ugroup == 4) ? '<a href="/admin/modules/users/?search='.$site_url.'id'.$uid_from.'" onclick="nav.go(this); return false">'.$uresult_name_from.'</a>' : $uresult_name_from).'
              <div class="admin_tasks_blacklist_author_info">
               <span>IP:</span> '.$ip_address.'
               <br />
               <span>Браузер:</span> '.$browser.'
              </div>
             </td>
             <td class="column_content column_content_date">'.new_time($time).'</td>
             <td class="column_content column_content_control">
              <div onclick="admin_tasks_blacklist._consider('.$id.')" class="blue_button_wrap admin_tasks_blacklist_button_app admin_tasks_blacklist_button_con'.$id.'"><div class="blue_button">Принять</div></div>
              <br />
              <div onclick="admin_tasks_blacklist._reject('.$id.')" class="blue_button_wrap admin_tasks_blacklist_button_rej admin_tasks_blacklist_button_rej'.$id.'"><div class="blue_button">Отклонить</div></div>
             </td>
            </tr>
    ';
   } elseif($list_section == 'considered') {
    $template .= '
            <tr id="blacklist_tr'.$id.'">
             <td class="column_content column_content_url">
              <a href="http://vk.com/'.$url_result.'" target="_blank">http://vk.com/'.$url_result.'</a>
              <div class="admin_tasks_blacklist_description">
               '.$text.'
              </div>
             </td>
             <td class="column_content column_content_author">
              '.(($ugroup == 4) ? '<a href="/admin/modules/users/?search='.$site_url.'id'.$uid_from.'" onclick="nav.go(this); return false">'.$uresult_name_from.'</a>' : $uresult_name_from).'
              <div class="admin_tasks_blacklist_author_info">
               <span>IP:</span> '.$ip_address.'
               <br />
               <span>Браузер:</span> '.$browser.'
              </div>
             </td>
             <td class="column_content column_content_author_deleted">
              '.(($ugroup == 4) ? '<a href="/admin/modules/users/?search='.$site_url.'id'.$uid_moder.'" onclick="nav.go(this); return false">'.$uname_moder.' '.$ulast_name_moder.'</a>' : $uname_moder.' '.$ulast_name_moder).'
              <div class="admin_tasks_blacklist_author_info">
               <span>IP:</span> '.$moder_ip_address.'
               <br />
               <span>Браузер:</span> '.$moder_browser.'
              </div>
             </td>
             <td class="column_content column_content_date">'.new_time($moder_time).'</td>
            </tr>
    ';
   } else {
    $template .= '
            <tr class="'.$now_blacklist.'" id="blacklist_tr'.$id.'">
             <td class="column_content column_content_url">
              <a href="http://vk.com/'.$url_result.'" target="_blank">http://vk.com/'.$url_result.'</a>
              <div class="admin_tasks_blacklist_description">
               '.$text.'
              </div>
             </td>
             <td class="column_content column_content_author">
              '.(($ugroup == 4) ? '<a href="/admin/modules/users/?search='.$site_url.'id'.$uid_from.'" onclick="nav.go(this); return false">'.$uresult_name_from.'</a>' : $uresult_name_from).'
              <div class="admin_tasks_blacklist_author_info">
               <span>IP:</span> '.$ip_address.'
               <br />
               <span>Браузер:</span> '.$browser.'
              </div>
             </td>
             <td class="column_content column_content_date">'.new_time($time).'</td>
             <td class="column_content column_content_control">
              <div onclick="admin_tasks_blacklist._delete('.$id.')" class="blue_button_wrap admin_tasks_blacklist_button_del admin_tasks_blacklist_button_del'.$id.'"><div class="blue_button">Удалить</div></div>
             </td>
            </tr>
    ';
   }
  }
  return $template;
 }
 
 public function list_table_user_num() {
  global $db, $user_id;
  
  $q = $db->query("SELECT `id` FROM `tasks_blacklist` WHERE `from_uid` = '$user_id'");
  $n = $db->num($q);
  
  return $n;

 }
 
 public function list_table_user() { // строим таблицу жалоб пользователя
  global $db, $user_id;
  
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 10;
  
  $q = $db->query("
   SELECT `type`, `url`, `status`, `moder_uid`, `text` FROM `tasks_blacklist`
   WHERE `from_uid` = '$user_id'
   ORDER BY `time` DESC
   LIMIT $start_limit, 10
  ");
  

  while($d = $db->assoc($q)) {
   $type = $d['type'];
   $url = $d['url'];
   $status = $d['status'];
   $moder_uid = $d['moder_uid'];
   $text = nl2br(fxss($d['text']));
   
   if($type == 'group') {
    $url_result = 'public'.str_replace('-', '', $url);
   } elseif($type == 'user') {
    $url_result = 'id'.$url;
   } else {
    $url_result = $type.''.$url;
   }
   
   if($status == 1) {
    $status_result = '<div class="blacklist_status_none">Рассматривается</div>';
   } elseif($status == 2) {
    $status_result = '<div class="blacklist_status_otkloneno">Отклонено</div>';
   } elseif($status == 3 || $moder_uid) {
    $status_result = '<div class="blacklist_status_odobreno">Одобрено</div>';
   } else {
    $status_result = '<div class="blacklist_status_odobreno">Одобрено</div>';
   }

   $template .= '
          <tr>
           <td class="column_content column_content_url_user"><a href="http://vk.com/'.$url_result.'" target="_blank">http://vk.com/'.$url_result.'</a></td>
           <td class="column_content column_content_status_user">'.$status_result.'</td>
          </tr>
    ';
   }
  return $template;
 } 
 
 public function add() {
  global $db, $dbName, $user_id, $tasks, $time, $ip_address, $browser, $vk, $ugroup, $user_logged, $logs;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4 && $ugroup != 3) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $url = $db->escape($_POST['url']);
  $description = $db->escape($_POST['description']);
  
  $api = json_decode($vk->screen_name($url), true);
  $api_type = $api['type'];
  $api_url = $api['url'];
  $api_url_result = $api_type == 'group' ? '-'.$api_url : $api_url;
  
  if(!$vk->url($url)) {
   $json = array('error_text' => 'Проверьте правильность введенной ссылки.');
  } elseif(tasks::check_blacklist($api_type, $api_url_result)) {
   $json = array('error_text' => 'Эта ссылка уже есть в черном списке.');
  } elseif($api_type && $api_url) {
   if($db->query("INSERT INTO `$dbName`.`tasks_blacklist` (`id`, `type`, `url`, `from_uid`, `time`, `browser`, `ip_address`, `text`, `del`, `del_uid`, `del_time`, `del_browser`, `del_ip_address`, `moder_uid`, `moder_time`, `moder_browser`, `moder_ip_address`, `status`, `last_time`) VALUES (NULL, '$api_type', '$api_url_result', '$user_id', '$time', '$browser', '$ip_address', '$description', '', '', '', '', '', '', '', '', '', '', '$time');")) {
    $db->query("UPDATE `$dbName`.`tasks` SET `tblocked` =  '1' WHERE `tasks`.`ttype` = '$api_type' AND `turl` = '$api_url';");
    $logs->addurl_in_blacklist($user_id, '{"url":"'.$url.'", "description":"'.$description.'"}');
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  }
  
  return jdecoder(json_encode($json));
 }
 
 public function delete() {
  global $db, $dbName, $user_id, $tasks, $time, $ip_address, $browser, $vk, $ugroup, $user_logged, $logs;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4 && $ugroup != 3) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $id = (int) $_GET['id'];
  $q = $db->query("SELECT `id`, `del`, `type`, `url`, `status` FROM `tasks_blacklist` WHERE `id` = '$id'");
  $d = $db->fetch($q);
  
  $bid = $d['id'];
  $del = $d['del'];
  $type = $d['type'];
  $url = $d['url'];
  $status = $d['status'];
  
  if(!$bid) {
   $json = array('error_text' => 'Ошибка доступа.');
  } elseif($status == 2) {
   $json = array('error_text' => 'Ошибка доступа.');
  } elseif($del) {
   $json = array('error_text' => 'Эта ссылка уже удалена.');
  } else {
   if($db->query("UPDATE `$dbName`.`tasks_blacklist` SET `del` = '1', `del_time` =  '$time', `del_browser` =  '$browser', `del_ip_address` =  '$ip_address', `del_uid` = '$user_id', `last_time` = '$time', `status` = '0' WHERE  `tasks_blacklist`.`id` = '$id';")) {
    $db->query("UPDATE `$dbName`.`tasks` SET `tblocked` =  '0' WHERE `tasks`.`ttype` = '$type' AND `turl` = '$url';");
    $logs->delurl_in_blacklist($user_id, $id);
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  }
  
  return jdecoder(json_encode($json));
 }
 
 public function reject() {
  global $db, $dbName, $user_id, $tasks, $time, $ip_address, $browser, $vk, $ugroup, $user_logged, $logs;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4 && $ugroup != 3) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $id = (int) $_GET['id'];
  $q = $db->query("SELECT `id`, `from_uid`, `del`, `type`, `url`, `status` FROM `tasks_blacklist` WHERE `id` = '$id'");
  $d = $db->fetch($q);
  
  $bid = $d['id'];
  $from = $d['from_uid'];
  $del = $d['del'];
  $type = $d['type'];
  $url = $d['url'];
  $status = $d['status'];
  
  if(!$bid) {
   $json = array('error_text' => 'Ошибка доступа.');
  } elseif($status == 3) {
   $json = array('error_text' => 'Эта ссылка промодерирована.');
  } elseif($status == 2) {
   $json = array('error_text' => 'Эта ссылка уже отклонена.');
  } elseif($del) {
   $json = array('error_text' => 'Эта ссылка удалена.');
  } else {
   if($db->query("UPDATE `$dbName`.`tasks_blacklist` SET `status` = '2', `last_time` = '$time' WHERE `tasks_blacklist`.`id` = '$id';")) {
    $db->query("UPDATE `$dbName`.`users` SET `blacklist_notif` = blacklist_notif + '1' WHERE `users`.`uid` = '$from'");
    $logs->rejecturl_in_blacklist($user_id, $id);
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  }
  
  return jdecoder(json_encode($json));
 }
 
 public function consider() {
  global $db, $dbName, $user_id, $tasks, $time, $ip_address, $browser, $vk, $ugroup, $user_logged, $logs;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4 && $ugroup != 3) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $id = (int) $_GET['id'];
  $q = $db->query("SELECT `id`, `from_uid`, `del`, `type`, `url`, `status` FROM `tasks_blacklist` WHERE `id` = '$id'");
  $d = $db->fetch($q);
  
  $bid = $d['id'];
  $from = $d['from_uid'];
  $del = $d['del'];
  $type = $d['type'];
  $url = $d['url'];
  $url_result = $type == 'group' ? '-'.$url : $url;
  $status = $d['status'];
  
  if(!$bid) {
   $json = array('error_text' => 'Ошибка доступа.');
  } elseif(tasks::check_blacklist($type, $url_result)) {
   $json = array('error_text' => 'Эта ссылка уже есть в черном списке.');
  } elseif($status == 3) {
   $json = array('error_text' => 'Эта ссылка уже промодерирована.');
  } elseif($status == 2) {
   $json = array('error_text' => 'Эта ссылка отклонена.');
  } elseif($del) {
   $json = array('error_text' => 'Эта ссылка удалена.');
  } else {
   if($db->query("UPDATE `$dbName`.`tasks_blacklist` SET `status` = '3', `moder_time` =  '$time', `moder_browser` =  '$browser', `moder_ip_address` =  '$ip_address', `moder_uid` = '$user_id', `last_time` = '$time' WHERE `tasks_blacklist`.`id` = '$id';")) {
    $db->query("UPDATE `$dbName`.`users` SET `blacklist_notif` = blacklist_notif + '1' WHERE `users`.`uid` = '$from'");
    $db->query("UPDATE `$dbName`.`tasks` SET `tblocked` =  '1' WHERE `tasks`.`ttype` = '$type' AND `turl` = '$url';");
    $logs->considerurl_in_blacklist($user_id, $id);
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  }
  
  return jdecoder(json_encode($json));
 }
 
 public function user_add() {
  global $db, $dbName, $user_id, $tasks, $time, $ip_address, $browser, $vk, $ugroup, $user_logged, $logs, $session;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  $url = $db->escape($_POST['url']);
  $description = $db->escape($_POST['description']);
  $ssid = (int) abs($_POST['ssid']);
  
  $api = json_decode($vk->screen_name($url), true);
  $api_type = $api['type'];
  $api_url = $api['url'];
  $api_url_result = $api_type == 'group' ? '-'.$api_url : $api_url;
  
  if(!$vk->url($url)) {
   $json = array('error_text' => 'Проверьте правильность введенной ссылки.');
  } elseif(tasks::check_blacklist($api_type, $api_url_result)) {
   $json = array('error_text' => 'Эта ссылка уже есть в черном списке.');
  } elseif($session->get('usession') != $ssid) {
   $json = array('error_text' => 'Истек период сессии. Обновите страницу или попробуйте позже.');
  } elseif($api_type && $api_url) {
   if($db->query("INSERT INTO `$dbName`.`tasks_blacklist` (`id`, `type`, `url`, `from_uid`, `time`, `browser`, `ip_address`, `text`, `del`, `del_uid`, `del_time`, `del_browser`, `del_ip_address`, `moder_uid`, `moder_time`, `moder_browser`, `moder_ip_address`, `status`, `last_time`) VALUES (NULL, '$api_type', '$api_url_result', '$user_id', '$time', '$browser', '$ip_address', '$description', '', '', '', '', '', '', '', '', '', '1', '$time');")) {
    $logs->addurl_in_blacklist_user($user_id, '{"url":"'.$url.'", "description":"'.$description.'"}');
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  } else {
   $json = array('error' => 'unknown');
  }
  
  return jdecoder(json_encode($json));
 }
}

$tasks_blacklist = new tasks_blacklist;
?>