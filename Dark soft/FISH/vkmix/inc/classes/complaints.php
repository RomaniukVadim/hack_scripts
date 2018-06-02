<?php
class complaints {
 public function capi($code = null) {
  global $token_complaints;
  return $api_url = 'https://api.vk.com/method/execute?code='.urlencode($code).'&access_token='.$token_complaints;
 }
 
 public function cget() {
  global $db, $dbName, $user_id, $ugroup, $user_logged, $vk, $token_complaints, $browser, $ip_address, $time, $logs;
 
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
 
  $task_id = (int) $_GET['id'];
  $complaints_limit = time() - (15 * 60);
  $dTaskDone_array = array();
  $users_result = array();
  $api_friends->response[0] = array();
 
  // открываем задание
  $qTask = $db->query("SELECT `tid`, `tsection`, `ttype`, `turl`, `tdone_count`, `complaints_time`, `tamount` FROM `tasks` WHERE `tid` = '$task_id' AND `tfrom` = '$user_id'");
  $dTask = $db->fetch($qTask);
 
  $dTask_id = $dTask['tid'];
  $dTask_section = $dTask['tsection'];
  $dTask_type = str_replace(array('wall_comment', 'wall'), array('comment', 'post'), $dTask['ttype']);
  $dTask_type_normal = $dTask['ttype'];
  $dTask_url = $dTask['turl'];
  $dTask_done_count = $dTask['tdone_count'];
  $dTask_done_amount = $dTask['tamount'];
  $dTask_complaints_time = $dTask['complaints_time'];
  $dTask_url_explode = explode('_', $dTask_url);
  $dTask_url_one = $dTask_url_explode[0];
  $dTask_url_two = $dTask_url_explode[1];
 
  if($dTask_complaints_time >= $complaints_limit) {
   return json_encode(array('error_text' => 'Выписывать штрафы можно не чаще, чем 1 раз в 15 минут.'));
   exit;
  }
 
  if($dTask_section != 1 && $dTask_section != 2 && $dTask_section != 4 && $dTask_section != 5) {
   return json_encode(array('error_text' => 'Неизвестная ошибка.'));
   exit;
  }
 
  if($dTask_done_count <= 0) {
   return json_encode(array('error_text' => 'Это задание ещё никто не выполнил.'));
   exit;
  }
 
  if($dTask_id) {
   // массив выполнивших задание
   $qTaskDone = $db->query("SELECT `tduid`, `tdvk_id` FROM `tasks_done` WHERE `tdtid` = '$task_id' AND `tdtype` = 'done' AND `tdread` = '0'");
   $dTaskDone_array = array();
   while($dTaskDone = $db->assoc($qTaskDone)) {
    $dTaskDone_uid = $dTaskDone['tdvk_id'];
    $dTaskDone_array[] = $dTaskDone['tdvk_id'];
   }
 
   if(!$dTaskDone_uid) {
    return json_encode(array('success' => 1, 'users' => 0));
    exit;
   }
   
   // категории
   if($dTask_section == 1) {
    // мне нравится
    $calls = array();
    $users_result = array();
    for($i = 0; $i < 16; $i++) {
     $calls[] = 'API.likes.getList({"type": "'.$dTask_type.'", "owner_id": "'.$dTask_url_one.'", item_id: "'.$dTask_url_two.'", "count": 1000, "offset": '.($i * 1000).'})';
    }
    $code = "return [" . @implode(',', $calls) . "];";
    $api = json_decode(@vk::_post(complaints::capi($code)));
 
    if($api->execute_errors || $api->error) { // ошибка сервера ВК
     $vk_error = 1;
    } else {
     foreach($api->response as $response_key => $response_value) {
      $users = $api->response[$response_key]->users;
      foreach($users as $key => $value) {
       $users_result[] = $value;
      }
     }
    }
   } elseif($dTask_section == 2) {
    // рассказать друзьям
    $calls = array();
    $users_result = array();
    for($i = 0; $i < 16; $i++) {
     $calls[] = 'API.likes.getList({"type": "'.$dTask_type.'", "owner_id": "'.$dTask_url_one.'", "item_id": "'.$dTask_url_two.'", "count": 1000, "filter": "copies", "offset": '.($i * 1000).'})';
    }
    $code = "return [" . @implode(',', $calls) . "];";
    $api = json_decode(@vk::_post(complaints::capi($code)));
   
    if($api->execute_errors || $api->error) { // ошибка сервера ВК
     $vk_error = 1;
    } else {
     foreach($api->response as $response_key => $response_value) {
      $users = $api->response[$response_key]->users;
      foreach($users as $key => $value) {
       $users_result[] = $value;
      }
     }
    }
   }  elseif($dTask_section == 4) {
    // подписаться
    $calls = array();
    $users_result = array();
    for($i = 0; $i < 16; $i++) {
     $calls[] = 'API.users.getFollowers({"user_id": '.$dTask_url.', "count": 1000, "offset": '.($i * 1000).'})';
    }
    $code = "return [" . @implode(',', $calls) . "];";
    $api = json_decode(@vk::_post(complaints::capi($code)));
    // друзья
    $calls_friends = array();
    $calls_friends = 'API.friends.get({"user_id": '.$dTask_url.'})';
    $code_friends = "return [".$calls_friends."];";
    $api_friends = json_decode(@vk::_post(complaints::capi($code_friends)));
 
    if($api->execute_errors || $api_friends->execute_errors || $api->error || $api_friends->error) { // ошибка сервера ВК
     $vk_error = 1;
    } else {
     foreach($api->response as $response_key => $response_value) {
      $users = $api->response[$response_key]->items;
      foreach($users as $key => $value) {
       $users_result[] = $value;
      }
     }
     $users_result = array_merge($users_result, $api_friends->response[0]);
    }
   } elseif($dTask_section == 5) {
    // вступить
    $calls = array();
    $users_result = array();
    for($i = 0; $i < 16; $i++) {
     $calls[] = 'API.groups.getMembers({"group_id":'.$dTask_url.', "count": 1000, "offset": '.($i * 1000).'})';
    }
    $code = "return [" . @implode(',', $calls) . "];";
    $api = json_decode(@vk::_post(complaints::capi($code)));
 
    if($api->execute_errors || $api->error) { // ошибка сервера ВК
     $vk_error = 1;
    } else {
     foreach($api->response as $response_key => $response_value) {
      $users = $api->response[$response_key]->users;
      foreach($users as $key => $value) {
       $users_result[] = $value;
      }
     }
    }
   }
   
   // те, кто не выполнил задание
   $user_complaints = array_diff($dTaskDone_array, $users_result);
   $user_complaints_count = count($user_complaints);
 
   // проверяем на выполнение
   if($vk_error) {
    $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже. <br /> Причиной этого может быть недоступность ссылки или потеря соединения.');
   } else {    
    // списываем монеты и увеличиваем счётчик
    $user_points_minus = "UPDATE  `$dbName`.`users` SET  `upoints` =  upoints - 10, `complaints` = complaints + 1  WHERE  `users`.`uvk_id` IN(".@implode(',', $user_complaints).") LIMIT 1 ;";
    // редактируем время начала сканирования
    $task_edit_ctime = "UPDATE  `$dbName`.`tasks` SET  `complaints_time` =  '$time' WHERE  `tasks`.`tid` = '$task_id' LIMIT 1 ;";
    // получаем список uid'ов по их vk_id
    $qUid = $db->query("SELECT `uid` FROM `users` WHERE `uvk_id` IN(".@implode(',', $user_complaints).")");
    $dUid_array = array();
    while($dUid = $db->fetch($qUid)) {
     $dUid_array[] = $dUid['uid'];
    }
   
    // цикл для таблиц logs и complaints
    for($i = 0; $i < $user_complaints_count; $i++) {
     $for_uid = $dUid_array[$i];
     $user_complaints_logs .= "(NULL ,  '$user_id',  '$for_uid',  '$ip_address',  '$browser',  '$time',  '8', '1',  '$task_id',  '0',  '0',  '',  '10',  '1',  '0',  '0',  '0', '0'),";
     $user_complaints_add .= "(NULL ,  '$user_id',  '$for_uid',  '$dTask_section',  '$dTask_url',  '$task_id',  '$time',  '$dTask_type_normal'),";
    }
   
    if($user_complaints_count <= 0) {
     $db->query($task_edit_ctime);
     $json = array('success' => 1, 'users' => 0);
    } elseif($db->query("INSERT INTO `$dbName`.`logs` ( `lid` , `lfrom` , `lto` , `lip_address` , `lbrowser` , `ltime` , `lmodule` , `lmodule_type` , `lmid` , `lview1` , `lview2` , `ltext` , `lpoints` , `lhistory` , `lapi` , `lapp_id` , `lread`, `ladmin` ) VALUES ".preg_replace('/,$/', '', $user_complaints_logs)."") && $db->query("INSERT INTO `$dbName`.`complaints` ( `id` , `from` , `to` , `section` , `url` , `tid` , `time` , `type` ) VALUES ".preg_replace('/,$/', '', $user_complaints_add)."") && $db->query($user_points_minus) && $db->query($task_edit_ctime) && $db->query("UPDATE  `$dbName`.`tasks_done` SET  `tdread` =  '1' WHERE  `tasks_done`.`tdtid` = '$task_id' AND `tdtype` = 'done' AND `tdvk_id` IN(".@implode(',', $user_complaints).");")) {
     $complaints_user_to_points = $dTask_done_amount * $user_complaints_count;
     $db->query("UPDATE `$dbName`.`users` SET  `upoints` = `upoints` + '$complaints_user_to_points' WHERE  `users`.`uid` = '$user_id';");
     $logs->complaints_return($user_id, $task_id, $complaints_user_to_points);
     $json = array('success' => 1, 'users' => $user_complaints_count, 'points' => $complaints_user_to_points);
    } else {
     $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
    }
   }
  } else {
   $json = array('error_text' => 'Ошибка доступа.');
  }
  return json_encode($json);
 }
 
