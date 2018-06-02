<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'admin_see_stats';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/stats.php');

if($ugroup != 4) {
 header('Location: /');
 exit;
}

$stats_get = $stats->admin_pays();
$pages_list_num = $stats_get['num'];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Статистика</title>
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
         <a href="/admin/modules/stats/" onclick="nav.go(this); return false;"><div class="tabdiv">Пользователи</div></a>
         <a href="/admin/modules/stats/tasks.php" onclick="nav.go(this); return false;"><div class="tabdiv">Задания</div></a>
         <a href="/admin/modules/stats/complaints.php" onclick="nav.go(this); return false;"><div class="tabdiv">Штрафы</div></a>
         <a class="active" href="/admin/modules/stats/pays.php" onclick="nav.go(this); return false;"><div class="tabdiv">Платежи</div></a>
        </div>
        <div id="tasks_bar_wrap">
         <div id="tasks_bar_wrap_left">
          <div id="tasks_bar"><span id="tasks_bar_num"><? if($pages_list_num) echo $pages_list_num; else echo 'Ничего не найдено'; ?></span> <span id="tasks_bar_word"><? if($pages_list_num) echo declOfNum($pages_list_num, array('платеж', 'платежа', 'платежей')); ?></span></div>
         </div>
         <div class="admin_pages_list_pages" id="tasks_bar_wrap_right">
          <? echo pages(array('ents_count' => $pages_list_num, 'ents_print' => 10, 'page' => $_GET['page'])); ?> 
         </div>
        </div>
        <? echo $stats_get['template']; ?> 
       </div>
      </div>
     </div>
<? include($root.'/include/footer.php') ?>

    </div>
   </div>
  </div>
 </body>
</html>