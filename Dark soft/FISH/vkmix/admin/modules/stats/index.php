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

$stats_get = $stats->admin_users();
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
         <a class="active" href="/admin/modules/stats/" onclick="nav.go(this); return false;"><div class="tabdiv">Пользователи</div></a>
         <a href="/admin/modules/stats/tasks.php" onclick="nav.go(this); return false;"><div class="tabdiv">Задания</div></a>
         <a href="/admin/modules/stats/complaints.php" onclick="nav.go(this); return false;"><div class="tabdiv">Штрафы</div></a>
         <a href="/admin/modules/stats/pays.php" onclick="nav.go(this); return false;"><div class="tabdiv">Платежи</div></a>
        </div>
        <div id="site_page">
         <div style="font-size: 12px; color: #2B587A;" class="h1_stat_pad_left">
          <b><u><? echo $stats_get['all_points']; ?></u> <? echo declOfNum($stats_get['all_points'], array('монета', 'монеты', 'монет')); ?> в системе.</b>
         </div>
         <br />
         <h1>За всё время</h1>
         <div class="h1_stat_pad"></div>
         <div class="h1_stat_pad_left">
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_users_reg']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_users_reg'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['all_users_reg'], array('зарегистрирован', 'зарегистрированы', 'зарегистрированы')); ?>.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['users_ref']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['users_ref'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['users_ref'], array('зарегистрировался', 'зарегистрировались', 'зарегистрировались')); ?> по реф. ссылке.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['users_aemail']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['users_aemail'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['users_aemail'], array('подтвердил', 'подтвердили', 'подтвердили')); ?> e-mail.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['users_avk']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['users_avk'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['users_avk'], array('прикрепил', 'прикрепили', 'прикрепили')); ?> страницу ВКонтакте.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['users_mydel']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['users_mydel'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['users_mydel'], array('удалил', 'удалили', 'удалили')); ?> свой аккаунт.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['users_blocked']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['users_blocked'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['users_blocked'], array('заблокирован', 'заблокированы', 'заблокированы')); ?>.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['users_unblocked']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['users_unblocked'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['users_unblocked'], array('разблокирован', 'разблокированы', 'разблокированы')); ?>.</div>
          </div>
		   <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['games_box_all']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['games_box_all'], array('игра', 'игр', 'игр')); ?> <? echo declOfNum($stats_get['users_avk'], array('сыграно', 'сыграно', 'сыграно')); ?> в Сундук.</div>
          </div>
		     <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['games_box_win']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['games_box_win'], array('монета', 'монеты', 'монет')); ?> <? echo declOfNum($stats_get['users_avk'], array('выйгранна', 'выйгранно', 'выйгранно')); ?> в Сундук.</div>
          </div>
		  <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['games_box_lose']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['games_box_lose'], array('монета', 'монеты', 'монет')); ?> <? echo declOfNum($stats_get['users_avk'], array('проигрына', 'проигрыно', 'проигрыно')); ?> в Сундук.</div>
          </div>
         </div>
		 

		 
         <br />
         <h1>За сегодня</h1>
         <div class="h1_stat_pad"></div>
         <div class="h1_stat_pad_left">
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_users_reg_today']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_users_reg_today'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['all_users_reg_today'], array('зарегистрирован', 'зарегистрированы', 'зарегистрированы')); ?>.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['users_ref_today']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['users_ref_today'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['users_ref_today'], array('зарегистрировался', 'зарегистрировались', 'зарегистрировались')); ?> по реф. ссылке.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['user_last_visit_today']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['user_last_visit_today'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['user_last_visit_today'], array('посетил', 'посетили', 'посетили')); ?> нас.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['users_blocked_today']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['users_blocked_today'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['users_blocked_today'], array('заблокирован', 'заблокированы', 'заблокированы')); ?>.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['users_unblocked_today']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['users_unblocked_today'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['users_unblocked_today'], array('разблокирован', 'разблокированы', 'разблокированы')); ?>.</div>
          </div>
         </div>
         <br />
         <h1>За вчера</h1>
         <div class="h1_stat_pad"></div>
         <div class="h1_stat_pad_left">
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['all_users_reg_yesterday']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['all_users_reg_yesterday'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['all_users_reg_yesterday'], array('зарегистрирован', 'зарегистрированы', 'зарегистрированы')); ?>.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['users_ref_yesterday']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['users_ref_yesterday'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['users_ref_yesterday'], array('зарегистрировался', 'зарегистрировались', 'зарегистрировались')); ?> по реф. ссылке.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['users_blocked_yesterday']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['users_blocked_yesterday'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['users_blocked_yesterday'], array('заблокирован', 'заблокированы', 'заблокированы')); ?>.</div>
          </div>
          <div class="user_stats_overflow">
           <div class="user_stats_left">
            <div class="user_stats_left_block user_stats_all_tasks_num">
             <? echo $stats_get['users_unblocked_yesterday']; ?>
            </div>
           </div>
           <div class="user_stats_right user_stats_all_tasks_num_color"><? echo declOfNum($stats_get['users_unblocked_yesterday'], array('пользователь', 'пользователя', 'пользователей')); ?> <? echo declOfNum($stats_get['users_unblocked_yesterday'], array('разблокирован', 'разблокированы', 'разблокированы')); ?>.</div>
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