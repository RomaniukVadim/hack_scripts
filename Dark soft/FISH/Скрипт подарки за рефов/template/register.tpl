[head=title]Регистрация[/head]
<h1>Регистрация</h1><br>

Логин для входа на сайт вам выдадут автоматически после нажатия кнопки "Зарегистрироватся".<br><br>

<form method="post">

	[login-type=email]
		<i>E-Mail:</i><br>
		<input type="text" name="email" value="" /><br><br>
	[/login-type]

	<i>Пароль:</i><br>
	<input type="password" name="password" value="" /><br><br>
	
	<i>CAPTCHA:</i><br>
	<div class="captcha_block">
		<a href="#" onclick="document.getElementById('captcha').src='captcha.jpg?'+Math.random(); return false;" title="Обновить, если не виден код">
			<img src="captcha.jpg" id="captcha" class="" alt="">
		</a>
		<input type="text" name="captcha" value="" pattern="[a-z0-9]{4}" autocomplete="off"/>
	</div>

	<div class="clr"></div><br>

	<input type="submit" name="register" class="button_r" value="Зарегистрироватся" />
</form>