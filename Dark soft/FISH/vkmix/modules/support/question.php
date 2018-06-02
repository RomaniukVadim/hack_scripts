<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'support';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');

$page_user_banned = $uban_type;

include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/support.php');

$support_question_id = (int) $_GET['id'];
$support_my_question_id = $support->question_id();

// сбрасываем статус
if($support_my_question_id['uid'] == $user_id && $support_my_question_id['status'] == 1) {
 $db->query("UPDATE `$dbName`.`support_questions` SET `status` =  '2' WHERE  `support_questions`.`id` = '$support_question_id';");
}

// аватар в форме добавления комментария
if($support_my_question_id['uid'] == $user_id) {
 if($uavatar) {
  $avatar_form_add_comment = $uavatar;
 } else {
  $avatar_form_add_comment = $noavatar;
 }
} elseif($ugroup == 4 || $ugroup == 5) {
 $avatar_form_add_comment = '/images/agents/agent_avatar'.$uagent_avatar.'.png';
} else {
 $avatar_form_add_comment = $noavatar;
}

if(!$support_my_question_id['id']) {
 header('Location: /support');
} elseif($support_my_question_id['del'] && $ugroup != 4 && $ugroup != 5) {
 header('Location: /support');
}

if($ugroup == 4 || $ugroup == 5) {
 $support_new_my = my_support_new();
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Помощь по сайту</title>
<? include($root.'/include/head.php') ?>

 </head>
 <body>
 <div id="page">
<? include($root.'/include/header.php') ?>

   <div id="content">
<? include($root.'/include/left.php') ?>

    <div id="right_wrap">
     <div id="right_wrap_b">
      <div id="right">
       <div class="main nopad">
        <div class="tabs">
         <span class="tabs_left">
          <a href="/support" onclick="nav.go(this); return false;"><div class="tabdiv">Мои вопросы <? if($support_new_my) echo '(<b>'.$support_new_my.'</b>)'; ?></div></a>
          <? if($ugroup == 4 || $ugroup == 5) { ?><a href="/support/questions" onclick="nav.go(this); return false;"><div class="tabdiv">Все вопросы <? if($support_new) echo '(<b>'.$support_new.'</b>)'; ?></div></a><? } ?> 
          <? if($ugroup == 4 || $ugroup == 5) { ?><a href="/support/rate" onclick="nav.go(this); return false;"><div class="tabdiv">Рейтинг агентов</div></a><? } ?>
          <a class="active" href="/support/question?id=<? echo $support_question_id; ?>" onclick="nav.go(this); return false;"><div class="tabdiv">Вопрос</div></a>
         </span>
         <span class="tabs_right">
          <a class="other_a_tab" href="/support/new" onclick="nav.go(this); return false;">Новый вопрос</a>
         </span>
        </div>
        <? echo $support_my_question_id['template']; ?> 
        <? echo $support->question_comment($support_my_question_id['uid']); ?> 
        <div id="support_question_add_comment">
         <div id="support_question_add_comment_avatar">
          <img src="<? echo $avatar_form_add_comment; ?>">
         </div>
         <div id="support_question_add_comment_txt">
          <textarea iplaceholder="Комментировать..." id="support_question_add_comment_field_text"></textarea>
          <div class="support_images_attach_comment" id="support_images_attach"></div>
          <input type="hidden" id="support_images_attach_img_field_ids">
          <div id="support_grad_progress"></div>
          <div id="support_question_add_comment_txt_buttons">
           <div id="support_question_add_comment_txt_buttons_left">
            <div onclick="support._add_comment(<? echo $support_question_id; ?>)" class="blue_button_wrap"><div class="blue_button">Отправить</div></div>
            <div id="error_msg_support_error"></div>
           </div>
           <div class="support_add_content_buttons_right_comment" id="support_add_content_buttons_right">
            <a href="javascript://">
             Прикрепить изображение
             <iframe id="support_upload_iframe" name="support_upload_iframe"></iframe>
             <form method="post" enctype="multipart/form-data" action="/support/img.upload" target="support_upload_iframe">
              <input id="support_add_img_file" onchange="support.upload_img('comment'); return false;" type="file" name="file">
              <input id="support_upload_iframe_submit" style="display: none;" type="submit">
             </form>
            </a>
           </div>
          </div>
         </div>
        </div>
       </div>
      </div>
     </div>
<? include($root.'/include/footer.php') ?>
 
    </div>
   </div>
  </div>
<? include($root.'/include/scripts.php') ?> 
 </body>
</html>