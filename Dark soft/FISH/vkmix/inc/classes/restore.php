<?php
class restore {
 public function restore_email() {
  global $db, $dbName, $session, $site_url, $time;
  
  $email = $db->escape(trim($_GET['email']));
  $code = $db->escape(trim($_GET['code']));
  
  if(preg_match("/^[a-zA-Z0-9_\.\-]+@([a-zA-Z0-9\-]+\.)+[a-zA-Z]{2,6}$/i", $email)) {
   $query = "SELECT `uid`, `ulogin`, `uemail` FROM `users` WHERE `uemail` = '$email'";
  } else {
   $query = "SELECT `uid`, `ulogin`, `uemail` FROM `users` WHERE `ulogin` = '$email'";
  }
  
  $q = $db->query($query);
  $d = $db->fetch($q);
  
  $d_uid = $d['uid'];
  $d_uemail = $d['uemail'];
  $d_ulogin = $d['ulogin'];
  $hash = md5("$d_uid+$d_uemail+$time");
  $new_password = rand_str(14);
  $new_password_md5 = md5(md5($new_password));
  
  if($d_uid) {
   if(!$session->get('unique_key_restore'.$d_uid)) {
    $session->add('unique_key_restore'.$d_uid, rand_str(6));
   }
   
   $key_get = $session->get('unique_key_restore'.$d_uid);
   
   if(!$code) {
    send_email($d_uemail, 'Восстановление доступа к аккаунту', 'Проверочный код: '.$key_get.' <br /> <br />С уважением, <br /><a href="'.$site_url.'">Команда MontyTool</a>.');
   }
   
   if($key_get == $code) {
    if($db->query("UPDATE  `$dbName`.`users` SET  `upassword` =  '$new_password_md5', `uhash` =  '$hash' WHERE  `users`.`uid` = '$d_uid';")) {
     $json = array('success' => 1, 'ulogin' => $d_ulogin, 'upassword' => $new_password);
    } else {
     $json = array('error_text' => 'Ошибка соединения с сервером. Попробуйте позже.');
    }
   } else {
    $json = array('email' => $d_uemail);
   }
  } elseif($key_get != $code) {
   $json = array('email' => $d_uemail);
  } else {
   $json = array('error_text' => 'Такой пользователь не зарегистрирован на MontyTool.');
  }
  
  return json_encode($json);
 }
 
 public function restore_vk() {
  global $db, $dbName, $vk, $session, $token, $time;
  
  $vk_id = $db->escape(trim($_GET['vkid']));
  
  if(!$vk->url($vk_id)) {
   $json = array('error_text' => 'Проверьте правильность введенной ссылки.');
  } else {
   $vk_end = json_decode($vk->explode($vk_id), true);
   $vk_end_url = $vk_end['url'];
   $vk_user_get_info = json_decode($vk->user_info($vk_end_url, $token), true);
   $vk_user_get_info_id = (int) $vk_user_get_info['id'];
   
   if($vk_user_get_info_id) {
    $statuses_list = array('я ты', 'не ты я', 'что такое?', 'как ты?', 'Павел Дуров', 'Pavel Durov', 'a', 'b', 'abc', 'abcd', 'abcde', 'мяу', 'мур', 'миу', 'хихи', 'ахаха', 'прикольно', 'ниче се', 'ммм', 'ну скажи', 'ааа', 'спасай!', 'круг', 'квадрат', 'треугольник', 'школа', 'монти', 'торт');
    $statuses_rand = $statuses_list[rand(0, count($statuses_list) - 1)];
    
    if(!$session->get('unique_key_restore_vk'.$vk_user_get_info_id)) {
     $session->add('unique_key_restore_vk'.$vk_user_get_info_id, $statuses_rand);
    }
    
    $status_get = $session->get('unique_key_restore_vk'.$vk_user_get_info_id);
    
    $q = $db->query("SELECT `uid`, `ulogin` FROM `users` WHERE `uvk_id` = '$vk_user_get_info_id'");
    $d = $db->fetch($q);
    
    $d_uid = $d['uid'];
    $d_ulogin = $d['ulogin'];
    $hash = md5("$d_uid+$d_ulogin+$time");
    $new_password = rand_str(14);
    $new_password_md5 = md5(md5($new_password));
    
    if(!$status_get) {
     $json = array('error_text' => 'Неизвестная ошибка.');
    } elseif($d_uid) {
     $status = $vk->get_status($vk_user_get_info_id, $token);
     if($status == $status_get) {
      if($db->query("UPDATE  `$dbName`.`users` SET  `upassword` =  '$new_password_md5', `uhash` =  '$hash' WHERE  `users`.`uid` = '$d_uid';")) {
       $json = array('success' => 1, 'ulogin' => $d_ulogin, 'upassword' => $new_password);
      } else {
       $json = array('error_text' => 'Ошибка соединения с сервером. Попробуйте позже.');
      }
     } else {
      $json = array('vstatus' => 1, 'text' => $status_get);
     }
    } else {
     $json = array('error_text' => 'Такой пользователь не зарегистрирован на MontyTool.');
    }
   } else {
    $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже.');
   }
  }
  return json_encode($json);
 }
}

$restore = new restore;
?>