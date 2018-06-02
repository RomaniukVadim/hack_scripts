<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'my_complaints';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/complaints.php');

$list_table_user_num = $complaints->list_table_user_num();

if($unew_complaints) {
 $db->query("UPDATE  `$dbName`.`users` SET  `complaints` = '0' WHERE  `users`.`uid` = '$user_id' LIMIT 1 ;");
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Мои штрафы</title>
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
         <div id="my_complaints">
          Система штрафов позволяет с точностью до 100% отследить, когда Вы вышли из группы, отписались от человека, отлайкали запись и т.д., при этом создатель задания сам решает, когда просканировать и оштрафовать всех нарушителей. 
          <br /><br />
          За каждое нарушение с Вас списывается <b>5 баллов</b>.
         </div>
        </div>
        <div class="gray_hr"></div>
        <div id="admin_complaints_content">
         <? if($list_table_user_num) { ?> 
         <div id="blacklist_bar">
          <div id="blacklist_bar_num">
           <? if(!$list_table_user_num) { ?>Ничего не найдено<? } else { ?>Вы получили <? echo $list_table_user_num.' '.declOfNum($list_table_user_num, array('штраф', 'штрафа', 'штрафов')); ?><? } ?> 
          </div>
          <div id="blacklist_bar_page">
           <? echo pages(array('ents_count' => $list_table_user_num, 'ents_print' => 10, 'page' => $_GET['page'])); ?> 
          </div>
         </div>
         <table cellspacing="0" cellpadding="0" id="admin_tasks_blacklist_table"> 
          <tr>
           <td class="column column_url_user"><div>Ссылка</div></td>
           <td class="column column_status_user"><div>Тип</div></td>
          </tr>
          <? echo $complaints->list_table_user(); ?> 
         </table>
         <? } else { ?> 
          <div id="my_complaints_none">Вы еще не получали штрафов.</div>
         <? } ?> 
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