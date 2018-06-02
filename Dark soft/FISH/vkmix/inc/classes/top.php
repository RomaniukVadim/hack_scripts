<?php
class top {
 public function users_list() {
  global $db;
  
  $act = $db->escape($_GET['act']);
  
  if($act == 'refs') {
   $q = $db->query("
    SELECT `users`.`uid`, `users`.`uavatar`, `users`.`uvk_id`, `users`.`uname`, `users`.`ulast_name`, `users`.`ulast_time`, `users`.`ugender`,
    COUNT(`ref`.`id`) as `count_refs`
    FROM `ref`
     INNER JOIN `users` ON (`ref`.`to` = `users`.`uid`)
    WHERE `users`.`uban_type` = 0 AND `users`.`uvk_id` > 0
    GROUP BY `ref`.`to`
    ORDER BY `count_refs` DESC
    LIMIT 100
   ");
   $i = 1;
   while($d = $db->assoc($q)) {
    $top_avatar = $d['uavatar'];
    $top_vk_id = $d['uvk_id'];
    $top_first_name = $d['uname'];
    $top_last_name = $d['ulast_name'];
    $top_last_time = $d['ulast_time'];
    $top_gender = $d['ugender'];
    $count_refs = $d['count_refs'];
    
    if($i == 1) {
     $top_user_num = '<div class="top_user_num_one">'.$i.'</div>';
    } elseif($i == 2) {
     $top_user_num = '<div class="top_user_num_two">'.$i.'</div>';
    } elseif($i == 3) {
     $top_user_num = '<div class="top_user_num_three">'.$i.'</div>';
    } else {
     $top_user_num = '<div class="top_user_num_no">'.$i.'</div>';
    }
    
    $template .= '
          <div class="top_user_overflow'.($i%2 ? '' : ' top_active').'">
           <div class="top_user_avatar"><a href="http://vk.com/id'.$top_vk_id.'" target="_blank"><img src="'.no_avatar($top_avatar).'"></a></div>
           <div class="top_user_info">
            <div class="top_user_name"><a href="http://vk.com/id'.$top_vk_id.'" target="_blank">'.no_name($top_first_name.' '.$top_last_name).'</a> <span class="top_time">заходил'.($top_gender == 2 ? '' : 'а').' '.new_time($top_last_time).'</span></div>
            <div class="top_user_tasks">Пригласил'.($top_gender == 2 ? '' : 'а').' <b>'.$count_refs.' '.declOfNum($count_refs, array('друга', 'друга', 'друзей')).'</b></div>
           </div>
           <div class="top_user_num">'.$top_user_num.'</div>
          </div>
    ';
    $i++;
   }
  } else {
   $q = $db->query("
    SELECT `users`.`uid`, `users`.`uavatar`, `users`.`uvk_id`, `users`.`uname`, `users`.`ulast_name`, `users`.`ulast_time`, `users`.`ugender`,
    COUNT(`tasks_done`.`tdid`) as `count_tasks_done`
    FROM `tasks_done`
     INNER JOIN `users` ON (`tasks_done`.`tduid` = `users`.`uid`)
    WHERE `tasks_done`.`tdtype` = 'done' AND `users`.`uban_type` = 0 AND `users`.`udel` = 0
    GROUP BY `tasks_done`.`tduid`
    ORDER BY `count_tasks_done` DESC
    LIMIT 100
   ");
   $i = 1;
   while($d = $db->assoc($q)) {
    $top_avatar = $d['uavatar'];
    $top_vk_id = $d['uvk_id'];
    $top_first_name = $d['uname'];
    $top_last_name = $d['ulast_name'];
    $top_last_time = $d['ulast_time'];
    $top_gender = $d['ugender'];
    $count_tasks_done = $d['count_tasks_done'];
    
    if($i == 1) {
     $top_user_num = '<div class="top_user_num_one">'.$i.'</div>';
    } elseif($i == 2) {
     $top_user_num = '<div class="top_user_num_two">'.$i.'</div>';
    } elseif($i == 3) {
     $top_user_num = '<div class="top_user_num_three">'.$i.'</div>';
    } else {
     $top_user_num = '<div class="top_user_num_no">'.$i.'</div>';
    }
    
    $template .= '
          <div class="top_user_overflow'.($i%2 ? '' : ' top_active').'">
           <div class="top_user_avatar"><a href="http://vk.com/id'.$top_vk_id.'" target="_blank"><img src="'.$top_avatar.'"></a></div>
           <div class="top_user_info">
            <div class="top_user_name"><a href="http://vk.com/id'.$top_vk_id.'" target="_blank">'.$top_first_name.' '.$top_last_name.'</a> <span class="top_time">заходил'.($top_gender == 2 ? '' : 'а').' '.new_time($top_last_time).'</span></div>
            <div class="top_user_tasks">Выполнено <b>'.$count_tasks_done.' '.declOfNum($count_tasks_done, array('задание', 'задания', 'заданий')).'</b></div>
           </div>
           <div class="top_user_num">'.$top_user_num.'</div>
          </div>
    ';
    $i++;
   }
  }
  return $template;
 }
}

$top = new top;
?>