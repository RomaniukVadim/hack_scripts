<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'my_settings';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks_blacklist.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Мои настройки</title>
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
         <a class="active" href="/settings" onclick="nav.go(this); return false;"><div class="tabdiv">Общее</div></a>
         <a href="/settings/ref?menu=1" onclick="nav.go(this); return false;"><div class="tabdiv">Партнерская программа</div></a>
         <a href="/settings/balance" onclick="nav.go(this); return false;"><div class="tabdiv">Баланс</div></a>
        </div>
        <div id="my_settings">
         <div class="my_settings_content">
          <div id="my_settings_change_password">
           <div id="my_settings_change_password_name">Изменить пароль</div>
           <div id="my_settings_change_password_error"></div>
           <div id="my_settings_change_password_content">
            <div class="overflow_field">
             <div class="label">Старый пароль:</div>
             <div class="field"><input id="my_settings_old_password" type="password"></div>
            </div>
            <div class="overflow_field">
             <div class="label">Новый пароль:</div>
             <div class="field"><input id="my_settings_new_password" type="password"></div>
            </div>
            <div class="overflow_field">
             <div class="label">Повторите пароль:</div>
             <div class="field">
              <input id="my_settings_new_password2" type="password">
              <br />
              <div onclick="users.change_password();" id="settings_password_button" class="blue_button_wrap small_blue_button"><div class="blue_button">Изменить пароль</div></div>
             </div>
            </div>
           </div>
          </div>
         </div>
         <div class="my_settings_content">
          <div id="my_settings_account">
           <div id="my_settings_account_name">Ваш персональный логин</div>
           <div id="my_settings_account_error"></div>
           <div id="my_settings_account_content">
            <div class="overflow_field">
             <div class="label">Текущий логин:</div>
             <div class="field field_settings_login"><? echo $user_login; ?></div>
            </div>
            <div class="overflow_field">
             <div class="label label_f">Новый логин:</div>
             <div class="field">
              <input id="my_settings_account_login_field" type="text">
              <br />
              <div onclick="users.change_login();" id="settings_login_button" class="blue_button_wrap small_blue_button"><div class="blue_button">Изменить логин</div></div>
             </div>
            </div>
           </div>
          </div>
          <div id="my_settings_email">
           <div id="my_settings_email_name">Адрес Вашей электронной почты</div><? if(!$uemail_activated) { ?> 
           <div id="my_settings_email_notif">
            На указанный адрес было отправлено письмо со ссылкой для подтверждения. Пожалуйста, проверьте Ваш почтовый ящик.
            <span id="reemail_a">
             <br />
             <a href="javascript://" onclick="users.reemail();">Отправить письмо повторно »</a>
            </span>
           </div><? } ?> 
           <div id="my_settings_email_content">
            <div class="overflow_field">
             <div class="label">Текущий адрес:</div>
             <div class="field field_settings_login"><? echo $uemail; ?></div>
            </div>
           </div>
          </div>
         </div>
        </div>
        <div id="my_settings_footer">Вы можете <a href="javascript://" onclick="users.delete_account();">удалить свой аккаунт</a>.</div>
       </div>
      </div>
     </div>
     <input type="hidden" value="<? echo $usession; ?>" id="ssid">
<? include($root.'/include/footer.php') ?>
 
    </div>
   </div>
  </div>
<? include($root.'/include/scripts.php') ?> 
 </body>
</html>