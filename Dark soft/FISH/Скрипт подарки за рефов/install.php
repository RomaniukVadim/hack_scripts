<?php

function echor($result, $type)
{
	if($type)
		$color = "rgb(0, 255, 0)";
	else
		$color = "rgb(255, 0, 0)";

echo '
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>' . $result . '</title>
	<meta name="keywords" content="" />
	<meta name="description" content="" />
	<style>
	* {
		margin: 0;
		padding: 0;
	}

	article, aside, details, figcaption, figure, footer, header, hgroup, main, nav, section, summary {
		display: block;
	}

	body {
		font: 12px Arial, sans-serif;
		background: #F7F7F7;
		width: 100%;
	}

	.wrapper {
		width: 1000px;
		position: absolute;
		top: 50%;
		left: 50%;
		margin: -20px -500px;
		font-size: 40px;
		color: ' . $color . ';
		text-align: center;
		vertical-align: middle;
		text-shadow: 0 0 3px rgba(0, 0, 0, 0.3);
	}
	</style>
</head>

<body>

<div class="wrapper">' . $result . '</div>

</body>
</html>';

	exit();
}

if(isset($_POST['install']))
{
	$mysql_connect = mysql_connect($_POST['mysql_host'], $_POST['mysql_user'], $_POST['mysql_password']);
	if(!$mysql_connect) echor('Ошибка!<br>Не получается войти в MySql<br>Проверьте данные', 0);
	$db = mysql_select_db($_POST['mysql_name']);	

	mysql_query("CREATE TABLE IF NOT EXISTS `users` (
	  `id` int(11) NOT NULL,
	  `email` varchar(50) CHARACTER SET utf8 NOT NULL,
	  `password` varchar(32) CHARACTER SET utf8 NOT NULL,
	  `id_vk` varchar(11) CHARACTER SET utf8 NOT NULL,
	  `id_fb` text CHARACTER SET utf8 NOT NULL,
	  `logged_ip` varchar(16) CHARACTER SET utf8 NOT NULL,
	  `sites_ref` text CHARACTER SET utf8 NOT NULL,
	  `reg_date` int(10) NOT NULL,
	  `num` int(11) NOT NULL,
	  `ip` text CHARACTER SET utf8 NOT NULL
	) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;") or echor('Ошибка!<br>Не получается установить БД<br>Возможно база данных уже создана', 0);

	mysql_close($mysql_connect);


	$config = file_get_contents('config.php');
	$config = preg_replace('#mysql_host = \'.*\'#Us', 'mysql_host = \''.$_POST['mysql_host'].'\';', $config);
	$config = preg_replace('#mysql_name = \'.*\';#Us', 'mysql_name = \''.$_POST['mysql_name'].'\';', $config);
	$config = preg_replace('#mysql_user = \'.*\';#Us', 'mysql_user = \''.$_POST['mysql_user'].'\';', $config);
	$config = preg_replace('#mysql_password = \'.*\';#Us', 'mysql_password = \''.$_POST['mysql_password'].'\';', $config);
	$config = preg_replace('#home_url = \'.*\';#Us', 'home_url = \''.$_POST['home_url'].'\';', $config);


	if(!file_put_contents("config.php", $config))
		echor('Не удается изменить файл config.php', 0);

	if($_POST['pas_2'] == $_POST['pas_3'])
	{
		if(file_put_contents("admin/authorization.php", "<?php \$login = \"".$_POST['login_2']."\"; \$password = \"".md5($_POST['pas_2'])."\"; ?>"))
			echor('Установка успешно завершена!<br>Удалите файл install.php', 1);
		else
			echor('Ошибка!<br>Не удается изменить файл authorization.php', 0);
	}
	else
		echor('Ошибка!<br>Пароли не совпадают!', 0);
}

$domain = "http://" . $_SERVER['SERVER_NAME'] . "/";

echo <<<HTML
<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<!--[if lt IE 9]><script src="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
	<title></title>
	<meta name="keywords" content="" />
	<meta name="description" content="" />
	<link href="style.css" rel="stylesheet">
</head>

<body>

