<?php
$root = $_SERVER['DOCUMENT_ROOT'];
//$page_name = 'my_settings';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/refs.php');

if($ugroup != 4) {
 if($_GET['id'] != $user_id) {
  header('Location: /');
  exit;
 }
}
$id_user=$_GET['id'];
$my_refs_num = $refs->notmy_num($id_user);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Рефералы пользователя</title>
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
        <div id="settings_ref_content">
         <div id="settings_ref_content_main_text">
          
          <div id="settings_ref_content_url">
           <a target="_blank" href="http://montytool.ru/ref<? echo $id_user; ?>">http://montytool.ru/ref<? echo $id_user; ?></a>
          </div>
         </div>
         <div id="settings_ref_c">
          <div id="settings_ref_count">
           <? if($my_refs_num) { ?>Он уже пригласили <? echo $my_refs_num; ?> <? echo declOfNum($my_refs_num, array('реферала', 'рефералов', 'рефералов')); ?>.<? } ?> 
          </div>
          <div id="settings_ref_body">
           <? if($my_refs_num) { ?><? echo $refs->notmy($id_user); ?><div id="next_page_small_c"></div><? } else { ?><div id="refs_none">Он ещё не приглашал друзей на сайт по реферальной ссылке.</div><? } ?> 
           
            <div id="next_page_small_d">1</div>
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
<? include($root.'/include/scripts.php') ?> 
 </body>
</html>