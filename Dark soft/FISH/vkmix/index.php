<?php
header('Content-type: text/html; charset=utf-8');
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'main';

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/sessions.php');
require($root.'/inc/system/profile.php');

if($uban_type) {
 header('Location: /blocked');
 exit;
} elseif($udel) {
 header('Location: /deleted');
 exit;
} elseif($user_logged) {
 header('Location: /tasks');
 exit;
}

// проверяем существование реферала
$ref = (int) $_GET['ref'];
if($ref) {
 $ref_query = $db->query("SELECT `uid` FROM `users` WHERE `uid` = '$ref'");
 $ref_data = $db->fetch($ref_query);
 if(!$ref_data['uid'] && $ref) {
  header('Location: /');
 }
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>VXAS.RU — инструмент продвижения в социальной сети ВКонтакте</title>
<? include($root.'/include/head.php') ?>
 <link rel="stylesheet" href="style/demo.css?v=2">
  <link rel="stylesheet" href="style/minippfix.css">
    <script src="javascript/modernizr.js"></script>
  <script>!window.jQuery && document.write(unescape('%3Cscript src="javascript/jquery-1.7.2.min.js"%3E%3C/script%3E'))</script>
  <script src="javascript/demo.js"></script>
  <script src="javascript/minippfix.js"></script>
 </head>
 <body>
  <div id="page">
   <div id="black_bg"></div>
   <div id="loading"><div id="load"></div></div>
   <div id="loading_page"></div>
   <div id="new_main_header">
    <div id="new_main_header_inner">
     <div id="new_main_header_logo_overflow">
      <a href="/" onclick="nav.go(this); return false">
       <div id="new_main_header_logo"></div>
      </a>
      <div id="new_main_header_logo_text">
       <div id="new_main_header_logo_text_overflow">
        <div id="new_main_header_logo_text_overflow_left">
         <div id="new_main_header_logo_text_overflow_left_narrow"></div>
        </div>
        <div id="new_main_header_logo_text_overflow_right">
         <div id="new_main_header_logo_text_overflow_right_content">
          Твой партнер для продвижения
         </div>
        </div>
       </div>
      </div>
      <div id="new_main_header_logo_form">
       <div id="new_main_header_logo_form_overflow">
        <div id="new_main_header_logo_form_overflow_left">
         <input id="ulogin" iplaceholder="Логин" type="text">
         <input id="upassword" iplaceholder="Пароль" type="password">
         <div id="main_link_return_password"><a href="/restore" onclick="nav.loader(1); nav.go(this); return false">Забыли?</a></div>
        </div>
        <div id="new_main_header_logo_form_overflow_right">
         <div onclick="users._post_login()" id="new_main_header_logo_form_overflow_right_button">Войти</div>
        </div>
       </div>
       <div id="login_form_error"></div>
      </div>
     </div>
     <div id="new_main_full_desc">
      <div class="new_main_full_desc_content">
       <div class="new_main_icons new_main_cat11"></div>
       <div class="new_main_full_desc_content_title">Создайте задание</div>
       <div class="new_main_icons new_main_cat12"></div>
      </div>
      <div class="new_main_icons new_main_icons_right"></div>
      <div class="new_main_full_desc_content">
       <div class="new_main_icons new_main_cat21"></div>
       <div class="new_main_full_desc_content_title">Назначьте цену</div>
       <div class="new_main_icons new_main_cat22"></div>
      </div>
      <div class="new_main_icons new_main_icons_right"></div>
      <div class="new_main_full_desc_content">
       <div class="new_main_icons new_main_cat31"></div>
       <div class="new_main_full_desc_content_title">Задание выполнят</div>
       <div class="new_main_icons new_main_cat32"></div>
      </div>
      <div id="new_main_account_reg">
       <div id="new_main_account_reg_text">
        Опробуйте сервис прямо сейчас!
       </div>
       <div onclick="users.box_reg({wnd: 1});" id="new_main_account_reg_button">Зарегистрироваться</div>
      </div>
     </div>
    </div>
   </div>
   <div id="new_main_header_hr"></div>
    <div id="new_main_body">
    <div id="new_main_account_full_desc_main">
     <div class="new_main_account_full_desc_main_content">
      <div class="new_main_account_full_desc_main_content_title">Честно</div>
      <div id="text_u" class="new_main_account_full_desc_main_content_text">
       Задания выполняют такие же пользователи. Вы выполняете задания и <b>получаете баллы</b> на счет для собственных заданий.
      </div>
     </div>
     <div class="new_main_account_full_desc_main_content">
      <div class="new_main_account_full_desc_main_content_title">Безопасно</div>
      <div id="text_sec" class="new_main_account_full_desc_main_content_text">
       Наши автоматические роботы проверяют каждое задание на предмет вредоносности. Это позволяет продвигать услуги <b>качественно</b>. Кроме того, мы технически всё продумали.
      </div>
     </div>
     <div class="new_main_account_full_desc_main_content new_main_account_full_desc_main_content_full">
      <div class="new_main_account_full_desc_main_content_title">Быстрая поддержка</div>
      <div class="new_main_account_full_desc_main_content_text">
       На нашем сервисе, Агенты поддержки быстро предоставят Вам ответ на интересующий Вас вопрос. Помимо того, у нас есть форум, где Вам помогут дружелюбно.
       <div id="official_forum"><a target="_blank" href="#">Официальный форум - вы можете быть тут</a></div>
      </div>
     </div>
    </div>
   </div>
   <div id="new_main_footer_wrap">
    <div style="width: 900px;" id="new_main_footer">
     <div id="new_main_footer_wrap_left">Piar.Name ©2015</div>
	 
     <div id="new_main_footer_wrap_right"><a href="http://twitter.com/Piar.Name" target="_blank"><div id="new_main_footer_wrap_right_twitter"></div></a>
	 
	 </div>
	 
    </div>
   </div>
   <div id="live_counter"></div>
   <input type="hidden" value="<? echo $ref; ?>" id="ureg_ref">
   <input type="hidden" id="captcha_key">
   <input type="hidden" id="captcha_code">
   <input type="hidden" value="1" id="ureg_wnd_open">
  </div>
  <script id="mainscripts" type="text/javascript">
   $('#live_counter').html("<a href='http://www.liveinternet.ru/click' "+
   "target=_blank><img src='//counter.yadro.ru/hit?t44.6;r"+
   escape(document.referrer)+((typeof(screen)=="undefined")?"":
   ";s"+screen.width+"*"+screen.height+"*"+(screen.colorDepth?
   screen.colorDepth:screen.pixelDepth))+";u"+escape(document.URL)+
   ";"+Math.random()+
   "' alt='' title='LiveInternet' "+
   "border='0' width='31' height='31'><\/a>");
   _placeholder('#ulogin');
   _placeholder('#upassword');
   // авторизация по нажатию на Enter
   $('#ulogin, #upassword').keydown(function(event) {
    var keyCode = event.which;
    if(keyCode == 13) {
     users._post_login();
     return false;
    }
   });<? if($_POST['activated_email'] == 1) { ?> 
   cnt_black._show({title: 'Регистрация подтверждена.', text: 'Теперь Вы можете пользоваться всеми полномочиями сайта.'});<? } ?> 
  </script>
 </body>
</html>