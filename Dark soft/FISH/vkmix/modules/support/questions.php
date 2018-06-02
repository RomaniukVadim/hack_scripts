<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'support';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/support.php');

if($ugroup != 4 && $ugroup != 5) {
 header('Location: /');
}

$support_new_my = my_support_new();
$all_questions_num = $support->all_questions_num();
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
          <? if($ugroup == 4 || $ugroup == 5) { ?><a class="active" href="/support/questions" onclick="nav.go(this); return false;"><div class="tabdiv">Все вопросы <? if($support_new) echo '(<b>'.$support_new.'</b>)'; ?></div></a><? } ?>
          <? if($ugroup == 4 || $ugroup == 5) { ?><a href="/support/rate" onclick="nav.go(this); return false;"><div class="tabdiv">Рейтинг агентов</div></a><? } ?>
         </span>
         <span class="tabs_right">
          <a class="other_a_tab" href="/support/new" onclick="nav.go(this); return false;">Новый вопрос</a>
         </span>
        </div>
        <div id="support_all_questions_bar">
         <div id="support_all_questions_bar_left"><? if($_GET['uid']) { ?>Вопросы пользователя<? } else { ?>Текущие вопросы<? } ?></div>
         <div id="support_all_questions_bar_right"><? echo pages(array('ents_count' => $all_questions_num, 'ents_print' => 20, 'page' => $_GET['page'])); ?></div>
        </div> 
        <div id="support_all_questions_content">
         <div id="support_all_questions_table">
          <div id="support_all_questions_table_header">
           <div id="support_all_questions_table_id">ID</div>
           <div id="support_all_questions_table_question">Вопрос</div>
           <div id="support_all_questions_table_status">Статус вопроса</div>
          </div>
          <div id="support_all_questions_table_content">
           <? echo $support->all_questions(); ?> 
          </div>
         </div>
        </div>
       </div>
      </div>
     </div>
     <input type="hidden" value="<? echo $usession; ?>" id="ssid">
<? include($root.'/include/footer.php') ?>
 
    </div>
   </div>
  </div>
<? include($root.'/include/scripts.php') ?> 
 </body>
</html>