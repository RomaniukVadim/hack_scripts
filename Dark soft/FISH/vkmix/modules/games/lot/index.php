<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'lot';

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
 If ($id !='') {
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
  <script type="text/javascript">
    function lot_timer(sec, block, direction) {
    var time    = sec;
    direction   = direction || false;
             
    var hour    = parseInt(time / 3600);
    if ( hour < 1 ) hour = 0;
    time = parseInt(time - hour * 3600);
    if ( hour < 10 ) hour = '0'+hour;
 
    var minutes = parseInt(time / 60);
    if ( minutes < 1 ) minutes = 0;
    time = parseInt(time - minutes * 60);
    if ( minutes < 10 ) minutes = '0'+minutes;
 
    var seconds = time;
    if ( seconds < 10 ) seconds = '0'+seconds;
 
    block.innerHTML = 'До конца лотереи осталось: '+hour+':'+minutes+':'+seconds;
 
    if ( direction ) {
        sec++;
 
        setTimeout(function(){ lot_timer(sec, block, direction); }, 1000);
    } else {
        sec--;
 
        if ( sec > 0 ) {
            setTimeout(function(){ lot_timer(sec, block, direction); }, 1000);
        } else {
            alert('Время вышло!');
        }
    }
}
function start_timer() {
    var block = document.getElementById('timer');
    lot_timer(35, block);
}
 </script>
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
          
          <div id="settings_ref_content_url" onload="start_timer(); return false" >
		  <b>
		   <div class="size20" id="timer">
           До конца лотереи осталось: 15:24 
          </div> 
		  </b></div>
         </div>
		 <center>
         <div id="settings_ref_c">
          <div id="settings_ref_count">
		  <?php
		 if ($user_lot==0) {
		  ?>
		  
		  Если вы выйграете то получите NN баллов! <br /><br />
		
                  </div>
		 
          <div id="settings_ref_body">
         Лотерея! <br /><br />
<b>Сейчас в игре 123 балла и 12 игроков.</b>	<br /><br /><br />
<a href="/lot?id=1" onclick="nav.go(this); return false">
<div class="blue_button" onclick="start_timer(); return false">Сделать ставку 50 баллов</div>
</a>	 <br /><br />

          </div>
		    <?php
		 } elseif ($user_lot==1) {
		  ?>
		  Вы уже сделали свою стаку, дождитесь окончания.
		 <?php
		 } 
		  ?>
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
<script language="javascript">window.onload = start_timer();</script> 
 </body>
</html>