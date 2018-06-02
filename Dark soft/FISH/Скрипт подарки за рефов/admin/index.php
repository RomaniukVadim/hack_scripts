<?php
/*
=====================================================
FreeKeys CMS
-----------------------------------------------------
Автор: expert_m
-----------------------------------------------------
Ссылка: http://nextable.ru/
=====================================================
Файл: admin/index.php
-----------------------------------------------------
Назначение: Админ-панель
=====================================================
*/

header('Content-Type: text/html; charset=utf-8');
session_start();

// *** Выход из профиля *** //
if($_GET['do'] == 'exit')
{
	unset($_SESSION['login_admin']);
	header('Location: index.php');
}

// *** Проверка авторизации пользователя *** //
$logged = false;
$ban = false;
$n;

function line($id, $task) 
{
	$file = file('logs.txt'); 

	for($i = 0; $i < sizeof($file); $i++)
	{
		if($i == $id)
		{
			if($task == "delete")
				unset($file[$i]);

			if($task == "rewrite")
				$file[$i] = $GLOBALS['n'][0] . " " . $GLOBALS['n'][1] . " " . date('U') . "\n";
		}
	}

	$i = 0;

	$fp = fopen('logs.txt', "w"); 
	fputs($fp,implode('', $file)); 
	fclose($fp);
}

function verification_ban($s)
{
	$fp = fopen('logs.txt', "r");

	$ip = true;
	$i = 0;
	while(fscanf($fp, "%s %s %s", $GLOBALS['n'][0], $GLOBALS['n'][1], $GLOBALS['n'][2]))
	{
		if(date('U') - $GLOBALS['n'][2] > 3600)
		{
			line($i,"delete");
			continue;
		}

		if($GLOBALS['n'][0] == $_SERVER['REMOTE_ADDR'] && $s)
		{
			$ip = false;
			$GLOBALS['n'][1]++;
			line($i,"rewrite");
			break;
		}

		if($GLOBALS['n'][1] >= 5)
		{
			$ip = false;
			$GLOBALS['ban'] = true;
			break;
		}
		$i++;
	}

	fclose ($fp);

	if($ip && $s)
	{
		$fp = fopen('logs.txt', "a");
		fwrite($fp, $_SERVER['REMOTE_ADDR'] . " 1 " . date('U') . "\n");
		fclose($fp);
	}
}

verification_ban(false);

if(isset($_SESSION['login_admin']) && !$ban)
{
 	include "authorization.php";

	if($_SESSION['login_admin'] != $login || $_SESSION['password_admin'] != $password)
		unset($_SESSION['login_admin']);
	else
		$logged = true;
}
else
{
	if(isset($_POST['login_v']) && !$ban)
	{
		if(isset($_POST['login_v']) && $_POST['login'] != NULL && $_POST['password'] != NULL)
		{
			include "authorization.php";

			if($_POST['login'] == $login && md5($_POST['password']) == $password)
			{
				$_SESSION['login_admin'] = $_POST['login'];
				$_SESSION['password_admin'] = md5($_POST['password']);
				$logged = true;
			}
			else
			{
				verification_ban(true);
			}
		}
	}
}

if(!$logged)
{
	echo '
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="UTF-8">
		<title>Авторизация</title>
		<link rel="stylesheet" href="authorization.css" media="screen" type="text/css" />
	</head>
	<body>
	    <div id="login">
	        <form method="post">
	            <fieldset class="clearfix">
	                <p><span class="fontawesome-user"></span><input type="text" name="login" value="Логин" onBlur="if(this.value == \'\') this.value = \'Логин\'" onFocus="if(this.value == \'Логин\') this.value = \'\'" required></p>
	                <p><span class="fontawesome-lock"></span><input type="password" name="password" value="Пароль" onBlur="if(this.value == \'\') this.value = \'Пароль\'" onFocus="if(this.value == \'Пароль\') this.value = \'\'" required></p>
					<img src="https://clck.ru/CTofd.png" alt="ВОЙТИ" border="0">
	                <p><input type="submit" name="login_v" value="ВОЙТИ"></p>
	            </fieldset>
	        </form>
	    </div>
	</body>
	</html>
	';

	exit();
}

