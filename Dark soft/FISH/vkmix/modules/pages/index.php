<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'site_page';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/pages.php');

if($uban_type) {
 header('Location: /blocked');
 exit;
}

$page_get = $pages->pinfo($_GET['page']);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title><? echo $page_get['title']; ?></title>
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
       <div id="site_page" class="main">
        <? echo stripslashes(str_replace("\n","\n\t", $page_get['template'])); ?> 
       </div>
      </div>
     </div>
<? include($root.'/include/footer.php') ?>
 
    </div>
   </div>
  </div>
  <input type="hidden" value="<? echo $usession; ?>" id="ssid">
<? include($root.'/include/scripts.php') ?> 
 </body>
</html>