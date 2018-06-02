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

$stats_get = $stats->admin_tasks();
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
         <a class="active" href="/admin/modules/stats/tasks.php" onclick="nav.go(this); return false;"><div class="tabdiv">Задания</div></a>
         <a href="/admin/modules/stats/complaints.php" onclick="nav.go(this); return false;"><div class="tabdiv">Штрафы</div></a>
         <a href="/admin/modules/stats/pays.php" onclick="nav.go(this); return false;"><div class="tabdiv">Платежи</div></a>
        </div>
        <div id="site_page">
         <h1>За всё время</h1>
         <div class="h1_stat_pad"></div>
         <div class="h1_stat_pad_left">
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_add']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_add'], array('задание', 'задания', 'заданий')); ?> создано.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_active']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_active'], array('задание', 'задания', 'заданий')); ?> активно.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_done']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_done'], array('задание', 'задания', 'заданий')); ?> выполнено.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_done_all']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_done_all'], array('выполнение', 'выполнения', 'выполнений')); ?> пользователями.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_blocked']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_blocked'], array('задание', 'задания', 'заданий')); ?> заблокировано.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_hide']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_hide'], array('задание', 'задания', 'заданий')); ?> скрыто.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_del']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_del'], array('задание', 'задания', 'заданий')); ?> удалено.</div>
          </div>
         </div>
         <br />
         <h1>За сегодня</h1>
         <div class="h1_stat_pad"></div>
         <div class="h1_stat_pad_left">
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_add_today']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_add_today'], array('задание', 'задания', 'заданий')); ?> создано.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_done_all_today']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_done_all_today'], array('выполнение', 'выполнения', 'выполнений')); ?> пользователями.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_hide_today']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_hide_today'], array('задание', 'задания', 'заданий')); ?> скрыто.</div>
          </div>
         </div>
         <br />
         <h1>За вчера</h1>
         <div class="h1_stat_pad"></div>
         <div class="h1_stat_pad_left">
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_add_yesterday']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_add_yesterday'], array('задание', 'задания', 'заданий')); ?> создано.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_done_all_yesterday']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_done_all_yesterday'], array('выполнение', 'выполнения', 'выполнений')); ?> пользователями.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_tasks_hide_yesterday']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_tasks_hide_yesterday'], array('задание', 'задания', 'заданий')); ?> скрыто.</div>
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
 </body>
</html>