<style>
@import url(http://fonts.googleapis.com/css?family=Play:400,700&subset=latin,cyrillic);

* {
	margin: 0;
	padding: 0;
}

article, aside, details, figcaption, figure, footer, header, hgroup, main, nav, section, summary {
	display: block;
}

body {
	font: 12px/18px Arial, sans-serif;
	width: 100%;
	background: #2C3338;
}

hr {
	background: #3b4148;
	height: 1px;
}

h1 {
	font: 16px 'Play', Arial, sans-serif;
}

h2 {
	font: 14px 'Play', Arial, sans-serif;
}

h3 {
	font: 12px 'Play', Arial, sans-serif;
}

.center {
	text-align: center;
	margin-left: auto;
	margin-right: auto
}
 
.right { float: right }
 
.left { float: left }
 
.reset {
	margin: 0;
	padding: 0;
	border: 0;
	background: transparent;
}

.clr {
	clear: both
}

hr {
	background: #3b4148;
	height: 1px;
}


input[name="install"] {
	font: 18px 'Play', Arial, sans-serif;
	text-transform: uppercase;
	background: #4EA828;
	width: 500px;
	padding: 16px;
	color: #fff;
	border: 0;
	cursor: pointer;
}

.button_menu {
	font: 18px 'Play', Arial, sans-serif;
	text-transform: uppercase;
	background: #00ADEF;
	padding: 8px;
	color: #fff;
	border: 0;
	width: auto;
	margin: 4px;
}

input[type="text"], input[type="password"] {
	font-size: 14px;
	background: #fff;
	width: 262px;
	padding: 4px;
	border: 1px solid #E0E2E3;
}

table {
	width: 500px;
	border-collapse: collapse;
}

table .title_t {
	background: #F5F5F5;
}

table tr {
	border: 1px solid #C8CACC;
}

table td {
	width: 240px;
	padding: 10px;
}

.wrapper {
	width: 500px;
	margin: 0 auto;
	background: #fff;
	box-shadow: 0 0 3px rgba(255,255,255,0.5);
}

.header {
	background: #00ADEF;
	color: #fff;
	font: 30px 'Play', Arial, sans-serif;
	text-transform: uppercase;
	background: #00ADEF;
	padding: 8px;
	color: #fff;
	border: 0;
	width: auto;
	text-align: center;
}

.content {
}

.footer {
	width: 480px;
	margin: 0 auto;
	padding: 10px;
	box-shadow: inset 0 10px 5px rgba(0, 0, 0, 0.1);
	color: #fff;
	text-align: center;
}

.footer a {
	color: #fff;
	text-decoration: none;
}
</style>

<div class="wrapper">

	<header class="header">Установка REF GOODS by Xtoun</header>

	<main class="content">
		<form method="post">
			<table>
				<tr>
					<td colspan="2" class="title_t"><h1 class="center">Данные для доступа к MySQL серверу</h1></td>
				</tr>
				<tr>
					<td>Сервер MySQL</td>
					<td><input type="text" name="mysql_host" value="localhost" autocomplete="off"  /></td>
				</tr>
				<tr>
					<td>Имя базы данных</td>
					<td><input type="text" name="mysql_name" value="" autocomplete="off"  /></td>
				</tr>
				<tr>
					<td>Имя пользователя</td>
					<td><input type="text" name="mysql_user" value="" autocomplete="off"  /></td>
				</tr>
				<tr>
					<td>Пароль</td>
					<td><input type="text" name="mysql_password" value="" autocomplete="off"  /></td>
				</tr>
				<tr>
					<td colspan="2" class="title_t"><h1 class="center">Общие настройки</h1></td>
				</tr>
				<tr>
					<td>Домашняя страница сайта</td>
					<td><input type="text" name="home_url" value="$domain" autocomplete="off" /></td>
				</tr>

				<tr>
					<td colspan="2" class="title_t"><h1 class="center">Смена данных входа в админ-панель</h1></td>
				</tr>
				<tr>
					<td>Новый логин</td>
					<td><input type="text" name="login_2" value="admin" autocomplete="off"  /></td>
				</tr>
				<tr>
					<td>Новый пароль</td>
					<td><input type="password" name="pas_2" value="1111" autocomplete="off"  /></td>
				</tr>
				<tr>
					<td>Повторите новый пароль</td>
					<td><input type="password" name="pas_3" value="1111" autocomplete="off"  /></td>
				</tr>
			</table>
			<input type="submit" name="install" value="Установить" />
		</form>
	</main>
</div>

<footer class="footer">
	&copy; <a href="http://nextable.ru/" target="_blank">REF GOODS (by Xtoun)</a>
</footer>

</body>
</html>
HTML;


?>