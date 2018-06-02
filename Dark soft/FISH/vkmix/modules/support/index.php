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
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/support.php');

$support_my_num = $support->my_num();
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
          <a class="active" href="/support" onclick="nav.go(this); return false;"><div class="tabdiv">Мои вопросы</div></a>
          <? if($ugroup == 4 || $ugroup == 5) { ?><a href="/support/questions" onclick="nav.go(this); return false;"><div class="tabdiv">Все вопросы <? if($support_new) echo '(<b>'.$support_new.'</b>)'; ?></div></a><? } ?>
          <? if($ugroup == 4 || $ugroup == 5) { ?><a href="/support/rate" onclick="nav.go(this); return false;"><div class="tabdiv">Рейтинг агентов</div></a><? } ?>
         </span>
         <span class="tabs_right">
          <a class="other_a_tab" href="/support/new" onclick="nav.go(this); return false;">Новый вопрос</a>
         </span>
        </div>
        <? if($_GET['que_del'] == 1) { ?><div id="support_msg" class="msg">Вопрос успешно удален.</div><? } ?> 
        <div id="support_my_count_que"><? if(!$support_my_num) { ?>Ничего не найдено<? } else { ?>Вы задали <? echo $support_my_num; ?> <? echo declOfNum($support_my_num, array('вопрос', 'вопроса', 'вопросов')); ?><? } ?></div>
        <div id="support_my_content">
         <? if(!$support_my_num) { ?><div id="support_none_questions">Вы ещё не обращались в поддержку.</div><? } else { ?><? echo $support->my(); ?><? } ?> 
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