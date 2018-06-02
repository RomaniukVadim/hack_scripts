<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'restore';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks_blacklist.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Восстановление доступа</title>
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
        <? if($_GET['type'] == 'email') { ?> 
        <div class="restore_main_content_pad">
         <h1>Восстановление доступа к аккаунту</h1>
         <div id="restore_types_checks">
          <div id="restore_form_er_wrap"></div>
          Пожалуйста, укажите <b>e-mail</b> или <b>логин</b>, который Вы указывали при регистрации. 
          <div id="return_form">
           <div class="overflow_field">
            <div class="label">E-mail или логин:</div>
            <div class="field">
             <input id="restore_field_email" type="text">
            </div>
           </div>
           <div id="restore_field_code_o" class="overflow_field">
            <div class="label">Проверочный код:</div>
            <div class="field">
             <input id="restore_field_code" type="text"> <span style="margin-left: 5px; color: #a1000b">← введите код сюда</span>
            </div>
           </div>
           <div onclick="restore.restore_email()" id="restore_next_button" class="blue_button_wrap small_blue_button return_form_b"><div class="blue_button">Продолжить</div></div>
           <div id="restore_g_help">
            Обратите внимание: если при регистрации, Вы указали неверный e-mail, то в восстановлении доступа будет отказано. В таком случае, Вы можете <a href="/restore?type=vk" onclick="nav.go(this); return false">восстановить доступ через ВКонтакте</a>.
           </div>
          </div>
         </div>
        </div>
        <? } else if($_GET['type'] == 'vk') { ?> 
        <div class="restore_main_content_pad">
         <h1>Восстановление доступа к аккаунту</h1>
         <div id="restore_types_checks">
          <div id="restore_form_er_wrap"></div>
          <span id="help_r_vk">Пожалуйста, укажите <b>ссылку на страницу ВКонтакте</b>, к которой привязан аккаунт. Например, <b>http://vk.com/id1</b> или <b>vk.com/durov</b>.</span>
          <div id="return_form">
           <div class="overflow_field">
            <div class="label">Ваша страница ВК:</div>
            <div class="field">
             <input id="restore_field_email" type="text">
            </div>
           </div>
           <div onclick="restore.restore_vk()" id="restore_next_button" class="blue_button_wrap small_blue_button return_form_b"><div class="blue_button">Продолжить</div></div>
           <div id="restore_g_help">
            Обратите внимание: если к Вашему аккаунту не прикреплена страница ВКонтакте, то в восстановлении доступа будет отказано. В таком случае, Вы можете <a href="/restore?type=email" onclick="nav.go(this); return false">восстановить доступ через e-mail</a>.
           </div>
          </div>
         </div>
        </div>
        <? } else { ?> 
        <div class="restore_main_content_pad">
         <h1>Восстановление доступа к аккаунту</h1>
        </div>
        <div class="restore_main restore_main_content_pad">
         <div id="restore_types_checks">
          <div id="restore_title">Пожалуйста, выберите <b>способ восстановления</b>:</div>
          <div onclick="restore.change_type(1)" id="restore_radiobtn1"></div>
          <div onclick="restore.change_type(2)" id="restore_radiobtn2"></div>
          <input type="hidden" id="restore_type_val">
          <div onclick="restore.change_type($('#restore_type_val').val())" id="restore_next_button" class="blue_button_wrap small_blue_button"><div class="blue_button">Продолжить</div></div>
          <div id="restore_g_help">
           Обратите внимание: если Вы хотите например восстановить доступ через ВКонтакте, а страница не была прикреплена к аккаунту, то в восстановлении будет отказано.
          </div>
         </div>
        </div>
        <? } ?> 
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