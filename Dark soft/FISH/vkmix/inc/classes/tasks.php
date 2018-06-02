<?php
class tasks {
 public function getCatNum($cat_name = null) { // определяем id отдельной категории по системному имени
  // название категорий
  $catsName = array('like', 'likes', 'repost', 'reposts', 'comment', 'comments', 'friend', 'friends', 'group', 'groups', 'poll', 'polls');

  // id категорий
  $catsId = array(
   0 => '',
   1 => array(0 => $catsName[0], 1 => $catsName[1]),
   2 => array(2 => $catsName[2], 3 => $catsName[3]),
   3 => array(4 => $catsName[4], 5 => $catsName[5]),
   4 => array(6 => $catsName[6], 7 => $catsName[7]),
   5 => array(8 => $catsName[8], 9 => $catsName[9]),
   6 => array(10 => $catsName[10], 11 => $catsName[11])
  );
  
  for($i = 0; $i < count($catsId); $i++) {
   if(@in_array($cat_name, $catsId[$i])) {
    // выводим id категории
    return $i;
   }
  }
 }
 
 public function filter($text = null) {
  // фильтрация слов
  $text = mb_strtolower($text, 'UTF-8');
  
  if(preg_match('/беспл/i', $text) && preg_match('/гол/i', $text)) $error = 1;
  elseif(preg_match('/биспл/i', $text) && preg_match('/гол/i', $text)) $error = 1;
  elseif(preg_match('/беспл/i', $text) && preg_match('/зараб/i', $text)) $error = 1;
  elseif(preg_match('/беспл/i', $text) && preg_match('/подарк/i', $text)) $error = 1;
  elseif(preg_match('/беспл/i', $text) && preg_match('/падарк/i', $text)) $error = 1;
  elseif(preg_match('/биспл/i', $text) && preg_match('/подарк/i', $text)) $error = 1;
  elseif(preg_match('/биспл/i', $text) && preg_match('/падарк/i', $text)) $error = 1;
  elseif(preg_match('/биспл/i', $text) && preg_match('/зараб/i', $text)) $error = 1;
  elseif(preg_match('/биспл/i', $text) && preg_match('/зароб/i', $text)) $error = 1;
  elseif(preg_match('/серв/i', $text) && preg_match('/продв/i', $text)) $error = 1;
  elseif(preg_match('/серв/i', $text) && preg_match('/накру/i', $text)) $error = 1;
  elseif(preg_match('/накрут/i', $text) && preg_match('/вк/i', $text)) $error = 1;
  elseif(preg_match('/накрут/i', $text) && preg_match('/др/i', $text)) $error = 1;
  elseif(preg_match('/накрут/i', $text) && preg_match('/подп/i', $text)) $error = 1;
  elseif(preg_match('/накрут/i', $text) && preg_match('/падп/i', $text)) $error = 1;
  elseif(preg_match('/накрут/i', $text) && preg_match('/лайк/i', $text)) $error = 1;
  elseif(preg_match('/накрут/i', $text) && preg_match('/ком/i', $text)) $error = 1;
  elseif(preg_match('/накрут/i', $text) && preg_match('/реп/i', $text)) $error = 1;
  elseif(preg_match('/накрут/i', $text) && preg_match('/рас/i', $text)) $error = 1;
  elseif(preg_match('/программа/i', $text) && preg_match('/дл/i', $text) && preg_match('/вк/i', $text)) $error = 1;
  elseif(preg_match('/праграмма/i', $text) && preg_match('/дл/i', $text) && preg_match('/вк/i', $text)) $error = 1;
  elseif(preg_match('/праграма/i', $text) && preg_match('/дл/i', $text) && preg_match('/вк/i', $text)) $error = 1;
  elseif(preg_match('/програма/i', $text) && preg_match('/дл/i', $text) && preg_match('/вк/i', $text)) $error = 1;
  elseif(preg_match('/серв/i', $text) && preg_match('/зар/i', $text)) $error = 1;
  elseif(preg_match('/зараб/i', $text) && preg_match('/вк/i', $text)) $error = 1;
  elseif(preg_match('/науч/i', $text) && preg_match('/зараб/i', $text)) $error = 1;
  elseif(preg_match('/зара/i', $text) && preg_match('/интерне/i', $text)) $error = 1;
  elseif(preg_match('/зараб/i', $text) && preg_match('/сет/i', $text)) $error = 1;
  elseif(preg_match('/зараб/i', $text) && preg_match('/сейч/i', $text)) $error = 1;
  elseif(preg_match('/беспл/i', $text) && preg_match('/порн/i', $text)) $error = 1;
  elseif(preg_match('/ска/i', $text) && preg_match('/порн/i', $text)) $error = 1;
  elseif(preg_match('/жестк/i', $text) && preg_match('/порн/i', $text)) $error = 1;
  elseif(preg_match('/жёстк/i', $text) && preg_match('/порн/i', $text)) $error = 1;
  elseif(preg_match('/жостк/i', $text) && preg_match('/порн/i', $text)) $error = 1;
  elseif(preg_match('/гор/i', $text) && preg_match('/порн/i', $text)) $error = 1;
  elseif(preg_match('/онл/i', $text) && preg_match('/порн/i', $text)) $error = 1;
  elseif(preg_match('/беспл/i', $text) && preg_match('/секс/i', $text)) $error = 1;
  elseif(preg_match('/ска/i', $text) && preg_match('/секс/i', $text)) $error = 1;
  elseif(preg_match('/жестк/i', $text) && preg_match('/секс/i', $text)) $error = 1;
  elseif(preg_match('/жёстк/i', $text) && preg_match('/секс/i', $text)) $error = 1;
  elseif(preg_match('/жостк/i', $text) && preg_match('/секс/i', $text)) $error = 1;
  elseif(preg_match('/гор/i', $text) && preg_match('/секс/i', $text)) $error = 1;
  elseif(preg_match('/онл/i', $text) && preg_match('/секс/i', $text)) $error = 1;
  elseif(preg_match('/нан/i', $text) && preg_match('/порч/i', $text)) $error = 1;
  elseif(preg_match('/онлай/i', $text) && preg_match('/маг/i', $text)) $error = 1;
  elseif(preg_match('/онлай/i', $text) && preg_match('/гадан/i', $text)) $error = 1;
  elseif(preg_match('/soc-service.ru/i', $text)) $error = 1;
  elseif(preg_match('/soc-service.com/i', $text)) $error = 1;
  elseif(preg_match('/snebes.ru/i', $text)) $error = 1;
  elseif(preg_match('/easybot.by/i', $text)) $error = 1;
  elseif(preg_match('/vktarget.ru/i', $text)) $error = 1;
  elseif(preg_match('/piar-groups.ru/i', $text)) $error = 1;
  elseif(preg_match('/sarafanka.org/i', $text)) $error = 1;
  elseif(preg_match('/montytool.by/i', $text)) $error = 1;
  elseif(preg_match('/smmok2.ru/i', $text)) $error = 1;
  elseif(preg_match('/vprka.com/i', $text)) $error = 1;
  
  return $error;
 }
 
 public function comment_filter($text = null) {
  // фильтрация комментариев
  $text = mb_strtolower($text, 'UTF-8');
  
  if(preg_match('/?/i', $text)) $error = 1;
  elseif(preg_match('/&(.*?);/i', $text)) $error = 1;
  
  return $error;
 }
 
 public function comment_normal($text = null) {
  $a1 = array('--', '<<', '>>');
  $a2 = array('—', '«', '»');
  return preg_replace('/\s{2,}/i', ' ', trim(str_replace($a1, $a2, $text)));
 }
 
 public function get_comment($id = null) {
  global $db;
  
  $id = (int) abs($id);
  
  $q = $db->query("SELECT `tcomments` FROM `tasks` WHERE `tid` = '$id'");
  $d = $db->fetch($q);
  $comments_list = $d['tcomments'];
  $comments_list_explode = explode('`', $comments_list);
  array_pop($comments_list_explode);
  $comments_rand = $comments_list_explode[rand(0, count($comments_list_explode) - 1)];
  
  if($comments_list) {
   return tasks::comment_normal($comments_rand);
  } else {
   return 'Access Denied';
  }
 }
 
 public function check_blacklist($type = null, $url = null, $id = null) {
  global $db;
  if($id) {
   $q = $db->query("SELECT `id` FROM `tasks_blacklist` WHERE `type` = '$type' AND `url` = '$url' OR `url` = '$id' AND `del` = '0' AND `status` != '1' AND `status` != '2'");
  } else {
   $q = $db->query("SELECT `id` FROM `tasks_blacklist` WHERE `type` = '$type' AND `url` = '$url' AND `del` = '0' AND `status` != '1' AND `status` != '2'");
  }
  $d = $db->fetch($q);
  
  return $d['id'] ? 1 : 0;
 }
 
