<?php
$root = $_SERVER['DOCUMENT_ROOT'];


require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/stats.php');

$show = $_GET['show'];



$stats_get = $stats->info();

if($sort == 'today') {
 $tab_sort = $show;
} elseif($sort = 'yesterday') {
 $tab_sort = $show;
} elseif($sort = 'week') {
 $tab_sort = $show;
} elseif($sort = 'month') {
 $tab_sort = $show;
} else {
 $tab_sort = '';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Статистика аккаунта</title>
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

        <div id="site_page">
         <div class="user_stats_desc">
          <? if($user_id != $_GET['id']) { ?> 
          Показывается статистика аккаунта по заданиям <b><? if($tab_sort == 'today') echo 'за сегодня'; elseif($tab_sort == 'yesterday') echo 'за вчера'; elseif($tab_sort == 'week') echo 'за неделю'; elseif($tab_sort == 'month') echo 'за месяц'; else echo 'за всё время'; ?></b>.
          <? } else { ?> 
          Показывается статистика <b>Вашего</b> аккаунта по заданиям <b><? if($tab_sort == 'today') echo 'за сегодня'; elseif($tab_sort == 'yesterday') echo 'за вчера'; elseif($tab_sort == 'week') echo 'за неделю'; elseif($tab_sort == 'month') echo 'за месяц'; else echo 'за всё время'; ?></b>.
          <? } ?> 
         </div>
         <div class="user_stats_overflow">
          <div class="user_stats_left">
           <div class="user_stats_left_block user_stats_all_tasks_num">
            <? echo $stats_get['all_tasks_add_count']; ?>
           </div>
          </div>
          <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_add_count'], array('задание', 'задания', 'заданий')); ?> создано.</div>
         </div>
         <div class="user_stats_overflow">
          <div class="user_stats_left">
           <div class="user_stats_left_block user_stats_all_tasks_num">
            <? echo $stats_get['all_tasks_done_count']; ?>
           </div>
          </div>
          <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_done_count'], array('задание', 'задания', 'заданий')); ?> выполнено.</div>
         </div>
         
         <div class="user_stats_overflow">
          <div class="user_stats_left">
           <div class="user_stats_left_block user_stats_all_tasks_num">
            <? echo $stats_get['all_tasks_hide_count']; ?>
           </div>
          </div>
          <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_hide_count'], array('задание', 'задания', 'заданий')); ?> скрыто.</div>
         </div>
         <div class="user_stats_overflow">
          <div class="user_stats_left">
           <div class="user_stats_left_block user_stats_all_tasks_num">
            <? echo $stats_get['all_tasks_del_count']; ?>
           </div>
          </div>
          <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_del_count'], array('задание', 'задания', 'заданий')); ?> удалено.</div>
         </div>
        </div>
       </div>
      </div>
     </div>
<? include($root.'/include/footer.php') ?>

    </div>
   </div>
  </div>
 </body>
</html>