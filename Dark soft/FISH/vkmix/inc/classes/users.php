<?php
class user {
 public function check_login($login = null) {
  global $db;
  $query = $db->query("SELECT `ulogin` FROM `users` WHERE `ulogin` = '$login'");
  $num = $db->num($query);
  // в случае, если логин существует, возвращаем 1
  return $num ? 1 : 0;
 }
 
 public function check_email($email = null) {
  global $db;
  $query = $db->query("SELECT `uemail` FROM `users` WHERE `uemail` = '$email'");
  $num = $db->num($query);
  // в случае, если email существует, возвращаем 1
  return $num ? 1 : 0;
 }
 
 public function check_vk_id($vk_id = null) {
  global $db;
  $query = $db->query("SELECT `uvk_id` FROM `users` WHERE `uvk_id` = '$vk_id'");
  $num = $db->num($query);
  // в случае, если вк ид существует, возвращаем 1
  return $num ? 1 : 0;
 }
 
 public function get_user_id($login) {
  global $db;
  $query = $db->query("SELECT `uid` FROM `users` WHERE `ulogin` = '$login'");
  $data = $db->fetch($query);
  // в случае, если id существует, возвращаем его
  return $data['uid'] ? $data['uid'] : '';
 }
 
 public function auth_cookies($user_id, $hash) {
  // куки авторизации
  setCookie('user_id', $user_id, time() + 60 * 60 * 24 * 365, '/');
  setCookie('user_hash', $hash, time() + 60 * 60 * 24 * 365, '/'); 
 }
 
 public function login($params) {
  global $db, $logs;
  $login = $db->escape(trim($params['login']));
  $password = $db->escape(trim(md5(md5($params['password']))));
  
  $query = $db->query("SELECT `uid`, `upassword`, `uhash` FROM `users` WHERE `ulogin` = '$login'");
  $data = $db->fetch($query);
  
  $user_id = $data['uid'];
  $hash = $data['uhash'];
  
  if($db->error()) {
   $json = array('error_text' => 'Ошибка соединения с базой данных.');
  } elseif($password == $data['upassword']) {
   user::auth_cookies($user_id, $hash);
   $logs->user_login($user_id);
   $json = array('response' => 1);
  } else {
   $json = array('error_text' => 'Неправильный логин или пароль.');
  }
  
  return json_encode($json);
 }
 
 public function reemail() {
  global $uemail, $ureg_time, $user_login, $site_url, $user_logged, $user_id;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  $unique_key = md5("$ureg_time+$user_login+$uemail");
  // отправляем на почту
  $email_title = 'Регистрация на Piar.name завершена!';
  $email_text = '
   Здравствуйте, <b>'.$user_login.'</b>!
   <br /> <br />
   Чтобы подтвердить регистрацию, перейдите по ссылке <a href="'.$site_url.'activate?key='.$unique_key.'&uid='.$user_id.'">'.$site_url.'activate?key='.$unique_key.'&uid='.$user_id.'</a>
   <br /> <br />
   С уважением, <br />
   <a href="'.$site_url.'">Команда Piar.name</a>.
  ';
  send_email($uemail, $email_title, $email_text);
  return json_encode(array('success' => 1));
 }
 
 public function reg($params) {
  global $db, $dbName, $ip_address, $browser, $ref_points, $logs, $session, $support, $site_url;
  $time = time();
  $login = $db->escape(trim($params['login']));
  $email = $db->escape(trim($params['email']));
  $password = $db->escape(trim($params['password']));
  $password_md5 = md5(md5($password));
  $ref = (int) $params['ref'];
  $captcha_code = $db->escape(trim($params['captcha_code']));
  $captcha_key = (int) abs($_POST['ucaptcha_key']);
  $hash = md5("$login+$password+$time");
  $unique_key = md5("$time+$login+$email");
  
  if(!preg_match('/^([@a-zA-Z0-9]){1,30}$/i', $login)) {
   $json = array('error_text' => 'Неправильный <b>логин</b>. Поле «логин» может содержать только латинские символы или цифры и не превышать 30 символов.');
  } elseif(user::check_login($login)) {
   $json = array('error_text' => 'Такой <b>логин уже занят</b>. Пожалуйста, придумайте другой логин, так как этот используется другим участником сайта Piar.name.');
  } elseif(mb_strlen($password, 'UTF-8') < 6) {
   $json = array('error_text' => 'Слишком <b>короткий пароль</b>. Пароль должен состоять не менее, чем из 6 символов.');
  } elseif(!preg_match('/^([a-zA-Z0-9]){6,32}$/i', $password)) {
   $json = array('error_text' => 'Неправильный <b>пароль</b>. Поле «пароль» может содержать только латинские символы или цифры и не превышать 32 символа.');
  } elseif(!preg_match("/^[a-zA-Z0-9_\.\-]+@([a-zA-Z0-9\-]+\.)+[a-zA-Z]{2,6}$/i", $email)) {
   $json = array('error_text' => 'Неправильный <b>email</b>. Поле «e-mail» имеет неправильный формат.');
  } elseif(user::check_email($email)) {
   $json = array('error_text' => 'Такой <b>e-mail уже зарегистрирован</b>  в системе.');
  } elseif(!$captcha_code) {
   $json = array('error_text' => 'Неверно введен код безопасности.');
  } elseif(mb_strtolower($session->get('captcha_code', $captcha_key), 'UTF-8') != mb_strtolower($captcha_code, 'UTF-8')) {
   $json = array('error_text' => 'Неверно введен код безопасности.');
  } else {
   $session->delete('captcha_code', $captcha_key);
   // регистрируем пользователя в таблицу users
   $query = "INSERT INTO  `$dbName`.`users` (
    `uid` ,
    `ulogin` ,
    `upassword` ,
    `uemail` ,
    `temp_password` ,
    `uemail_activated` ,
    `uemail_helper` ,
    `uvk_id` ,
    `uname` ,
    `ulast_name` ,
    `uip_address` ,
    `ureg_time` ,
    `ulast_time` ,
    `ugender` ,
    `ugroup` ,
    `upoints` ,
    `uban_type` ,
    `uban_time` ,
    `uban_text` ,
    `uhash` ,
    `blacklist_notif` ,
    `uavatar` ,
    `uagent_id` ,
    `uagent_avatar` ,
    `uagent_rate_plus` ,
    `uagent_rate_minus` ,
    `udel` ,
    `city` ,
    `ubrowser`,
    `ubyear`,
    `complaints`,
    `account`,
    `vk_time_update`,
    `top`
    )
    VALUES (
    NULL ,  '$login',  '$password_md5',  '$email',  '0',  '0',  '0',  '0',  '',  '',  '$ip_address',  '$time',  '$time',  '0',  '0',  '0',  '0',  '0',  '',  '$hash',  '',  '',  '',  '',  '',  '',  '',  '',  '$browser', '', '', '', '0', '0'
    );";
   if($db->query($query)) {
    $user_id = $db->insert_id(); // определяем id нового пользователя
    user::auth_cookies($user_id, $hash); // записываем куки для авторизации
    $logs->user_login($user_id);
    // отправляем на почту
    $email_title = 'Регистрация на Piar.name завершена!';
    $email_text = '
     Здравствуйте, <b>'.$login.'</b>!
     <br /> <br />
     Чтобы подтвердить регистрацию, перейдите по ссылке <a href="'.$site_url.'activate?key='.$unique_key.'&uid='.$user_id.'">'.$site_url.'activate?key='.$unique_key.'&uid='.$user_id.'</a>
     <br /> <br />
     С уважением, <br />
     <a href="'.$site_url.'">Команда Piar.name</a>.
    ';
    send_email($email, $email_title, $email_text);
    // постим в поддержку
    $reg_title = 'Регистрация завершена!';
    $reg_message = '[site_demo]';
    $support->send_notif($user_id, $reg_title, $reg_message); // постим в поддержку
    // если реферал
    if($ref) {
     $query_ref = $db->query("SELECT `uid` FROM `users` WHERE `uid` = '$ref'");
     $data_ref = $db->fetch($query_ref);
     if($data_ref['uid']) {
      // проверка IP
      $query_ref_ip = $db->query("SELECT `id` FROM `ref` WHERE `to` = '$ref' AND `ip` = '$ip_address'");
      $num_ref_ip = $db->num($query_ref_ip);
      if(!$num_ref_ip) {
       $logs->add_ref($user_id, $ref, $ref, $ref_points);
       $db->query("UPDATE `$dbName`.`users` SET  `upoints` =  upoints + '$ref_points' WHERE  `users`.`uid` = '$ref' LIMIT 1 ;");
       $db->query("INSERT INTO `$dbName`.`ref` (`id`, `from`, `to`, `ip`, `browser`, `time`, `points`) VALUES (NULL, '$user_id', '$ref', '$ip_address', '$browser', '$time', '$ref_points');");
      }
     }
    }
    $json = array('response' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с сервером. Попробуйте позже.');
   }
  }
  
  return json_encode($json);
 }
 
