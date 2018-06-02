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

$lastid = $_POST['lastid'];
if(!is_numeric($lastid)){
	$id = false;
}

if(!$error){
	if($Messages = $chat->Get($lastid)){
		$result = '';
		$LastId = 0;
        foreach($Messages as $message){
            $time = showDate($message['time']);
            $name = $message['name'];
            $vkid = $message['vk_id'];
            $ava = $message['avatar'];
            $text = $message['message'];
            $LastId = $message['id'];
			if ($ugroup==4) $del_text="<div class=\"chat_comment_question_info_del\" title=\"Удалить\" onclick=\"chat._del_comment(this)\" id=\"$LastId\" >X</div>"; 
            $result .= "
         <div class=\"chat_comment_question\">
         <div class=\"chat_comment_question_avatar\">
          <img src=\"$ava\">
         </div>
         <div class=\"chat_comment_question_info\">
          <div class=\"chat_comment_question_info_name\"><a href=\"http://vk.com/id$vkid\">$name</a></div>
          <div class=\"chat_comment_question_info_date\" id=\"$LastId\">$time</div>
		  ".$del_text."
          <div class=\"chat_comment_question_info_text\">
            ".emoji_unified_to_html(htmlspecialchars($text))."
          </div>
         </div>
         </div>";
        }
		echo json_encode(array(
		'result' => $result,
		'lastid' => $LastId
		));
	} else {
		echo '0';
	}
} else {
	echo json_encode(array(
		'error' => $error
		));
}