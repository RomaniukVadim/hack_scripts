<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'site_page_all';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/tasks.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/pages.php');

if($ugroup != 4) {
 header('Location: /');
 exit;
}

$pages_list_num = $pages->pages_list_num();

if($_GET['act'] == 'del') {
 $show_list = 'delete';
} else {
 $show_list = '';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Страницы</title>
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
          <a<? if(!$show_list) echo ' class="active"'; ?> href="/admin/modules/pages/" onclick="nav.go(this); return false;"><div class="tabdiv">Все страницы</div></a>
          <a<? if($show_list == 'delete') echo ' class="active"'; ?> href="/admin/modules/pages/?act=del" onclick="nav.go(this); return false;"><div class="tabdiv">Удаленные</div></a>
         </span>
         <span class="tabs_right">
          <a class="other_a_tab" href="/admin/modules/pages/add.php" onclick="nav.go(this); return false;">Новая страница</a>
         </span>
        </div>
        <div id="tasks_bar_wrap">
         <div id="tasks_bar_wrap_left">
          <div id="tasks_bar"><span id="tasks_bar_num"><? if($pages_list_num) echo $pages_list_num; else echo 'Ничего не найдено'; ?></span> <span id="tasks_bar_word"><? if($pages_list_num) echo declOfNum($pages_list_num, array('страница', 'страницы', 'страниц')); ?></span></div>
         </div>
         <div class="admin_pages_list_pages" id="tasks_bar_wrap_right">
          <? echo pages(array('ents_count' => $pages_list_num, 'ents_print' => 10, 'page' => $_GET['page'])); ?> 
         </div>
        </div>
        <div id="admin_pages_list">
         <? if($pages_list_num) echo $pages->pages_list(); else echo '<div id="tasks_none">Не найдено ни одной страницы.</div>'; ?> 
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