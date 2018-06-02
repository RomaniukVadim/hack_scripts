<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks_blacklist.php');

if(!$udel) {
 header('Location: /tasks');
 exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Аккаунт удален.</title>
<? include($root.'/include/head.php') ?>

 </head>
 <body>
 <div id="page">
  <div id="black_bg"></div>
  <div id="loading"><div id="load"></div></div>
  <div id="loading_page"></div>
  <div id="header">
   <div id="inner">
    <div id="head_loader"><div class="upload"></div></div>
    <div id="logo">
     <a href="/tasks" onclick="nav.go(this); return false">
      <div class="logo"></div>
     </a>
    </div>
    <div class="menu">
     <a href="/logout?hash=<? echo $user_uhash; ?>" onclick="nav.go(this); return false"><div>выйти</div></a>
    </div>
   </div>
  </div>
  <div id="header_bottom"></div>

   <div id="content">
<? include($root.'/include/left.php') ?>

    <div id="right_wrap">
     <div id="right_wrap_b">
      <div id="right">
       <div class="main">
        <div id="account_is_deleted">Аккаунт пользователя удален. <br /> Информация недоступна.</div>
       </div>
      </div>
     </div>
     <input type="hidden" value="<? echo $usession; ?>" id="ssid">
<? include($root.'/include/footer.php') ?>

    </div>
   </div>
  </div>
 </body>
</html>