<?php
class support {
 public function text_replace($text = null) {
  $text = preg_replace('/\[br\]/i', '<br />', $text);
  $text = preg_replace('/\[url\=(.*?)\](.*?)\[\/url\]/i', '<a href="$1" target="_blank">$2</a>', $text);
  
  return $text;
 }
 
 public function add() {
  global $db, $dbName, $user_id, $time, $ip_address, $browser, $user_logged, $vk, $logs, $session;
  
  $title = $db->escape(trim($_POST['title']));
  $message = $db->escape(trim($_POST['message']));
  $photo_attaches = $db->escape($_POST['photo_attaches']);
  $photo_attaches_explode = explode(',', $photo_attaches);
  $photo_attaches_explode = array_diff($photo_attaches_explode, array(''));
  $photo_attaches_result = check_id_attaches($photo_attaches) ? $photo_attaches : '';
  $ssid = (int) abs($_POST['ssid']);
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if(mb_strlen($title, 'UTF-8') < 2) {
   $json = array('error_text' => 'Слишком короткий заголовок.');
  } elseif(mb_strlen($message, 'UTF-8') < 2) {
   $json = array('error_text' => 'Слишком короткий текст.');
  } elseif(count($photo_attaches_explode) > 5) {
   $json = array('error_text' => 'Вы можете прикрепить к вопросу не более 5 файлов.');
  } else {
  // открываем список вопросов
  $qQuestions_limit = time() - (60 * 60);
  $qQuestions = $db->query("SELECT `id` FROM `support_questions` WHERE `time` >= '$qQuestions_limit' AND `uid` = '$user_id' AND `system` = '0'");
  $nQuestions = $db->num($qQuestions);
   
  if($session->get('usession') != $ssid) {
   $json = array('error_text' => 'Истек период сессии.');
  } elseif($nQuestions) {
    $json = array('error_text' => 'Вы задаете вопросы слишком часто. Попробуйте позже.');
   } elseif($db->query("INSERT INTO `$dbName`.`support_questions` (`id`, `uid`, `title`, `text`, `time`, `ip`, `browser`, `last_time`, `agent_time`, `agent_id`, `agent_avatar`, `status`, `closed`, `del`, `edit_time`, `attaches_photo`, `system`, `aid`) VALUES (NULL, '$user_id', '$title', '$message', '$time', '$ip_address', '$browser', '$time', '', '', '', '0', '', '', '', '$photo_attaches_result', '0', '');")) {
    $dQuestions_last_id = $db->insert_id();
    $logs->support_new_question($user_id, $dQuestions_last_id);
    $json = array('success' => 1, 'id' => $dQuestions_last_id);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  }
  return jdecoder(json_encode($json));
 }
 
 public function send_notif($to = null, $title = null, $message = null) {
  global $db, $dbName, $time, $ip_address, $browser;
  $db->query("INSERT INTO `$dbName`.`support_questions` (`id`, `uid`, `title`, `text`, `time`, `ip`, `browser`, `last_time`, `agent_time`, `agent_id`, `agent_avatar`, `status`, `closed`, `del`, `edit_time`, `attaches_photo`, `system`, `aid`) VALUES (NULL, '$to', '$title', '$message', '$time', '$ip_address', '$browser', '$time', '$time', '1', '1', '1', '', '', '', '', '1', '0');");
 }
 
 public function my_num() {
  global $db, $user_id;
  
  $q = $db->query("SELECT `id` FROM `support_questions` WHERE `uid` = '$user_id' AND `del` = '0'");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function my() {
  global $db, $user_id, $noavatar;
  
  $q = $db->query("
   SELECT support_questions.id AS qid, support_questions.title AS qtitle, support_questions.last_time AS qlast_time, support_questions.agent_time AS qagent_time, support_questions.agent_avatar AS qagent_avatar, support_questions.status AS qstatus, support_questions.system AS qsystem, users.uname, users.ulast_name, users.uavatar as uavatar
   FROM `support_questions`
    INNER JOIN `users` ON support_questions.uid = users.uid
   WHERE support_questions.uid = '$user_id' AND support_questions.del = '0'
   ORDER BY support_questions.id DESC
  ");
  while($d = $db->assoc($q)) {
   $question_id = $d['qid'];
   $question_title = fxss($d['qtitle']);
   $question_last_time = $d['qlast_time'];
   $question_agent_time = $d['qagent_time'];
   $question_agent_avatar = $d['qagent_avatar'];
   $question_status = $d['qstatus'];
   $qsystem = $d['qsystem'];
   $ufirst_name = $d['uname'];
   $ulast_name = $d['ulast_name'];
   $uavatar = $d['uavatar'];
   $uresult_time = !$question_agent_avatar ? new_time($question_last_time) : 'ответил '.new_time($question_agent_time);
   
   // имя
   if($question_agent_time) {
    $uresult_name = 'Агент поддержки';
   } elseif(!$d['uname']) {
    $uresult_name = 'Безымянный';
   } else {
    $uresult_name = $ufirst_name.' '.$ulast_name;
   }
   
   // статус вопроса
   if($question_status == 1) {
    $result_question_status = 'Есть <b>новый ответ</b>.';
   } elseif($question_status == 2) {
    $result_question_status = 'Есть ответ.';
   } else {
    $result_question_status = 'Рассматривается.';
   }
   
   // аватар агента
   if($question_agent_avatar) {
    $uresult_avatar = '/images/agents/agent_avatar'.$question_agent_avatar.'.png';
   } elseif($uavatar) {
    $uresult_avatar = $uavatar;
   } else {
    $uresult_avatar = $noavatar;
   }
   
   $template .= '
         <div class="question_main">
          <div class="question_left">
           <div class="question_title"><a href="/support/question?id='.$question_id.'" onclick="nav.go(this); return false">'.($question_title ? $question_title : '&amp;nbsp;').'</a></div>
           <div class="question_status">'.$result_question_status.'</div>
          </div>
          <div class="question_right">
           <div onclick="nav.go(\'\', \'/support/question?id='.$question_id.'\')" class="question_mini_profile">
            <div class="question_mini_avatar"><img src="'.$uresult_avatar.'"></div>
            <div class="question_mini_info">
             <div class="question_mini_username">
              <a href="javascript://">'.$uresult_name.'</a>
             </div>
             <div class="question_mini_time">
              '.$uresult_time.'
             </div>
            </div>
           </div>
          </div>
         </div>
   ';
  }
  return $template;
 }
 
 public function question_id() {
  global $db, $user_id, $noavatar, $ugroup;
  
  $qid = (int) abs($_GET['id']);
  
  $site_demo = '
   Поздравляем! Вы зарегистрировались в сервисе продвижения услуг ВКонтакте - <b>Piar.Name</b>.
   <br /><br />
   С помощью нашего сервиса Вы сможете накрутить:
   <br /> <br />
   <ul>
    <li>Мне нравится</li>
    <li>Рассказать друзьям</li>
    <li>Комментарии</li>
    <li>Подписчиков</li>
    <li>Участников в группу</li>
    <li>Голоса в опрос</li>
   </ul>
   <br />
   <h1>Как мне накрутить то, что я хочу?</h1>
   Всё очень просто. Чтобы накрутить, например, «Мне нравится», Вам нужны <b>баллы</b>, которые можно заработать, <a href="/tasks" onclick="nav.go(this); return false"><b>выполняя задания</b></a> других пользователей. После того, как Вы выполнили задания и получили баллы, можете <a href="/tasks/add" onclick="nav.go(this); return false"><b>перейти к созданию своего задания</b></a>. Создав своё задание, другие пользователи начнут его выполнять и так же получать баллы.
   <br /> <br />
   С остальным функционалом Вы можете ознакомиться сами.
   <br />
   Если у Вас появились вопросы, Вы можете задать их в форму ниже.
   <br /><br />
   Мы очень рады, что Вы выбрали именно нас!
   <br /><br />
   <i>С Уважением,
   <br />
   Команда Piar.Name.</i>
  ';
  
  $q = $db->query("
   SELECT support_questions.id AS qid, support_questions.title AS qtitle, support_questions.text AS qtext, support_questions.last_time AS qlast_time, support_questions.time AS qreal_time, support_questions.agent_time AS qagent_time, support_questions.agent_avatar AS qagent_avatar, support_questions.status AS qstatus, support_questions.system AS qsystem, support_questions.del, support_questions.browser AS qbrowser, support_questions.attaches_photo AS qattaches_photo, users.uid, users.uvk_id, users.uname, users.ulast_name, users.uavatar
   FROM `support_questions`
    INNER JOIN `users` ON support_questions.uid = users.uid
   WHERE support_questions.id = '$qid'
   ORDER BY support_questions.id DESC
  ");
  while($d = $db->assoc($q)) {
   $question_id = $d['qid'];
   $question_title = fxss($d['qtitle']);
   $question_text = str_replace(array('[site_demo]'), array($site_demo), fxss($d['qtext']));
   $question_real_time = $d['qreal_time'];
   $question_last_time = $d['qlast_time'];
   $question_agent_time = $d['qagent_time'];
   $question_agent_avatar = $d['qagent_avatar'];
   $question_status = $d['qstatus'];
   $qsystem = $d['qsystem'];
   $qbrowser = $d['qbrowser'];
   $qattaches_photo = normal_id_attaches($d['qattaches_photo']);
   $del = $d['del'];
   $uid = $d['uid'];
   $uvk_id = $d['uvk_id'];
   $ufirst_name = $d['uname'];
   $ulast_name = $d['ulast_name'];
   $uavatar = $d['uavatar'] ? $d['uavatar'] : $noavatar;
   $uresult_name = !$d['uname'] ? 'Безымянный' : $ufirst_name.' '.$ulast_name;
   
   // дополнительные функции
   if($uid == $user_id) {
    $button_delete = '<span class="divider_support">|</span><a href="javascript://" onclick="support._delete_post('.$question_id.')">Удалить вопрос</a>';
   } else {
    $button_delete = '';
   }
   
   // аттачи
   if($qattaches_photo) {
    $query_attaches_photo = $db->query("SELECT `small`, `big` FROM `attaches_img` WHERE `uid` = '$uid' AND `id` IN(".$db->escape($qattaches_photo).") AND `modules` = 6 ORDER BY `id` ASC");
    while($data_attaches_photo = $db->fetch($query_attaches_photo)) {
     $attaches_img_result .= '<a href="/images/support/uploads/'.$data_attaches_photo['big'].'.jpg" target="_blank"><img src="/images/support/uploads/'.$data_attaches_photo['small'].'.jpg"></a>';
    }
    $attaches_img_result_que = '<div class="question_attaches_img_result">'.$attaches_img_result.'</div>';
   }
   
   // браузер
   if($ugroup == 4 || $ugroup == 5) {
    if($uid == $user_id) {
     $browser_head = '';
     $status_edit = '';
    } else {
     $browser_head = '<span class="support_browser_agent">(браузер '.$qbrowser.')</span>';
     $status_edit = '
     <div style="display: none" id="list_edit_que_status">
      <div onclick="admin_support._edit_status(1, '.$qid.')" class="mnav">Есть <b>новый ответ</b></div>
      <div onclick="admin_support._edit_status(2, '.$qid.')" class="mnav">Есть ответ</div>
      <div onclick="admin_support._edit_status(0, '.$qid.')" onclick="asdasd()" class="mnav">Рассматривается</div>
     </div>';
    }
   } else {
    $browser_head = '';
    $status_edit = '';
   }
   
   // имя
   if($qsystem) {
    $uresult_name = 'Агент поддержки';
    $uresult_url = '<a>';
   } elseif(!$d['uname']) {
    $uresult_name = 'Безымянный';
    $uresult_url = '<a>';
   } else {
    $uresult_name = $ufirst_name.' '.$ulast_name;
    $uresult_url = '<a href="http://vk.com/id'.$uvk_id.'" target="_blank">';
   }
   
   // статус вопроса
   if($del) {
    $result_question_status = 'Вопрос удален.';
   } elseif($question_status == 1) {
    $result_question_status = 'Есть <b>новый ответ</b>.';
   } elseif($question_status == 2) {
    $result_question_status = 'Есть ответ.';
   } else {
    $result_question_status = 'Рассматривается.';
   }
   
   // аватар агента
   if($qsystem) {
    $uresult_avatar = '/images/agents/agent_avatar1.png';
   } elseif($uavatar) {
    $uresult_avatar = $uavatar;
   } else {
    $uresult_avatar = $noavatar;
   }
   
   $template = '
        <div id="support_title_head">
         <div id="support_title_head_left">
          <div id="support_title_head_left_title">'.($question_title ? $question_title : '&amp;nbsp;').' '.$browser_head.'</div>
          <div id="support_title_head_left_status">'.$result_question_status.'</div>
         </div>
         <div id="support_title_head_right_status"><div id="edit_que_navigation"></div></div>
        </div>
        <div class="support_comment_question">
         <div class="support_comment_question_avatar">
          <img src="'.$uresult_avatar.'">
         </div>
         <div class="support_comment_question_info">
          <div class="support_comment_question_info_name">'.$uresult_url.''.$uresult_name.'</a></div>
          <div class="support_comment_question_info_text">
           '.($question_text ? stripslashes(str_replace("\n","\n\t", support::text_replace($question_text))) : '&amp;nbsp;').'
           '.$attaches_img_result_que.'
          </div>
          <div class="support_comment_question_info_footer">
           <span class="support_comment_question_info_footer_date">'.new_time($question_real_time).'</span>'.$button_delete.'
          </div>
         </div>
        </div>
        '.$status_edit.'
   ';
  }
  
  if($uid != $user_id && $ugroup != 4 && $ugroup != 5) {
   return '';
   exit;
  }
  
  return array('template' => $template, 'uid' => $uid, 'status' => $question_status, 'id' => $question_id, 'del' => $del);
 }
 
 public function question_comment_attaches($list = null, $uid = null) {
  global $db;
  
  // аттачи к комментариям
  $query_attaches_photo = $db->query("SELECT `small`, `big` FROM `attaches_img` WHERE `uid` = '$uid' AND `id` IN(".$db->escape($list).") AND `modules` = 6 ORDER BY `id` ASC");
  while($data_attaches_photo = $db->fetch($query_attaches_photo)) {
   $attaches_img_result .= '<a href="/images/support/uploads/'.$data_attaches_photo['big'].'.jpg" target="_blank"><img src="/images/support/uploads/'.$data_attaches_photo['small'].'.jpg"></a>';
  }
  return $attaches_img_result ? '<div class="question_attaches_img_result">'.$attaches_img_result.'</div>' : '';
 }
 
 public function question_comment($que_uid = null) {
  global $db, $user_id, $noavatar, $uagent_id;
  
  $question_id = (int) abs($_GET['id']);

  $q = $db->query("
   SELECT support_comments.id AS qid, support_comments.agent_id AS qaid, support_comments.agent_avatar AS qagent_avatar, support_comments.time AS qtime, support_comments.text AS qtext, support_comments.rate AS qrate, support_comments.attaches_photo AS qattaches_photo, users.uid, users.uavatar, users.uname, users.ulast_name, users.uvk_id FROM `support_comments`
    INNER JOIN `users` ON support_comments.uid = users.uid
   WHERE support_comments.qid = '$question_id' 
   ORDER BY `id` ASC
  ");
  while($d = $db->assoc($q)) {
   $qid = $d['qid'];
   $qaid = $d['qaid'];
   $qagent_avatar = $d['qagent_avatar'];
   $qtext = nl2br(fxss($d['qtext']));
   $qrate = $d['qrate'];
   $qtime = $d['qtime'];
   $uid = $d['uid'];
   $qattaches_photo = normal_id_attaches($d['qattaches_photo']);
   $qattaches_photo_list = support::question_comment_attaches($qattaches_photo, $uid);
   $uavatar = $d['uavatar'];
   $ufirst_name = $d['uname'];
   $ulast_name = $d['ulast_name'];
   $uvk_id = $d['uvk_id'];
   $uresult_name = !$ufirst_name ? 'Безымянный' : $ufirst_name.' '.$ulast_name;
   $uresult_avatar = !$uavatar ? $noavatar : $uavatar;
   $uresult_url = !$ufirst_name ? '<a href="javascript://">' : '<a href="http://vk.com/id'.$uvk_id.'" target="_blank">';
   
   // рейтинг ответа
   if($qrate) {
    if($qrate == 1) {
     if($uagent_id == $qaid || $user_id != $que_uid) {
      $rate_answer_button = '<span class="divider_support">|</span><span class="rate_result_comment_support rate_result_comment_support_1">Оставлен <b>положительный</b> отзыв</span>';
     } else {
      $rate_answer_button = '<span class="divider_support">|</span><span class="rate_result_comment_support">Вы оставили положительный отзыв</span>';
     }
    } else {
     if($uagent_id == $qaid || $user_id != $que_uid) {
      $rate_answer_button = '<span class="divider_support">|</span><span class="rate_result_comment_support rate_result_comment_support_2">Оставлен <b>негативный</b> отзыв</span>';
     } else {
      $rate_answer_button = '<span class="divider_support">|</span><span class="rate_result_comment_support">Вы оставили негативный отзыв</span>';
     }
    }
   } elseif($qaid) {
    if($uagent_id == $qaid || $que_uid == $uid || $uagent_id && $que_uid) {
     $rate_answer_button = '';
    } else {
     $rate_answer_button = '<span class="divider_support">|</span><span id="rate_support_comment'.$qid.'"><a href="javascript://" onclick="support._rate_comment('.$qid.', 1)">Это хороший ответ</a><span class="divider_support">|</span><a href="javascript://" onclick="support._rate_comment('.$qid.', 2)">Это плохой ответ</a></span>';
    }
   } else {
    $rate_answer_button = '';
   }
   
   if($qaid) {
    $template .= '
         <div class="support_comment_question">
          <div class="support_comment_question_avatar">
           <img src="/images/agents/agent_avatar'.$qagent_avatar.'.png">
          </div>
          <div class="support_comment_question_info">
           <div class="support_comment_question_info_name"><span class="agent_name">Агент поддержки #'.$qaid.'</span></div>
           <div class="support_comment_question_info_text">
            '.stripslashes(str_replace("\n","\n\t", $qtext)).'
            '.$qattaches_photo_list.'
           </div>
           <div class="support_comment_question_info_footer">
            <span class="support_comment_question_info_footer_date">'.new_time($qtime).'</span>'.$rate_answer_button.'
           </div>
          </div>
         </div>
    ';
   } else {
    $template .= '
         <div class="support_comment_question">
          <div class="support_comment_question_avatar">
           <img src="'.$uresult_avatar.'">
          </div>
          <div class="support_comment_question_info">
           <div class="support_comment_question_info_name">'.$uresult_url.''.$uresult_name.'</a></div>
           <div class="support_comment_question_info_text">
            '.stripslashes(str_replace("\n","\n\t", $qtext)).'
            '.$qattaches_photo_list.'
           </div>
           <div class="support_comment_question_info_footer">
            <span class="support_comment_question_info_footer_date">'.new_time($qtime).'</span>
           </div>
          </div>
         </div>
    ';
   }
  }
  return $template;
 }
 
 public function question_comments_num($id = null) {
  global $db;
  
  $q = $db->query("SELECT `id` FROM `support_comments` WHERE `qid` = '$id'");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function add_comment() {
  global $db, $dbName, $user_id, $time, $ip_address, $browser, $user_logged, $vk, $logs, $session, $ugroup, $uavatar, $uagent_id, $uagent_avatar;
  
  $id = (int) abs($_POST['id']);
  $text = $db->escape(trim($_POST['text']));
  $photo_attaches = $db->escape($_POST['photo_attaches']);
  $photo_attaches_explode = explode(',', $photo_attaches);
  $photo_attaches_explode = array_diff($photo_attaches_explode, array(''));
  $photo_attaches_result = check_id_attaches($photo_attaches) ? $photo_attaches : '';
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  // открываем вопрос
  $qQuestion = $db->query("SELECT `id`, `uid`, `aid` FROM `support_questions` WHERE `id` = '$id' AND `del` = '0'");
  $dQuestion = $db->fetch($qQuestion); 
  
  $dQuestion_id = $dQuestion['id'];
  $dQuestion_uid = $dQuestion['uid'];
  $dQuestion_aid = $dQuestion['aid'];
  
  if($ugroup == 4 || $ugroup == 5) {
   if($dQuestion_uid == $user_id) {
    // если свой вопрос для агента
    $query_question = "UPDATE `$dbName`.`support_questions` SET  `status` =  '0', `agent_time` = '', `agent_id` = '', `agent_avatar` = '', `last_time` = '$time' WHERE  `support_questions`.`id` = '$id' LIMIT 1 ;";
    $query_comment = "INSERT INTO `$dbName`.`support_comments` (`id`, `uid`, `qid`, `ip`, `browser`, `time`, `agent_id`, `agent_avatar`, `edit_time`, `text`, `rate`, `attaches_photo`) VALUES (NULL, '$user_id', '$id', '$ip_address', '$browser', '$time', '', '', '', '$text', '', '$photo_attaches_result');";
   } else {
    // чужой вопрос для агента
    $query_question = "UPDATE `$dbName`.`support_questions` SET  `status` =  '1', `agent_time` = '$time', `agent_id` = '$uagent_id', `agent_avatar` = '$uagent_avatar', `aid` = '$uagent_id' WHERE  `support_questions`.`id` = '$id' LIMIT 1 ;";
    $query_comment = "INSERT INTO `$dbName`.`support_comments` (`id`, `uid`, `qid`, `ip`, `browser`, `time`, `agent_id`, `agent_avatar`, `edit_time`, `text`, `rate`, `attaches_photo`) VALUES (NULL, '$user_id', '$id', '$ip_address', '$browser', '$time', '$uagent_id', '$uagent_avatar', '', '$text', '', '$photo_attaches_result');";
   }
  } else {
   $query_question = "UPDATE `$dbName`.`support_questions` SET `status` =  '0', `agent_time` = '', `agent_id` = '', `agent_avatar` = '', `last_time` = '$time' WHERE  `support_questions`.`id` = '$id' LIMIT 1 ;";
   $query_comment = "INSERT INTO `$dbName`.`support_comments` (`id`, `uid`, `qid`, `ip`, `browser`, `time`, `agent_id`, `agent_avatar`, `edit_time`, `text`, `rate`, `attaches_photo`) VALUES (NULL, '$user_id', '$id', '$ip_address', '$browser', '$time', '', '', '', '$text', '', '$photo_attaches_result');";
  }
  
  if(!$dQuestion_id) {
   $json = array('error_text' => 'Ошибка доступа.');
  } elseif(mb_strlen($text, 'UTF-8') < 2) {
   $json = array('error_text' => 'Слишком короткий комментарий.');
  } elseif(count($photo_attaches_explode) > 5) {
   $json = array('error_text' => 'Вы можете прикрепить к вопросу не более 5 файлов.');
  } elseif($dQuestion_uid != $user_id && $ugroup != 4 && $ugroup != 5) {
   $json = array('error_text' => 'Ошибка доступа.');
  } elseif($dQuestion_uid != $user_id && $dQuestion_aid && $dQuestion_aid != $uagent_id && $ugroup != 4) {
   $json = array('error_text' => 'Вопрос уже присвоен другому агенту.');
  } elseif(support::question_comments_num($id) > 10 && $ugroup != 4 && $ugroup != 5) {
   $json = array('error_text' => 'Превышен лимит на количество комментариев.');
  } else {
   if($db->query($query_comment)) {
    $db->query($query_question);
    $logs->support_new_comment($user_id, $dQuestion_id);
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  }
  return json_encode($json);
 }
 
 public function del_post() {
  global $db, $dbName, $user_id, $user_logged, $logs;
  
  $id = (int) abs($_GET['id']);
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  $q = $db->query("SELECT `id` FROM `support_questions` WHERE `id` = '$id' AND `uid` = '$user_id' AND `del` = '0'");
  $d = $db->fetch($q);
  
  if($d['id']) {
   if($db->query("UPDATE `$dbName`.`support_questions` SET  `del` =  '1' WHERE  `support_questions`.`id` = '$id' LIMIT 1 ;")) {
    $logs->support_del_post($user_id, $id);
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  } else {
   $json = array('error_text' => 'Ошибка доступа.');
  }
  return jdecoder(json_encode($json));
 }
 
 public function rate_comment() {
  global $db, $dbName, $user_id, $user_logged, $logs, $uagent_id;
  
  $id = (int) abs($_GET['id']);
  $type = (int) abs($_GET['type']);
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  // открываем комментарий
  $qComment = $db->query("SELECT `uid`, `agent_id`, `qid` FROM `support_comments` WHERE `id` = '$id' AND `del` = '0' AND `rate` = '0'");
  $dComment = $db->fetch($qComment);
  
  $dComment_uid = $dComment['uid'];
  $dComment_aid = $dComment['agent_id'];
  $dComment_qid = $dComment['qid'];
  
  // открываем вопрос
  $qQuestion = $db->query("SELECT `uid` FROM `support_questions` WHERE `id` = '$dComment_qid'");
  $dQuestion = $db->fetch($qQuestion);
  
  $dQuestion_uid = $dQuestion['uid'];

  if($dComment_aid) {
   if($uagent_id == $dComment_aid || $dQuestion_uid == $dComment_uid) {
    $json = array('error_text' => 'Ошибка доступа.');
   } else {
    if($type == 1) {
     $type_rate = 1;
     $query_rate = "UPDATE  `$dbName`.`users` SET  `uagent_rate_plus` =  uagent_rate_plus + 1 WHERE  `users`.`uid` = '$dComment_uid' LIMIT 1 ;";
    } else {
     $type_rate = 2;
     $query_rate = "UPDATE  `$dbName`.`users` SET  `uagent_rate_minus` =  uagent_rate_minus + 1 WHERE  `users`.`uid` = '$dComment_uid' LIMIT 1 ;";
    }
    if($db->query($query_rate)) {
     $logs->support_rate_comment($user_id, $id);
     $db->query("UPDATE `$dbName`.`support_comments` SET  `rate` =  '$type_rate' WHERE  `support_comments`.`id` = '$id' LIMIT 1 ;");
     $json = array('success' => 1);
    } else {
     $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
    }
   }
  } else {
   $json = array('error_text' => 'Ошибка доступа.');
  }
  return jdecoder(json_encode($json));
 }
 
 public function all_questions_num() {
  global $db, $user_id;
  
  $uid = (int) $_GET['uid'];
  $uid_sql = $uid ? "`uid` = '$uid'" : "`uid` != '$user_id'";
  
  $q = $db->query("SELECT `id`, `title`, `status`, `del` FROM `support_questions` WHERE ".$uid_sql." AND `system` != 1");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function all_questions() {
  global $db, $user_id;
  
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 20;
  $uid = (int) $_GET['uid'];
  $uid_sql = $uid ? "`uid` = '$uid'" : "`uid` != '$user_id' AND `system` != 1 OR (`system` = 1 AND `status` = 0)";
  
  $q = $db->query("SELECT `id`, `title`, `status`, `del` FROM `support_questions` WHERE ".$uid_sql." ORDER BY `del` ASC, `status` ASC, `last_time` DESC LIMIT $start_limit, 20");
  $i = 0;
  while($d = $db->fetch($q)) {
   $id = $d['id'];
   $title = fxss($d['title']);
   $status = $d['status'];
   $del = $d['del'];
   $class = $i % 2 ? ' active' : '';
   
   // статус вопроса
   if($del == 1) {
    $result_status = 'удален';
   } elseif($status == 1) {
    $result_status = 'есть <b>новый ответ</b>';
   } elseif($status == 2) {
    $result_status = 'есть ответ';
   } else {
    $result_status = '<b>*</b> нет ответа';
   }
   
   $template .= '
           <div class="support_all_questions_table_content'.$class.'">
            <div class="support_all_questions_table_id">'.$id.'</div>
            <div class="support_all_questions_table_question">
             <a href="/support/question?id='.$id.'" onclick="nav.go(this); return false">'.($title ? $title : '&amp;nbsp;').'</a>
            </div>
            <div class="support_all_questions_table_status">
             '.$result_status.'
            </div>
           </div>
   ';
   $i++;
  }
  return $template;
 }
 
 public function agents_rate() {
  global $db;
  
  $q = $db->query("SELECT `uname`, `ulast_name`, `uagent_rate_plus`, `uagent_rate_minus`, `uagent_id` FROM `users` WHERE `ugroup` = '4' OR `ugroup` = '5' ORDER BY uagent_rate_plus/uagent_rate_minus DESC");
  while($d = $db->fetch($q)) {
   $agent_id = $d['uagent_id'];
   $first_name = $d['uname'];
   $last_name = $d['ulast_name'];
   $agent_rate_plus = $d['uagent_rate_plus'];
   $agent_rate_minus = $d['uagent_rate_minus'];
   $agent_rate = !$agent_rate_minus ? $agent_rate_plus : $agent_rate_plus/$agent_rate_minus;
   
   $template .= '
          <div class="support_agents_rate_table_content">
           <div class="support_agents_rate_table_header_agent_name">
            <b>#'.$agent_id.'</b> <a href="#">'.$first_name.' '.$last_name.'</a>
           </div>
           <div class="support_agents_rate_table_header_rate_plus">
            <b>'.$agent_rate_plus.'</b> '.declOfNum($agent_rate_plus, array('ответ', 'ответа', 'ответов')).'
           </div>
           <div class="support_agents_rate_table_header_rate_minus">
            <b>'.$agent_rate_minus.'</b> '.declOfNum($agent_rate_minus, array('ответ', 'ответа', 'ответов')).'
           </div>
           <div class="support_agents_rate_table_header_rate">
            <b>'.$agent_rate.'</b>
           </div>
          </div>
   ';
  }
  return $template;
 }
 
 public function img_upload() {
  global $db, $dbName, $root, $picture, $user_logged, $user_id, $time;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  $__dir = $root.'/images/support/uploads/';
  $__format_explode = explode('.', $_FILES['file']['name']);
  $__format = $__format_explode[count($__format_explode) - 1]; // получаем формат изображения
  $__format_type = array('jpeg', 'JPEG', 'jpg', 'JPG', 'png', 'PNG', 'gif', 'GIF', 'bmp', 'BMP');
  $__file_rand_name = rand_str(10);
  $__file_mini_rand_name = rand_str(15);
  $__file_name = $__dir.''.$__file_rand_name.'.jpg';
  
  // открываем последние загруженные изображения
  $qattach_img_limit = time() - (30 * 60);
  $qattach_img = $db->query("SELECT `id` FROM `attaches_img` WHERE `time` >= '$qattach_img_limit' AND `uid` = '$user_id'");
  $nattach_img = $db->num($qattach_img);
  
  if($nattach_img >= 30) {
   $json = array('error_text' => 'Вы загружаете слишком много изображений. Попробуйте позже.');
  } elseif(!in_array($__format, $__format_type)) {
   $json = array('error_text' => 'Неизвестный формат изображения.');
  } else {
   if(copy($_FILES['file']['tmp_name'], $__file_name)) {
    // если файл загружен, то создаем уменьшенное изображение
    $new_image = new picture($__dir.'/'.$__file_rand_name.'.jpg');
    $new_image->autoimageresize(75, 75);
    $new_image->imagesave('jpeg', $__dir.'/'.$__file_mini_rand_name.'.jpg');
    $new_image->imageout();
    $db->query("INSERT INTO `$dbName`.`attaches_img` (`id`, `uid`, `small`, `big`, `time`, `modules`) VALUES ('', '$user_id', '$__file_mini_rand_name', '$__file_rand_name', '$time', '6');");
    // получаем id загруженного изображения
    $qImg = $db->query("SELECT `id` FROM `attaches_img` WHERE `small` = '$__file_mini_rand_name' AND `uid` = '$user_id'");
    $dImg = $db->fetch($qImg);
    $json = array('success' => 1, 'id' => $dImg['id'], 'result_big_file' => $__file_rand_name.'.jpg', 'result_mini_file' => $__file_mini_rand_name.'.jpg');
   } else {
    $json = array('error_text' => 'Неизвестная ошибка.');
   }
  }
  
  return json_encode($json);
 }
 
 public function admin_edit_status() {
  global $db, $dbName, $ugroup, $user_logged, $user_id, $logs;
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if($ugroup != 4 && $ugroup != 5) {
   return json_encode(array('access' => 'denied'));
   exit;
  }
  
  $id = (int) $_GET['id'];
  $status = (int) abs($_GET['status']);
  
  $q = $db->query("SELECT `id` FROM `support_questions` WHERE `id` = '$id'");
  $n = $db->num($q);
  
  if($status < 0 || $status > 2) {
   $json = array('error_text' => 'status error');
  } elseif(!$n) {
   $json = array('error_text' => 'question not found');
  } else {
   if($db->query("UPDATE  `$dbName`.`support_questions` SET  `status` =  '$status' WHERE  `support_questions`.`id` = '$id' LIMIT 1 ;")) {
    $logs->support_edit_status($user_id, $id, $status);
    $json = array('success' => 1);
   }
  }
  
  return json_encode($json);
 }
}

$support = new support;
?>