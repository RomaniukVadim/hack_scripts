<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'blacklist_task';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks.php');
require($root.'/inc/classes/tasks_blacklist.php');

$list_table_user_num = $tasks_blacklist->list_table_user_num();

if($blacklist_notif) {
 $db->query("UPDATE  `$dbName`.`users` SET  `blacklist_notif` = '0' WHERE  `users`.`uid` = '$user_id' LIMIT 1 ;");
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Мои жалобы</title>
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
        <div id="description">
         <div class="text_que mrgnone">
          <div id="blacklist_user_add">
           Здесь Вы можете пожаловаться на ссылку, которая нарушает <a href="/page/rules" onclick="nav.go(this); return false">правила сайта</a> или из-за которой Ваша страница ВКонтакте была заблокирована.
           <br /> <br />
           Статус жалоб Вы можете наблюдать ниже. В случае рассмотрения, Вы будете оповещены.
           <br /> <br />
           <div id="tasks_blacklist_error_add" class="error_msg"></div>
           <? if($_GET['success'] == 1) { ?><div id="tasks_blacklist_info_add" class="msg">Ваша жалоба отправлена на рассмотрение.</div><? } ?> 
           <div class="label_field">Ссылка:</div>
           <input iplaceholder="Например: http://vk.com/wall1_1" type="text" id="url_blacklist_task">
           <br /> <br />
           <div class="label_field">Текст жалобы:</div>
           <textarea id="text_blacklist_task"></textarea>
           <div onclick="blacklist._add({'ssid': <? echo $usession; ?>})" id="blacklist_user_add_button" class="blue_button_wrap"><div class="blue_button">Отправить жалобу</div></div>
          </div>
         </div>
        </div>
        <div class="gray_hr"></div>
        <? if($list_table_user_num) { ?> 
        <div id="admin_tasks_blacklist_content">
         <div id="blacklist_bar">
          <div id="blacklist_bar_num">
           <? if(!$list_table_user_num) { ?>Ничего не найдено<? } else { ?>Вы отправили <? echo $list_table_user_num.' '.declOfNum($list_table_user_num, array('жалобу', 'жалобы', 'жалоб')); ?><? } ?> 
          </div>
          <div id="blacklist_bar_page">
           <? echo pages(array('ents_count' => $list_table_user_num, 'ents_print' => 10, 'page' => $_GET['page'])); ?> 
          </div>
         </div>
         <table cellspacing="0" cellpadding="0" id="admin_tasks_blacklist_table"> 
          <tr>
           <td class="column column_url_user"><div>Ссылка</div></td>
           <td class="column column_status_user"><div>Статус</div></td>
          </tr>
          <? echo $tasks_blacklist->list_table_user(); ?> 
         </table>
        </div>
        <? } else { ?> 
        <div id="my_tasks_blacklist_none">Вы еще не отправляли жалоб.</div>
        <? } ?> 
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