 public function all_tasks_num($uid = null, $section = null, $usearch = null) { // получаем количество всех заданий для пользователя
  global $db, $vk, $ugroup, $user_id;
  
  $section_id = (int) abs($section);
  $section_sql = $section_id ? "AND tasks.tsection = '$section_id'" : '';
  if(preg_match('/uid:([0-9]+)/', $usearch, $search_matches) && $ugroup == 4) {
   $search_matches_uid = (int) $search_matches[1];
   $search_sql = "AND tasks.tfrom = '$search_matches_uid'";
  } elseif(preg_match('/tid:([0-9]+)/', $usearch, $search_matches) && $ugroup == 4) {
   $search_matches_tid = (int) $search_matches[1];
   $search_sql = "AND tasks.tid = '$search_matches_tid'";
  } elseif($vk->url($usearch)) {
   $search = json_decode($vk->screen_name($usearch), true);
   $search_type = $db->escape($search['type']);
   $search_url = $db->escape($search['url']);
   $search_sql = $search ? "AND tasks.ttype = '$search_type' AND tasks.turl = '$search_url'" : '';
  } else {
   $search_sql = '';
  }
  $del_admin_sql = ($ugroup == 4 || $ugroup == 3) ? "" : "AND tasks.tdel_admin = 0";
  $del_sql = ($ugroup == 4 || $ugroup == 3) ? "" : "AND tasks.tdel = 0";
  $usession = (int) abs($params['usession']);
  
  if($ugroup == 4 || $ugroup == 3) {
   $q = $db->query("
    SELECT COUNT(tasks.tid) as `count`
    FROM `tasks`
    WHERE tasks.tid > 0 ".$section_sql." ".$search_sql." ".$del_sql." ".$del_admin_sql."
   "); 
  } else {
   $q = $db->query("
    SELECT COUNT(tasks.tid) as `count`
    FROM `tasks`
    WHERE tasks.tid NOT IN (SELECT `tdtid` FROM `tasks_done` WHERE `tduid` = '$uid') ".$section_sql." ".$search_sql." ".$del_sql." ".$del_admin_sql." AND tasks.tfrom != '$uid' AND tasks.tblocked = 0 AND tasks.tsuccess = 0
   ");
  }
  $d = $db->fetch($q);
  
  return $d['count'];
 }
 
 public function my_tasks_num($uid = null, $section = null, $cat = null, $usearch = null) { // получаем количество моих заданий для пользователя
  global $db, $vk;
  
  $section_id = (int) abs($section);
  $section_sql = $section_id ? "`tsection` = '$section_id' AND" : '';
  $cat_id = (int) abs($cat);
  $cat_sql = $cat_id ? "`tcat` = '$cat_id' AND" : '';
  if($vk->url($usearch)) {
   $search = json_decode($vk->screen_name($usearch), true);
   $search_type = $db->escape($search['type']);
   $search_url = $db->escape($search['url']);
   $search_sql = $search ? "`ttype` = '$search_type' AND `turl` = '$search_url' AND" : '';
  } else {
   $search_sql = '';
  }
  
  $q = $db->query("
   SELECT COUNT(`tid`) as `count`
   FROM `tasks`
   WHERE ".$section_sql." ".$cat_sql." ".$search_sql." `tfrom` = '$uid' AND `tdel` = '0'
  ");
  $d = $db->fetch($q);
  
  return $d['count'];
 }
 
 public function all_tasks($params = null) {
  global $db, $user_logged, $ugroup, $vk, $site_url;
  
  $tasks_num = (int) abs($params['tasks_num']);
  $uid = (int) abs($params['uid']);
  $section_id = (int) abs($params['section']);
  $section_sql = $section_id ? "AND tasks.tsection = '$section_id'" : '';
  $page = (int) abs($params['page']) * 10;
  if(preg_match('/uid:([0-9]+)/', $params['search'], $search_matches) && $ugroup == 4) {
   $search_matches_uid = (int) $search_matches[1];
   $search_sql = "AND tasks.tfrom = '$search_matches_uid'";
  } elseif(preg_match('/tid:([0-9]+)/', $params['search'], $search_matches) && $ugroup == 4) {
   $search_matches_tid = (int) $search_matches[1];
   $search_sql = "AND tasks.tid = '$search_matches_tid'";
  } elseif($vk->url($params['search'])) {
   $search = json_decode($vk->screen_name($params['search']), true);
   $search_type = $db->escape($search['type']);
   $search_url = $db->escape($search['url']);
   $search_sql = $search ? "AND tasks.ttype = '$search_type' AND tasks.turl= '$search_url'" : '';
  } else {
   $search_sql = '';
  }
  $del_admin_sql = ($ugroup == 4 || $ugroup == 3) ? "" : "AND tasks.tdel_admin = '0'";
  $del_sql = ($ugroup == 4 || $ugroup == 3) ? "" : "AND tasks.tdel = '0'";
  $sort = $db->escape($params['sort']);
  
  if($sort == 'amount') {
   $sort_sql = "ORDER BY tasks.tamount DESC";
  } elseif($sort == 'popular') {
   $sort_sql = "ORDER BY tasks.tdone_count DESC";
  } elseif($sort == 'date') {
   $sort_sql = "ORDER BY tasks.ttime DESC";
  } else {
   $sort_sql = "ORDER BY tasks.tamount DESC";
  }
  
  $usession = (int) abs($params['usession']);
  
  if(!$user_logged) {
   return '';
   exit;
  }
  
  if($ugroup == 4 || $ugroup == 3) {
   $q = $db->query("
    SELECT tasks.tid, tasks.tfrom, tasks.tsection, tasks.ttype, tasks.ttitle, tasks.tcomments, tasks.turl, tasks.timg, tasks.tamount, tasks.tcount, tasks.tdone_count, tasks.tip_address, tasks.ttime, tasks.tdel, tasks.tdel_admin, tasks.tedit_time, users.uname, users.ulast_name, tasks.tsuccess, tasks.tblocked
    FROM `tasks`
     LEFT OUTER JOIN `users` ON tasks.tfrom = users.uid
    WHERE tasks.tid > 0 ".$section_sql." ".$search_sql." ".$del_sql." ".$del_admin_sql."
    ".$sort_sql."
    LIMIT $page, 10
   ");
  } else {
   $q = $db->query("
    SELECT tasks.tid, tasks.tsection, tasks.ttype, tasks.ttitle, tasks.tcomments, tasks.turl, tasks.timg, tasks.tamount, tasks.tcount, tasks.tdone_count, tasks.tip_address, tasks.ttime, tasks.tdel, tasks.tdel_admin, tasks.tedit_time, tasks.tblocked
    FROM `tasks`
    WHERE `tid` NOT IN (SELECT `tdtid` FROM `tasks_done` WHERE `tduid` = '$uid') ".$section_sql." ".$search_sql." ".$del_sql." ".$del_admin_sql." AND tasks.tfrom != '$uid' AND tasks.tsuccess = 0 AND tasks.tblocked = 0
    ".$sort_sql."
    LIMIT $page, 10
   ");
  }
  $i = 0;
  while($d = $db->assoc($q)) {
   $tid = $d['tid'];
   $tsection = $d['tsection'];
   $ttype = $d['ttype'];
   $ttitle = fxss($d['ttitle']);
   $turl = $d['turl'];
   $timg = $d['timg'];
   $tcomments = (int) $d['tcomments'];
   $tdel = $d['tdel'];
   $tdel_admin = $d['tdel_admin'];
   $tdel_status = ($tdel || $tdel_admin) ? '<div id="admin_tasks_del_f'.$tid.'" class="admin_tasks_del_f">Задание <b>удалено</b></div>' : '';
   $tamount = $d['tamount'];
   $tcount = $d['tcount'];
   $tdone_count = $d['tdone_count'];
   $tip_address = $d['tip_address'];
   $ttime = $d['ttime'];
   $tedit_time = $d['tedit_time'];
   $tedit_time_text = $tedit_time ? new_time($tedit_time) : 'не изменялось';
   $tclass = $i % 2 ? ' active' : '';
   $tfrom = $d['tfrom'];
   $ufirst_name = $d['uname'];
   $ulast_name = $d['ulast_name'];
   $tsuccess = $d['tsuccess'];
   $tblocked = $d['tblocked'];
   $ufull_name = (!$ufirst_name && !$ulast_name) ? 'Безымянный' : $ufirst_name.' '.$ulast_name;
   $tdel_button_text = ($tdel || $tdel_admin) ? 'Восстановить задание' : 'Удалить задание';
   
   if($ttype == 'group') {
    $url_result = 'public'.$turl;
   } elseif($ttype == 'user') {
    $url_result = 'id'.$turl;
   } elseif($ttype == 'wall_comment') {
    $url_result = 'wall'.$turl;
   } else {
    $url_result = $ttype.''.$turl;
   }

   // права администратора
   if($ugroup == 4 || $ugroup == 3) {
    $admin_info = '
           '.($ugroup == 3 || $ugroup == 4 ? '<br /> <a href="http://vk.com/'.$url_result.'" target="_blank">vk.com/'.$url_result.'</a>' : '').'
           <br />
           <span class="admin_task_legend">ID задания:</span> '.$tid.'
           <br />
           <span class="admin_task_legend">Создано:</span> '.new_time($ttime).'
           <br />
           <span class="admin_task_legend">Изменение:</span> '.$tedit_time_text.'
           <br />
           '.(($ugroup == 4) ? '<span class="admin_task_legend">Автор:</span> <a href="/admin/modules/users/?search='.$site_url.'id'.$tfrom.'" onclick="nav.go(this); return false">'.$ufull_name.'</a><br />' : '').'
           '.(($ugroup == 4) ? '<span class="admin_task_legend">IP:</span> '.$tip_address.'<br />' : '').'
           '.((($ugroup == 3 || $ugroup == 4) && $tblocked) ? '<span class="admin_tasks_del_f">Задание <b>заблокировано</b></span><br />' : '').'
           '.((($ugroup == 3 || $ugroup == 4) && $tsuccess) ? '<span class="task_success">Задание <b>выполнено</b></span><br />' : '').'
           <div id="admin_tasks_del_fd'.$tid.'">'.$tdel_status.'</div>
           <div class="admin_task_button">
            '.(($ugroup == 4) ? '<div onclick="admin_tasks._logs_edits('.$tid.');" class="blue_button_wrap task_button_othes_admin"><div class="blue_button">История изменений</div></div>' : '').'
            '.(($ugroup == 4) ? '<div onclick="admin_tasks._logs_dels('.$tid.');" class="blue_button_wrap task_button_othes_admin"><div class="blue_button">История удалений</div></div>' : '').'
            <div onclick="admin_tasks._delete('.$tid.', \''.$tdel_button_text.'\')" class="blue_button_wrap task_button_delete_admin task_button_delete_admin'.$tid.'"><div class="blue_button">'.$tdel_button_text.'</div></div>
           </div>
          ';
   }
   
   if($tsection == 1) {
    $tsection_img = '<div class="image"><div class="left_icon left_icon_like"></div></div>';
    if($ttype == 'wall') {
     $title = 'Нажать «мне нравится» на <a href="javascript://" onclick="tasks._task_go('.$tid.')">записи</a>';
    } elseif($ttype == 'wall_comment') {
     $title = 'Нажать «мне нравится» на <a href="javascript://" onclick="tasks._task_go('.$tid.')">комментарии</a>';
    }  elseif($ttype == 'photo') {
     $title = 'Нажать «мне нравится» на <a href="javascript://" onclick="tasks._task_go('.$tid.')">фотографии</a>';
    } elseif($ttype == 'video') {
     $title = 'Нажать «мне нравится» на <a href="javascript://" onclick="tasks._task_go('.$tid.')">видеозаписи</a>';
    } elseif($ttype == 'topic') {
     $title = 'Нажать «мне нравится» на <a href="javascript://" onclick="tasks._task_go('.$tid.')">обсуждении</a>';
    }
   } elseif($tsection == 2) {
    $tsection_img = '<div class="image"><div class="left_icon left_icon_repost"></div></div>';
    if($ttype == 'wall') {
     $title = 'Рассказать друзьям о <a href="javascript://" onclick="tasks._task_go('.$tid.')">записи</a>';
    } elseif($ttype == 'wall_comment') {
     $title = 'Рассказать друзьям о <a href="javascript://" onclick="tasks._task_go('.$tid.')">комментарии</a>';
    } elseif($ttype == 'photo') {
     $title = 'Рассказать друзьям о <a href="javascript://" onclick="tasks._task_go('.$tid.')">фотографии</a>';
    } elseif($ttype == 'video') {
     $title = 'Рассказать друзьям о на <a href="javascript://" onclick="tasks._task_go('.$tid.')">видеозаписи</a>';
    }
   } elseif($tsection == 3) {
    $tsection_img = '<div class="image"><div class="left_icon left_icon_comment"></div></div>';
    if($ttype == 'wall') {
     $tcomment_ptext = 'Напишите следующий комментарий к записи';
     $tcomment_purl = 'http://vk.com/wall'.$turl;
     $title = 'Оставить комментарий к <a href="javascript://" onclick="tasks._get_comment('.$tid.', \''.$tcomment_ptext.'\', \''.$tcomment_purl.'\')">записи</a>';
    } elseif($ttype == 'photo') {
     $tcomment_ptext = 'Напишите следующий комментарий к фотографии';
     $tcomment_purl = 'http://vk.com/photo'.$turl;
     $title = 'Оставить комментарий к <a href="javascript://" onclick="tasks._get_comment('.$tid.', \''.$tcomment_ptext.'\', \''.$tcomment_purl.'\')">фотографии</a>';
    } elseif($ttype == 'video') {
     $tcomment_ptext = 'Напишите следующий комментарий к видеозаписи';
     $tcomment_purl = 'http://vk.com/video'.$turl;
     $title = 'Оставить комментарий к <a href="javascript://" onclick="tasks._get_comment('.$tid.', \''.$tcomment_ptext.'\', \''.$tcomment_purl.'\')">видеозаписи</a>';
    }
   } elseif($tsection == 4) {
    $tsection_img = '<div class="image"><img src="'.$timg.'"></div>';
    $title = 'Подписаться на <a href="javascript://" onclick="tasks._task_go('.$tid.')">'.$ttitle.'</a>';
   } elseif($tsection == 5) {
    $tsection_img = '<div class="image"><img src="'.$timg.'"></div>';
    $title = 'Вступить в группу <a href="javascript://" onclick="tasks._task_go('.$tid.')">'.$ttitle.'</a>'; 
   } elseif($tsection == 6) {
    $tsection_img = '<div class="image"><div class="left_icon left_icon_poll"></div></div>';
    $title = 'Проголосовать '.($tcomments ? '<b>за '.$tcomments.'-й пункт</b>' : '').' в <a href="javascript://" onclick="tasks._task_go_poll('.$tid.', \''.$turl.'\')">опросе</a>'; 
   }
   
   if($tsection == 3) {
    $task_onclick_run = 'tasks._get_comment('.$tid.', \''.$tcomment_ptext.'\', \''.$tcomment_purl.'\')';
   } elseif($tsection == 6) {
    $task_onclick_run = 'tasks._task_go_poll('.$tid.', \''.$turl.'\')';
   } else {
    $task_onclick_run = 'tasks._task_go('.$tid.')';
   }
    
   $ttemplate .= '
         <div id="del_table'.$tid.'" class="del_table"></div>
         <div id="task'.$tid.'" class="task'.$tclass.'">
          <div id="task_all_error_msg'.$tid.'" class="task_all_error_msg error_msg"></div>
          <div class="image"><span class="image_section">'.$tsection_img.'</span> <div class="ajax_loader"></div></div>
          <div class="text"><span class="task_my_mini_description">'.$title.'</span>'.$admin_info.'</div>
          <div class="count">'.$tdone_count.' из '.$tcount.'</div>
          <div class="amount">+'.$tamount.' '.declOfNum($tamount, array('балл', 'балла', 'баллов')).'</div>
          <div class="control">
           <span id="task_button_control_1'.$tid.'"><a href="javascript://" onclick="'.$task_onclick_run.'">Выполнить</a> <span class="line_c">/</span></span> <a href="javascript://" onclick="tasks._ignored('.$tid.')">Скрыть</a>
          </div>
         </div>
          ';
   $i++;
  }
  return $ttemplate;
 }
 
 public function my_tasks($params = null) {
  global $db, $user_logged, $vk;
  
  $tasks_num = (int) abs($params['tasks_num']);
  $uid = (int) abs($params['uid']);
  $section_id = (int) abs($params['section']);
  $section_sql = $section_id ? "`tsection` = '$section_id' AND" : '';
  $cat_id = (int) abs($params['cat']);
  $cat_sql = $cat_id ? "`tcat` = '$cat_id' AND" : '';
  $page = (int) abs($params['page']) * 10;
  if($vk->url($params['search'])) {
   $search = json_decode($vk->screen_name($params['search']), true);
   $search_type = $db->escape($search['type']);
   $search_url = $db->escape($search['url']);
   $search_sql = $search ? "`ttype` = '$search_type' AND `turl`= '$search_url' AND" : '';
  } else {
   $search_sql = '';
  }
  $usession = (int) abs($params['usession']);

  if(!$user_logged) {
   return '';
   exit;
  }
  
  $q = $db->query("
   SELECT `tid`, `tsection`, `ttype`, `ttitle`, `turl`, `timg`, `tamount`, `tcount`, `tdone_count`, `tblocked`, `tdel_admin`, `tsuccess`, `tcomments`
   FROM `tasks`
   WHERE ".$cat_sql." ".$section_sql." ".$search_sql." `tfrom` = '$uid' AND `tdel` = '0'
   ORDER BY `tid` DESC
   LIMIT $page, 10
  ");
  
  $i = 0;
  while($d = $db->assoc($q)) {
   $tid = $d['tid'];
   $tsection = $d['tsection'];
   $ttype = $d['ttype'];
   $ttitle = fxss($d['ttitle']);
   $turl = $d['turl'];
   $timg = $d['timg'];
   $tcomments = $d['tcomments'];
   $tamount = $d['tamount'];
   $tcount = $d['tcount'];
   $tdone_count = $d['tdone_count'];
   $return_points = $tamount * ($tcount - $tdone_count);
   $tblocked = $d['tblocked'];
   $tsuccess = $d['tsuccess'];
   $tdel_admin = $d['tdel_admin'];
   $tclass = $i % 2 ? ' active' : '';

   if($ttype == 'group') {
    $url_result = 'public'.$turl;
   } elseif($ttype == 'user') {
    $url_result = 'id'.$turl;
   } else {
    $url_result = str_replace(array('wall_comment'), array('wall'), $ttype).''.$turl;
   }
   
   $tblocked == 1 ? $tblocked_text = '<div>Задание <span class="task_blocked">заблокировано</span> модератором. Обратитесь в <a href="/support/new" onclick="nav.go(this); return false">поддержку</a> для выяснения обстоятельств.</div>' : $tblocked_text = '';
   $tdel_admin == 1 ? $tdel_admin_text = '<div>Задание <span class="task_blocked">приостановлено</span> модератором.</div>' : $tdel_admin_text = '';
   $tsuccess == 1 ? $tsuccess_text = '<div>Задание <span class="task_success">выполнено</span>.</div>' : $tsuccess_text = '';
   
   if($tsection == 1) {
    $tsection_img = '<div class="image"><div class="left_icon left_icon_like"></div></div>';
    if($ttype == 'wall') {
     $title = '<span class="task_my_mini_description">Нажать «мне нравится» на <a href="http://vk.com/'.$url_result.'" target="_blank">записи</a></span>';
    } elseif($ttype == 'wall_comment') {
     $title = '<span class="task_my_mini_description">Нажать «мне нравится» на <a href="http://vk.com/'.$url_result.'" target="_blank">комментарии</a></span>';
    } elseif($ttype == 'photo') {
     $title = '<span class="task_my_mini_description">Нажать «мне нравится» на <a href="http://vk.com/'.$url_result.'" target="_blank">фотографии</a></span>';
    } elseif($ttype == 'video') {
     $title = '<span class="task_my_mini_description">Нажать «мне нравится» на <a href="http://vk.com/'.$url_result.'" target="_blank">видеозаписи</a></span>';
    } elseif($ttype == 'topic') {
     $title = '<span class="task_my_mini_description">Нажать «мне нравится» на <a href="http://vk.com/'.$url_result.'" target="_blank">обсуждении</a></span>';
    }
   } elseif($tsection == 2) {
    $tsection_img = '<div class="image"><div class="left_icon left_icon_repost"></div></div>';
    if($ttype == 'wall') {
     $title = '<span class="task_my_mini_description">Рассказать друзьям о <a href="http://vk.com/'.$url_result.'" target="_blank">записи</a></span>';
    } elseif($ttype == 'wall_comment') {
     $title = '<span class="task_my_mini_description">Рассказать друзьям о <a href="http://vk.com/'.$url_result.'" target="_blank">комментарии</a></span>';
    } elseif($ttype == 'photo') {
     $title = '<span class="task_my_mini_description">Рассказать друзьям о <a href="http://vk.com/'.$url_result.'" target="_blank">фотографии</a></span>';
    } elseif($ttype == 'video') {
     $title = '<span class="task_my_mini_description">Рассказать друзьям о на <a href="http://vk.com/'.$url_result.'" target="_blank">видеозаписи</a></span>';
    }
   } elseif($tsection == 3) {
    $tsection_img = '<div class="image"><div class="left_icon left_icon_comment"></div></div>';
    if($ttype == 'wall') {
     $title = '<span class="task_my_mini_description">Оставить комментарий к <a href="http://vk.com/'.$url_result.'" target="_blank">записи</a></span>';
    } elseif($ttype == 'photo') {
     $title = '<span class="task_my_mini_description">Оставить комментарий к <a href="http://vk.com/'.$url_result.'" target="_blank">фотографии</a></span>';
    } elseif($ttype == 'video') {
     $title = '<span class="task_my_mini_description">Оставить комментарий к на <a href="http://vk.com/'.$url_result.'" target="_blank">видеозаписи</a></span>';
    }
   } elseif($tsection == 4) {
    $tsection_img = '<div class="image"><img src="'.$timg.'"></div>';
    $title = '<span class="task_my_mini_description">Подписаться на <a href="http://vk.com/'.$url_result.'" target="_blank">'.$ttitle.'</a></span>';
   } elseif($tsection == 5) {
    $tsection_img = '<div class="image"><img src="'.$timg.'"></div>';
    $title = '<span class="task_my_mini_description">Вступить в группу <a href="http://vk.com/'.$url_result.'" target="_blank">'.$ttitle.'</a></span>'; 
   } elseif($tsection == 6) {
    $tsection_img = '<div class="image"><div class="left_icon left_icon_poll"></div></div>';
    $title = '<span class="task_my_mini_description">Проголосовать '.($tcomments ? '<b>за '.$tcomments.'-й пункт</b>' : '').' в <a href="http://vk.com/'.$url_result.'" target="_blank">опросе</a></span>'; 
   }
    
   $ttemplate .= '
         <div id="del_table'.$tid.'" class="del_table"></div>
         <div id="task'.$tid.'" class="task'.$tclass.'">
          <div class="image">'.$tsection_img.'</div>
          <div class="text">'.$title.' '.$tsuccess_text.' '.$tblocked_text.' '.$tdel_admin_text.' '.($tsection == 3 || $tsection == 6 ? '' : '<div '.($tdone_count <= 0 ? 'style="opacity: 0.5"' : '').' onclick="tasks._get_complaints('.$tid.')" id="complaints_get_task'.$tid.'" class="blue_button_wrap small_blue_button complaints_get_task"><div class="blue_button">Выписать штрафы</div></div>').'</div>
          <div class="count">'.$tdone_count.' из <span class="count_tcount">'.$tcount.'</span></div>
          <div class="amount">+'.$tamount.' '.declOfNum($tamount, array('балл', 'балла', 'баллов')).'</div>
          <div class="control">
           <a href="javascript://" onclick="tasks._edit_form('.$tid.')">Изменить</a> <span class="line_c">/</span> <a href="javascript://" onclick="tasks._delete('.$tid.')">Удалить</a>
          </div>
         </div>
          ';
   $i++;
  }
  return $ttemplate;
 }
 
 public function add($params = null) {
  global $db, $dbName, $time, $ip_address, $browser, $user_logged, $vk, $logs, $session,  $token, $ugroup;
  
  $uid = (int) abs($params['uid']);
  $points = (int) abs($params['upoints']);
  $cat = tasks::getCatNum($params['section']);
  $url = $db->escape($params['url']);
  $amount = (int) abs($params['amount']);
  $count = (int) abs($params['count']);
  $result_points = $amount * $count;
  $result_points_comission = $result_points + round(($result_points / 100) * 5);
  $personal_cat = (int) abs($params['cat']);
  $comments = $db->escape(trim($params['comments']));
  $comments_explode = explode('`', $comments);
  $comments_explode = array_diff($comments_explode, array(''));
  $captcha_code = $db->escape(trim($params['captcha_code']));
  $captcha_key = (int) abs($params['captcha_key']);
  $ssid = (int) abs($params['ssid']);
  $error = '';
  $title = '';
  $img_url = '';
  $black_list = '';
  
  $time_new = substr_replace(time(), '0', -1);
  $unique_key = 'add_'.$user_id.''.$url.''.$count.''.$time_new;
  $unique_del = 'add_del'.$user_id.'_'.rand().'_'.$time;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($vk->url($url)) { // проверяем информацию
   $info_url = json_decode($vk->explode($url), true);
   if($cat == 1 || $cat == 2 || $cat == 3) { // категория "Мне нравится"
    if($info_url['type'] == 'unknown') {
     $error = 'error url';
    } elseif($info_url['type'] == 'wall') {
     $wall_info = json_decode($vk->wall_info($info_url['url']), true);
     if($wall_info['error'] == 'deleted' || $wall_info['error'] == 'access denied') {
      $error = 'wall_deleted'; // запись не найдена
     } elseif($wall_info['error'] == 'authorization failed') {
      $error = 'authorization';
     } elseif($wall_info['response'] == 1) {
      $error = 1;
      $type = 'wall';
      $id = $db->escape($wall_info['from']);
      $full_id = $db->escape($wall_info['full_id']);
      $text = $db->escape($wall_info['text']);
      $title = $text;
      $black_list = tasks::check_blacklist($type, $full_id, $id);
     } else {
      $error = 'unknown'; // неизвестная ошибка
     }
    } elseif($info_url['type'] == 'wall_comment' && $cat != 3) {
     $wall_info = json_decode($vk->wall_info($info_url['url']), true);
     if($wall_info['error'] == 'deleted' || $wall_info['error'] == 'access denied') {
      $error = 'wall_deleted'; // запись не найдена
     } elseif($wall_info['error'] == 'authorization failed') {
      $error = 'authorization';
     } elseif($wall_info['response'] == 1) {
      $error = 1;
      $type = 'wall_comment';
      $id = $db->escape($wall_info['from']);
      $reply = (int) $info_url['reply'];
      $full_id = $id.'_'.$reply;
     } else {
      $error = 'unknown'; // неизвестная ошибка
     }
    } elseif($info_url['type'] == 'photo') {
     $photo_info = json_decode($vk->photo_info($info_url['url'], $token), true);
     if($photo_info['error'] == 'deleted' || $photo_info['error'] == 'access denied') {
      $error = 'photo_deleted'; // фотография не найдена
     } elseif($photo_info['error'] == 'authorization failed') {
      $error = 'authorization';
     } elseif($photo_info['response'] == 1) {
      $error = 1;
      $type = 'photo';
      $id = $db->escape($photo_info['from']);
      $full_id = $db->escape($photo_info['full_id']);
      $black_list = tasks::check_blacklist($type, $full_id, $id);
     } else {
      $error = 'error url';
     }
    } elseif($info_url['type'] == 'video') {
     $video_info = json_decode($vk->video_info($info_url['url'], $token), true);
     if($video_info['error'] == 'deleted' || $video_info['error'] == 'access denied') {
      $error = 'video_deleted'; // видеозапись не найдена
     } elseif($video_info['error'] == 'authorization failed') {
      $error = 'authorization';
     } elseif($video_info['response'] == 1) {
      $error = 1;
      $type = 'video';
      $id = $db->escape($video_info['from']);
      $full_id = $db->escape($video_info['full_id']);
      $text = $db->escape($video_info['text']);
      $black_list = tasks::check_blacklist($type, $full_id, $id);
     } else {
      $error = 'error url';
     }
    } else {
     $error = 'unknown'; // неизвестная ошибка
    }
   } elseif($cat == 4) {
    $user_info = json_decode($vk->user_info($info_url['url']), true);
    if($user_info['error'] == 'deleted') {
     $error = 'user_deleted'; // пользователь удалён
    } elseif($user_info['error'] == 'banned') {
     $error = 'user_banned'; // пользователь заблокирован
    } elseif($user_info['response'] == 1) {
     $error = 1;
     $type = 'user';
     $img_url = $user_info['avatar'];
     $full_id = (int) $user_info['id'];
     $title = $db->escape($user_info['first_name'].' '.$user_info['last_name']);
     $black_list = tasks::check_blacklist($type, $full_id);
    } else {
     $error = 'error url';
    }
   } elseif($cat == 5) {
    $group_info = json_decode($vk->group_info($info_url['url']), true);
    if($group_info['error'] == 'deleted') {
     $error = 'group_deleted'; // группа удалена
    } elseif($group_info['error'] == 'closed') {
     $error = 'group_closed'; // группа заблокирована
    } elseif($group_info['response'] == 1) {
     $error = 1;
     $type = 'group';
     $img_url = $group_info['avatar'];
     $full_id = (int) $group_info['id'];
     $title = $db->escape($group_info['name']);
     $text = $db->escape($group_info['text']);
     $black_list = tasks::check_blacklist($type, '-'.$full_id);
    } else {
     $error = 'error url';
    }
   } elseif($cat == 6) {
    $wall_info = json_decode($vk->wall_info($info_url['url']), true);
    $comments = (int) $comments;
    if($wall_info['error'] == 'deleted' || $wall_info['error'] == 'access denied') {
     $error = 'wall_deleted'; // запись не найдена
    } elseif($wall_info['error'] == 'authorization failed') {
     $error = 'authorization';
    } elseif($wall_info['response'] == 1 && $wall_info['poll_id']) {
     $error = 1;
     $type = 'wall';
     $id = $db->escape($wall_info['from']);
     $full_id = $db->escape($wall_info['full_id']);
     $text = $db->escape($wall_info['text']);
     $title = $text;
     $black_list = tasks::check_blacklist($type, $full_id, $id);
    } else {
     $error = 'unknown'; // неизвестная ошибка
    }
   } else {
    $error = 'unknown'; // неизвестная ошибка
   }
  } else {
   // неверный URL
   $error = 'error url';
  }
  
  // цены
  if($cat == 1) {
   $min_points = 1;
   $max_points = 3;
  } elseif($cat == 2) {
   $min_points = 3;
   $max_points = 6;
  } elseif($cat == 3) {
   $min_points = 3;
   $max_points = 6;
  } elseif($cat == 4) {
   $min_points = 2;
   $max_points = 4;
  } elseif($cat == 5) {
   $min_points = 2;
   $max_points = 4;
  } elseif($cat == 6) {
   $min_points = 1;
   $max_points = 4;
  }
  
  // обрабатываем информацию для отправки запроса
  if(!$cat) {
   $json = array('error_text' => 'Не выбрана категория.');
  } elseif($error == 'error url') {
   $json = array('error_text' => 'Проверьте правильность введенной ссылки.');
  } elseif($comments && $comments > 50 && $cat == 6) {
   $json = array('error_text' => 'Неверное значение поля <b>Проголосовать за</b>.');
  } elseif(tasks::filter($title) == 1 || tasks::filter($text) == 1) {
   $json = array('error_text' => 'Задание не может быть добавлено, так как оно нарушает <a href="/terms" onclick="nav.go(this); return false">правила</a> сайта.');
  } elseif($error == 'authorization') {
   $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже.');
  } elseif($black_list == 1) {
   $json = array('error_text' => 'Ссылка добавлена в черный список до выяснения обстоятельств в <a href="/support/new" onclick="nav.go(this); return false">поддержке</a>.');
  } elseif($error == 'wall_deleted') {
   $json = array('error_text' => 'Запись удалена или недоступна.');
  } elseif($error == 'photo_deleted') {
   $json = array('error_text' => 'Фотография удалена или недоступна.');
  } elseif($error == 'video_deleted') {
   $json = array('error_text' => 'Видеозапись удалена или недоступна.');
  } elseif($error == 'user_deleted') {
   $json = array('error_text' => 'Пользователь удален или не существует.');
  } elseif($error == 'user_banned') {
   $json = array('error_text' => 'Пользователь заблокирован.');
  } elseif($error == 'group_deleted') {
   $json = array('error_text' => 'Группа удалена или не существует.');
  } elseif($error == 'topic_deleted') {
   $json = array('error_text' => 'Обсуждение удалено или не существует.');
  } elseif(count($comments_explode) < 1 && $cat == 3) {
   $json = array('error_text' => 'В задании должен присутствовать хоть 1 комментарий.');
  } elseif(preg_match('/(.*?)\.(ru|su|com|pro|net|to)/i', $comments) && $cat == 3) {
   $json = array('error_text' => 'Ссылки в комментариях запрещены.');
  } elseif(tasks::comment_filter($comments) == 1 && $cat == 3) {
   $json = array('error_text' => 'В комментарии содержатся недопустимые символы.');
  } elseif(mb_strlen($comments, 'UTF-8') < 4 && $cat == 3) {
   $json = array('error_text' => 'Слишком короткий комментарий.');
  } elseif($amount < $min_points) {
   $json = array('error_text' => 'Стоимость не может быть меньше, чем <b>'.$min_points.' '.declOfNum($min_points, array('балл', 'балла', 'баллов')).'</b>.');
  } elseif($amount > $max_points) {
   $json = array('error_text' => 'Стоимость не может быть больше, чем <b>'.$max_points.' '.declOfNum($max_points, array('балл', 'балла', 'баллов')).'</b>.');
  } elseif($count < 5) {
   $json = array('error_text' => 'Количество не может быть меньше, чем <b>5</b>.');
  } elseif($count > 10000) {
   $json = array('error_text' => 'Количество не может быть больше, чем <b>10000</b>.');
  } elseif($result_points_comission > $points) {
   $json = array('error_text' => 'Недостаточно баллов для размещения задания.');
  } elseif($session->get('usession') != $ssid) {
   $json = array('error_text' => 'Истек период сессии. Обновите страницу или попробуйте позже.');
  } elseif($error == 'unknown') {
   $json = array('error_text' => 'Неизвестная ошибка.');
  } elseif($error == 1) {
   if($personal_cat) {
    // открываем категорию
    $qCat = $db->query("SELECT `tcid` FROM `tasks_categories` WHERE `tcid` = '$personal_cat' AND `tcuid` = '$uid' AND `tcdel` = '0'");
    $dCat = $db->fetch($qCat);
    $dCat_id = $dCat['tcid'];
   }
   
   if($db->query("INSERT INTO `$dbName`.`tasks` (`tid`, `tfrom`, `ttime`, `tedit_time`, `tip_address`, `tbrowser`, `tsection`, `ttype`, `ttitle`, `timg`, `turl`, `tcomments`, `tamount`, `tcount`, `tdone_count`, `tdel`, `tdel_admin`, `tdel_admin_text`, `tcat`, `tblocked`, `tsuccess`, `user_url`, `complaints_time`, `unique_key`) VALUES (NULL, '$uid', '$time', '0', '$ip_address', '$browser', '$cat', '$type', '$title', '$img_url', '$full_id', '$comments', '$amount', '$count', '0', '0', '0', '', '$dCat_id', '0', '0', '$url', '0', '$unique_key');")) {
    $dTask_last_id = $db->insert_id();
    $db->query("UPDATE `$dbName`.`users` SET  `upoints` =  `upoints` - '$result_points_comission' WHERE  `users`.`uid` = '$uid';"); // списываем баллы
    $logs->add_task($uid, $dTask_last_id, $result_points_comission); // записываем в лог
    
    // проценты рефералу
    /*$qRef = $db->query("SELECT `to` FROM `ref` WHERE `from` = '$uid'");
    $dRef = $db->fetch($qRef);
    
    $dRef_id = $dRef['to'];
    
    if($dRef_id) { // если есть реферал
     $ref_percents = round(($result_points_comission / 100) * 10); // вычисляем процент
     $db->query("UPDATE `$dbName`.`users` SET  `upoints` =  `upoints` + '$ref_percents' WHERE  `users`.`uid` = '$dRef_id';"); // начисляем баллы
     $logs->add_ref_percents($uid, $dRef_id, $ref_percents); // записываем в лог
    }*/
    
    $json = array('success' => 1, 'points' => $result_points_comission);
   } else {
    $db_er = $db->error();
    if(preg_match('/Duplic/i', $db_er)) {
     $json = array('error_text' => 'Действие выполняется слишком часто. Попробуйте позже.');
    } else {
     $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.'.$db_er);
    }
   }
  }
  
  return json_encode($json);
 }
 
 public function delete_task($params = null) {
  global $db, $dbName, $time, $ip_address, $browser, $user_logged, $vk, $logs, $session, $user_id;
  
  $id = (int) abs($params['id']);
  $uid = (int) abs($params['uid']);
  $ssid = (int) abs($params['ssid']);
  $time_new = substr_replace(time(), '0', -1);
  $unique_key = 'del_'.$user_id.''.$id.''.$time_new;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  $q = $db->query("SELECT `tid`, `tfrom`, `tamount`, `tcount`, `tdone_count`, `turl`, `ttype` FROM `tasks` WHERE `tid` = '$id' AND `tfrom` = '$uid' AND `tdel` = '0'");
  $d = $db->fetch($q);

  $tid = $d['tid'];
  $tfrom = $d['tfrom'];
  $tamount = $d['tamount'];
  $tcount = $d['tcount'];
  $turl = $d['turl'];
  $type = $d['ttype'];
  $tdone_count = $d['tdone_count'];
  $return_points = $tamount * ($tcount - $tdone_count);
  
  if($tid) {
   if($session->get('usession') != $ssid) {
    $json = array('error_text' => 'Истек период сессии. Обновите страницу или попробуйте позже.');
   } elseif($db->query("INSERT INTO `$dbName`.`tasks_del_unique` (`id`, `unique_key`) VALUES (NULL, '$unique_key');")) {
    $db->query("UPDATE `$dbName`.`tasks` SET `tdel` = '1' WHERE  `tasks`.`tid` = '$id'");
    $db->query("UPDATE `$dbName`.`users` SET `upoints` =  upoints + '$return_points' WHERE  `users`.`uid` = '$uid';"); // зачисляем баллы
    $logs->delete_task($uid, $tid, '{"type":"'.$type.'", "url":"'.$turl.'"}', $return_points, 0); // записываем в лог
    $json = array('success' => 1, 'points' => $return_points);
   } else {
    $db_er = $db->error();
    if(preg_match('/Duplic/i', $db_er)) {
     $json = array('error_text' => 'Действие выполняется слишком часто. Попробуйте позже.');
    } else {
     $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
    }
   }
  } else {
   $json = array('error_text' => 'Ошибка доступа.');
  }
  
  return jdecoder(json_encode($json));
 }
 
 public function ignored() {
  global $db, $dbName, $user_id, $time, $logs, $session, $user_logged, $uvk_id;
  
  $id = (int) abs($_GET['id']);
  $ssid = (int) abs($_GET['ssid']);
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  } 
 
  $qTask = $db->query("SELECT `tid`, `ttype`, `turl`, `tsection` FROM `tasks` WHERE `tid` = '$id'");
  $dTask = $db->fetch($qTask);
  $dTask_id = $dTask['tid'];
  $dTask_type = $dTask['ttype'];
  $dTask_section = $dTask['tsection'];
  $dTask_url = $dTask['turl'];
  $dTask_url_result = $dTask_type.''.$dTask_url;
  
  $unique_key = 'ignored_'.$dTask_id.''.$user_id;
  
  $qTask_done = $db->query("SELECT `tdid` FROM `tasks_done` WHERE `tdtid` = '$dTask_id' AND `tduid` = '$user_id'");
  $nTask_done = $db->num($qTask_done);
  
  if($dTask_id) {
   if($nTask_done) {
    $json = array('success' => 1);
   } elseif($session->get('usession') != $ssid) {
    $json = array('error_text' => 'Истек период сессии. Обновите страницу или попробуйте позже.');
   } elseif($db->query("INSERT INTO `$dbName`.`tasks_done` (`tdid`, `tduid`, `tdvk_id`, `tdtid`, `tdurl`, `tdtype`, `tdtime`, `tdsection`, `tdread`, `unique_key`) VALUES (NULL, '$user_id', '$uvk_id', '$id', '$dTask_url_result', 'ignored', '$time', '$dTask_section', '0', '$unique_key');")) {
    $logs->ignored_task($user_id, $dTask_id); // записываем в лог
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  } else {
   $json = array('error_text' => 'Ошибка доступа.');
  }
  return jdecoder(json_encode($json));
 }
 
 public function admin_delete() {
  global $db, $dbName, $user_id, $time, $ip_address, $browser, $user_logged, $vk, $logs, $session, $ugroup;
  
  $id = (int) abs($_GET['id']);
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4 && $ugroup != 3) {
   return json_encode(array('access' => 'denied'));
   exit;
  }

  $q = $db->query("SELECT `tid`, `tdel`, `tdel_admin` FROM `tasks` WHERE `tid` = '$id'");
  $d = $db->fetch($q); 
  
  $tid = $d['tid'];
  $tdel = $d['tdel'];
  $tdel_admin = $d['tdel_admin'];
  
  if($tdel || $tdel_admin) {
   $tdel_query = "UPDATE `$dbName`.`tasks` SET `tdel` = '0', `tdel_admin` = '0' WHERE  `tasks`.`tid` = '$id'";
   $tdel_type = 'return';
  } else {
   $tdel_query = "UPDATE `$dbName`.`tasks` SET `tdel_admin` = '1' WHERE  `tasks`.`tid` = '$id'";
   $tdel_type = 'delete';
  }
  
  if($tid) {
   if($db->query($tdel_query)) {
    if($tdel || $tdel_admin) {
     $logs->return_task($user_id, $tid, 1);
    } else {
     $logs->delete_task($user_id, $tid, '', 0, 1);
    }
    $json = array('success' => 1, 'type' => $tdel_type);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  } else {
   $json = array('error_text' => 'Ошибка доступа.');
  }
  
  return jdecoder(json_encode($json));
 }
 
 public function edit_form($params = null) {
  global $db, $dbName, $time, $ip_address, $browser, $user_logged, $vk, $logs, $session;
  
  $id = (int) abs($params['id']);
  $uid = (int) abs($params['uid']);
  $section = (int) abs($params['section']);
  
  if(!$user_logged) {
   return 'login';
   exit;
  }
  
  $q = $db->query("SELECT `tid`, `tsection`, `tamount`, `tcount`, `turl`, `ttype`, `tcat` FROM `tasks` WHERE `tid` = '$id' AND `tfrom` = '$uid' AND `tdel` = '0'");
  $d = $db->fetch($q);
  
  $tid = $d['tid'];
  $tsection = $d['tsection'];
  $tfrom = $d['tfrom'];
  $tamount = $d['tamount'];
  $tcount = $d['tcount'];
  $turl = $d['turl'];
  $type = $d['ttype'];
  $cat = $d['tcat'];
  $tdone_count = $d['tdone_count'];
  $tcount_limit = 10000 - $tcount;
  
   if($type == 'group') {
    $url_result = 'public'.$turl;
   } elseif($type == 'user') {
    $url_result = 'id'.$turl;
   } else {
    $url_result = str_replace(array('wall_comment'), array('wall'), $type).''.$turl;
   }
  
  if($tsection == 1) {
   $recom_placeholder = declOfNum($tcount_limit, array('отметки', 'отметки', 'отметок'));
   $count_placeholder = 'отметок «Мне нравится»';
  } elseif($tsection == 2) {
   $recom_placeholder = declOfNum($tcount_limit, array('репоста', 'репостов', 'репостов'));
   $count_placeholder = 'репостов';
  } elseif($tsectionn == 3) { 
   $recom_placeholder = declOfNum($tcount_limit, array('комментария', 'комментариев', 'комментариев'));
   $count_placeholder = 'комментариев';
  } elseif($tsection == 4) {
   $recom_placeholder = declOfNum($tcount_limit, array('подписчика', 'подписчиков', 'подписчиков'));
   $count_placeholder = 'подписчиков';
  } elseif($tsection == 5) {
   $recom_placeholder = declOfNum($tcount_limit, array('вступившего', 'вступивших', 'вступивших'));
   $count_placeholder = 'вступивших';
  } elseif($tsection == 6) {
   $recom_placeholder = declOfNum($tcount_limit, array('голос', 'голоса', 'голосов'));
   $count_placeholder = 'голосов';
  } else {
   $recom_placeholder = '';
   $count_placeholder = '';
  }
  
  if($tid) {
   return '
    <div class="task_edit_bg" id="task_add_bg">
     <div class="form_edit_task" id="form_add_task">
      <div class="error_msg error"></div>
      <div id="form_edit_task_section">'.$tsection.'</div>
      <div id="form_edit_task_cat">'.$cat.'</div>
      <div class="overflow_field">
       <div class="label cat">Категория:</div>
       <div class="field"><div id="task_add_categories"></div></div> 
      </div>
      <div class="overflow_field">
       <div class="label">Ссылка:</div>
       <div class="field">
        <input type="text" value="http://vk.com/'.$url_result.'" disabled="disabled" id="add_task_url" class="disabled_input">
       </div>
      </div>
      <div class="overflow_field">
       <div class="label">Стоимость выполнения:</div>
       <div class="field">
        <input type="text" disabled="disabled" value="'.$tamount.'" id="add_task_amount" class="disabled_input"><span id="amount_right" class="field_right">'.declOfNum($tamount, array('балл', 'балла', 'баллов')).'</span>
       </div>
      </div>
      <div class="overflow_field">
       <div class="label">Количество:</div>
       <div class="field">
        <input iplaceholder="+" type="text" id="add_task_count"><span id="count_right" class="field_right">'.$count_placeholder.'</span>
        <div class="tooltip_field_append">
         Рекомендованное значение – от <b>5</b> до <b>'.$tcount_limit.'</b> '.$recom_placeholder.'
        </div>
       </div>
      </div>
      <div id="tasks_edit_captcha" class="overflow_field">
       <div class="label">Код безопасности:</div>
       <div class="field">
        <div class="tasks_edit_captcha">
         <div class="tasks_edit_captcha_left"><img src="/secure"></div>
         <div class="tasks_edit_captcha_right"><input id="tasks_edit_captcha_field" maxlength="6" type="text"></div>
        </div>
       </div>
      </div>
     </div>
    </div>
   ';
  } else {
   return '
   <div style="padding: 5px !important" id="tasks_none">Ошибка доступа.</div>
   <script type="text/javascript">$(\'#box_button_blue\').hide()</script>
   ';
  }
 }
 
 public function edit_task($params = null) {
  global $db, $dbName, $user_id, $time, $ip_address, $browser, $user_logged, $vk, $logs, $session;
  
  $id = (int) abs($params['id']);
  $uid = (int) abs($params['uid']);
  $personal_cat = (int) abs($params['cat']);
  $points = (int) abs($params['upoints']);
  $count = (int) abs($params['count']);
  $captcha_code = $db->escape(trim($params['captcha_code']));
  $captcha_key = (int) abs($params['captcha_key']);
  $ssid = (int) abs($params['ssid']);
  
  if($points <= 0) {
   return json_encode(array('error_text' => 'Неизвестная ошибка.'));
   exit;
  }
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  $q = $db->query("SELECT `tid`, `tcat`, `tcount`, `tamount`, `tsuccess` FROM `tasks` WHERE `tid` = '$id' AND `tfrom` = '$uid' AND `tdel` = '0'");
  $d = $db->fetch($q);
  
  $tid = $d['tid'];
  $tcat = $d['tcat'];
  $tcount = $d['tcount'];
  $tamount = $d['tamount'];
  $tcount_limit = 10000 - $tcount;
  $tsuccess = $count ? 0 : $d['tsuccess'];
  $result_points = $tamount * $count;
  $result_points_comission = $result_points + round(($result_points / 100) * 5);
  
  if($personal_cat) {
   // открываем категорию
   $qCat = $db->query("SELECT `tcid` FROM `tasks_categories` WHERE `tcid` = '$personal_cat' AND `tcuid` = '$uid' AND `tcdel` = '0'");
   $dCat = $db->fetch($qCat);
   $dCat_id = $dCat['tcid'];
  }
  
  // открываем последние редактирования
  $qTasks_edit_limit = time() - (0.5 * 60);
  $qTasks_edit = $db->query("SELECT `teid` AS `count` FROM `tasks_edit` WHERE `tetime` >= '$qTasks_edit_limit' AND `teuid` = '$uid'");
  $nTasks_edit = $db->num($qTasks_edit);
  
  // код безопасности
  $captcha_session = $session->get('task.edit');
  if($nTasks_edit >= 3) {
   if(!$captcha_session) {
    $captcha_session = $session->add('task.edit', 1, '', 1);
   }
  }
  
  $last_count = $tcount;
  $new_count = $last_count + $count;
  $last_cat = $tcat;
  $new_cat = $personal_cat;
  
  $time_new = substr_replace(time(), '0', -1);
  $unique_key = 'edit_'.$user_id.''.$time_new;
  
  if($tid) {
   if($count && $count < 5) {
    $json = array('error_text' => 'Количество не может быть меньше 5.');
   } elseif($count && $count > $tcount_limit) {
    $json = array('error_text' => 'Превышен лимит на количество. Вы можете <a href=\'/tasks/add\' onclick=\'nav.go(this); return false\'>создать новое задание.</a>');
   } elseif($result_points_comission > $points) {
    $json = array('error_text' => 'Недостаточно баллов для размещения задания.');
   } elseif($session->get('usession') != $ssid) {
    $json = array('error_text' => 'Истек период сессии. Обновите страницу или попробуйте позже.');
   } elseif($captcha_session && !$captcha_code) {
    $json = array('error_text' => 'captcha');
   } elseif($captcha_session && mb_strtolower($session->get('captcha_code', $captcha_key), 'UTF-8') != mb_strtolower($captcha_code, 'UTF-8')) {
    $json = array('error_text' => 'captcha');
   } elseif($db->query("INSERT INTO `$dbName`.`tasks_edit` (`teid`, `teuid`, `tetid`, `tetime`, `teip`, `tebrowser`, `telast_cat`, `telast_count`, `telast_comments`, `tenew_cat`, `tenew_count`, `tenew_comments`, `unique_key`) VALUES (NULL, '$uid', '$tid', '$time', '$ip_address', '$browser', '$last_cat', '$last_count', '$last_comments', '$new_cat', '$new_count', '$new_comments', '$unique_key');")) {
    if($captcha_session) {
     $session->delete('captcha_code', $captcha_key);
    }
    $db->query("UPDATE `$dbName`.`users` SET `upoints` =  upoints - '$result_points_comission' WHERE `users`.`uid` = '$uid';"); // списываем баллы
    $db->query("UPDATE `$dbName`.`tasks` SET `tdel_admin` = '0', `tedit_time` = '$time', `tcount` = tcount + '$count', `tcat` = '$dCat_id', `tsuccess` = '$tsuccess' WHERE  `tasks`.`tid` = '$id'");
    $logs->edit_task($uid, $tid, '{"last_cat":"'.$last_cat.'", "last_count":"'.$last_count.'", "last_comments":"'.$last_comments.'", "new_cat":"'.$new_cat.'", "new_count":"'.$new_count.'", "new_comments":"'.$new_comments.'"}', $result_points_comission); // записываем в лог
    $json = array('success' => 1, 'new_count' => $new_count, 'points' => $result_points_comission);
   } else {
    $db_er = $db->error();
    if(preg_match('/Duplic/i', $db_er)) {
     $json = array('error_text' => 'Действие выполняется слишком часто. Попробуйте позже.');
    } else {
     $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
    }
   }
  } else {
   $json = array('error_text' => 'Ошибка доступа.');
  }
  
  return jdecoder(json_encode($json));
 }
 
 public function go() {
  global $db, $dbName, $user_id, $logs, $session, $token, $vk, $uvk_id, $redis;
  
  $id = (int) $_GET['id'];
  $private = '';
  
  $q = $db->query("SELECT `tfrom`, `tsection`, `ttype`, `turl`, `tdel`, `tdel_admin`, `tblocked`, `tsuccess`, `tamount`, `tcount`, `tcomments` FROM `tasks` WHERE `tid` = '$id'");
  $d = $db->fetch($q);
  
  $tfrom = $d['tfrom'];
  $section = $d['tsection'];
  $type = $d['ttype'];
  $url = $d['turl'];
  $amount = $d['tamount'];
  $count = $d['tcount'];
  $del = $d['tdel'];
  $del_admin = $d['tdel_admin'];
  $blocked = $d['tblocked'];
  $success = $d['tsuccess'];
  $comments = $d['tcomments'];
  $group_closed = 0;
  
  if($type == 'group') {
   $url_result = 'public'.$url;
  } elseif($type == 'user') {
   $url_result = 'id'.$url;
  } elseif($type == 'wall_comment') {
   $url_result = 'wall'.$url;
  } else {
   $url_result = $type.''.$url;
  }
 
  if($type) {
   if($user_id == $tfrom || $section == 6 && !$_GET['answer_poll']) {
    $json = array('error_text' => 'Ошибка доступа.');
   } elseif(!$uvk_id) {
    $json = array('error_text' => 'Необходимо привязать страницу ВКонтакте к Вашему аккаунту.');
   } elseif($del) {
    $json = array('error_text' => 'Задание удалено пользователем.');
   } elseif($del_admin) {
    $json = array('error_text' => 'Задание удалено модератором за нарушение правил сайта.');
   } elseif($blocked) {
    $json = array('error_text' => 'Задание заблокировано модератором за нарушение правил сайта.');
   } elseif($success) {
    $json = array('error_text' => 'Задание уже выполнено другими участниками сайта.');
   } else {
    if($type == 'wall') {
     $wall_info = json_decode($vk->wall_info($url, $token), true);
     if($section == 6) {
      $url = $wall_info['from'].'_'.$wall_info['id'];
     }
     $text = $wall_info['text'];
     $poll_id = $wall_info['poll_id'];
     if($wall_info['error'] == 'deleted') {
      $private = 1;
     }
    } elseif($type == 'video') {
     $video_info = json_decode($vk->video_info($url, $token), true);
     $text = $video_info['text'];
     if($video_info['error'] == 'deleted') {
      $private = 1;
     }
    } elseif($type == 'group') {
     $group_info = json_decode($vk->group_info($url, $token), true);
     $title = $group_info['name'];
     $text = $group_info['text'];
     $group_closed = $group_info['closed'];
     if($group_info['error'] == 'closed') {
      $private = 1;
     }
     if($group_info['error'] == 'deleted') {
      $private = 1;
     }
    } elseif($type == 'user') {
     $user_info = json_decode($vk->user_info($url, $token), true);
     if($user_info['error'] == 'deleted') {
      $private = 1;
     }
     if($user_info['error'] == 'banned') {
      $private = 1;
     }
    } elseif($type == 'photo') {
     $photo_info = json_decode($vk->photo_info($url, $token), true);
     if($photo_info['error'] == 'deleted') {
      $private = 1;
     }
     if($photo_info['error'] == 'access denied') {
      $private = 1;
     }
    }
 
    if(tasks::filter($title) == 1 || tasks::filter($text) == 1) {
     $json = array('error_text' => 'Задание заблокировано модератором за нарушение правил сайта.');
    } elseif($private) {
     $json = array('error_text' => 'Ссылка недоступна или защищена настройками приватности.');
    } elseif($wall_info['error'] || $video_info['error'] || $group_info['error'] || $user_info['error'] || $photo_info['error']) {
     $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже.');
    } else {
     $json = array('success' => 1, 'section' => $section, 'url' => $url_result, 'comments' => $comments, 'amount' => $amount, 'count' => $count, 'small_url' => $url, 'group_closed' => $group_closed, 'poll_id' => $poll_id, 'type' => $type);
    }
   }
  } else {
   $json = array('error_text' => 'Ошибка доступа.');
  }
  
  if($json['success'] == 1) {
   $redis->hdel('task_check_id'.$id.''.$user_id, 'error_text');
   $redis->hmset('task_check_id'.$id.''.$user_id, array('success' => 1, 'section' => $section, 'url' => $url_result, 'comments' => $comments, 'amount' => $amount, 'count' => $count, 'small_url' => $url, 'group_closed' => $group_closed, 'poll_id' => $poll_id, 'type' => $type));
   $redis->expire('task_check_id'.$id.''.$user_id, 900);
  } else {
   $redis->hmset('task_check_id'.$id.''.$user_id, array('error_text' => $json['error_text']));
   $redis->expire('task_check_id'.$id.''.$user_id, 900);
  }
  
  //$json = array($wall_info, $video_info, $group_info, $user_info, $photo_info);
  return jdecoder(json_encode($json));
 }
 
 public function check() {
  global $db, $dbName, $user_id, $token, $vk, $time, $logs, $user_logged, $uvk_id, $session, $redis;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  $user_vk_id = $uvk_id;
  $id = (int) abs($_GET['id']);
  $comment = $db->escape($_GET['comment']);
  $answer_poll = (int) $_GET['answer_poll'];
  $go = 'task_check_id'.$id.''.$user_id;
  $go_error_text = $redis->hget($go, 'error_text');
  $go_success = $redis->hget($go, 'success');
  $go_section = $redis->hget($go, 'section');
  $go_type = $redis->hget($go, 'type');
  $go_url = $redis->hget($go, 'small_url');
  $go_amount = $redis->hget($go, 'amount');
  $go_count = $redis->hget($go, 'count');
  $go_comments = $redis->hget($go, 'comments');
  $go_group_closed = $redis->hget($go, 'group_closed');
  $go_poll_id = $redis->hget($go, 'poll_id');
  $go_url_result = $go_type.''.$go_url;
  $read_done = $go_group_closed ? 1 : 0;
  
  // открываем таблицу выполненных заданий
  $qTask_done = $db->query("SELECT `tdtid` FROM `tasks_done` WHERE `tdurl` = '$go_url_result' AND `tduid` = '$user_id' AND `tdtype` = 'done' AND `tdsection` = '$go_section' ORDER BY `tdid` DESC LIMIT 1");
  $dTask_done = $db->fetch($qTask_done);
  $dTask_done_tid = $dTask_done['tdtid'];
  if($dTask_done_tid != $id && $dTask_done_tid) {
   $task_done_flag = 1;
   $unique_key = 'ignored_'.$id.''.$user_id;
   $db->query("INSERT INTO `$dbName`.`tasks_done` (`tdid`, `tduid`, `tdvk_id`, `tdtid`, `tdurl`, `tdtype`, `tdtime`, `tdsection`, `tdread`, `unique_key`) VALUES (NULL, '$user_id', '$uvk_id', '$id', '$go_url_result', 'ignored', '$time', '$go_section', '0', '$unique_key');");
   $logs->ignored_task($user_id, $id); // записываем в лог
  } else {
   $task_done_flag = 2;
  }
  
  if($go_error_text) {
   $json = array('error_text' => $go_error_text);
  } elseif($dTask_done_tid) {
   if($task_done_flag == 1) {
    $json = array('error_text' => 'Похожее задание Вы уже выполняли ранее, поэтому это мы скроем.');
   } else {
    $json = array('error_text' => 'Вы уже выполняли это задание. В дальнейшем оно не будет показываться.');
   }
  } elseif($go_success && $go_amount) {
   if($go_section == 5) {
    $check_group = $vk->check_group($go_url, $user_vk_id, $token);
    if($check_group == 1) {
     $json = array('success' => 1, 'points' => $go_amount);
     tasks::task_done($id, $go_type.''.$go_url, $go_amount, $go_count, $go_section, $go_group_closed);
    } elseif($check_group == 2) {
     $json = array('error_text' => 'Пожалуйста, <b>вступите в группу</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
    } elseif($check_group == 0) {
     $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже.');
    } else {
     $json = array('error_text' => 'unknown');
    }
   } elseif($go_section == 1) {
    $check_like = $vk->check_like($user_vk_id, $go_type, $go_url, $token);
    if($check_like == 1) {
     $json = array('success' => 1, 'points' => $go_amount);
     tasks::task_done($id, $go_type.''.$go_url, $go_amount, $go_count, $go_section, $go_group_closed);
    } elseif($check_like == 2) {
     if($go_type == 'wall') {
      $json = array('error_text' => 'Пожалуйста, <b>нажмите «Мне нравится» на записи</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
     } elseif($go_type == 'wall_comment') {
      $json = array('error_text' => 'Пожалуйста, <b>нажмите «Мне нравится» на комментарии</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
     } elseif($go_type == 'photo') {
      $json = array('error_text' => 'Пожалуйста, <b>нажмите «Мне нравится» на фотографии</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
     } elseif($go_type == 'video') {
      $json = array('error_text' => 'Пожалуйста, <b>нажмите «Мне нравится» на видеозаписи</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
     } elseif($go_type == 'topic') {
      $json = array('error_text' => 'Пожалуйста, <b>нажмите «Мне нравится» на обсуждении</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
     }
    } elseif($check_like == 0) {
     $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже.');
    } else {
     $json = array('error_text' => 'unknown');
    }
   } elseif($go_section == 2) {
    $check_repost = $vk->check_repost($user_vk_id, $go_type, $go_url, $token);
    if($check_repost == 1) {
     $json = array('success' => 1, 'points' => $go_amount);
     tasks::task_done($id, $go_type.''.$go_url, $go_amount, $go_count, $go_section, $go_group_closed);
    } elseif($check_repost == 2) {
     if($go_type == 'wall') {
      $json = array('error_text' => 'Пожалуйста, <b>расскажите друзьям о записи</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
     } elseif($go_type == 'wall_comment') {
      $json = array('error_text' => 'Пожалуйста, <b>расскажите друзьям о комментарии</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
     } elseif($go_type == 'photo') {
      $json = array('error_text' => 'Пожалуйста, <b>расскажите друзьям о фотографии</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
     } elseif($go_type == 'video') {
      $json = array('error_text' => 'Пожалуйста, <b>расскажите друзьям о видеозаписи</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
     }
    } elseif($check_repost == 0) {
     $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже.');
    } else {
     $json = array('error_text' => 'unknown');
    }
   } elseif($go_section == 4) {
    $check_follower = $vk->check_follower($user_vk_id, $go_url, $token);
    if($check_follower == 1) {
     $json = array('success' => 1, 'points' => $go_amount);
     tasks::task_done($id, $go_type.''.$go_url, $go_amount, $go_count, $go_section, $go_group_closed);
    } elseif($check_follower == 2) {
      $json = array('error_text' => 'Пожалуйста, <b>подпишитесь на человека</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
    } elseif($check_follower == 0) {
     $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже.');
    } else {
     $json = array('error_text' => 'unknown');
    }
   } elseif($go_section == 3) {
    if($go_type == 'wall') {
     $check_comment = $vk->check_wall_comment($user_vk_id, $go_url, $comment, $token);
     if($check_comment == 1) {
      $json = array('success' => 1, 'points' => $go_amount);
      tasks::task_done($id, $go_type.''.$go_url, $go_amount, $go_count, $go_section, $go_group_closed);
     } elseif($check_comment == 2) {
      $json = array('error_text' => 'Пожалуйста, <b>оставьте комментарий к записи</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
     } elseif($check_comment == 0) {
      $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже.');
     } else {
      $json = array('error_text' => 'unknown');
     }
    } elseif($go_type == 'video') {
     $check_comment = $vk->check_video_comment($user_vk_id, $go_url, $comment, $token);
     if($check_comment == 1) {
      $json = array('success' => 1, 'points' => $go_amount);
      tasks::task_done($id, $go_type.''.$go_url, $go_amount, $go_count, $go_section, $go_group_closed);
     } elseif($check_comment == 2) {
      $json = array('error_text' => 'Пожалуйста, <b>оставьте комментарий к видеозаписи</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
     } elseif($check_comment == 0) {
      $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже.');
     } else {
      $json = array('error_text' => 'unknown');
     }
    } elseif($go_type == 'photo') {
     $check_comment = $vk->check_photo_comment($user_vk_id, $go_url, $comment, $token);
     if($check_comment == 1) {
      tasks::task_done($id, $go_type.''.$go_url, $go_amount, $go_count, $go_section, $go_group_closed);
      $json = array('success' => 1, 'points' => $go_amount);
     } elseif($check_comment == 2) {
      $json = array('error_text' => 'Пожалуйста, <b>оставьте комментарий к фотографии</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
     } elseif($check_comment == 0) {
      $json = array('error_text' => 'Ошибка соединения с сервером ВКонтакте. Попробуйте позже.');
     } else {
      $json = array('error_text' => 'Неизвестная ошибка.');
     }
    }
   } elseif($go_section == 6) {
    $check_poll = $vk->wall_poll_check($go_url, $go_poll_id, $answer_poll, $token);
    if($check_poll == 1) {
     tasks::task_done($id, $go_type.''.$go_url, $go_amount, $go_count, $go_section, $go_group_closed);
     $json = array('success' => 1, 'points' => $go_amount);
    } else {
     $json = array('error_text' => 'Пожалуйста, <b>проголосуйте в опросе</b> и мы зачислим на Ваш счет <b>'.$go_amount.' '.declOfNum($go_amount, array('балл', 'балла', 'баллов')).'</b>.');
    }
   } else {
    $json = array('error_text' => 'Неизвестная ошибка.');
   }
  } else {
   $json = array('error_text' => 'Неизвестная ошибка.');
  }
  return jdecoder(json_encode($json));
 }
 
 public function task_done($tid = null, $url = null, $amount = null, $count = null, $section = null, $read_done = null) {
  global $db, $dbName, $user_id, $time, $logs, $uvk_id;
  
  $unique_key = 'done_'.$tid.''.$user_id.''.$uvk_id;
  
  if($db->query("INSERT INTO `$dbName`.`tasks_done` (`tdid`, `tduid`, `tdvk_id`, `tdtid`, `tdurl`, `tdtype`, `tdtime`, `tdsection`, `tdread`, `unique_key`) VALUES (NULL, '$user_id', '$uvk_id', '$tid', '$url', 'done', '$time', '$section', '$read_done', '$unique_key');")) {
   $db->query("UPDATE `$dbName`.`users` SET `upoints` =  upoints + '$amount' WHERE `users`.`uid` = '$user_id';"); // начисляем баллы
   $db->query("UPDATE `$dbName`.`tasks` SET `tdone_count` =  tdone_count + 1 WHERE  `tasks`.`tid` = '$tid'");
   $logs->done_task($user_id, $tid, $amount);
   
   $qTask = $db->query("SELECT `tdone_count` FROM `tasks` WHERE `tid` = '$tid'");
   $dTask = $db->fetch($qTask);
   if($dTask['tdone_count'] == $count) {
    $db->query("UPDATE `$dbName`.`tasks` SET `tsuccess` = '1' WHERE  `tasks`.`tid` = '$tid'");
   }
  }
 }
 
 public function logs_edits_num() {
  global $db;
  
  $id = (int) $_GET['id'];
  
  $q = $db->query("SELECT `lid` FROM `logs` WHERE (`lmodule` = '4' AND `lmodule_type` = '3') AND `lmid` = '$id'");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function logs_edits() {
  global $db, $user_id, $user_logged, $ugroup, $site_url;
  
  if(!$user_logged) {
   return 'login';
   exit;
  }
  
  if($ugroup != 4) {
   return 'Access Denied';
   exit;
  }
  
  $id = (int) $_GET['id'];
  $num = tasks::logs_edits_num();
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 10;
  
  $q = $db->query("
   SELECT logs.lfrom, logs.ltext, logs.lip_address, logs.lbrowser, logs.ltime, users.uname, users.ulast_name FROM `logs`
    INNER JOIN `users` ON logs.lfrom = users.uid
   WHERE (logs.lmodule = '4' AND logs.lmodule_type = '3') AND logs.lmid = '$id'
   ORDER BY logs.lid DESC
   LIMIT $start_limit, 10
  ");
  while($d = $db->fetch($q)) {
   $from = $d['lfrom'];
   $text = json_decode($d['ltext'], true);
   $ip = $d['lip_address'];
   $browser = $d['lbrowser'];
   $time = $d['ltime'];
   $name = $d['uname'];
   $last_name = $d['ulast_name'];
   
   $template .= '
     <div class="admin_edit_info_history_overflow">
      <div class="admin_edit_info_history_overflow_ftype">
       <a href="/admin/modules/users/?search='.$site_url.'id'.$from.'" onclick="nav.go(this); return false"><b>'.($name ? $name : 'Безымянный').' '.$last_name.'</b></a> изменил задание:
       <div class="admin_edit_info_history_overflow_fsystem"><span>IP:</span> '.$ip.'; <span>Браузер:</span> '.$browser.'; <span>Время:</span> '.new_time($time).';</div>
      </div>
      <div class="overflow_field">
       <div class="label">ID категории:</div>
       <div class="field">'.($text['new_cat'] == $text['last_cat'] ? $text['new_cat'] : '<span class="admin_edit_info_history_overflow_new_string">'.$text['new_cat'].'</span>').' / '.$text['last_cat'].'</div>
      </div>
      <div class="overflow_field">
       <div class="label">Количество:</div>
       <div class="field">'.($text['new_count'] == $text['last_count'] ? $text['new_count'] : '<span class="admin_edit_info_history_overflow_new_string">'.$text['new_count'].'</span>').' / '.$text['last_count'].'</div>
      </div>
     </div>
   ';
  }
  return $template ? '
   <div id="admin_edit_info_box_pages">
    '.pages_ajax(array('ents_count' => $num, 'ents_print' => 10, 'page' => $page)).' 
    <span class="pages_ajax"><div class="upload"></div></span>
   </div>
   '.$template.'
  ' : '<div id="admin_edit_info_box_none">История редактирований пуста.</div>';
 }
 
 public function logs_dels_num() {
  global $db;
  
  $id = (int) $_GET['id'];
  
  $q = $db->query("SELECT `lid` FROM `logs` WHERE (`lmodule` = '4' AND `lmodule_type` = '2' OR `lmodule` = '4' AND `lmodule_type` = '4') AND `lmid` = '$id'");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function logs_dels() {
  global $db, $user_id, $user_logged, $ugroup, $site_url;
  
  if(!$user_logged) {
   return 'login';
   exit;
  }
  
  if($ugroup != 4) {
   return 'Access Denied';
   exit;
  }
  
  $id = (int) $_GET['id'];
  $num = tasks::logs_dels_num();
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 10;
  
  $q = $db->query("
   SELECT logs.lfrom, logs.lip_address, logs.lbrowser, logs.ltime, logs.lmodule, logs.lmodule_type, users.uname, users.ulast_name FROM `logs`
    INNER JOIN `users` ON logs.lfrom = users.uid
   WHERE (logs.lmodule = '4' AND logs.lmodule_type = '2' OR logs.lmodule = '4' AND logs.lmodule_type = '4') AND logs.lmid = '$id'
   ORDER BY logs.lid DESC
   LIMIT $start_limit, 10
  ");
  while($d = $db->fetch($q)) {
   $from = $d['lfrom'];
   $ip = $d['lip_address'];
   $browser = $d['lbrowser'];
   $time = $d['ltime'];
   $name = $d['uname'];
   $last_name = $d['ulast_name'];
   $module = $d['lmodule'];
   $module_type = $d['lmodule_type'];
   
   if($module == 4 && $module_type == 2) {
    $template .= '
      <div class="admin_edit_info_history_overflow">
       <div class="admin_edit_info_history_overflow_ftype">
        <a href="/admin/modules/users/?search='.$site_url.'id'.$from.'" onclick="nav.go(this); return false"><b>'.($name ? $name : 'Безымянный').' '.$last_name.'</b></a> удалил задание.
        <div class="admin_edit_info_history_overflow_fsystem"><span>IP:</span> '.$ip.'; <span>Браузер:</span> '.$browser.'; <span>Время:</span> '.new_time($time).';</div>
       </div>
      </div>
    ';
   } elseif($module == 4 && $module_type == 4) {
    $template .= '
      <div class="admin_edit_info_history_overflow">
       <div class="admin_edit_info_history_overflow_ftype">
        <a href="/admin/modules/users/?search='.$site_url.'id'.$from.'" onclick="nav.go(this); return false"><b>'.($name ? $name : 'Безымянный').' '.$last_name.'</b></a> восстановил задание.
        <div class="admin_edit_info_history_overflow_fsystem"><span>IP:</span> '.$ip.'; <span>Браузер:</span> '.$browser.'; <span>Время:</span> '.new_time($time).';</div>
       </div>
      </div>
    ';
   } else {
    $template .= '';
   }
  }
  return $template ? '
   <div id="admin_edit_info_box_pages">
    '.pages_ajax(array('ents_count' => $num, 'ents_print' => 10, 'page' => $page)).' 
    <span class="pages_ajax"><div class="upload"></div></span>
   </div>
   '.$template.'
  ' : '<div id="admin_edit_info_box_none">История удалений пуста.</div>';
 }
}
$tasks = new tasks;
?>