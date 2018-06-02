<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'chat';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/chat.php');
require($root.'/inc/classes/emoji.php');

// аватар в форме добавления комментария
 if($uavatar) {
  $avatar_form_add_comment = $uavatar;
 } else {
  $avatar_form_add_comment = $noavatar;
 }

 $History = $chat->Get();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Чат</title>
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
       <div id="top_page" class="main nopad">
        <div id="page_top_pad">
         <div id="top_text">
          <b>Чат</b> Безудержное общение!
         </div>
         <div id="top_text_hr"></div>
         <div class="chat_comment_list">

          <?php
            if(count($History) > 0){
              $LastId = 0;
               foreach($History as $message){
                $time = showDate($message['time']);
                $name = $message['name'];
                $vkid = $message['vk_id'];
                $ava = $message['avatar'];
                $text = $message['message'];
                $LastId = $message['id'];
					if ($ugroup==4) $del_text="<div class=\"chat_comment_question_info_del\" title=\"Удалить\" onclick=\"chat._del_comment(this)\" id=\"$LastId\" >X</div>"; 
                echo "
         <div class=\"chat_comment_question\">
         <div class=\"chat_comment_question_avatar\">
          <img src=\"$ava\">
         </div>
         <div class=\"chat_comment_question_info\">
          <div class=\"chat_comment_question_info_name\"><a href=\"http://vk.com/id$vkid\">$name</a></div>
          <div class=\"chat_comment_question_info_date\" id=\"$LastId\">$time</div>
		  <div class=\"chat_comment_question_info_date\" id=\"$LastId\">$time</div>
		   ".$del_text."
          <div class=\"chat_comment_question_info_text\">
            ".emoji_unified_to_html(htmlspecialchars($text))."
          </div>
         </div>
         </div>";
               }
            } else {
              echo emoji_unified_to_html('<div class="notfound">Сообщений не найдено. Спешите, пусть ваше сообщение будет первым! 😄</div>');
            }
          ?>
         </div>

         <div id="chat_question_add_comment">
         <div id="chat_question_add_comment_avatar">
          <img src="<? echo $avatar_form_add_comment; ?>">
         </div>
         <div id="chat_question_add_comment_txt">
          <div id="chat_question_add_comment_field_text_wrapper">
             <div contenteditable="true" hidefocus="true" strip-br="true" tabindex="0" iplaceholder="Мат и оскорбления участников чата запрещено. Максимальный размер сообщения - 300 символов." id="chat_question_add_comment_field_text"></div>
          </div>
          <div class="chat_images_attach_comment" id="chat_images_attach"></div>
          <div id="chat_question_add_comment_txt_buttons">
           <div id="chat_question_add_comment_txt_buttons_left">
		 <div onclick="chat._add_comment(this)" name="a"  class="blue_button_wrap"><div class="blue_button">Отправить</div></div>
            <input type="hidden" name="lastid" id="chat_last_id" value="<?php echo $LastId;?>" />
            <div id="error_msg_chat_error"></div>
           </div>
           <div class="chat_add_content_buttons_right_comment" id="chat_add_content_buttons_right">
            <a href="javascript:" class="smile"><span class="emoji emoji1f606" data-emoji="1f606"></span></a>
            <a href="javascript:" class="smile"><span class="emoji emoji1f60a" data-emoji="1f60a"></span></a>
            <a href="javascript:" class="smile"><span class="emoji emoji1f622" data-emoji="1f622"></span></a>
            <a href="javascript:" class="smile"><span class="emoji emoji1f621" data-emoji="1f621"></span></a>
            <a href="javascript:" class="smile"><span class="emoji emoji1f61c" data-emoji="1f61c"></span></a>
            <a href="javascript:" class="chat_smile_button">Смайлы</a>
            <div class="emoji-menu">
             <div class="emoji-items-wrap1">
                <table class="emoji-menu-tabs">
                   <tbody>
                      <tr>
                         <td><a class="emoji-menu-tab icon-smile-selected" data-emoji-tab="smile"></a></td>
                         <td><a class="emoji-menu-tab icon-flower" data-emoji-tab="flower"></a></td>
                         <td><a class="emoji-menu-tab icon-bell" data-emoji-tab="bell"></a></td>
                         <td><a class="emoji-menu-tab icon-car" data-emoji-tab="car"></a></td>
                         <td><a class="emoji-menu-tab icon-grid" data-emoji-tab="grid"></a></td>
                      </tr>
                   </tbody>
                </table>
                <div class="emoji-items-wrap mobile_scrollable_wrap">
                   <div class="emoji-items nano-content" tabindex="-1"></div>
                      <div class="emoji-items"></div>
                </div>
              </div>
            </div>

           </div>
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
<script>
$('#chat_question_add_comment_field_text').autoResize({limit: 500, animate:false});
_htmlplaceholder('#chat_question_add_comment_field_text');
</script>
<script>
$(function() {
  chat_pull_down();
  setInterval(function(){
  	chat._refresh();
  }, 2000);
  setInterval(function(){
  	chat._full_refresh();
  }, 10000);
});
</script>
 </body>
</html>