 public function list_table_user_num() {
  global $db, $user_id;
 
  $q = $db->query("SELECT `id` FROM `complaints` WHERE `to` = '$user_id'");
  $n = $db->num($q);
 
  return $n;
 }
 
 public function list_table_user() { // строим таблицу жалоб пользователя
  global $db, $user_id;
 
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 10;
 
  $q = $db->query("SELECT `section`, `url`, `type` FROM `complaints` WHERE `to` = '$user_id' ORDER BY `id` DESC LIMIT $start_limit, 10");
  while($d = $db->fetch($q)) {
   $section = $d['section'];
   $url = $d['url'];
   $type = $d['type'];
   
   if($type == 'group') {
    $url_result = 'public'.$url;
   } elseif($type == 'user') {
    $url_result = 'id'.$url;
   } else {
    $url_result = $type.''.$url;
   }
   
   if($section == 1) {
    $section_type = 'Снят лайк';
   } else if($section == 2) {
    $section_type = 'Удален репост';
   } else if($section == 4) {
    $section_type = 'Отписка от человека';
   } else if($section == 5) {
    $section_type = 'Выход из группы';
   }
   
   $template .= '
         <tr>
          <td class="column_content column_content_url_user"><a href="http://vk.com/'.$url_result.'" target="_blank">http://vk.com/'.$url_result.'</a></td>
          <td class="column_content column_content_status_user"><b>'.$section_type.'</b></td>
         </tr>
   ';
  }
  return $template;
 }
}
 
$complaints = new complaints;
?>