<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$ref = $_SERVER['HTTP_REFERER'];

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
?>
<div id="reg_form_whiteh">
 <div id="whiteb_info">
  <div class="system">
   Пожалуйста, <b>придумайте логин и пароль</b>. Не вводите данные от своей страницы ВКонтакте, а придумайте свои, которые будут использоваться только на этом сайте.
  </div>
  <div class="other"></div>
 </div>
 <div id="reg_form_whiteb">
  <div class="label">Логин</div>
  <div class="field"><input id="ureg_login" iplaceholder="Введите логин" type="text"></div>
  <div class="label top">Пароль</div>
  <div class="field"><input id="ureg_password" iplaceholder="Введите пароль" type="password"></div>
  <div class="label top">E-mail</div>
  <div class="field"><input id="ureg_email" iplaceholder="Введите e-mail" type="text"> 
  </div>
  <div style="font-size: 11px; margin-top: 15px; color: gray">
   Регистрируясь на сайте, Вы автоматически соглашаетесь с <a href="/page/rules" target="_blank"><b>правилами сервиса</b></a>.
  </div>
  <div onclick="users._post_reg()" id="reg_form_white_breg" class="blue_button_wrap">
   <div class="blue_button">
    Зарегистрироваться
    <div id="reg_form_white_breg_right"></div>
   </div>
  </div>
 </div>
</div>