<?php
class stats {
 public function info() {
  global $db, $user_id, $ugroup;
  
  $uid = (int) $_GET['id'];
  $show = $db->escape($_GET['show']);
  
  if($ugroup != 4) {
   if($user_id != $uid) {
    return false;
    exit;
   }
  }
  
  if($show == 'today') {
   $tasks_date = 'AND UNIX_TIMESTAMP(CURRENT_DATE()) < `ttime';
   $tasks_done_date = 'AND UNIX_TIMESTAMP(CURRENT_DATE()) < `tdtime`';
  } elseif($show == 'yesterday') {
   $tasks_date = 'AND UNIX_TIMESTAMP(INTERVAL - 1 day + CURRENT_DATE()) < `ttime` AND UNIX_TIMESTAMP(CURRENT_DATE()) > `ttime`';
   $tasks_done_date = 'AND UNIX_TIMESTAMP(INTERVAL - 1 day + CURRENT_DATE()) < `tdtime` AND UNIX_TIMESTAMP(CURRENT_DATE()) > `tdtime`';
  } elseif($show == 'week') {
   $tasks_date = 'AND UNIX_TIMESTAMP(INTERVAL - 7 day + CURRENT_DATE()) < `ttime`';
   $tasks_done_date = 'AND UNIX_TIMESTAMP(INTERVAL - 7 day + CURRENT_DATE()) < `tdtime`';
  } elseif($show == 'month') {
   $tasks_date = 'AND UNIX_TIMESTAMP(INTERVAL - 30 day + CURRENT_DATE()) < `ttime`';
   $tasks_done_date = 'AND UNIX_TIMESTAMP(INTERVAL - 30 day + CURRENT_DATE()) < `tdtime`';
  } else {
   $add_date = '';
   $tasks_done_date = '';
  }
  
  $all_tasks_add_count_q = $db->query("SELECT COUNT(`tid`) AS `count` FROM `tasks` WHERE `tfrom` = '$uid' ".$tasks_date."");
  $all_tasks_add_count_d = $db->fetch($all_tasks_add_count_q);
  
  $all_tasks_done_count_q = $db->query("SELECT COUNT(`tdid`) as `count` FROM `tasks_done` WHERE `tduid` = '$uid' AND `tdtype` = 'done' ".$tasks_done_date."");
  $all_tasks_done_count_d = $db->fetch($all_tasks_done_count_q);
  
  $likes_tasks_done_count_q = $db->query("SELECT COUNT(`tdid`) as `count` FROM `tasks_done` WHERE `tduid` = '$uid' AND `tdtype` = 'done' AND `tdsection` = 1 ".$tasks_done_date."");
  $likes_tasks_done_count_d = $db->fetch($likes_tasks_done_count_q);
  
  $likes_tasks_done_count = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tduid` = '$uid' AND `tdtype` = 'done' AND `tdsection` = 1 ".$tasks_done_date.""));
  $reposts_tasks_done_count = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tduid` = '$uid' AND `tdtype` = 'done' AND `tdsection` = 2 ".$tasks_done_date.""));
  $comments_tasks_done_count = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tduid` = '$uid' AND `tdtype` = 'done' AND `tdsection` = 3 ".$tasks_done_date.""));
  $friends_tasks_done_count = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tduid` = '$uid' AND `tdtype` = 'done' AND `tdsection` = 4 ".$tasks_done_date.""));
  $groups_tasks_done_count = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tduid` = '$uid' AND `tdtype` = 'done' AND `tdsection` = 5 ".$tasks_done_date.""));
  $polls_tasks_done_count = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tduid` = '$uid' AND `tdtype` = 'done' AND `tdsection` = 6 ".$tasks_done_date.""));
  
  $all_tasks_hide_count = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tduid` = '$uid' AND `tdtype` = 'ignored' ".$tasks_done_date.""));
  $all_tasks_del_count = $db->num($db->query("SELECT `tid` FROM `tasks` WHERE `tfrom` = '$uid' AND `tdel` = 1 ".$tasks_date.""));
  
  return array('all_tasks_done_count' => $all_tasks_done_count_d['count'], 'likes_tasks_done_count' => $likes_tasks_done_count_d['count'], 'reposts_tasks_done_count' => $reposts_tasks_done_count, 'comments_tasks_done_count' => $comments_tasks_done_count, 'friends_tasks_done_count' => $friends_tasks_done_count, 'groups_tasks_done_count' => $groups_tasks_done_count, 'polls_tasks_done_count' => $polls_tasks_done_count, 'all_tasks_hide_count' => $all_tasks_hide_count, 'all_tasks_add_count' => $all_tasks_add_count_d['count'], 'all_tasks_del_count' => $all_tasks_del_count);
 }
 
 public function admin_users() {
  global $db;
  
  $all_points_q = $db->query("SELECT SUM(`upoints`) as `points` FROM `users`");
  $all_points_d = $db->fetch($all_points_q);
  
  $all_users_reg = $db->num($db->query("SELECT `uid` FROM `users`"));
  $users_aemail = $db->num($db->query("SELECT `uid` FROM `users` WHERE `uemail_activated` = 1"));
  $users_avk = $db->num($db->query("SELECT `uid` FROM `users` WHERE `uvk_id` > 0"));
  $users_mydel = $db->num($db->query("SELECT `uid` FROM `users` WHERE `udel` = 1"));
  $users_blocked = $db->num($db->query("SELECT `id` FROM `logs_blocked` WHERE `type` = 1 GROUP BY `to`"));
  $users_unblocked = $db->num($db->query("SELECT `id` FROM `logs_blocked` WHERE `type` = 2 GROUP BY `to`"));
  $users_ref = $db->num($db->query("SELECT `id` FROM `ref`"));
  
  $games_box_all = $db->num($db->query("SELECT `lid` FROM `logs`  WHERE `lmodule` = 10"));
  $games_box_win = ($db->num($db->query("SELECT `lid` FROM `logs`  WHERE `lmodule` = 10 AND `lmodule_type`= 2")))*150;
  $games_box_lose = ($db->num($db->query("SELECT `lid` FROM `logs`  WHERE `lmodule` = 10 AND `lmodule_type`= 1")))*50;
  
  $all_users_reg_today = $db->num($db->query("SELECT `uid` FROM `users` WHERE UNIX_TIMESTAMP(CURRENT_DATE()) < `ureg_time`"));
  $user_last_visit_today = $db->num($db->query("SELECT `uid` FROM `users` WHERE UNIX_TIMESTAMP(CURRENT_DATE()) < `ulast_time`"));
  $users_ref_today = $db->num($db->query("SELECT `id` FROM `ref` WHERE UNIX_TIMESTAMP(CURRENT_DATE()) < `time`"));
  $users_blocked_today = $db->num($db->query("SELECT `id` FROM `logs_blocked` WHERE `type` = 1 AND UNIX_TIMESTAMP(CURRENT_DATE()) < `time` GROUP BY `to`"));
  $users_unblocked_today = $db->num($db->query("SELECT `id` FROM `logs_blocked` WHERE `type` = 2 AND UNIX_TIMESTAMP(CURRENT_DATE()) < `time` GROUP BY `to`"));
  
  $all_users_reg_yesterday = $db->num($db->query("SELECT `uid` FROM `users` WHERE UNIX_TIMESTAMP(INTERVAL - 1 day + CURRENT_DATE()) < `ureg_time` AND UNIX_TIMESTAMP(CURRENT_DATE()) > `ureg_time`"));
  $users_ref_yesterday = $db->num($db->query("SELECT `id` FROM `ref` WHERE UNIX_TIMESTAMP(INTERVAL - 1 day + CURRENT_DATE()) < `time` AND UNIX_TIMESTAMP(CURRENT_DATE()) > `time`"));
  $users_blocked_yesterday = $db->num($db->query("SELECT `id` FROM `logs_blocked` WHERE `type` = 1 AND UNIX_TIMESTAMP(INTERVAL - 1 day + CURRENT_DATE()) < `time` AND UNIX_TIMESTAMP(CURRENT_DATE()) > `time` GROUP BY `to`"));
  $users_unblocked_yesterday = $db->num($db->query("SELECT `id` FROM `logs_blocked` WHERE `type` = 2 AND UNIX_TIMESTAMP(INTERVAL - 1 day + CURRENT_DATE()) < `time` AND UNIX_TIMESTAMP(CURRENT_DATE()) > `time` GROUP BY `to`"));
  
  return array('all_points' => $all_points_d['points'],'games_box_win' => $games_box_win ,'games_box_lose' => $games_box_lose , 'games_box_all' => $games_box_all,'all_users_reg' => $all_users_reg, 'users_aemail' => $users_aemail, 'users_avk' => $users_avk, 'users_mydel' => $users_mydel, 'users_blocked' => $users_blocked, 'users_unblocked' => $users_unblocked, 'users_ref' => $users_ref, 'all_users_reg_today' => $all_users_reg_today, 'user_last_visit_today' => $user_last_visit_today, 'users_ref_today' => $users_ref_today, 'users_blocked_today' => $users_blocked_today, 'users_unblocked_today' => $users_unblocked_today, 'all_users_reg_yesterday' => $all_users_reg_yesterday, 'users_ref_yesterday' => $users_ref_yesterday, 'users_blocked_yesterday' => $users_blocked_yesterday, 'users_unblocked_yesterday' => $users_unblocked_yesterday);
 }
 
 public function admin_tasks() {
  global $db;

  $all_tasks_add = $db->num($db->query("SELECT `tid` FROM `tasks`"));
  $all_tasks_active = $db->num($db->query("SELECT `tid` FROM `tasks` WHERE `tsuccess` = 0 AND `tdel` = 0 AND `tdel_admin` = 0 AND `tblocked` = 0"));
  $all_tasks_done = $db->num($db->query("SELECT `tid` FROM `tasks` WHERE `tsuccess` = 1"));
  $all_tasks_done_all = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tdtype` = 'done'"));
  $all_tasks_del = $db->num($db->query("SELECT `tid` FROM `tasks` WHERE `tdel` = 1 OR `tdel_admin` = 1"));
  $all_tasks_blocked = $db->num($db->query("SELECT `tid` FROM `tasks` WHERE `tblocked` = 1"));
  $all_tasks_hide = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tdtype` = 'ignored' GROUP BY `tdtid`"));
  
  $all_tasks_add_today = $db->num($db->query("SELECT `tid` FROM `tasks` WHERE UNIX_TIMESTAMP(CURRENT_DATE()) < `ttime`"));
  $all_tasks_done_all_today = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tdtype` = 'done' AND UNIX_TIMESTAMP(CURRENT_DATE()) < `tdtime`"));
  $all_tasks_hide_today = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tdtype` = 'ignored' AND UNIX_TIMESTAMP(CURRENT_DATE()) < `tdtime` GROUP BY `tdtid`"));
  
  $all_tasks_add_yesterday = $db->num($db->query("SELECT `tid` FROM `tasks` WHERE UNIX_TIMESTAMP(INTERVAL - 1 day + CURRENT_DATE()) < `ttime` AND UNIX_TIMESTAMP(CURRENT_DATE()) > `ttime`"));
  $all_tasks_done_all_yesterday = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tdtype` = 'done' AND UNIX_TIMESTAMP(INTERVAL - 1 day + CURRENT_DATE()) < `tdtime` AND UNIX_TIMESTAMP(CURRENT_DATE()) > `tdtime`"));
  $all_tasks_hide_yesterday = $db->num($db->query("SELECT `tdid` FROM `tasks_done` WHERE `tdtype` = 'ignored' AND UNIX_TIMESTAMP(INTERVAL - 1 day + CURRENT_DATE()) < `tdtime` AND UNIX_TIMESTAMP(CURRENT_DATE()) > `tdtime` GROUP BY `tdtid`"));
  
  return array('all_tasks_add' => $all_tasks_add, 'all_tasks_active' => $all_tasks_active, 'all_tasks_done' => $all_tasks_done, 'all_tasks_del' => $all_tasks_del, 'all_tasks_blocked' => $all_tasks_blocked, 'all_tasks_done_all' => $all_tasks_done_all, 'all_tasks_hide' => $all_tasks_hide, 'all_tasks_add_today' => $all_tasks_add_today, 'all_tasks_done_all_today' => $all_tasks_done_all_today, 'all_tasks_hide_today' => $all_tasks_hide_today, 'all_tasks_add_yesterday' => $all_tasks_add_yesterday, 'all_tasks_done_all_yesterday' => $all_tasks_done_all_yesterday, 'all_tasks_hide_yesterday' => $all_tasks_hide_yesterday);
 }
 
