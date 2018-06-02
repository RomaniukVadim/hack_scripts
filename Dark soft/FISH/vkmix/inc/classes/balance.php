<?php
class balance {
 public function my_history_num() {
  global $db, $user_id;
  
  $q = $db->query("
   SELECT `lid` FROM `logs`
   WHERE `lhistory` = 1 AND `lto` = '$user_id' AND `lpoints` > 0
  ");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function my_history() {
  global $db, $user_id;
  
  $page = (int) abs($_GET['page']) * 10;
  
  $q = $db->query("
   SELECT logs.lid, logs.lfrom, logs.lto, logs.lmodule, logs.lmodule_type, logs.lpoints, logs.lbrowser, logs.ltime, logs.lip_address, logs.lmid, logs.ltext, users1.uname as uname1, users1.ulast_name as ulast_name1, users1.uvk_id as uvk_id1, users2.uname as uname2, users2.ulast_name as ulast_name2 FROM `logs`
    INNER JOIN users users1 ON logs.lfrom = users1.uid
    LEFT JOIN users users2 ON logs.lto = users2.uid
   WHERE logs.lhistory = 1 AND logs.lto = '$user_id' AND logs.lpoints > 0
   ORDER BY logs.lid DESC
   LIMIT $page, 10
  ");
  while($d = $db->fetch($q)) {
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
   $users_uvk_id1 = $d['uvk_id1'];
   $users_uvk_id1_url_result = $users_uvk_id1 ? '<a href="http://vk.com/id'.$users_uvk_id1.'" target="_blank">' : '<a href="javascript://">';
   $users1_fullname = $users_uname1 ? $users_uname1.' '.$users_ulast_name1 : 'Безымянный';
   $users2_fullname = $users_uname2 ? $users_uname2.' '.$users_ulast_name2 : 'Безымянный';
   
   if($logs_lmodule == 4 && $logs_lmodule_type == 1) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_add_task"></div></div>
         <div class="balance_overflow_text">Создание нового задания</div>
         <div class="balance_overflow_points_minus">-'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    ';
   } elseif($logs_lmodule == 4 && $logs_lmodule_type == 3) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_edit_task"></div></div>
         <div class="balance_overflow_text">Редактирование задания</div>
         <div class="balance_overflow_points_minus">-'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    ';
   } elseif($logs_lmodule == 4 && $logs_lmodule_type == 2) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_delete_task"></div></div>
         <div class="balance_overflow_text">Удаление задания</div>
         <div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   } elseif($logs_lmodule == 4 && $logs_lmodule_type == 6) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_done_task"></div></div>
         <div class="balance_overflow_text">Выполнение задания</div>
         <div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   } elseif($logs_lmodule == 1 && $logs_lmodule_type == 6) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_ref"></div></div>
         <div class="balance_overflow_text">Новый реферал '.$users_uvk_id1_url_result.''.$users1_fullname.'</a></div>
         <div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   } elseif($logs_lmodule == 1 && $logs_lmodule_type == 5) {
    $result_points = $logs_ltext == 1 ? '<div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>' : '<div class="balance_overflow_points_minus">-'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>';
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_edit_admin"></div></div>
         <div class="balance_overflow_text">Изменение баланса администрацией</div>
         '.$result_points.'
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   } elseif($logs_lmodule == 1 && $logs_lmodule_type == 9) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_percent"></div></div>
         <div class="balance_overflow_text">Начисление процентов с заработка реферала</div>
         <div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   } elseif($logs_lmodule == 8 && $logs_lmodule_type == 1) {
    $result_points = $logs_ltext == 1 ? '<div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>' : '<div class="balance_overflow_points_minus">-'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>';
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_complaints"></div></div>
         <div class="balance_overflow_text">Штраф за невыполнение задания</div>
         '.$result_points.'
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   } elseif($logs_lmodule == 8 && $logs_lmodule_type == 2) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_complaints"></div></div>
         <div class="balance_overflow_text">Компенсация</div>
         <div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   } elseif($logs_lmodule == 9 && $logs_lmodule_type == 1) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_edit_admin"></div></div>
         <div class="balance_overflow_text">Зачисление баллов через «WebMoney»</div>
         <div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   } elseif($logs_lmodule == 9 && $logs_lmodule_type == 2) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_edit_admin"></div></div>
         <div class="balance_overflow_text">Зачисление баллов через телефон</div>
         <div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   } elseif($logs_lmodule == 9 && $logs_lmodule_type == 3) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_edit_admin"></div></div>
         <div class="balance_overflow_text">Зачисление баллов через «Visa QIWI Wallet»</div>
         <div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   }elseif($logs_lmodule == 9 && $logs_lmodule_type == 5) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_edit_admin"></div></div>
         <div class="balance_overflow_text">Зачисление баллов через «Way To Pay»</div>
         <div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   } elseif($logs_lmodule == 10 && $logs_lmodule_type == 1) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_game_lose"></div></div>
         <div class="balance_overflow_text">Проигрыш в игре "Сундук"</div>
         <div class="balance_overflow_points_minus">-'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   } elseif($logs_lmodule == 10 && $logs_lmodule_type == 2) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_game_win"></div></div>
         <div class="balance_overflow_text">Выйгрыш в игре "Сундук"</div>
         <div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   } elseif($logs_lmodule == 11 && $logs_lmodule_type == 1) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_game_lose"></div></div>
         <div class="balance_overflow_text">Создание купона</div>
         <div class="balance_overflow_points_minus">-'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   }elseif($logs_lmodule == 11 && $logs_lmodule_type == 2) {
    $template .= '
        <div class="balance_overflow">
         <div class="balance_overflow_icon"><div class="balance_icons balance_icons_game_win"></div></div>
         <div class="balance_overflow_text">Активация купона</div>
         <div class="balance_overflow_points_plus">+'.$logs_lpoints.' '.declOfNum($logs_lpoints, array('балл', 'балла', 'баллов')).'</div>
         <div class="balance_overflow_date">'.new_time($logs_ltime).'</div>
        </div>
    '; 
   }
  }
  return $template;
 }
}

$balance = new balance;
?>