// *** Основные настройки *** //
if($_GET['do'] == NULL && isset($_POST['save']))
{
	$line_conf = "<?php
define(FreeKeys, true, true);
\$mysql_host = '".$_POST['mysql_host']."';
\$mysql_name = '".$_POST['mysql_name']."';
\$mysql_user = '".$_POST['mysql_user']."';
\$mysql_password = '".$_POST['mysql_password']."';
\$home_title = '".$_POST['home_title']."';
\$short_title = '".$_POST['short_title']."';
\$home_url = '".$_POST['home_url']."';
\$main_description = '".$_POST['main_description']."';
\$main_keywords = '".$_POST['main_keywords']."'; 
\$vk_client_id_conf = '".$_POST['vk_client_id_conf']."';
\$vk_client_secret_conf = '".$_POST['vk_client_secret_conf']."';
\$fb_client_id_conf = '".$_POST['fb_client_id_conf']."';
\$fb_client_secret_conf = '".$_POST['fb_client_secret_conf']."';
\$url_shortener_conf = '".$_POST['url_shortener_conf']."';
\$save_passwords_conf = '".$_POST['save_passwords_conf']."';
\$registration_type_conf = '" . $_POST['registration_type_conf'] . "';\n";

	if($_POST['ref_links_conf'] == '0')
		$line_conf .= "\$ref_links_conf = false;\n";
	else
		$line_conf .= "\$ref_links_conf = true;\n";

	if($_POST['vk_links_conf'] == '0')
		$line_conf .= "\$vk_links_conf = false;\n";
	else
		$line_conf .= "\$vk_links_conf = true;\n";

	$line_conf .= "\$number_links_conf = ".$_POST['number_links_conf'].";\n";

	$line_conf .= "\$game_conf = array(";

	$i = 0;	$j = 0;
	while(true)
	{
		if($_POST['game_conf_n_'.$i] == NULL)
		{
			$j++;

			if($j > 3)
				break;
		}
		else
		{
			if($i > 0)
				$line_conf .= ",";

			$line_conf .= "array('".$_POST['game_conf_n_'.$i]."', '".$_POST['game_conf_d_'.$i]."', '".$_POST['game_conf_nr_'.$i]."', '".$_POST['game_conf_dg_'.$i]."')";
		}

		$i++;
	}

	$line_conf .= ");\n\$additional_tasks = array(";

	$i = 0;	$j = 0;
	while(true)
	{
		if($_POST['additional_tasks_'.$i] == NULL)
		{
			$j++;

			if($j > 3)
				break;
		}
		else
		{
			if($i > 0)
				$line_conf .= ",";

			$line_conf .= 'array("'.addslashes($_POST['additional_tasks_'.$i]).'", '.$_POST['additional_tasks_select_'.$i].', "'.addslashes($_POST['additional_tasks_d_'.$i]).'")';
		}

		$i++;
	}

	$line_conf .= ");\n?>";

	if(file_put_contents("../config.php", $line_conf))
		$notice = '<div class="notice">Данные успешно сохранены!</div>';
	else
		$notice = '<div class="notice">Ошибка!</div>';
}