 public function change_password() {
  global $db, $dbName, $user_id, $user_login, $upassword, $user_logged, $logs, $session;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  $time = time();
  $old_password = $db->escape($_POST['old_password']);
  $new_password = $db->escape($_POST['new_password']);
  $new_password2 = $db->escape($_POST['new_password2']);
  $ssid = (int) abs($_POST['ssid']);
  $md5_new_password = md5(md5($new_password));
  $new_hash = md5("$user_login+$new_password+$time");
  
  if(md5(md5($old_password)) != $upassword) {
   $json = array('error_text' => 'Пароль не изменён, так как прежний пароль введён неправильно.');
  } elseif(mb_strlen($new_password, 'UTF-8') < 6) {
   $json = array('error_text' => 'Пароль должен состоять не менее, чем из 6 символов.');
  } elseif(!preg_match('/^([a-zA-Z0-9]){6,32}$/i', $new_password)) {
   $json = array('error_title' => 'Неправильный логин.', 'error_text' => 'Пароль должен содержать только латинские символы или цифры и не превышать 32 символа.');
  } elseif($new_password != $new_password2) {
   $json = array('error_text' => 'Пароль не изменён, так как новый пароль повторен неправильно.');
  } elseif($session->get('usession') != $ssid) {
    $json = array('error_text' => 'Истек период сессии. Обновите страницу или попробуйте позже.');
   } else {
   if($db->query("UPDATE  `$dbName`.`users` SET  `upassword` =  '$md5_new_password', `uhash` = '$new_hash' WHERE  `users`.`uid` = '$user_id' LIMIT 1 ;")) {
    user::auth_cookies($user_id, $new_hash);
    $logs->change_password($user_id, $user_id, '{"old_password":"'.$old_password.'", "new_password":"'.$new_password.'"}');
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  }
  return jdecoder(json_encode($json));
 }
 
 public function change_login() {
  global $db, $dbName, $user_login, $user_id, $user_logged, $logs, $session;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  $login = $db->escape($_POST['login']);
  $ssid = (int) abs($_POST['ssid']);
  
  if(mb_strlen($login, 'UTF-8') < 1) {
   $json = array('error_text' => 'Слишком короткий логин.');
  } elseif(!preg_match('/^([@a-zA-Z0-9]){1,30}$/i', $login)) {
   $json = array('error_text' => 'Логин должен содержать только латинские символы или цифры и не превышать 30 символов.');
  } elseif($user_login == $login) {
   $json = array('error_text' => 'Не совершайте глупость, пожалуйста.');
  } elseif(user::check_login($login)) {
   $json = array('error_text' => 'Такой логин уже занят.');
  } elseif($session->get('usession') != $ssid) {
   $json = array('error_text' => 'Истек период сессии. Обновите страницу или попробуйте позже.');
  } else {
   if($db->query("UPDATE  `$dbName`.`users` SET  `ulogin` =  '$login' WHERE  `users`.`uid` = '$user_id' LIMIT 1 ;")) {
    $logs->change_login($user_id, $user_id, '{"old_login":"'.$user_login.'", "new_login":"'.$login.'"}');
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  }
  return jdecoder(json_encode($json));
 }
 
 public function delete_account() {
  global $db, $dbName, $user_id, $udel, $session, $logs;
  
  $time = time();
  $ssid = (int) abs($_GET['ssid']);
  
  if($udel) {
   $type = 'return';
   $query = "UPDATE `$dbName`.`users` SET  `udel` =  '0' WHERE  `users`.`uid` = '$user_id' LIMIT 1 ;";
  } else {
   $type = 'del';
   $query = "UPDATE `$dbName`.`users` SET  `udel` =  '$time' WHERE  `users`.`uid` = '$user_id' LIMIT 1 ;";
  }
  
  if($session->get('usession') != $ssid) {
   $json = array('error_text' => 'Истек период сессии. Обновите страницу или попробуйте позже.');
  } elseif($db->query($query)) {
   if($udel) {
    $logs->return_account($user_id, $user_id);
   } else {
    $logs->delete_account($user_id, $user_id);
   }
   $json = array('success' => 1, 'type' => $type);
  } else {
   $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
  }
  return jdecoder(json_encode($json));
 }
 
 public function admin_users_list_num() {
  global $db, $online_limit, $vk;
  
  $section = $db->escape($_GET['section']);
  $search = $db->escape($_GET['search']);
  
  if($vk->url($search) == 1) {
   $search_end = json_decode($vk->explode($search), true);
   $user_get_info_vk = json_decode($vk->user_info($search_end['url']), true);
   $user_get_info_vk_id = (int) $user_get_info_vk['id'];
   if($user_get_info_vk_id) {
    $search_sql = "AND `uvk_id` = '$user_get_info_vk_id'";
   } else {
    $search_sql = "";
   }
  } elseif(preg_match('/^https?:\/\/Piar.name/', $search) || preg_match('/^Piar.name/', $search)) {
   $search_explode = explode('id', $search);
   $search_explode_id = (int) $search_explode[1];
   if($search_explode_id) {
    $search_sql = "AND `uid` = '$search_explode_id'";
   } else {
    $search_sql = "";
   }
  } else {
   $search_sql = $search ? "AND `ulogin` = '$search'" : '';
  }
  
  if($section == 'online') {
   $section_sql = "AND `ulast_time` >= '$online_limit'";
  } elseif($section == 'admin') {
   $section_sql = "AND `ugroup` = 4";
  } elseif($section == 'moder') {
   $section_sql = "AND `ugroup` = 3";
  } elseif($section == 'agent') {
   $section_sql = "AND `ugroup` = 5";
  } else {
   $section_sql = "";
  }
  
  $q = $db->query("
   SELECT `uid` FROM `users`
   WHERE `uid` > 0 ".$section_sql." ".$search_sql."
  ");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function admin_users_list() {
  global $db, $noavatar, $online_limit, $vk;
  
  $sort = $db->escape($_GET['sort']);
  $section = $db->escape($_GET['section']);
  $search = $db->escape($_GET['search']);
  
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 10;
  
  if($vk->url($search) == 1) {
   $search_end = json_decode($vk->explode($search), true);
   $user_get_info_vk = json_decode($vk->user_info($search_end['url']), true);
   $user_get_info_vk_id = (int) $user_get_info_vk['id'];
   if($user_get_info_vk_id) {
    $search_sql = "AND `uvk_id` = '$user_get_info_vk_id'";
   } else {
    $search_sql = "";
   }
  } elseif(preg_match('/^https?:\/\/Piar.name/', $search) || preg_match('/^Piar.name/', $search)) {
   $search_explode = explode('id', $search);
   $search_explode_id = (int) $search_explode[1];
   if($search_explode_id) {
    $search_sql = "AND `uid` = '$search_explode_id'";
   } else {
    $search_sql = "";
   }
  } else {
   $search_sql = $search ? "AND `ulogin` = '$search'" : '';
  }
  
  if($section == 'online') {
   $section_sql = "AND `ulast_time` >= '$online_limit'";
  } elseif($section == 'admin') {
   $section_sql = "AND `ugroup` = 4";
  } elseif($section == 'moder') {
   $section_sql = "AND `ugroup` = 3";
  } elseif($section == 'agent') {
   $section_sql = "AND `ugroup` = 5";
  } else {
   $section_sql = "";
  }
  
  if($sort == 'asc') {
   $sort_sql = "ORDER BY `uid` ASC";
  } elseif($sort == 'desc') {
   $sort_sql = "ORDER BY `uid` DESC";
  } elseif($sort == 'max_points') {
   $sort_sql = "ORDER BY `upoints` DESC";
  } elseif($sort == 'min_points') {
   $sort_sql = "ORDER BY `upoints` ASC";
  } else {
   $sort_sql = "ORDER BY `uid` ASC";
  }
  
  $q = $db->query("
   SELECT `uid`, `ulogin`, `uvk_id`, `uname`, `ulast_name`, `ureg_time`, `ulast_time`, `ugroup`, `upoints`, `uavatar`, `uagent_id`, `uip_address`, `uemail`, `uemail_activated`, `uban_type`, `complaints`, `blacklist_notif`
   FROM `users` WHERE `uid` > 0 ".$section_sql." ".$search_sql."
   ".$sort_sql."
   LIMIT $start_limit, 10
  ");
   
  while($d = $db->fetch($q)) {
   $uid = $d['uid'];
   $ulogin = $d['ulogin'];
   $uemail = $d['uemail'];
   $uemail_activated = $d['uemail_activated'];
   $uvk_id = $d['uvk_id'];
   $uvk_id_page = $uvk_id ? '<a href="http://vk.com/id'.$uvk_id.'" target="_blank">http://vk.com/id'.$uvk_id.'</a>' : 'не прикреплена';
   $uname = $d['uname'];
   $ulast_name = $d['ulast_name'];
   $ureg_time = $d['ureg_time'];
   $ulast_time = $d['ulast_time'];
   $ufull_name = $uname ? $uname.' '.$ulast_name : 'Безымянный';
   $ugroup = $d['ugroup'];
   $upoints = $d['upoints'];
   $uavatar = $d['uavatar'] ? $d['uavatar'] : $noavatar;
   $uip_address = $d['uip_address'];
   $uagent_id = $d['uagent_id'];
   $uban_type = $d['uban_type'];
   $ucomplaints = $d['complaints'];
   $ublacklist_notif = $d['blacklist_notif'];
   
   
   
  $wr = $db->query("SELECT `to` FROM `ref` WHERE `from` = '$uid'");
  $wrf = $db->fetch($wr);
 $u_ref = $wrf['to'];
 if ($u_ref) {
 $r_u = $db->query("SELECT `uvk_id`, `uname`, `ulast_name` FROM `users` WHERE `uid` = '$u_ref'");
 $r_user = $db->fetch($r_u);
 $user_ref=  '<a href="http://vk.com/id'.$r_user['uvk_id'].'" target="_blank">'.$r_user['uname'].' '.$r_user['ulast_name'].'</a>';
 } else  $user_ref='Ни кто';
 
 $wr2 = $db->query("SELECT `from` FROM `ref` WHERE `to` = '$uid'");
 $ref_user= $db->num($wr2);
 
 
   if($ugroup == 4) {
    $ugroup_result = 'Администратор';
   } elseif($ugroup == 3) {
    $ugroup_result = 'Модератор';
   } elseif($ugroup == 5) {
    $ugroup_result = 'Агент поддержки #'.$uagent_id;
   } else {
    $ugroup_result = 'Пользователь';
   }
   
   $template .= '
         <div class="admin_users_user_mini">
          <div class="admin_users_user_mini_avatar"><img src="'.$uavatar.'"></div>
          <div class="admin_users_user_mini_info">
           <a><b>'.$ufull_name.'</b></a>
           <br /> 
           <b>'.$ugroup_result.'</b>
           <br />
           <div class="admin_users_user_mini_other_info">
            <span class="overflow_field_user_mini_label">ID:</span>
            <span class="overflow_field_user_mini_text">'.$uid.'</span>
            <br />
            <span class="overflow_field_user_mini_label">Логин:</span>
            <span class="overflow_field_user_mini_text">'.$ulogin.'</span>
            <br />
            <span class="overflow_field_user_mini_label">E-mail:</span>
            <span class="overflow_field_user_mini_text">'.$uemail.' — '.($uemail_activated ? 'активирован' : '<b>не</b> активирован').'</span>
            <br />
            <span class="overflow_field_user_mini_label">Баллы:</span>
            <span class="overflow_field_user_mini_text"><b>'.$upoints.'</b> '.declOfNum(abs($upoints), array('балл', 'балла', 'баллов')).'</span>
            <br />
            <span class="overflow_field_user_mini_label">Страница ВКонтакте:</span>
            <span class="overflow_field_user_mini_text">'.$uvk_id_page.'</span>
            <br />
            <span class="overflow_field_user_mini_label">IP:</span>
            <span class="overflow_field_user_mini_text">'.$uip_address.'</span>
            <br />
            <span class="overflow_field_user_mini_label">Регистрация:</span>
            <span class="overflow_field_user_mini_text">'.new_time($ureg_time).'</span>
            <br />
            <span class="overflow_field_user_mini_label">Последний вход:</span>
            <span class="overflow_field_user_mini_text">'.new_time($ulast_time).'</span>
            <br />
            <span class="overflow_field_user_mini_label">Непрочитанные штрафы:</span>
            <span class="overflow_field_user_mini_text">'.$ucomplaints.'</span>
            <br />
            <span class="overflow_field_user_mini_label">Непрочитанные жалобы:</span>
            <span class="overflow_field_user_mini_text">'.$ublacklist_notif.'</span>
			 <br />
			<span class="overflow_field_user_mini_label">Юзера пригласил:</span>
            <span class="overflow_field_user_mini_text">'.$user_ref.'</span>
			<br />
			<span class="overflow_field_user_mini_label">Рефералов:</span>
            <span class="overflow_field_user_mini_text">'.$ref_user.'</span>
            <br />
            <span class="overflow_field_user_mini_label">Статус аккаунта:</span>
            <span id="overflow_field_user_mini_text'.$uid.'" class="overflow_field_user_mini_text">'.($uban_type ? '<div class="user_list_ban_status">заблокирован</div>' : '<div class="user_list_ok_status">активен</div>').'</span>
           </div>
          </div>
          <div class="admin_users_user_mini_control">
           <div class="admin_users_user_mini_control_menu">
            <a href="javascript://" onclick="admin_users._edit_balance('.$uid.')">Изменить баланс / История</a>
            <a href="javascript://" onclick="admin_users._edit_info('.$uid.')">Редактировать информацию</a>
            <hr />
            <span id="user_blocked_span'.$uid.'">'.($uban_type ? '<a href="javascript://" onclick="admin_users._unblocked('.$uid.')">Разблокировать аккаунт</a>' : '<a href="javascript://" onclick="admin_users._blocked('.$uid.')">Заблокировать аккаунт</a>').'</span>
            <a href="javascript://" onclick="admin_users._blocked_history('.$uid.')">История блокировок</a>
            <hr />
            <a href="javascript://" onclick="admin_users._history_login('.$uid.')">История авторизаций</a>
            <hr />
            <a href="/tasks?search=uid:'.$uid.'" onclick="nav.go(this); return false;">Созданные задания</a>
            <a href="/support/questions?uid='.$uid.'" onclick="nav.go(this); return false;">Вопросы в поддержке</a>
            <hr />
            <a href="/stats?id='.$uid.'" onclick="nav.go(this); return false;">Статистика заданий</a>
			<hr />
            <a href="/usref?id='.$uid.'" onclick="nav.go(this); return false;">Рефералы пользователя</a>
           </div>
          </div>
         </div>
   ';
  }
  return $template;
 }
 
 public function history_balance_num($uid = null) {
  global $db;
  
  $q = $db->query("
   SELECT `lid` FROM `logs`
   WHERE (`lmodule` = 4 AND `lmodule_type` = 1 OR `lmodule` = 4 AND `lmodule_type` = 3 OR `lmodule` = 4 AND `lmodule_type` = 2 OR `lmodule` = 4 AND `lmodule_type` = 6 OR `lmodule` = 1 AND `lmodule_type` = 5 OR `lmodule` = 1 AND `lmodule_type` = 6 OR `lmodule` = 1 AND `lmodule_type` = 9 OR `lmodule` = 8 AND `lmodule_type` = 1 OR `lmodule` = 8 AND `lmodule_type` = 2 OR `lmodule` = 9 AND `lmodule_type` = 1 OR `lmodule` = 9 AND `lmodule_type` = 2 OR `lmodule` = 9 AND `lmodule_type` = 3) AND `lto` = '$uid' AND `lpoints` > 0
  ");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function history_balance() {
  global $db, $ugroup, $user_logged, $user_id;
  
  if(!$user_logged) {
   return 'login';
   exit;
  }
  
  if($ugroup != 4) {
   return 'Access Denied';
   exit;
  }
  
  $uid = (int) $_GET['uid'];
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 10;
  
  $q = $db->query("
   SELECT logs.lid, logs.lfrom, logs.lto, logs.lmodule, logs.lmodule_type, logs.lpoints, logs.lbrowser, logs.ltime, logs.lip_address, logs.lmid, logs.ltext, users1.uname as uname1, users1.ulast_name as ulast_name1, users2.uname as uname2, users2.ulast_name as ulast_name2 FROM `logs`
    INNER JOIN users users1 ON logs.lfrom = users1.uid
    LEFT JOIN users users2 ON logs.lto = users2.uid
   WHERE (logs.lmodule = 4 AND logs.lmodule_type = 1 OR logs.lmodule = 4 AND logs.lmodule_type = 3 OR logs.lmodule = 4 AND logs.lmodule_type = 2 OR logs.lmodule = 4 AND logs.lmodule_type = 6 OR logs.lmodule = 1 AND logs.lmodule_type = 5 OR logs.lmodule = 1 AND logs.lmodule_type = 6 OR logs.lmodule = 1 AND logs.lmodule_type = 9 OR logs.lmodule = 8 AND logs.lmodule_type = 1 OR logs.lmodule = 8 AND logs.lmodule_type = 2 OR logs.lmodule = 9 AND logs.lmodule_type = 1 OR logs.lmodule = 9 AND logs.lmodule_type = 2 OR logs.lmodule = 9 AND logs.lmodule_type = 3) AND logs.lto = '$uid' AND logs.lpoints > 0
   ORDER BY logs.lid DESC
   LIMIT $start_limit, 10
  ");
  while($d = $db->assoc($q)) {
   $logs_lid = $d['lid'];
   $logs_lfrom = $d['lfrom'];
   $logs_lto = $d['lto'];
   $logs_lmodule = $d['lmodule'];
   $logs_lmodule_type = $d['lmodule_type'];
   $logs_lpoints = $d['lpoints'];
   $logs_lbrowser = $d['lbrowser'];
   $logs_ltime = $d['ltime'];
   $logs_lip_address = $d['lip_address'];
   $logs_lmid = $d['lmid'];
   $logs_ltext = $d['ltext'];
   $users_uname1 = $d['uname1'];
   $users_ulast_name1 = $d['ulast_name1'];
   $users_uname2 = $d['uname2'];
   $users_ulast_name2 = $d['ulast_name2'];
   $users1_fullname = $users_uname1 ? $users_uname1.' '.$users_ulast_name1 : 'Безымянный';
   $users2_fullname = $users_uname2 ? $users_uname2.' '.$users_ulast_name2 : 'Безымянный';
   
   if($logs_lmodule == 4 && $logs_lmodule_type == 1) {
    $template .= '
     <div class="settings_balance_admin_users_history">
      <div class="settings_balance_admin_users_history_title"><a href="/admin/modules/users/?search=http://Piar.name/id'.$logs_lfrom.'" target="_blank"><b>'.$users2_fullname.'</b></a> создал новое <a href="/tasks?search=tid:'.$logs_lmid.'" target="_blank"><u>задание</u></a></div>
      <div class="settings_balance_admin_users_history_text">
       <span class="settings_balance_admin_users_history_label">Баллы:</span>
       <span class="settings_balance_admin_users_history_field"><b class="settings_balance_admin_users_history_points_minus">-'.$logs_lpoints.'</b></span>
       <br />
       <span class="settings_balance_admin_users_history_label">IP:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lip_address.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Браузер:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lbrowser.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Время:</span>
       <span class="settings_balance_admin_users_history_field">'.new_time($logs_ltime).'</span>
      </div>
     </div>
    ';
   } elseif($logs_lmodule == 4 && $logs_lmodule_type == 3) {
    $template .= '
     <div class="settings_balance_admin_users_history">
      <div class="settings_balance_admin_users_history_title"><a href="/admin/modules/users/?search=http://Piar.name/id'.$logs_lfrom.'" target="_blank"><b>'.$users2_fullname.'</b></a> изменил <a href="/tasks?search=tid:'.$logs_lmid.'" target="_blank"><u>задание</u></a></div>
      <div class="settings_balance_admin_users_history_text">
       <span class="settings_balance_admin_users_history_label">Баллы:</span>
       <span class="settings_balance_admin_users_history_field"><b class="settings_balance_admin_users_history_points_minus">-'.$logs_lpoints.'</b></span>
       <br />
       <span class="settings_balance_admin_users_history_label">IP:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lip_address.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Браузер:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lbrowser.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Время:</span>
       <span class="settings_balance_admin_users_history_field">'.new_time($logs_ltime).'</span>
      </div>
     </div>
    ';
   } elseif($logs_lmodule == 4 && $logs_lmodule_type == 2) {
    $template .= '
     <div class="settings_balance_admin_users_history">
      <div class="settings_balance_admin_users_history_title"><a href="/admin/modules/users/?search=http://Piar.name/id'.$logs_lfrom.'" target="_blank"><b>'.$users2_fullname.'</b></a> удалил <a href="/tasks?search=tid:'.$logs_lmid.'" target="_blank"><u>задание</u></a></div>
      <div class="settings_balance_admin_users_history_text">
       <span class="settings_balance_admin_users_history_label">Баллы:</span>
       <span class="settings_balance_admin_users_history_field"><b class="settings_balance_admin_users_history_points_plus">+'.$logs_lpoints.'</b></span>
       <br />
       <span class="settings_balance_admin_users_history_label">IP:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lip_address.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Браузер:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lbrowser.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Время:</span>
       <span class="settings_balance_admin_users_history_field">'.new_time($logs_ltime).'</span>
      </div>
     </div>
    ';
   } elseif($logs_lmodule == 4 && $logs_lmodule_type == 6) {
    $template .= '
     <div class="settings_balance_admin_users_history">
      <div class="settings_balance_admin_users_history_title"><a href="/admin/modules/users/?search=http://Piar.name/id'.$logs_lfrom.'" target="_blank"><b>'.$users2_fullname.'</b></a> выполнил <a href="/tasks?search=tid:'.$logs_lmid.'" target="_blank"><u>задание</u></a></div>
      <div class="settings_balance_admin_users_history_text">
       <span class="settings_balance_admin_users_history_label">Баллы:</span>
       <span class="settings_balance_admin_users_history_field"><b class="settings_balance_admin_users_history_points_plus">+'.$logs_lpoints.'</b></span>
       <br />
       <span class="settings_balance_admin_users_history_label">IP:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lip_address.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Браузер:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lbrowser.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Время:</span>
       <span class="settings_balance_admin_users_history_field">'.new_time($logs_ltime).'</span>
      </div>
     </div>
    ';
   }  elseif($logs_lmodule == 1 && $logs_lmodule_type == 5) {
    $result_points = $logs_ltext == 1 ? '<b class="settings_balance_admin_users_history_points_plus">+'.$logs_lpoints.'</b>' : '<b class="settings_balance_admin_users_history_points_minus">-'.$logs_lpoints.'</b>';
    $template .= '
     <div class="settings_balance_admin_users_history">
      <div class="settings_balance_admin_users_history_title"><a href="/admin/modules/users/?search=http://Piar.name/id'.$logs_lfrom.'" target="_blank"><b>'.$users1_fullname.'</b></a> изменил баланс</div>
      <div class="settings_balance_admin_users_history_text">
       <span class="settings_balance_admin_users_history_label">Баллы:</span>
       <span class="settings_balance_admin_users_history_field">'.$result_points.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">IP:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lip_address.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Браузер:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lbrowser.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Время:</span>
       <span class="settings_balance_admin_users_history_field">'.new_time($logs_ltime).'</span>
      </div>
     </div>
    ';
   } elseif($logs_lmodule == 1 && $logs_lmodule_type == 6) {
     $template .= '
     <div class="settings_balance_admin_users_history">
      <div class="settings_balance_admin_users_history_title"><a href="/admin/modules/users/?search=http://Piar.name/id'.$logs_lto.'" target="_blank"><b>'.$users2_fullname.'</b></a> пригласил реферала <a href="/admin/modules/users/?search=http://Piar.name/id'.$logs_lfrom.'" target="_blank"><b>'.$users1_fullname.'</b></a></div>
      <div class="settings_balance_admin_users_history_text">
       <span class="settings_balance_admin_users_history_label">Баллы:</span>
       <span class="settings_balance_admin_users_history_field"><b class="settings_balance_admin_users_history_points_plus">+'.$logs_lpoints.'</b></span>
       <br />
       <span class="settings_balance_admin_users_history_label">IP:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lip_address.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Браузер:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lbrowser.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Время:</span>
       <span class="settings_balance_admin_users_history_field">'.new_time($logs_ltime).'</span>
      </div>
     </div>
    ';
   } elseif($logs_lmodule == 1 && $logs_lmodule_type == 9) {
     $template .= '
     <div class="settings_balance_admin_users_history">
      <div class="settings_balance_admin_users_history_title"><a href="/admin/modules/users/?search=http://Piar.name/id'.$logs_lto.'" target="_blank"><b>'.$users2_fullname.'</b></a> получил баллы с заработка реферала <a href="/admin/modules/users/?search=http://Piar.name/id'.$logs_lfrom.'" target="_blank"><b>'.$users1_fullname.'</b></a></div>
      <div class="settings_balance_admin_users_history_text">
       <span class="settings_balance_admin_users_history_label">Баллы:</span>
       <span class="settings_balance_admin_users_history_field"><b class="settings_balance_admin_users_history_points_plus">+'.$logs_lpoints.'</b></span>
       <br />
       <span class="settings_balance_admin_users_history_label">IP:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lip_address.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Браузер:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lbrowser.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Время:</span>
       <span class="settings_balance_admin_users_history_field">'.new_time($logs_ltime).'</span>
      </div>
     </div>
    ';
   } elseif($logs_lmodule == 8 && $logs_lmodule_type == 1) {
     $template .= '
     <div class="settings_balance_admin_users_history">
      <div class="settings_balance_admin_users_history_title"><a href="/admin/modules/users/?search=http://Piar.name/id'.$logs_lto.'" target="_blank"><b>'.$users2_fullname.'</b></a> получил штраф за невыполнение <a href="/tasks?search=tid:'.$logs_lmid.'" target="_blank"><u>задания</u></a></div>
      <div class="settings_balance_admin_users_history_text">
       <span class="settings_balance_admin_users_history_label">Баллы:</span>
       <span class="settings_balance_admin_users_history_field"><b class="settings_balance_admin_users_history_points_minus">-'.$logs_lpoints.'</b></span>
       <br />
       <span class="settings_balance_admin_users_history_label">IP:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lip_address.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Браузер:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lbrowser.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Время:</span>
       <span class="settings_balance_admin_users_history_field">'.new_time($logs_ltime).'</span>
      </div>
     </div>
    ';
   } elseif($logs_lmodule == 8 && $logs_lmodule_type == 2) {
     $template .= '
     <div class="settings_balance_admin_users_history">
      <div class="settings_balance_admin_users_history_title"><a href="/admin/modules/users/?search=http://Piar.name/id'.$logs_lto.'" target="_blank"><b>'.$users2_fullname.'</b></a> получил компенсацию за <a href="/tasks?search=tid:'.$logs_lmid.'" target="_blank"><u>задание</u></a></div>
      <div class="settings_balance_admin_users_history_text">
       <span class="settings_balance_admin_users_history_label">Баллы:</span>
       <span class="settings_balance_admin_users_history_field"><b class="settings_balance_admin_users_history_points_plus">+'.$logs_lpoints.'</b></span>
       <br />
       <span class="settings_balance_admin_users_history_label">IP:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lip_address.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Браузер:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lbrowser.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Время:</span>
       <span class="settings_balance_admin_users_history_field">'.new_time($logs_ltime).'</span>
      </div>
     </div>
    ';
   } elseif($logs_lmodule == 9 && $logs_lmodule_type == 1) {
     $template .= '
     <div class="settings_balance_admin_users_history">
      <div class="settings_balance_admin_users_history_title"><a href="/admin/modules/users/?search=http://piar.name/id'.$logs_lto.'" target="_blank"><b>'.$users2_fullname.'</b></a> купил баллы через «WebMoney»</div>
      <div class="settings_balance_admin_users_history_text">
       <span class="settings_balance_admin_users_history_label">Баллы:</span>
       <span class="settings_balance_admin_users_history_field"><b class="settings_balance_admin_users_history_points_plus">+'.$logs_lpoints.'</b></span>
       <br />
       <span class="settings_balance_admin_users_history_label">IP:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lip_address.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Браузер:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lbrowser.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Время:</span>
       <span class="settings_balance_admin_users_history_field">'.new_time($logs_ltime).'</span>
      </div>
     </div>
    ';
   } elseif($logs_lmodule == 9 && $logs_lmodule_type == 2) {
     $template .= '
     <div class="settings_balance_admin_users_history">
      <div class="settings_balance_admin_users_history_title"><a href="/admin/modules/users/?search=http://piar.name/id'.$logs_lto.'" target="_blank"><b>'.$users2_fullname.'</b></a> купил баллы через телефон</div>
      <div class="settings_balance_admin_users_history_text">
       <span class="settings_balance_admin_users_history_label">Баллы:</span>
       <span class="settings_balance_admin_users_history_field"><b class="settings_balance_admin_users_history_points_plus">+'.$logs_lpoints.'</b></span>
       <br />
       <span class="settings_balance_admin_users_history_label">IP:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lip_address.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Браузер:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lbrowser.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Время:</span>
       <span class="settings_balance_admin_users_history_field">'.new_time($logs_ltime).'</span>
      </div>
     </div>
    ';
   } elseif($logs_lmodule == 9 && $logs_lmodule_type == 3) {
     $template .= '
     <div class="settings_balance_admin_users_history">
      <div class="settings_balance_admin_users_history_title"><a href="/admin/modules/users/?search=http://piar.name/id'.$logs_lto.'" target="_blank"><b>'.$users2_fullname.'</b></a> купил баллы через «QIWI»</div>
      <div class="settings_balance_admin_users_history_text">
       <span class="settings_balance_admin_users_history_label">Баллы:</span>
       <span class="settings_balance_admin_users_history_field"><b class="settings_balance_admin_users_history_points_plus">+'.$logs_lpoints.'</b></span>
       <br />
       <span class="settings_balance_admin_users_history_label">IP:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lip_address.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Браузер:</span>
       <span class="settings_balance_admin_users_history_field">'.$logs_lbrowser.'</span>
       <br />
       <span class="settings_balance_admin_users_history_label">Время:</span>
       <span class="settings_balance_admin_users_history_field">'.new_time($logs_ltime).'</span>
      </div>
     </div>
    ';
   }
  }
  return $template ? '
   <div id="settings_balance_admin_users_pages">
    '.pages_ajax(array('ents_count' => user::history_balance_num($uid), 'ents_print' => 10, 'page' => $page)).'
    <span class="pages_ajax"><div class="upload"></div></span>
   </div>
   '.$template : '<div id="box_none">История баланса пуста.</div>';
 }
 
 public function edit_balance() {
  global $db, $dbName, $user_id, $user_logged, $ugroup, $logs;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $uid = (int) $_POST['uid'];
  $type = $db->escape($_POST['type']);
  $num = (int) $_POST['num'];
  
  if($type == 1) {
   $result_type = 1;
   $query = "UPDATE `$dbName`.`users` SET `upoints` = upoints + '$num' WHERE  `users`.`uid` = '$uid' LIMIT 1 ;";
  } else {
   $result_type = 2;
   $query = "UPDATE `$dbName`.`users` SET `upoints` = upoints - '$num' WHERE  `users`.`uid` = '$uid' LIMIT 1 ;";
  }
  
  if($num < 1) {
   $json = array('error_text' => 'Введите количество.');
  } else {
   if($db->query($query)) {
    $logs->edit_points($user_id, $uid, $uid, $result_type, $num);
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных.');
   }
  }
  return jdecoder(json_encode($json));
 }
 
 public function history_login_num() {
  global $db;
  
  $uid = (int) $_GET['uid'];
  
  $q = $db->query("SELECT `lid` FROM `logs` WHERE `lmodule` = '1' AND `lmodule_type` = '7' AND `lto` = '$uid' ORDER BY `lid` DESC");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function history_login() {
  global $db, $ugroup, $user_logged;
  
  if(!$user_logged) {
   return 'login';
   exit;
  }
  
  if($ugroup != 4) {
   return 'Access Denied';
   exit;
  }
  
  $uid = (int) $_GET['uid'];
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 10;
  $num = user::history_login_num();
  
  $q = $db->query("SELECT `lto`, `lbrowser`, `ltime`, `lip_address` FROM `logs` WHERE `lmodule` = '1' AND `lmodule_type` = '7' AND `lto` = '$uid' ORDER BY `lid` DESC LIMIT $start_limit, 10");
  while($d = $db->fetch($q)) {
   $browser = $d['lbrowser'];
   $time = $d['ltime'];
   $ip = $d['lip_address'];
   
   $template .= '
      <div class="admin_history_login_column_content_overflow">
       <div class="admin_history_login_column_content1">Браузер '.mb_substr($browser, 0, 20).'</div>
       <div class="admin_history_login_column_content2">'.new_time($time).'</div>
       <div class="admin_history_login_column_content3">'.$ip.'</div>
      </div>
   ';
  }
  return '
   <div id="admin_history_login_info">
    <b>История активности</b> показывает информацию о том, с каких устройств и в какое время пользователь заходил на сайт.
   </div>
   <div id="admin_history_login_pages">
    '.pages_ajax(array('ents_count' => $num, 'ents_print' => 10, 'page' => $page)).' 
    <span class="pages_ajax"><div class="upload"></div></span>
   </div>
   <div id="admin_history_login">
    <div id="admin_history_login_table">
     <div id="admin_history_login_table_column">
      <div class="admin_history_login_column" id="admin_history_login_column1">Тип доступа</div>
      <div class="admin_history_login_column" id="admin_history_login_column2">Время</div>
      <div class="admin_history_login_column" id="admin_history_login_column3">IP-адрес</div>
     </div>
     <div id="admin_history_login_table_content">
     '.$template.'
     </div>
    </div>
   </div>
  ';
 }
 
 public function add_vk($text = null, $url = null) {
  global $db, $dbName, $token, $vk, $user_logged, $user_id, $uvk_id, $sites_list_rand;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  $wall_comments_decode = json_decode($vk->check_wall_comment_json($url, $text, $token), true);
  $wall_comments_decode_error = $wall_comments_decode['error'];
  $wall_comments_decode_uid = (int) $wall_comments_decode['uid'];
  if($uvk_id) {
   $json = array('error_text' => 'К этому аккаунту уже прикреплена страница ВКонтакте.');
  } elseif($wall_comments_decode_error == 2) {
   $json = array('error_text' => 'Оставьте <b>комментарий, который Вас просят ниже</b>, к записи <a href="'.$sites_list_rand.'go.html?url=http://vk.com/wall'.$url.'" target="_blank">vk.com/wall'.$url.'</a>.');
  } elseif($wall_comments_decode_error == 0) {
   $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже.');
  } elseif($wall_comments_decode_error == 1) {
   $user_vk_info = json_decode($vk->user_info($wall_comments_decode_uid, $token), true);
   $user_vk_info_uid = (int) $user_vk_info['id'];
   $user_vk_info_first_name = $db->escape($user_vk_info['first_name']);
   $user_vk_info_last_name = $db->escape($user_vk_info['last_name']);
   $user_vk_info_avatar = $db->escape($user_vk_info['avatar']);
   $user_vk_info_gender = (int) $user_vk_info['gender'];
   $user_vk_info_city = (int) $user_vk_info['city'];
   $user_vk_info_year = (int) $user_vk_info['year'];
   if($user_vk_info_uid) {
    if(user::check_vk_id($user_vk_info_uid) == 1) {
     $json = array('error_text' => 'Такая страница <b>уже зарегистрирована в системе</b>.');
    } else {
     if($db->query("UPDATE  `$dbName`.`users` SET  `uname` =  '$user_vk_info_first_name', `ulast_name` =  '$user_vk_info_last_name', `uavatar` =  '$user_vk_info_avatar', `uvk_id` = '$user_vk_info_uid', `ugender` = '$user_vk_info_gender', `city` = '$user_vk_info_city', `ubyear` = '$user_vk_info_year' WHERE  `users`.`uid` = '$user_id' LIMIT 1 ;")) {
      $json = array('success' => 1, 'text' => 'К Вашему аккаунту прикреплена страница <a href="http://vk.com/id'.$user_vk_info_uid.'" target="_blank"><b>'.$user_vk_info_first_name.' '.$user_vk_info_last_name.'</b></a>.');
     } else {
      $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
     }
    }
   } else {
    $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже.');
   }
  } else {
   $json = array('error_text' => 'Неизвестная ошибка.');
  }
  return json_encode($json);
 }
 
 public function edit_info() {
  global $db, $ugroup, $user_logged;
  
  if(!$user_logged) {
   return 'login';
   exit;
  }
  
  if($ugroup != 4) {
   return 'Access Denied';
   exit;
  }
  
  $uid = (int) $_GET['uid'];
  
  $q = $db->query("SELECT `ulogin`, `uemail`, `uemail_activated`, `uvk_id`, `uname`, `ulast_name`, `uavatar`, `ugender` FROM `users` WHERE `uid` = '$uid'");
  $d = $db->fetch($q);
  
  $login = $d['ulogin'];
  $email = $d['uemail'];
  $email_activated = $d['uemail_activated'];
  $vk_id = $d['uvk_id'];
  $first_name = $d['uname'];
  $last_name = $d['ulast_name'];
  $avatar = $d['uavatar'];
  $gender = $d['ugender'];
  
  if($login) {
   return '
    <div id="admin_user_edit_info_error"></div>
    <div class="overflow_field">
     <div class="label label_f">Имя:</div>
     <div class="field"><input iplaceholder="Не указано" id="user_edit_info_name" type="text" value="'.fxss($first_name).'"></div>
    </div>
    <div class="overflow_field">
     <div class="label label_f">Фамилия:</div>
     <div class="field"><input iplaceholder="Не указано" id="user_edit_info_last_name" type="text" value="'.fxss($last_name).'"></div>
    </div>
    <div class="overflow_field">
     <div class="label label_f">Логин:</div>
     <div class="field"><input iplaceholder="Не указано" id="user_edit_info_login" type="text" value="'.$login.'"></div>
    </div>
    <div class="overflow_field">
     <div class="label label_f">E-mail:</div>
     <div class="field"><input iplaceholder="Не указано" id="user_edit_info_email" type="text" value="'.$email.'"></div>
    </div>
    <div class="overflow_field">
     <div class="label label_f">ID ВКонтакте:</div>
     <div class="field"><input iplaceholder="Не указано" id="user_edit_info_vk" type="text" value="'.($vk_id ? $vk_id : '').'"></div>
    </div>
    <div class="overflow_field">
     <div class="label label_f">Ссылка на аватар:</div>
     <div class="field"><input iplaceholder="Не указано" id="user_edit_info_avatar" type="text" value="'.fxss($avatar).'"></div>
    </div>
    <div class="overflow_field">
     <div class="label label_f">Пол:</div>
     <div class="field">
      <div id="user_edit_info_gender"></div>
     </div>
     <input type="hidden" id="user_edit_info_gender_value" value="'.$gender.'">
    </div>
   ';
  } else {
   return 'Ошибка доступа.';
  }
 }
 
 public function edit_info_post() {
  global $db, $dbName, $user_id, $user_logged, $ugroup, $logs;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $uid = (int) $_POST['uid'];
  $post_name = $db->escape(trim($_POST['name']));
  $post_last_name = $db->escape(trim($_POST['last_name']));
  $post_avatar = $db->escape(trim($_POST['avatar']));
  $post_login = $db->escape(trim($_POST['login']));
  $post_email = $db->escape(trim($_POST['email']));
  $post_vk_id = (int) abs($_POST['id_vk']);
  $post_gender = (int) abs($_POST['gender']);
  
  $q = $db->query("SELECT `ulogin`, `uemail`, `uemail_activated`, `uvk_id`, `uname`, `ulast_name`, `uavatar`, `ugender` FROM `users` WHERE `uid` = '$uid'");
  $d = $db->fetch($q);
  
  $login = $d['ulogin'];
  $email = $d['uemail'];
  $email_activated = $d['uemail_activated'];
  $vk_id = $d['uvk_id'];
  $first_name = $d['uname'];
  $last_name = $d['ulast_name'];
  $avatar = $d['uavatar'];
  $gender = $d['ugender'];
  
  if($login) {
   if($first_name == $post_name && $last_name == $post_last_name && $login == $post_login && $email == $post_email && $vk_id == $post_vk_id && $avatar == $post_avatar && $gender == $post_gender) {
    $json = array('error_text' => 'Вы ничего не изменили.');
   } elseif(user::check_login($post_login) && $post_login != $login) {
    $json = array('error_text' => 'Такой логин уже занят.');
   } elseif(!preg_match('/^([@a-zA-Z0-9]){1,30}$/i', $post_login)) {
    $json = array('error_text' => 'Поле «логин» может содержать только латинские символы или цифры и не превышать 30 символов.');
   } elseif(user::check_email($post_email) && $post_email != $email) {
    $json = array('error_text' => 'Такой e-mail уже занят.');
   } elseif(!preg_match("/^[a-zA-Z0-9_\.\-]+@([a-zA-Z0-9\-]+\.)+[a-zA-Z]{2,6}$/i", $post_email)) {
    $json = array('error_text' => 'Поле «e-mail» имеет неправильный формат.');
   } elseif(user::check_vk_id($post_vk_id) && $post_vk_id != $vk_id && $post_vk_id) {
    $json = array('error_text' => 'Такая страница ВКонтакте уже зарегистрирована.');
   } else {
    if($db->query("UPDATE  `$dbName`.`users` SET  `ulogin` =  '$post_login',
     `uemail` =  '$post_email',
     `uemail_activated` = '1',
     `uvk_id` =  '$post_vk_id',
     `uname` =  '$post_name',
     `ulast_name` =  '$post_last_name',
     `ugender` = '$post_gender',
     `uavatar` =  '$post_avatar' WHERE  `users`.`uid` = '$uid' LIMIT 1 ;
    ")) {
     $logs_edit = '{"new_name":"'.$post_name.'", "new_last_name":"'.$post_last_name.'", "new_login":"'.$post_login.'", "new_email":"'.$post_email.'", "new_vk_id":"'.$post_vk_id.'", "new_avatar":"'.$post_avatar.'", "new_gender":"'.$post_gender.'", "old_name":"'.$first_name.'", "old_last_name":"'.$last_name.'", "old_login":"'.$login.'", "old_email":"'.$email.'", "old_vk_id":"'.$vk_id.'", "old_avatar":"'.$avatar.'", "old_gender":"'.$gender.'"}';
     $logs->user_edit_info($user_id, $uid, $logs_edit);
     $json = array('success' => 1);
    } else {
     $json = array('error_text' => 'Ошибка соединения с сервером. Попробуйте позже.');
    }
   }
  } else {
   $json = array('error_text' => 'id');
  }
  
  return json_encode($json);
 }
 
 public function edit_info_history_num() {
  global $db;
  
  $uid = (int) $_GET['uid'];
  
  $q = $db->query("SELECT `lid` FROM `logs` WHERE (`lmodule` = '1' AND `lmodule_type` = '8' OR `lmodule` = '1' AND `lmodule_type` = '1' OR `lmodule` = '1' AND `lmodule_type` = '2') AND `lto` = '$uid'");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function edit_info_history() {
  global $db, $user_id, $user_logged, $ugroup, $logs, $site_url;
  
  if(!$user_logged) {
   return 'login';
   exit;
  }
  
  if($ugroup != 4) {
   return 'Access Denied';
   exit;
  }
  
  $uid = (int) $_GET['uid'];
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 10;
  $num = user::edit_info_history_num();
  
  $q = $db->query("
   SELECT logs.lfrom, logs.ltext, logs.lip_address, logs.lbrowser, logs.ltime, logs.lmodule, logs.lmodule_type, users.uname, users.ulast_name FROM `logs`
    INNER JOIN `users` ON logs.lfrom = users.uid
   WHERE (logs.lmodule = '1' AND logs.lmodule_type = '8' OR logs.lmodule = '1' AND logs.lmodule_type = '1' OR logs.lmodule = '1' AND logs.lmodule_type = '2') AND logs.lto = '$uid'
   ORDER BY logs.lid DESC
   LIMIT $start_limit, 10
  ");
  while($d = $db->assoc($q)) {
   $from = $d['lfrom'];
   $text = json_decode($d['ltext'], true);
   $ip = $d['lip_address'];
   $browser = $d['lbrowser'];
   $time = $d['ltime'];
   $name = $d['uname'];
   $last_name = $d['ulast_name'];
   $module = $d['lmodule'];
   $module_type = $d['lmodule_type'];
   
   // Старый пол
   if(!$text['old_gender']) {
    $old_gender = 'Не выбран';
   } elseif($text['old_gender'] == 2) {
    $old_gender = 'Мужской';
   } else {
    $old_gender = 'Женский';
   }
   
   // Новый пол
   if(!$text['new_gender']) {
    $new_gender = 'Не выбран';
   } elseif($text['new_gender'] == 2) {
    $new_gender = 'Мужской';
   } else {
    $new_gender = 'Женский';
   }
   
   if($module == 1 && $module_type == 8) {    
    $template .= '
     <div class="admin_edit_info_history_overflow">
      <div class="admin_edit_info_history_overflow_ftype">
       <a href="/admin/modules/users/?search='.$site_url.'id'.$from.'" onclick="nav.go(this); return false"><b>'.($name ? $name : 'Безымянный').' '.$last_name.'</b></a> изменил информацию:
       <div class="admin_edit_info_history_overflow_fsystem"><span>IP:</span> '.$ip.'; <span>Браузер:</span> '.$browser.'; <span>Время:</span> '.new_time($time).';</div>
      </div>
      <div class="overflow_field">
       <div class="label">Имя:</div>
       <div class="field"><span class="admin_edit_info_history_overflow_new_string">'.($text['new_name'] ? $text['new_name'] : 'Безымянный').'</span> / '.($text['old_name'] ? $text['old_name'] : 'Безымянный').'</div>
      </div>
      <div class="overflow_field">
       <div class="label">Фамилия:</div>
       <div class="field"><span class="admin_edit_info_history_overflow_new_string">'.($text['new_last_name'] ? $text['new_last_name'] : 'Не указано').'</span> / '.($text['old_last_name'] ? $text['old_last_name'] : 'Не указано').'</div>
      </div>
      <div class="overflow_field">
       <div class="label">Логин:</div>
       <div class="field"><span class="admin_edit_info_history_overflow_new_string">'.$text['new_login'].'</span> / '.$text['old_login'].'</div>
      </div>
      <div class="overflow_field">
       <div class="label">E-mail:</div>
       <div class="field"><span class="admin_edit_info_history_overflow_new_string">'.$text['new_email'].'</span> <br /> '.$text['old_email'].'</div>
      </div>
      <div class="overflow_field">
       <div class="label">ID ВКонтакте:</div>
       <div class="field"><span class="admin_edit_info_history_overflow_new_string">id'.$text['new_vk_id'].'</span> / id'.$text['old_vk_id'].'</div>
      </div>
      <div class="overflow_field">
       <div class="label">Ссылка на аватар:</div>
       <div class="field"><span class="admin_edit_info_history_overflow_new_string">'.($text['new_avatar'] ? $text['new_avatar'] : 'Не указано').'</span> <br /> '.($text['old_avatar'] ? $text['old_avatar'] : 'Не указано').'</div>
      </div>
      <div class="overflow_field">
       <div class="label">Пол:</div>
       <div class="field"><span class="admin_edit_info_history_overflow_new_string">'.$new_gender.'</span> / '.$old_gender.'</div>
      </div>
     </div>
    ';
   } elseif($module == 1 && $module_type == 1) {
    $template .= '
     <div class="admin_edit_info_history_overflow">
      <div class="admin_edit_info_history_overflow_ftype">
       <a href="/admin/modules/users/?search='.$site_url.'id'.$from.'" onclick="nav.go(this); return false"><b>'.($name ? $name : 'Безымянный').' '.$last_name.'</b></a> изменил информацию:
       <div class="admin_edit_info_history_overflow_fsystem"><span>IP:</span> '.$ip.'; <span>Браузер:</span> '.$browser.'; <span>Время:</span> '.new_time($time).';</div>
      </div>
      <div class="overflow_field">
       <div class="label">Пароль:</div>
       <div class="field"><span class="admin_edit_info_history_overflow_new_string">'.$text['new_password'].'</span> / '.$text['old_password'].'</div>
      </div>
     </div>
    ';
   } elseif($module == 1 && $module_type == 2) {
    $template .= '
     <div class="admin_edit_info_history_overflow">
      <div class="admin_edit_info_history_overflow_ftype">
       <a href="/admin/modules/users/?search='.$site_url.'id'.$from.'" onclick="nav.go(this); return false"><b>'.($name ? $name : 'Безымянный').' '.$last_name.'</b></a> изменил информацию:
       <div class="admin_edit_info_history_overflow_fsystem"><span>IP:</span> '.$ip.'; <span>Браузер:</span> '.$browser.'; <span>Время:</span> '.new_time($time).';</div>
      </div>
      <div class="overflow_field">
       <div class="label">Логин:</div>
       <div class="field"><span class="admin_edit_info_history_overflow_new_string">'.$text['new_login'].'</span> / '.$text['old_login'].'</div>
      </div>
     </div>
    ';
   }
  }
  return $template ? '
   <div id="admin_edit_info_box_pages">
    '.pages_ajax(array('ents_count' => $num, 'ents_print' => 10, 'page' => $page)).' 
    <span class="pages_ajax"><div class="upload"></div></span>
   </div>
   '.$template.'
  ' : '<div id="admin_edit_info_box_none">История редактирований пуста.</div>';
 }
 
 public function blocked() {
  global $db, $dbName, $user_id, $user_logged, $ugroup, $time, $browser, $ip_address;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $uid = (int) $_GET['uid'];
  $text = $db->escape(trim($_GET['text']));
  
  $q = $db->query("SELECT `uban_type` FROM `users` WHERE `uid` = '$uid'");
  $d = $db->fetch($q);
  
  $uban_type = $d['uban_type'];
  
  if(mb_strlen($text, 'UTF-8') < 3) {
   $json = array('error_text' => 'Слишком короткая причина блокировки.');
  } elseif($uban_type) {
   $json = array('error_text' => 'Аккаунт уже заблокирован.');
  } else {
   if($db->query("UPDATE  `$dbName`.`users` SET  `uban_type` =  '1', `uban_text` = '$text' WHERE  `users`.`uid` = '$uid';")) {
    $db->query("INSERT INTO `$dbName`.`logs_blocked` (`id`, `uid`, `to`, `type`, `text`, `time`, `browser`, `ip`) VALUES (NULL, '$user_id', '$uid', '1', '$text', '$time', '$browser', '$ip_address');");
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  }
  
  return json_encode($json);
 }
 
 public function unblocked() {
  global $db, $dbName, $user_id, $user_logged, $ugroup, $time, $browser, $ip_address;
  
  $uid = (int) $_GET['uid'];
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $q = $db->query("SELECT `uban_type` FROM `users` WHERE `uid` = '$uid'");
  $d = $db->fetch($q);
  
  $uban_type = $d['uban_type'];
  
  if($uban_type) {
   if($db->query("UPDATE  `$dbName`.`users` SET  `uban_type` =  '0', `uban_text` = '' WHERE  `users`.`uid` = '$uid';")) {
    $db->query("INSERT INTO `$dbName`.`logs_blocked` (`id`, `uid`, `to`, `type`, `text`, `time`, `browser`, `ip`) VALUES (NULL, '$user_id', '$uid', '2', '$text', '$time', '$browser', '$ip_address');");
    $json = array('success' => 1);
   } else {
    $json = array('error' => 1);
   }
  } else {
   $json = array('error' => 1);
  }
  
  return json_encode($json);
 }
 
 public function blocked_history_num() {
  global $db;
  
  $uid = (int) $_GET['uid'];
  
  $q = $db->query("SELECT `id` FROM `logs_blocked` WHERE `to` = '$uid'");
  
  return $db->num($q);
 }
 
 public function blocked_history() {
  global $db, $dbName, $user_id, $user_logged, $ugroup, $site_url;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $uid = (int) $_GET['uid'];
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 10;
  $num = user::blocked_history_num();
  
  $q = $db->query("
   SELECT users.uname, users.ulast_name, logs_blocked.uid, logs_blocked.type, logs_blocked.text, logs_blocked.time, logs_blocked.browser, logs_blocked.ip
   FROM `logs_blocked`
    INNER JOIN `users` ON logs_blocked.uid = users.uid
   WHERE logs_blocked.to = '$uid'
   ORDER BY logs_blocked.id DESC
   LIMIT $start_limit, 10
  ");

  while($d = $db->fetch($q)) {
   $from = $d['uid'];
   $first_name = $d['uname'];
   $last_name = $d['ulast_name'];
   $type = $d['type'];
   $text = $d['text'];
   $time = $d['time'];
   $browser = $d['browser'];
   $ip = $d['ip'];
   
   if($type == 1) {
    $template .= '
     <div class="admin_edit_info_history_overflow">
      <div class="admin_edit_info_history_overflow_ftype">
       <a href="/admin/modules/users/?search='.$site_url.'id'.$from.'" onclick="nav.go(this); return false"><b>'.no_name($first_name.' '.$last_name).'</b></a> <div class="user_list_ban_status">заблокировал</div> пользователя:
       <div class="admin_edit_info_history_overflow_fsystem"><span>IP:</span> '.$ip.'; <span>Браузер:</span> '.$browser.'; <span>Время:</span> '.new_time($time).';</div>
      </div>
      Причина: <b>'.nl2br($text).'</b>
     </div>';
   } elseif($type == 2) {
    $template .= '
     <div class="admin_edit_info_history_overflow">
      <div class="admin_edit_info_history_overflow_ftype">
       <a href="/admin/modules/users/?search='.$site_url.'id'.$from.'" onclick="nav.go(this); return false"><b>'.no_name($first_name.' '.$last_name).'</b></a> <div class="user_list_ok_status">разблокировал</div> пользователя:
       <div class="admin_edit_info_history_overflow_fsystem"><span>IP:</span> '.$ip.'; <span>Браузер:</span> '.$browser.'; <span>Время:</span> '.new_time($time).';</div>
      </div>
     </div>';
   }
  }
  return $template ? '
   <div id="admin_edit_info_box_pages">
    '.pages_ajax(array('ents_count' => $num, 'ents_print' => 10, 'page' => $page)).' 
    <span class="pages_ajax"><div class="upload"></div></span>
   </div>
   '.$template.'
  ' : '<div id="admin_edit_info_box_none">История блокировок пуста.</div>';
 }
}

$user = new user;
?>