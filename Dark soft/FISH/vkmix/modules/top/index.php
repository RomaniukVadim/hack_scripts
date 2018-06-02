<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/top.php');

$act = $db->escape($_GET['act']);

if($act == 'refs') {
 $act = $act;
} else {
 $act = '';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Топ активных</title>
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
       <div id="top_page" class="main nopad">
        <div id="page_top_pad">
         <div id="top_text">
          <b>ТОП-100</b> пользователей, <? if($act == 'refs') echo 'которые <a href="/settings/ref?menu=1" onclick="nav.go(this); return false">пригласили</a> больше всего друзей на сайт'; else echo 'которые выполнили больше всего заданий'; ?>.
         </div>
         <div id="top_text_hr"></div>
         <div id="categories_top" class="blue_tab">
          <a<? if(!$act) echo ' class="active"' ?> href="/top" onclick="nav.go(this); return false"><div class="blue_tab_a">по количеству <b>выполненных заданий</b></div></a>
          <a<? if($act == 'refs') echo ' class="active"' ?> href="/top?act=refs" onclick="nav.go(this); return false"><div class="blue_tab_a">по количеству <b>приглашенных друзей</b></div></a>
         </div>
         <? echo $top->users_list(); ?> 
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