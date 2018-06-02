<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'my_tasks';

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
require($root.'/inc/classes/tasks_categories.php');
require($root.'/inc/classes/tasks_blacklist.php');

$personal_cat_id = $_GET['list'];
$search = trim($_GET['search']);
$cat_id = $tasks->getCatNum(fxss($_GET['section'])); // id категории
$tasks_num = $tasks->my_tasks_num($user_id, $cat_id, $personal_cat_id, $search); // количество заданий

if($personal_cat_id) {
 // открываем категорию
 $dCat_id = $redis->hget('tasks_categories_id'.$personal_cat_id, 'id');
 $dCat_uid = $redis->hget('tasks_categories_id'.$personal_cat_id, 'user_id');
 if(!$dCat_id || $dCat_uid != $user_id) {
  header('Location: /tasks/my');
  exit;
 }

 /*
 $qCat = $db->query("SELECT `tcid`, `tcuid` FROM `tasks_categories` WHERE `tcid` = '$personal_cat_id' AND `tcuid` = '$user_id' AND `tcdel` = '0'");
 $dCat = $db->fetch($qCat);
 $dCat_id = $dCat['tcid'];
 $dCat_uid = $dCat['tcuid'];
 if(!$dCat_id || $dCat_uid != $user_id) {
  header('Location: /tasks/my');
  exit;
 }
 */
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Мои задания</title>
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
         <a<? if($cat_id < 1) echo ' class="active"'; ?> href="<? echo querys('/tasks/my', 'section'); ?>all" onclick="nav.go(this); return false;"><div class="tabdiv">Все</div></a>
         <a<? if($cat_id == 1) echo ' class="active"'; ?> href="<? echo querys('/tasks/my', 'section'); ?>likes" onclick="nav.go(this); return false;"><div class="tabdiv">Мне нравится</div></a>
         <a<? if($cat_id == 2) echo ' class="active"'; ?> href="<? echo querys('/tasks/my', 'section'); ?>reposts" onclick="nav.go(this); return false;"><div class="tabdiv">Рассказать друзьям</div></a>
         <a<? if($cat_id == 3) echo ' class="active"'; ?> href="<? echo querys('/tasks/my', 'section'); ?>comments" onclick="nav.go(this); return false;"><div class="tabdiv">Комментарии</div></a>
         <a<? if($cat_id == 4) echo ' class="active"'; ?> href="<? echo querys('/tasks/my', 'section'); ?>friends" onclick="nav.go(this); return false;"><div class="tabdiv">Друзья</div></a>
         <a<? if($cat_id == 5) echo ' class="active"'; ?> href="<? echo querys('/tasks/my', 'section'); ?>groups" onclick="nav.go(this); return false;"><div class="tabdiv">Сообщества</div></a>
         <a<? if($cat_id == 6) echo ' class="active"'; ?> href="<? echo querys('/tasks/my', 'section'); ?>polls" onclick="nav.go(this); return false;"><div class="tabdiv">Опросы</div></a>
        </div>
        <div id="search_bg">
         <div class="search">
          <div id="field">
           <div id="search_icon" class="search_icon"></div>
           <input id="input_tasks_search" value="<? echo fxss($search); ?>" iplaceholder="Например: http://vk.com/wall1_1" type="text">
          </div>
          <div id="search">
           <div onclick="tasks._search_go('<? echo querys('/tasks/my', 'search'); ?>'+$('#input_tasks_search').val())" class="blue_button_wrap"><div class="blue_button">Поиск</div></div>
          </div>
         </div>
         <div id="tasks_my_categories_hide">["0", <? echo json_encode('- Не выбрано -'); ?>]<? echo $tasks_categories->my_select(array('uid' => $user_id)); ?></div>

        </div>
        <div id="search_bg_hr"></div>
        <div id="tasks_bar_wrap">
         <div id="tasks_bar"><? if($tasks_num) { ?><? echo '<span id="tasks_bar_num">'.$tasks_num.'</span> <span id="tasks_bar_word">'.declOfNum($tasks_num, array('задание', 'задания', 'заданий')).'</span>'; ?><? } else { ?>Ничего не найдено<? } ?><? if($dCat_uid) { ?><span class="delete_categorie_bar"><span class="line_c"> | </span><a href="javascript://" onclick="tasks._tcatecories_del(<? echo $dCat_id; ?>)">Удалить категорию</a></span><? } ?></div>
        </div>
        <div id="tasks_list">
         <?
         if(!$tasks_num) {
          if($search) {
           echo '<div id="tasks_none">Ваш запрос не дал результатов.</div>';
          } else {
           echo '<div id="tasks_none">Не найдено ни одного задания.</div>';
          }
         } else {
          echo $tasks->my_tasks(array(
           'section' => $cat_id,
           'uid' => $user_id,
           'cat' => $personal_cat_id,
           'search' => $search,
           'usession' => $usession
          ));
         }
         ?> 
         <? if($db->error()) { ?><div id="tasks_none">Ошибка соединения с сервером. Попробуйте позже.</div><? } ?> 
         <div id="next_page_small_c"></div>
         <div style="<? if($tasks_num > 10) { ?>display: block;<? } else { ?>display: none;<? } ?>" onclick="tasks._next('<? echo $_GET['section']; ?>', <? echo $personal_cat_id ? $personal_cat_id : '\'\''; ?>, 1)" id="next_page_small_t">Показать еще задания</div>
         <div id="next_page_small_d">1</div>
        </div>
       </div>
      </div>
     </div>
     <input type="hidden" id="captcha_key">
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