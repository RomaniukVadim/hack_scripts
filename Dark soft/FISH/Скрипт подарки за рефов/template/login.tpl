[head=title]Авторизация[/head]

<h1>Авторизация</h1><br>
<form method="post">
	[login-type=email]
		<i>E-Mail:</i><br>
		<input type="text" name="email" value="" /><br><br>
	[/login-type]

	[login-type=usual]
		<i>Ваш уникальный ID:</i><br>
		<input type="text" name="login" value="" pattern="[0-9]{1,16}"/><br><br>
	[/login-type]

	<i>Пароль:</i><br>
	<input type="password" name="password" value="" pattern="[A-Za-z0-9_-/]{1,16}" /><br><br>
	<input type="submit" name="login_b" class="button_r" value="Войти" />
</form>