if($_GET['do'] == NULL)
{
	include '../config.php';

	for($i = 0; $i < (sizeof($game_conf)+1); $i++)
	{
		$game_conf_a .= '<tr><td colspan="2">'.($i+1).'.&nbsp;Название игры:&nbsp;<input type="text" style="width:600px" name="game_conf_n_'.$i.'" value="'.$game_conf[$i][0].'" autocomplete="off" />
		&nbsp;&nbsp;Кол-во переходов:&nbsp;<input type="text" style="width:130px" name="game_conf_d_'.$i.'" value="'.$game_conf[$i][1].'" autocomplete="off" /><br><br>
		&nbsp;Название для реф. ссылки:&nbsp;<input type="text" style="width:800px" name="game_conf_nr_'.$i.'" value="'.$game_conf[$i][2].'" autocomplete="off" /><br><br>
		&nbsp;Описание:&nbsp;<input type="text" style="width:895px;" name="game_conf_dg_'.$i.'" value="'.$game_conf[$i][3].'" autocomplete="off" /></td></tr>
		';
	}

	for($i = 0; $i < (sizeof($additional_tasks)+1); $i++)
	{
		$add_task_selected[$additional_tasks[$i][1]] = 'selected="selected"';

		$additional_tasks_a .= '<tr><td colspan="2">'.($i+1).'.&nbsp;<input type="text" style="width:950px" name="additional_tasks_'.$i.'" value="'.htmlspecialchars(stripslashes($additional_tasks[$i][0])).'" autocomplete="off" /><br><br>
		Тип задания:&nbsp;<select name="additional_tasks_select_'.$i.'" id="additional_tasks_select_'.$i.'" size="1" style="width:200px">
		<option '.$add_task_selected[0].' value="0">Не выбрано</option>
		<option '.$add_task_selected[1].' value="1">Простое задание</option>
		<option '.$add_task_selected[2].' value="2">"Мне нравится"</option>
		<option '.$add_task_selected[3].' value="3">Вступление в ВК группу</option>
		<option '.$add_task_selected[4].' value="4">Пост на стене в ВК</option>
		</select>
		&nbsp;&nbsp;Значение:&nbsp;<input type="text" style="width:616px" name="additional_tasks_d_'.$i.'" id="additional_tasks_d_'.$i.'" value="'.htmlspecialchars(stripslashes($additional_tasks[$i][2])).'" autocomplete="off" /></td></tr>
		<script>
		$(function(){
			$(\'#additional_tasks_select_'.$i.'\').change(function(){
				var curdata = $(this).val();
				if(curdata == 1) { result = \'Оставьте это поле пусты\'; }
				if(curdata == 2) { result = \'Укажите apiId виджета "Мне нравится"\'; }
				if(curdata == 3) { result = \'Укажите id вашей группы\'; }
				if(curdata == 4) { result = \'Укажите текст который присутствует в посте\'; }
				$(\'#additional_tasks_d_'.$i.'\').val(result);
			})
		})
		</script>
		';

		$add_task_selected[$additional_tasks[$i][1]] = '';
	}
	
	if($registration_type_conf == 'usual')
		$registration_type_conf_usual = 'selected="selected"';
	elseif($registration_type_conf == 'vk')
		$registration_type_conf_vk = 'selected="selected"';
	elseif($registration_type_conf == 'fb')
		$registration_type_conf_fb = 'selected="selected"';
	elseif($registration_type_conf == 'email')
		$registration_type_conf_mail = 'selected="selected"';
	elseif($registration_type_conf == 'ip')
		$registration_type_conf_ip = 'selected="selected"';

	if($ref_links_conf)
		$ref_links_conf_a = 'selected="selected"';
	else
		$ref_links_conf_b = 'selected="selected"';

	if($vk_links_conf)
		$vk_links_conf_a = 'selected="selected"';
	else
		$vk_links_conf_b = 'selected="selected"';

	$content = '
		<form method="post">
		<table>
			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Данные для доступа к MySQL серверу</h1></td>
			</tr>

			<tr>
				<td>Сервер MySQL</td>
				<td><input type="text" name="mysql_host" value="'.$mysql_host.'" autocomplete="off"  /></td>
			</tr>
			<tr>
				<td>Имя базы данных</td>
				<td><input type="text" name="mysql_name" value="'.$mysql_name.'" autocomplete="off"  /></td>
			</tr>
			<tr>
				<td>Имя пользователя</td>
				<td><input type="text" name="mysql_user" value="'.$mysql_user.'" autocomplete="off"  /></td>
			</tr>
			<tr>
				<td>Пароль</td>
				<td><input type="text" name="mysql_password" value="'.$mysql_password.'" autocomplete="off"  /></td>
			</tr>

			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Общие настройки</h1></td>
			</tr>

			<tr>
				<td>Название сайта</td>
				<td><input type="text" name="home_title" value="'.$home_title.'" autocomplete="off"  /></td>
			</tr>
			<tr>
				<td>Краткое название сайта</td>
				<td><input type="text" name="short_title" value="'.$short_title.'" autocomplete="off" /></td>
			</tr>
			<tr>
				<td>Домашняя страница сайта</td>
				<td><input type="text" name="home_url" value="'.$home_url.'" autocomplete="off" /></td>
			</tr>
			<tr>
				<td>Описание (Description) сайта</td>
				<td><input type="text" name="main_description" value="'.$main_description.'" autocomplete="off" /></td>
			</tr>
			<tr>
				<td>Ключевые слова (Keywords) сайта</td>
				<td><input type="text" name="main_keywords" value="'.$main_keywords.'" autocomplete="off" /></td>
			</tr>
			<tr>
				<td>Сервис сокращения ссылок</td>
				<td>
					<input type="text" name="url_shortener_conf" value="'.$url_shortener_conf.'" autocomplete="off" />
					<br>Укажите один из сервисов сокращения ссылок (g.ua, clck.ru, bit.ly, j.mp, bitly.com, goo.gl, qps.ru, loh.ru, is.gd, v.gd, tinyurl.com, b23.ru, nn.nf, tiny.cc, ur1.ca, x.co, qr.net, z.te.ua, чоч.рф, uri0.su, lin.io, u.to, 1-1.su).
					Для отключения сокращения реферальных ссылок оставьте поле пустым.
				</td>
			</tr>
			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Настройки для пользователей</h1></td>
			</tr>
			<tr>
				<td>Тип регистрации</td>
				<td>
					<select name="registration_type_conf" size="1">
						<option '. $registration_type_conf_usual .' value="usual">Обычная</option>
						<option '. $registration_type_conf_mail .' value="email">Через E-mail</option>
						<option '. $registration_type_conf_vk .' value="vk">Через ВКонтакте</option>
						<option '. $registration_type_conf_fb .' value="fb">Через Facebook</option>
						<option '. $registration_type_conf_ip .' value="ip">Через IP</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Файл для сохранения данных пользователей</td>
				<td>
					<input type="text" name="save_passwords_conf" value="'.$save_passwords_conf.'" autocomplete="off" />
					<br>Все данные пользователей при регистрации будут сохранятся. Для отключения оставьте поле пустым.
				</td>
			</tr>
			<tr>
				<td>Ссылки на профиль</td>
				<td>
					<select name="ref_links_conf" size="1">
						<option '. $ref_links_conf_a .' value="1">Включить</option>
						<option '. $ref_links_conf_b .' value="0">Выключить</option>
					</select>
					<br>Если данная функция включена то пользователь обязан разослать ссылку на свой профиль на N количество сайтов.
				</td>
			</tr>
			<tr>
				<td>Ссылка на ВКонтакте профиль</td>
				<td>
					<select name="vk_links_conf" size="1">
						<option '. $vk_links_conf_a .' value="1">Включить</option>
						<option '. $vk_links_conf_b .' value="0">Выключить</option>
					</select>
				</td>
			</tr>
			<tr>
				<td>Количество ссылок на профиль</td>
				<td>
					<input type="text" name="number_links_conf" value="'.$number_links_conf.'" autocomplete="off" />
					<br>Количество ссылок на профиль которые пользователь должен набрать
				</td>
			</tr>

			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Настройка ВКонтакте авторизации</h1></td>
			</tr>
			<tr>
				<td>ID приложения</td>
				<td><input type="text" name="vk_client_id_conf" value="'.$vk_client_id_conf.'" autocomplete="off"  /></td>
			</tr>
			<tr>
				<td>Защищённый ключ</td>
				<td><input type="text" name="vk_client_secret_conf" value="'.$vk_client_secret_conf.'" autocomplete="off" /></td>
			</tr>

			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Настройка Facebook авторизации</h1></td>
			</tr>
			<tr>
				<td>ID приложения</td>
				<td><input type="text" name="fb_client_id_conf" value="'.$fb_client_id_conf.'" autocomplete="off"  /></td>
			</tr>
			<tr>
				<td>Защищённый ключ</td>
				<td><input type="text" name="fb_client_secret_conf" value="'.$fb_client_secret_conf.'" autocomplete="off" /></td>
			</tr>

			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Подарки</h1></td>
			</tr>
			'.$game_conf_a.'

			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Дополнительные задания</h1></td>
			</tr>
			'.$additional_tasks_a.'
		</table>
		<input type="submit" name="save" value="Сохранить" />
		</form>
	';
}

// *** Генератор ключей *** //
if($_GET['do'] == 'keys')
{
	if(isset($_POST['save']))
	{
		if(file_put_contents("../key-generation.php", $_POST['func_keys_text']))
			$notice = '<div class="notice">Данные успешно сохранены!</div>';
		else
			$notice = '<div class="notice">Ошибка!</div>';
	}

	$func_keys_text = file_get_contents('../key-generation.php');

	$content = '
	<form method="post">
		<table>
			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Редактирование генератора ключей</h1></td>
			</tr>
			<tr>
				<td colspan="2">
					<textarea id="PHP" class="prettyprint" cols="118" rows="30" wrap="off" name="func_keys_text" spellcheck="false">'.$func_keys_text.'</textarea>
				</td>
			</tr>
		</table>
		<input type="submit" name="save" value="Сохранить" />
	</form>
	';
}

// *** Смена данных входа *** //
if($_GET['do'] == 'change_login')
{
	if(isset($_POST['save']))
	{
		if($_POST['login_1'] != NULL && $_POST['login_2'] != NULL && $_POST['pas_1'] != NULL && $_POST['pas_2'] != NULL && $_POST['pas_3'] != NULL)
		{
			if($login == $_POST['login_1'] && $password == md5($_POST['pas_1']))
			{
				if($_POST['pas_2'] == $_POST['pas_3'])
				{
					if(file_put_contents("authorization.php", "<?php \$login = \"".$_POST['login_2']."\"; \$password = \"".md5($_POST['pas_2'])."\"; ?>"))
						$notice = '<div class="notice">Данные успешно сохранены!</div>';
					else
						$notice = '<div class="notice">Ошибка!</div>';
				}
				else
					$notice = '<div class="notice">Оба новых пароля должны быть идентичными!</div>';
			}
			else
				$notice = '<div class="notice">Введен неправильно старый логин или старый пароль!</div>';
		}
		else
			$notice = '<div class="notice">Нужно заполнять все поля!</div>';
	}

	$content = '
	<form method="post">
		<table>
			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Смена данных входа в админ-панель</h1></td>
			</tr>
			<tr>
				<td>Старый логин</td>
				<td><input type="text" name="login_1" value="" autocomplete="off"  /></td>
			</tr>
			<tr>
				<td>Старый пароль</td>
				<td><input type="password" name="pas_1" value="" autocomplete="off"  /></td>
			</tr>
			<tr>
				<td>Новый логин</td>
				<td><input type="text" name="login_2" value="" autocomplete="off"  /></td>
			</tr>
			<tr>
				<td>Новый пароль</td>
				<td><input type="password" name="pas_2" value="" autocomplete="off"  /></td>
			</tr>
			<tr>
				<td>Повторите новый пароль</td>
				<td><input type="password" name="pas_3" value="" autocomplete="off"  /></td>
			</tr>
		</table>
		<input type="submit" name="save" value="Сохранить" />
	</form>
	';
}

// *** Рейтинг игр *** //
if($_GET['do'] == 'rating_game')
{
	include '../config.php';

	$rating_game_max = 600;

	for($i = 0; $i < sizeof($game_conf); $i++)
	{
		if($rating_game_max < file_get_contents('../stat-game/' . $game_conf[$i][2] . '.txt'))
			$rating_game_max = file_get_contents('../stat-game/' . $game_conf[$i][2] . '.txt');
	}

	for($i = 0; $i < sizeof($game_conf); $i++)
	{
		$rating_game .= '<h1>' . $game_conf[$i][0] . ':</h1><progress value="' . file_get_contents('../stat-game/' . $game_conf[$i][2] . '.txt') . '" max="' . $rating_game_max . '"></progress>';
		$rating_game .= '<div class="progress_text"><div class="center">' . file_get_contents('../stat-game/' . $game_conf[$i][2] . '.txt') . '</div></div><br>';
	}

	$content = '
	<table>
		<tr>
			<td colspan="2" class="title_t"><h1 class="center">Рейтинг игр</h1></td>
		</tr>
		<tr>
			<td colspan="2">' . $rating_game . '</td>
		</tr>
	</table>
	';
}

// *** FAQ *** //
if($_GET['do'] == 'faq')
{
	$content = '
	<form method="post">
		<table>
			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Основные теги</h1></td>
			</tr>
			<tr>
				<td>[head=title] текст [/head]</td>
				<td>Текст между тегами используется как заголовок страницы.</td>
			</tr>
			<tr>
				<td>[head=keywords] текст [/head]</td>
				<td>Текст между тегами используется как ключевые слова страницы.</td>
			</tr>
			<tr>
				<td>[head=description] текст [/head]</td>
				<td>Текст между тегами используется как описание страницы.</td>
			</tr>
			<tr>
				<td>[head=meta] текст [/head]</td>
				<td>Текст между тегами выводиться с помощью {meta} (обычно в head).</td>
			</tr>
			<tr>
				<td>[login] текст [/login]</td>
				<td>Текст между тегами выводиться для авторизованных пользователей.</td>
			</tr>
			<tr>
				<td>[no-login] текст [/no-login]</td>
				<td>Текст между тегами выводиться для не авторизованных пользователей.</td>
			</tr>
			<tr>
				<td>[notice] текст [/notice]</td>
				<td>Текст между тегами выводиться если есть уведомление от сайта.</td>
			</tr>
			<tr>
				<td>[login-type=тип авторизации] текст [/login-type]</td>
				<td>Текст между тегами выводиться для определенного типа авторизации.<br>Варианты: usual (обычная авторизация), vk (авторизация через ВКонтакте)</td>
			</tr>
			<tr>
				<td>[replace_content] текст [/replace_content]</td>
				<td>Текст между тегами выводится если {content} теряет смысл</td>
			</tr>
			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Основные переменные</h1></td>
			</tr>
			<tr>
				<td>{content}</td>
				<td>Вывод непосредственно самого контекста на сайте, в общем основная колонка. Присутствие тега практически обязательно в шаблоне.</td>
			</tr>
			<tr>
				<td>{replace_content}</td>
				<td>Вывод информации если {content} не требуется.</td>
			</tr>
			<tr>
				<td>{notice}</td>
				<td>Уведомления сайта.</td>
			</tr>
			<tr>
				<td>{title}</td>
				<td>title главной страницы.</td>
			</tr>
			<tr>
				<td>{keywords}</td>
				<td>keywords главной страницы.</td>
			</tr>
			<tr>
				<td>{description}</td>
				<td>description главной страницы.</td>
			</tr>
			<tr>
				<td>{meta}</td>
				<td>Дополнительные meta теги.</td>
			</tr>
			<tr>
				<td>{id-profile}</td>
				<td>Id пользователя.</td>
			</tr>
			<tr>
				<td>{id_ref}</td>
				<td>Id владельца реф. ссылки.</td>
			</tr>
			<tr>
				<td>{home-title}</td>
				<td>Название сайта</td>
			</tr>
			<tr>
				<td>{main-keywords}</td>
				<td>Ключевые слова</td>
			</tr>
			<tr>
				<td>{main-description}</td>
				<td>Описание сайта</td>
			</tr>
			<tr>
				<td>{short_title}</td>
				<td>Краткое название сайта.</td>
			</tr>
			<tr>
				<td>{home-url}</td>
				<td>Ссылка на домашнюю страницу сайта</td>
			</tr>
			<tr>
				<td>{ref-link}</td>
				<td>Реферальная ссылка.</td>
			</tr>
			<tr>
				<td>{number-users}</td>
				<td>Количество пользователей</td>
			</tr>
			<tr>
				<td>{sold-games}</td>
				<td>Количество розданных игр</td>
			</tr>
			<tr>
				<td>{vk-profile}</td>
				<td>Id ВК профиля.</td>
			</tr>
			<tr>
				<td>{vk-reg}</td>
				<td>Ссылка для аутентификации через ВКонтакте</td>
			</tr>
			<tr>
				<td>{fb-reg}</td>
				<td>Ссылка для аутентификации через Facebook</td>
			</tr>
			<tr>
				<td>{free_key}</td>
				<td>Ключ</td>
			</tr>
			<tr>
				<td>{ref-num}</td>
				<td>Количество посещений владельца реф. ссылки</td>
			</tr>
			<tr>
				<td>{number-visits}</td>
				<td>Количество посещений реф. ссылки</td>
			</tr>
			<tr>
				<td>{ref_transitions}</td>
				<td>Кол-во посещение которые нужно собрать владельцу реф. ссылки</td>
			</tr>
			<tr>
				<td>{all-visit}</td>
				<td>Кол-во посещение которые нужно собрать пользователю</td>
			</tr>
			<tr>
				<td>{name-game}</td>
				<td>Название выбранной игры</td>
			</tr>
			<tr>
				<td>{description-game}</td>
				<td>Описание выбранной игры</td>
			</tr>
			<tr>
				<td>{name-game-ref}</td>
				<td>Название игры реферала</td>
			</tr>
			<tr>
				<td>{description-game-ref}</td>
				<td>Описание игры реферала</td>
			</tr>
			<tr>
				<td>{link-game}</td>
				<td>Название выбранной игры для ссылки</td>
			</tr>
			<tr>
				<td>{tasks}</td>
				<td>Список заданий</td>
			</tr>
			<tr>
				<td>{settings-profile}</td>
				<td>Настройки пользователя</td>
			</tr>
			<tr>
				<td>{number-links}</td>
				<td>Кол-во ссылок которые пользователь должен набрать</td>
			</tr>
			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Оформление заданий (tasks.tpl)</h1></td>
			</tr>
			<tr>
				<td>[job] текст [/job]</td>
				<td>Текст между тегами это шаблон вывода каждого задания.</td>
			</tr>
			<tr>
				<td>[link] текст [/link]</td>
				<td>Текст между тегами это шаблон вывода задания с реф ссылками.</td>
			</tr>
			<tr>
				<td>{i}</td>
				<td>Номер задания</td>
			</tr>
			<tr>
				<td>{additional-tasks}</td>
				<td>Описание задания</td>
			</tr>
			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Оформление настроек пользователя (profile-settings.tpl)</h1></td>
			</tr>
			<tr>
				<td>[link-tasks] текст [/link-tasks]</td>
				<td>Текст между тегами это шаблон вывода поля.</td>
			</tr>
			<tr>
				<td>[link] текст [/link]</td>
				<td>Текст между тегами выводит информацию если в настройках включены ссылки на реф. профиль.</td>
			</tr>
			<tr>
				<td>{i}</td>
				<td>Выводит номер поля.</td>
			</tr>
			<tr>
				<td>{specified-link}</td>
				<td>Выводит указанную ссылку в поле</td>
			</tr>
			<tr>
				<td>[vk] текст [/vk]</td>
				<td>Текст между тегами выводит информацию если в настройках включен ввод id ВК профиля.</td>
			</tr>
			<tr>
				<td colspan="2" class="title_t"><h1 class="center">Оформление листа (block.tpl)</h1></td>
			</tr>
			<tr>
				<td>{name-block}</td>
				<td>Название игры</td>
			</tr>
			<tr>
				<td>{block-num}</td>
				<td>Кол-во нужных переходов для получения данной игры</td>
			</tr>
			<tr>
				<td>{block-link}</td>
				<td>Название игры для ссылки</td>
			</tr>
		</table>
	</form>
	';
}

echo '
	<!DOCTYPE html>
	<html>
	<head>
		<meta charset="utf-8" />
		<title>Администраторская</title>
		<meta name="keywords" content="" />
		<meta name="description" content="" />
		<link href="admin-panel.css" rel="stylesheet">
		<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
	</head>

	<body onload="prettyPrint()">

	<div class="wrapper">

		<header class="header">
			<div class="header-title"><a href="index.php">Админ-панель</a><div class="right">by Xtoun</div></div>
			<a href="index.php"><div class="button_menu left">Основные настроки</div></a>
	
			<a href="index.php?do=change_login"><div class="button_menu left">Смена данных входа</div></a>

			<a href="index.php?do=exit"><div class="button_menu right">Выход</div></a>
			<div class="clr"></div>
		</header>

		<main class="content">
			'.$notice.'
			'.$content.'
		</main>
	</div>
	<footer class="footer center">
		&copy; <a href="" target="_blank">REF GOODS (by Xtoun)</a>
	</footer>
	</body>
	</html>
';

?>