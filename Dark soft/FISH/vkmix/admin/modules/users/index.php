<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'admin.users';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/vk.api.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/tasks.php');
require($root.'/inc/classes/tasks_blacklist.php');

if($ugroup != 4) {
 header('Location: /');
 exit;
}

$users_list_num = $user->admin_users_list_num();
$sort = $_GET['sort'];
$section = $_GET['section'];
$search = $_GET['search'];

if($section == 'online' || $section == 'admin' || $section == 'moder' || $section == 'agent') {
 $section_result = $section;
} else {
 $section_result = '';
}

if($sort == 'asc') {
 $sort_text = 'по дате регистрации — <b>A</b>';
} elseif($sort == 'desc') {
 $sort_text = 'по дате регистрации — <b>D</b>';
} elseif($sort == 'max_points') {
 $sort_text = 'по баллам — <b>D</b>';
} elseif($sort == 'min_points') {
 $sort_text = 'по баллам — <b>A</b>';
} else {
 $sort_text = 'по дате регистрации — <b>A</b>';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Пользователи</title>
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
         <a<? if(!$section_result) echo ' class="active"'; ?> href="/admin/modules/users/" onclick="nav.go(this); return false;"><div class="tabdiv">Все пользователи</div></a>
         <a<? if($section_result == 'online') echo ' class="active"'; ?> href="/admin/modules/users/?section=online" onclick="nav.go(this); return false;"><div class="tabdiv">Сейчас на сайте</div></a>
         <a<? if($section_result == 'admin') echo ' class="active"'; ?> href="/admin/modules/users/?section=admin" onclick="nav.go(this); return false;"><div class="tabdiv">Администраторы</div></a>
         <a<? if($section_result == 'moder') echo ' class="active"'; ?> href="/admin/modules/users/?section=moder" onclick="nav.go(this); return false;"><div class="tabdiv">Модераторы</div></a>
         <a<? if($section_result == 'agent') echo ' class="active"'; ?> href="/admin/modules/users/?section=agent" onclick="nav.go(this); return false;"><div class="tabdiv">Агенты поддержки</div></a>
        </div>
        <div id="search_bg">
         <div class="search">
          <div id="field">
           <div id="search_icon" class="search_icon"></div>
           <input id="input_tasks_search" value="<? echo fxss($search); ?>" iplaceholder="Например: http://vk.com/id1, http://montytool.ru/id1 или логин пользователя" type="text">
          </div>
          <div id="search">
           <div onclick="admin_users._search('<? echo querys('/admin/modules/users/', 'search'); ?>'+$('#input_tasks_search').val())" class="blue_button_wrap"><div class="blue_button">Поиск</div></div>
          </div>
         </div>
        </div>
        <div id="search_bg_hr"></div>
        <div id="tasks_bar_wrap">
         <div id="tasks_bar_wrap_left">
          <div id="tasks_bar"><? if(!$users_list_num) { ?>Ничего не найдено<? } else { ?><? echo declOfNum($users_list_num, array('Найден', 'Найдено', 'Найдено')); ?> <? echo $users_list_num; ?> <? echo declOfNum($users_list_num, array('пользователь', 'пользователя', 'пользователей')); ?><? } ?></div>
         </div>
         <div id="tasks_bar_wrap_right">
          <? if($users_list_num) { ?><div id="admin_list_users_page"><? echo pages(array('ents_count' => $users_list_num, 'ents_print' => 10, 'page' => $_GET['page'])); ?></div><? } ?> 
          <? if($users_list_num) { ?><span id="sort_tasks_sel">сортировать</span> <span id="admin_users_navigation"></span><? } ?>
         </div>
        </div>
        <? if(!$users_list_num) { ?>
        <div id="tasks_none">Не найдено ни одного пользователя.</div>
        <? } else { ?>
        <div id="admin_users">
         <? echo $user->admin_users_list(); ?>
        </div>
        <? } ?>
       </div>
      </div>
     </div>
     <input type="hidden" id="captcha_key">
     <input type="hidden" id="captcha_code">
<? include($root.'/include/footer.php') ?>
 
    </div>
   </div>
  </div>
<? include($root.'/include/scripts.php') ?> 
 </body>
</html>