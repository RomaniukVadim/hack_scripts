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
require($root.'/inc/classes/logs.php');
$id=$_GET['id'];
$guid=$_GET['guid'];

 If ($id !='') {
	 if ($user_id != $guid) { 
header('Location: /');
  exit;
  } 
if ($upoints > 50) {
	
		  $rand=rand(1,6); 
		  $text.= '<br />Победным оказался сундук №'.$rand.'  <br />  <br />';
		  if ($id==$rand) {$text.= '<p class="p_green" >Вы победили и получили приз - 150 баллов!</p><br /><br /> ';
		   $db->query("UPDATE `$dbName`.`users` SET  `upoints` =  upoints + 150 WHERE  `users`.`uid` = '$user_id' LIMIT 1 ;");
		   $logs->game_win($user_id, $user_id,'150');
		  } else {
			  $db->query("UPDATE `$dbName`.`users` SET  `upoints` =  upoints - 50 WHERE  `users`.`uid` = '$user_id' LIMIT 1 ;");
			 $text.=  '<p class="p_red" >К сожелению вы не угадали.</p><br /><br />';
			 $logs->game_lose($user_id, $user_id,'50');
		  }
		  
 } else {
	 $id='';
	  $text.=  '<p class="p_red" >К сожелению у вас не достаточно баллов для игры</p><br /><br />';
 } }

include($root.'/inc/system/profile.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Игра Сундук</title>
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
           <a target="_blank" href="http://montytool.ru/">Выйграй баллы играя в Сундук!</a>
          </div>
         </div>
		 <center>
         <div id="settings_ref_c">
          <div id="settings_ref_count">
		  <?php
		 echo $text;
		  ?>
		  
		  Если вы угадаете то получите 150 баллов! Но в случае проигрыша с вас снимается 50 баллов. <br /><br />
		
                  </div>
		 
          <div id="settings_ref_body">
          <a href="/game?id=1&guid=<?php echo $user_id; ?>" onclick="nav.go(this); return false"> <?php if ($id == 1) {?>  <img src="http://montytool.ru/images/box_o.jpg"  class="game_box_pic"> <?php } else {?> <img src="http://montytool.ru/images/box.jpg" class="game_box_pic"><?php } ?> </a> 
		  <a href="/game?id=2&guid=<?php echo $user_id; ?>" onclick="nav.go(this); return false"> <?php if ($id == 2) {?>  <img src="http://montytool.ru/images/box_o.jpg"  class="game_box_pic"><?php } else {?> <img src="http://montytool.ru/images/box.jpg" class="game_box_pic"><?php } ?>  </a> 
		  <a href="/game?id=3&guid=<?php echo $user_id; ?>" onclick="nav.go(this); return false"> <?php if ($id == 3) {?>  <img src="http://montytool.ru/images/box_o.jpg"  class="game_box_pic"><?php } else {?> <img src="http://montytool.ru/images/box.jpg" class="game_box_pic"><?php } ?>  </a> 
		  <a href="/game?id=4&guid=<?php echo $user_id; ?>" onclick="nav.go(this); return false"> <?php if ($id == 4) {?>  <img src="http://montytool.ru/images/box_o.jpg"  class="game_box_pic"><?php } else {?> <img src="http://montytool.ru/images/box.jpg" class="game_box_pic"><?php } ?>  </a> 
		 <a href="/game?id=5&guid=<?php echo $user_id; ?>" onclick="nav.go(this); return false">  <?php if ($id == 5) {?>  <img src="http://montytool.ru/images/box_o.jpg"  class="game_box_pic"><?php } else {?> <img src="http://montytool.ru/images/box.jpg" class="game_box_pic"><?php } ?>  </a> 
		  <a href="/game?id=6&guid=<?php echo $user_id; ?>" onclick="nav.go(this); return false"> <?php if ($id == 6) {?>  <img src="http://montytool.ru/images/box_o.jpg"  class="game_box_pic"><?php } else {?> <img src="http://montytool.ru/images/box.jpg" class="game_box_pic"><?php } ?>  </a> 
		 
		
          </div>
         </div>
		  </center>
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