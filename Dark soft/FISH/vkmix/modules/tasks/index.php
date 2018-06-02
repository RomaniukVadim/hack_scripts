<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'tasks';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/sessions.php');
require($root.'/inc/classes/vk.api.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks.php');
require($root.'/inc/classes/tasks_blacklist.php');

$cat_id = $tasks->getCatNum(fxss($_GET['section'])); // id категории
$search = trim($_GET['search']);
$sort = $_GET['sort'];
$tasks_num = $tasks->all_tasks_num($user_id, $cat_id, $search); // количество заданий

if($sort == 'amount') {
 $sort_text = 'по цене';
} elseif($sort == 'popular') {
 $sort_text = 'по популярности';
} elseif($sort == 'date') {
 $sort_text = 'по дате добавления';
} else {
 $sort_text = 'по цене';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Список заданий</title>
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
         <a<? if($cat_id < 1) echo ' class="active"'; ?> href="/tasks" onclick="nav.go(this); return false;"><div class="tabdiv">Все</div></a>
         <a<? if($cat_id == 1) echo ' class="active"'; ?> href="/tasks?section=likes" onclick="nav.go(this); return false;"><div class="tabdiv">Мне нравится</div></a>
         <a<? if($cat_id == 2) echo ' class="active"'; ?> href="/tasks?section=reposts" onclick="nav.go(this); return false;"><div class="tabdiv">Рассказать друзьям</div></a>
         <a<? if($cat_id == 3) echo ' class="active"'; ?> href="/tasks?section=comments" onclick="nav.go(this); return false;"><div class="tabdiv">Комментарии</div></a>
         <a<? if($cat_id == 4) echo ' class="active"'; ?> href="/tasks?section=friends" onclick="nav.go(this); return false;"><div class="tabdiv">Друзья</div></a>
         <a<? if($cat_id == 5) echo ' class="active"'; ?> href="/tasks?section=groups" onclick="nav.go(this); return false;"><div class="tabdiv">Сообщества</div></a>
         <a<? if($cat_id == 6) echo ' class="active"'; ?> href="/tasks?section=polls" onclick="nav.go(this); return false;"><div class="tabdiv">Опросы</div></a>
        </div>


        </div>
        <div id="search_bg_hr"></div>

        <div id="tasks_list">
         <? 
         if(!$tasks_num) {
          if($search) {
           echo '<div id="tasks_none">Ваш запрос не дал результатов.</div>';
          } else {
           echo '<div id="tasks_none">Не найдено ни одного задания.</div>';
          }
         } else {
          echo $tasks->all_tasks(array(
           'section' => $cat_id,
           'search' => $search,
           'sort' => $sort,
           'uid' => $user_id
          ));
         }
         ?> 
         <? if($db->error()) { ?><div id="tasks_none">Ошибка соединения с сервером. Попробуйте позже.</div><? } ?> 
         <div id="next_page_small_c"></div>
         <div style="<? if($tasks_num > 10) { ?>display: block;<? } else { ?>display: none;<? } ?>" onclick="tasks._next('<? echo $_GET['section']; ?>', '', 0, '<? echo $sort; ?>')" id="next_page_small_t">Показать еще задания</div>
         <div id="next_page_small_d">1</div>
        </div>
       </div>
      </div>
     </div>
     <input type="hidden" id="task_wnd_open_val">
     <input type="hidden" value="<? echo $usession; ?>" id="ssid">
<? include($root.'/include/footer.php') ?>
 
    </div>
   </div>
  </div>
 </div>
</div>
<? include($root.'/include/scripts.php') ?> 
 </body>
</html>