 public function admin_complaints() {
  global $db;
  
  $all_complaints = $db->num($db->query("SELECT `id` FROM `complaints`"));
  $all_complaints_today = $db->num($db->query("SELECT `id` FROM `complaints` WHERE UNIX_TIMESTAMP(CURRENT_DATE()) < `time`"));
  $all_complaints_yesterday = $db->num($db->query("SELECT `id` FROM `complaints` WHERE UNIX_TIMESTAMP(INTERVAL - 1 day + CURRENT_DATE()) < `time` AND UNIX_TIMESTAMP(CURRENT_DATE()) > `time`"));
  
  return array('all_complaints' => $all_complaints, 'all_complaints_today' => $all_complaints_today, 'all_complaints_yesterday' => $all_complaints_yesterday);
 }
 
 public function admin_pays_num() {
  global $db;
  
  $q = $db->query("SELECT COUNT(`id`) as `count` FROM `logs_pay_points` WHERE `status` = 1");
  $d = $db->fetch($q);
  
  return $d['count'];
 }
 
 public function admin_pays() {
  global $db, $site_url;
  
  $num = stats::admin_pays_num();
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 10;

  $q = $db->query("
   SELECT users.uname, users.ulast_name, users.ugender, logs_pay_points.uid, logs_pay_points.type, logs_pay_points.points, logs_pay_points.time
   FROM `logs_pay_points`
    INNER JOIN `users` ON users.uid = logs_pay_points.uid
   WHERE logs_pay_points.status = 1
   ORDER BY logs_pay_points.id DESC
   LIMIT $start_limit, 10
  ");
  while($d = $db->fetch($q)) {
   $first_name = $d['uname'];
   $last_name = $d['ulast_name'];
   $gender = $d['ugender'];
   $uid = $d['uid'];
   $type = $d['type'];
   $points = $d['points'];
   $time = $d['time'];
   
   if($type == 1) {
    $type_r = '<span class="pay_stat_wm"><b>WebMoney</b></span>';
   } elseif($type == 2) {
    $type_r = '<span class="pay_stat_telephone"><b>телефон</b></span>';
   } elseif($type == 3) {
    $type_r = '<span class="pay_stat_qiwi"><b>Visa Qiwi Wallet</b></span>';
   } else {
    $type_r = '';
   }
   
   $template .= '
        <div class="pay_stats_overflow">
         <a href="/admin/modules/users/?search='.$site_url.'id'.$uid.'" onclick="nav.go(this); return false"><b>'.no_name($first_name.' '.$last_name).'</b></a> купил'.($gender == 1 ? 'а' : '').' <b>'.$points.' '.declOfNum($points, array('балл', 'балла', 'баллов')).'</b> через '.$type_r.' <span class="pay_stat_date">'.new_time($time).'</span>
        </div>
   ';
  }
  return array('template' => $template, 'num' => $num);
 }
}

$stats = new stats;
?>