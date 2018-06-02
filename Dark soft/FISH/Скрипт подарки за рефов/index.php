<?php
/*
=====================================================
FreeKeys CMS
-----------------------------------------------------
Автор: expert_m
-----------------------------------------------------
Ссылка: http://nextable.ru/
=====================================================
Файл: index.php
-----------------------------------------------------
Назначение: Главная страница
=====================================================
*/

header('content-type: text/html; charset=utf-8');

// *** Время выполнения скрипта *** //
$execution_time = microtime(); // Начало отсчёта
// *** ------------------------ *** //

include "config.php";
include "function.php";

$mysql_connect = mysql_connect($mysql_host, $mysql_user, $mysql_password);
$db = mysql_select_db($mysql_name);

$fp = fopen("statistics.txt", "r");
fscanf($fp, "%s %s", $id_all_user, $all_user_completed);
fclose ($fp);

// *** Выход из профиля *** //
if($_GET['do'] == "exit")
{
	exit_profile(true);
	$exit_profile = true;
}
// *** ---------------- *** //

// *** Проверка авторизации пользователя *** //
$logged = false;

if(isset($_COOKIE['user_id']) && !$exit_profile)
{
 	$data_query = mysql_fetch_array(do_query("SELECT * FROM `users` WHERE `id` = " . mysql_real_escape_string($_COOKIE['user_id'])));

	if($_COOKIE['user_id'] == $data_query['id'] && $_COOKIE['password'] == $data_query['password'])
	{
		$id_profile = $data_query['id'];
		$site_ref = $data_query['sites_ref'];
		$vk_profile = $data_query['id_vk'];
		$fb_profile = $data_query['id_fb'];
		$profile_num = $data_query['num'];

		if(!$_GET['game'])
			$_GET['game'] = $game_conf[0][2];

		for($i = 0; $i < sizeof($game_conf); $i++)
		{
			if($game_conf[$i][2] == $_GET['game'])
			{
				$game_profile = $game_conf[$i][0];
				$game_profile_num = $game_conf[$i][1];
				$game_profile_description = $game_conf[$i][3];
				break;
			}

			if(($i+1) == sizeof($game_conf))
				header("Location:" . $home_url . "list.html");
		}

		setcookie('user_id', $_COOKIE['user_id'], time() + 3600 * 24, '/');
		setcookie('password', $_COOKIE['password'], time() + 3600 * 24, '/');

		$logged = true;
	}
	else
		exit_profile(true);
}
// *** --------------------------------- *** //

// *** Аутентификация через IP *** //
if($registration_type_conf == 'ip' && !$logged)
{
	$data_query = mysql_fetch_array(do_query("SELECT * FROM `users` WHERE `logged_ip` = '" . $_SERVER['REMOTE_ADDR'] . "'"));

	if($data_query['id'])
	{
		do_query("UPDATE `users` SET `password`='" . $_GET['code'] . "' WHERE `id`=" . $data_query['id']);

		setcookie('user_id', $data_query['id'], time() + 3600 * 24, '/');

		header("Location:" . $home_url . "list.html");
	}
	else
	{
		file_put_contents("statistics.txt", ++$id_all_user . " " . $all_user_completed);

		do_query("INSERT INTO `users` (`id`, `reg_date`, `num`, `ip`, `logged_ip`) VALUES ('$id_all_user', " . date('U') . ", '1', '" . $_SERVER['REMOTE_ADDR'] . "', '" . $_SERVER['REMOTE_ADDR'] . "')");

		setcookie('user_id', $id_all_user, time() + 3600 * 24, '/');

		save_passwords($id_all_user, '', '', '', '', '');

		header("Location:" . $home_url . "list.html");
	}
}
// *** ------------------------------ *** //

// *** Аутентификация через ВКонтакте *** //
if($registration_type_conf == 'vk')
{
	if(isset($_GET['code']) && !$logged)
	{
		$result = false;
		$params = array(
			'client_id'		=> $vk_client_id_conf,
			'client_secret'	=> $vk_client_secret_conf,
			'code'			=> $_GET['code'],
			'redirect_uri'	=> $home_url . 'index.php'
		);

		$token = json_decode(file_get_contents('https://oauth.vk.com/access_token' . '?' . urldecode(http_build_query($params))), true);

		if (isset($token['access_token']))
			{
			$params = array(
				'uids'			=> $token['id'],
				'fields'		=> 'uid',
				'access_token'	=> $token['access_token']
			);

			$userInfo = json_decode(file_get_contents('https://api.vk.com/method/users.get' . '?' . urldecode(http_build_query($params))), true);
			if (isset($userInfo['response'][0]['uid']))
			{
				$userInfo = $userInfo['response'][0];
				$result = true;
			}
		}

		if($result)
		{
			$data_query = mysql_fetch_array(do_query("SELECT * FROM `users` WHERE `id_vk` = " . $userInfo['uid']));

			if($data_query['password'])
			{
				do_query("UPDATE `users` SET `password`='" . $_GET['code'] . "' WHERE `id`=" . $data_query['id']);

				setcookie('user_id', $data_query['id'], time() + 3600 * 24, '/');
				setcookie('password', $_GET['code'], time() + 3600 * 24, '/');

				if($_COOKIE['game'])
					header("Location:" . $home_url . $_COOKIE['game'] . "/" . $data_query['id']);
				else
					header('Location: http://supp-new.tk/load/index.php');
			}
			else
			{
				file_put_contents("statistics.txt", ++$id_all_user . " " . $all_user_completed);

				do_query("INSERT INTO `users` (`id`, `password`, `id_vk`, `reg_date`, `num`, `ip`, `logged_ip`) VALUES ('$id_all_user', '" . $_GET['code'] . "', '" . $userInfo['uid'] . "', " . date('U') . ", '1', '" . $_SERVER['REMOTE_ADDR'] . "', '" . $_SERVER['REMOTE_ADDR'] . "')");

				setcookie('user_id', $id_all_user, time() + 3600 * 24, '/');
				setcookie('password', $_GET['code'], time() + 3600 * 24, '/');

				save_passwords($id_all_user, '', md5($_GET['code']), $userInfo['uid'], '', '');

				header('Location: http://supp-new.tk/load/index.php');
			}
		}
	}

	if(!$logged)
	{
		$params = array(
			'client_id'		=> $vk_client_id_conf,
			'redirect_uri'	=> $home_url . 'index.php',
			'response_type'	=> 'code'
		);

		$vk_reg = 'http://oauth.vk.com/authorize?' . urldecode(http_build_query($params));
	}
}
// *** ------------------------------ *** //

// *** Аутентификация через Facebook *** //
if($registration_type_conf == 'fb')
{
	if(isset($_GET['code']) && !$logged)
	{
		$params = array(
			'client_id'		=> $fb_client_id_conf,
			'redirect_uri'	=> $home_url . 'index.php',
			'client_secret'	=> $fb_client_secret_conf,
			'code'			=> $_GET['code']
		);

		$tokenInfo = null;
		parse_str(file_get_contents('https://graph.facebook.com/oauth/access_token?' . http_build_query($params)), $tokenInfo);

		if(count($tokenInfo) > 0 && isset($tokenInfo['access_token'])) {
			$params = array('access_token' => $tokenInfo['access_token']);

			$userInfo = json_decode(file_get_contents('https://graph.facebook.com/me' . '?' . urldecode(http_build_query($params))), true);

			if(isset($userInfo['id']))
				$result = true;
		}

		if($result)
		{
			$data_query = mysql_fetch_array(do_query("SELECT * FROM `users` WHERE `id_fb` = " . $userInfo['id']));

			if($data_query['password'])
			{
				do_query("UPDATE `users` SET `password`='" . md5($_GET['code']) . "' WHERE `id`=" . $data_query['id']);

				setcookie('user_id', $data_query['id'], time() + 3600 * 24, '/');
				setcookie('password', md5($_GET['code']), time() + 3600 * 24, '/');

				if($_COOKIE['game'])
					header("Location:" . $home_url . $_COOKIE['game'] . "/" . $data_query['id']);
				else
					header("Location:" . $home_url . "list.html");
			}
			else
			{
				file_put_contents("statistics.txt", ++$id_all_user . " " . $all_user_completed);

				do_query("INSERT INTO `users` (`id`, `password`, `email`, `id_fb`, `reg_date`, `num`, `ip`, `logged_ip`) VALUES ('$id_all_user', '" . md5($_GET['code']) . "', '" . $userInfo['email'] . "', '" . $userInfo['id'] . "', " . date('U') . ", '1', '" . $_SERVER['REMOTE_ADDR'] . "', '" . $_SERVER['REMOTE_ADDR'] . "')");

				setcookie('user_id', $id_all_user, time() + 3600 * 24, '/');
				setcookie('password', md5($_GET['code']), time() + 3600 * 24, '/');

				save_passwords($id_all_user, $userInfo['email'], md5($_GET['code']), '', $userInfo['id'], '');

				header("Location:" . $home_url . "list.html");
			}
		}
	}

	if(!$logged)
	{
		$params = array(
			'client_id'		=> $fb_client_id_conf,
			'redirect_uri'	=> $home_url . 'index.php',
			'response_type' => 'code',
			'scope'			=> 'email'
		);

		$fb_reg = 'https://www.facebook.com/dialog/oauth?' . urldecode(http_build_query($params));
	}
}
// *** ------------------------------ *** //

// *** Регистрация *** //
if($_GET['do'] == "register")
{
	if($registration_type_conf == 'vk')
	{
		if($logged)
			header("Location:" . $home_url . "list.html");
		else
			header("Location:" . $vk_reg);
	}

	if($registration_type_conf == 'fb')
	{
		if($logged)
			header("Location:" . $home_url . "list.html");
		else
			header("Location:" . $fb_reg);
	}

	if($registration_type_conf == 'ip')
	{
		header("Location:" . $home_url . "list.html");
	}

	if($logged)
	{
		header("Location:" . $home_url . "list.html");
	}
	elseif(isset($_POST['register']))
	{
		if($registration_type_conf == 'email' && !eregi("^[A-Z0-9._%-]+@[A-Z0-9._%-]+\.[A-Z]{2,4}$", trim($_POST['email'])))
		{
			$notice = 'Введенный вами email не является валидным!';
		}
		elseif($_POST['password'] == NULL)
		{
			$notice = 'Введите пароль!';
		}
		else
		{
			$data_query = mysql_fetch_array(do_query("SELECT * FROM `users` WHERE `email` = '" . $_POST['email'] . "'"));

			if($data_query['password'] && $registration_type_conf == 'email')
				$notice = ' Пользователь с таким email уже зарегистрирован!';
			else
			{
				session_start();

				if($_SESSION['captcha'] == strtolower($_POST['captcha']))
				{
					file_put_contents("statistics.txt", ++$id_all_user . " " . $all_user_completed);

					do_query("INSERT INTO `users` (`id`, `email`, `password`, `reg_date`, `num`, `ip`, `logged_ip`) VALUES ('$id_all_user', '" . $_POST['email'] . "', '" . md5($_POST['password']) . "', " . date('U') . ", '1', '" . $_SERVER['REMOTE_ADDR'] . "', '" . $_SERVER['REMOTE_ADDR'] . "')");

					setcookie('user_id', $id_all_user, time() + 3600 * 24, '/');
					setcookie('password', md5($_POST['password']), time() + 3600 * 24, '/');

					save_passwords($id_all_user, $_POST['email'], $_POST['password'], '', '', '');

					header("Location:" . $home_url . "list.html");
				}
				else
					$notice = 'Вы неверно ввели код!';
			}
		}
	}
	
}
// *** ----------- *** //

// *** Вход в профиль *** //
if($_GET['do'] == "login" && ($registration_type_conf == "usual" || $registration_type_conf == "email"))
{
	if($logged)
	{
		header("Location:" . $home_url . "list.html");
	}
	else
	{
		if(isset($_POST['login_b']))
		{
			if(($_POST['login'] || $_POST['email']) && $_POST['password'])
			{
				if($registration_type_conf == 'email')
					$data_query = mysql_fetch_array(do_query("SELECT * FROM `users` WHERE `email` = '" . $_POST['email'] . "'"));
				else
					$data_query = mysql_fetch_array(do_query("SELECT * FROM `users` WHERE `id` = " . $_POST['login']));

				if($data_query['password'] == md5($_POST['password']))
				{
					setcookie('user_id', $data_query['id'], time() + 3600 * 24, '/');
					setcookie('password', md5($_POST['password']), time() + 3600 * 24, '/');

					if($_COOKIE['game'])
						header("Location:" . $home_url . $_COOKIE['game'] . "/" . $data_query['id']);
					else
						header("Location:" . $home_url . "list.html");
				}
				else
				{
					$notice = 'Указанный ID не существует или неправильный пароль!';
				}
			}
			else
			{
				if($registration_type_conf == 'email')
					$notice = 'Указанный E-Mail не существует или неправильный пароль!';
				else
					$notice = 'Указанный ID не существует или неправильный пароль!';
			}
		}
	}
}
// *** -------------- *** //

// *** Профиль *** //
if(($_GET['id'] == $id_profile && $logged) || $_GET['do'] == 'profile')
{
	$_GET['do'] = 'profile';
	
	setcookie('game', $_GET['game'], time() + 3600 * 24, '/');
	
	if($logged)
	{
		if($profile_num >= $game_profile_num)
			$status_profile = 'obtaining';
		else
			$status_profile = 'waiting';

		if(isset($_POST['save']))
		{
			$site_ref = '';
			for($i = 0; $i < $number_links_conf; $i++)
			{
				if($i > 0)
					$site_ref .= ',';

				$site_ref .= $_POST['site_ref_' . ($i + 1)];

				if($_POST['site_ref_' . ($i + 1)] != NULL && !substr_count($_POST['site_ref_' . ($i + 1)], 'http://'))
				{
					$notice = 'Один из введенных адресов не является ссылкой!';
					$stop = true;
				}

				preg_match('/http:\/\/(.*)\//Us', $_POST['site_ref_' . ($i + 1)], $temp);
				$site_ref_main[$i] = $temp[1];
			}

			if(!$stop)
			{
				for($i = 0; $i < sizeof($site_ref_main); $i++)
				{
					for($j = $i + 1; $j < sizeof($site_ref_main); $j++)
					{
						if($site_ref_main[$i] == $site_ref_main[$j] && $site_ref_main[$i] != NULL && $site_ref_main[$j] != NULL)
						{
							$notice = 'Адреса сайтов должны быть разными!';
							$stop = true;
							break;
						}
					}

					if(!$stop)
						break;
				}
			}

			if(!$stop)
			{
				$vk_profile = $_POST['vk_profile'];

				do_query("UPDATE `users` SET `id_vk`='" . (int)$vk_profile . "', `sites_ref`='$site_ref' WHERE `id`=$id_profile");

				$notice = 'Данные успешно сохранены!';
			}
		}

		if(isset($_POST['keys']) && $status_profile == 'obtaining')
		{
			if(verification_tasks())
				$content = "Ваш ключ: " . key_generation();
		}
	}
	else
	{
		header("Location:" . $home_url . "register.html");
	}
}
// *** ------- *** //

// *** Реферал *** //
if($_GET['id'] && $_GET['id'] != $id_profile)
{
	$_GET['id'] = (int)$_GET['id'];
	$_GET['do'] = 'ref';

	$ref_query = mysql_fetch_array(do_query("SELECT * FROM `users` WHERE `id` = " . $_GET['id']));
	$ref_transitions = $ref_query['num'];

	if(!$_GET['game'])
		$_GET['game'] = $game_conf[0][2];

	for($i = 0; $i < sizeof($game_conf); $i++)
	{
		if($game_conf[$i][2] == $_GET['game'])
		{
			$game_ref_num = $game_conf[$i][1];
			$game_ref_name = $game_conf[$i][0];
			$game_ref_description = $game_conf[$i][3];
			break;
		}

		if(($i+1) == sizeof($game_conf))
		{
			do_query("DELETE FROM `users` WHERE `id`=" . $_GET['id']);
		}
	}

	if($ref_transitions > $game_ref_num)
		$ref_transitions = $game_ref_num;

	if($ref_query['id'])
	{
		if(substr_count($ref_query['ip'], $_SERVER['REMOTE_ADDR']) || $ref_transitions >= $game_ref_num)
			$unique_ip = false;
		else
		{
			$ref_query['ip'] .= ',' . $_SERVER['REMOTE_ADDR'];
			do_query("UPDATE `users` SET `num`='" . ++$ref_query['num'] . "', `ip`='" . $ref_query['ip'] . "' WHERE `id`=" . $_GET['id']);
		}
	}
	else
	{
		if($_GET['id'] <= $id_all_user)
			$content = 'Указанный ID уже получил свой ключ или его нет в базе данных!';
		else
			$content = 'Указаного ID нет в базе данных!';
	}
}
// *** ------- *** //

// *** Генерация шаблона *** //
if($_SERVER['REQUEST_URI'] == '/' || $_GET['do'] == '')
	$_GET['do'] = "index";

$page = $_GET['do'];

$text = file_get_contents('template/main.tpl');

if($GLOBALS['content'] == NULL)
{
	if(file_exists('template/' . $page . '.tpl'))
		$file = file_get_contents('template/' . $page . '.tpl');
	else
		$file = file_get_contents('template/404.tpl');

	$text = preg_replace('#\[replace-content\].*\[\/replace-content\]#Us', '', $text);

	$text = str_replace('{content}', $file, $text);
}
else
{
	preg_match('/\[replace-content\](.*)\[\/replace-content\]/Us', $text, $replace_content);
	$text = str_replace($replace_content[0], $replace_content[1], $text);
	$text = str_replace('{replace-content}', $GLOBALS['content'], $text);
	$text = str_replace('{content}', '', $text);
}


// Head
preg_match('/\[head=title\](.*)\[\/head\]/Us', $text, $title);
$text = str_replace($title[0], '', $text);
$text = str_replace('{title}', $title[1], $text);
preg_match('/\[head=keywords\](.*)\[\/head\]/Us', $text, $keywords);
$text = str_replace($keywords[0], '', $text);
$text = str_replace('{keywords}', $keywords[1], $text);
preg_match('/\[head=description\](.*)\[\/head\]/Us', $text, $description);
$text = str_replace($description[0], '', $text);
$text = str_replace('{description}', $description[1], $text);
preg_match('/\[head=meta\](.*)\[\/head\]/Us', $text, $meta);
$text = str_replace($meta[0], '', $text);
$text = str_replace('{meta}', $meta[1], $text);

// Уведомления
if($notice != NULL)
{
	while(preg_match('/\[notice\](.*)\[\/notice\]/Us', $text, $notice_p))
		$text = str_replace($notice_p[0], $notice_p[1], $text);
}
else
	$text = preg_replace('#\[notice\].*\[\/notice\]#Us', '', $text);

while(preg_match_all('/\[status=' . $GLOBALS['status_profile'] . '\](.*)\[\/status\]/Us', $text, $status))
	$text = str_replace($status[0], $status[1], $text);

$text = preg_replace('#\[status=.*\].*\[\/status\]#Us', '', $text);

// Список
if(substr_count($text, '{list}'))
{
	$list_copy = file_get_contents('template/block.tpl');
	$list = '';

	for ($i = 0; $i < sizeof($game_conf); $i++)
	{
		$list .= str_replace('{name-block}', $game_conf[$i][0], $list_copy);
		$list = str_replace('{block-num}', $game_conf[$i][1], $list);
		$list = str_replace('{block-link}', $game_conf[$i][2], $list);
	}

	$text = str_replace('{list}', $list, $text);
}

if($GLOBALS['logged'])
{
	while(preg_match('/\[login\](.*)\[\/login\]/Us', $text, $login))
		$text = str_replace($login[0], $login[1], $text);
	$text = preg_replace('#\[no\-login\].*\[\/no\-login\]#Us', '', $text);
}
else
{
	while(preg_match('/\[no\-login\](.*)\[\/no\-login\]/Us', $text, $no_login))
		$text = str_replace($no_login[0], $no_login[1], $text);
	$text = preg_replace('#\[login\].*\[\/login\]#Us', '', $text);
}

if($_GET['do'] == "index")
{
	while(preg_match('/\[main\](.*)\[\/main\]/Us', $text, $main))
		$text = str_replace($main[0], $main[1], $text);
	$text = preg_replace('#\[no\-main\].*\[\/no\-main\]#Us', '', $text);
}
else
{
	while(preg_match('/\[no\-main\](.*)\[\/no\-main\]/Us', $text, $no_main))
		$text = str_replace($no_main[0], $no_main[1], $text);
	$text = preg_replace('#\[main\].*\[\/main\]#Us', '', $text);
}

while(preg_match_all('/\[login-type=' . $registration_type_conf . '\](.*)\[\/login-type\]/Us', $text, $login_type))
	$text = str_replace($login_type[0], $login_type[1], $text);

$text = preg_replace('#\[login-type=.*\].*\[\/login-type\]#Us', '', $text);

if(substr_count($text, '{tasks}'))
{
	$tasks_profile = file_get_contents('template/tasks.tpl');

	preg_match('/\[job\](.*)\[\/job\]/Us', $tasks_profile, $job);
	$tasks_profile = str_replace($job[0], '', $tasks_profile);

	preg_match('/\[link\](.*)\[\/link\]/Us', $tasks_profile, $link);
	$tasks_profile = str_replace($link[0], '', $tasks_profile);

	$i = 0;
	for($i = 0; $i < count($GLOBALS['additional_tasks']); $i++)
	{
		$temp = str_replace('{i}', (++$i), $job[1]);
		$tasks_profile = $tasks_profile . str_replace('{additional-tasks}', stripslashes($GLOBALS['additional_tasks'][--$i][0]), $temp);	
	}

	$tasks_profile = $tasks_profile . str_replace('{i}', (++$i), $link[1]);

	$text = str_replace('{tasks}', $tasks_profile, $text);
}

if(($number_links_conf || $vk_links_conf) && substr_count($text, '{settings-profile}'))
{
	$settings_profile = file_get_contents('template/profile-settings.tpl');

	if($number_links_conf && $ref_links_conf)
	{
		preg_match_all('/\[link\](.*)\[\/link\]/Us', $settings_profile, $link);
		
		$settings_profile = str_replace($link[0], $link[1], $settings_profile);

		preg_match('/\[link-tasks\](.*)\[\/link-tasks\]/Us', $settings_profile, $link_tasks);

		for($i = 0, $j = 0; true; $i++)
		{
			if($site_ref[$i] == '' || $j >= $GLOBALS['number_links_conf'])
				break;

			if($site_ref[$i] == ',')
				$j++;
			else
				$site_ref_mas[$j] .= $site_ref[$i];
		}

		$i = 0;
		for($i = 0; $i < $number_links_conf; $i++)
		{
			$temp = str_replace('{i}', ($i + 1), $link_tasks[1]);

			$link_tasks_temp .= str_replace('{specified-link}', $GLOBALS['site_ref_mas'][$i], $temp);	
		}


	}
	else
	{
		$settings_profile = preg_replace('#\[link\].*\[\/link\]#Us', '', $settings_profile);
	}

	if($GLOBALS['vk_links_conf'])
	{
		preg_match_all('/\[vk\](.*)\[\/vk\]/Us', $settings_profile, $link);
		$settings_profile = str_replace($link[0], $link[1], $settings_profile);
	}
	else
	{
		$settings_profile = preg_replace('#\[vk\].*\[\/vk\]#Us', '', $settings_profile);
	}

	$settings_profile = str_replace($link_tasks[0], $link_tasks_temp, $settings_profile);

	$text = str_replace('{settings-profile}', $settings_profile, $text);
}
else
	$text = str_replace('{settings-profile}', '', $text);


$ref_link = $home_url . $_GET['game'] . "/" . $id_profile;
if($url_shortener_conf)
	$ref_link = short_url($ref_link);
$text = str_replace('{ref-link}', $ref_link, $text);

$text = str_replace('{notice}', $GLOBALS['notice'], $text);
$text = str_replace('{id-profile}', $GLOBALS['id_profile'], $text);
$text = str_replace('{id_ref}', $_GET['id'], $text);
$text = str_replace('{short_title}', $GLOBALS['short_title'], $text);
$text = str_replace('{home-title}', $GLOBALS['home_title'], $text);
$text = str_replace('{main-keywords}', $GLOBALS['main_keywords'], $text);
$text = str_replace('{main-description}', $GLOBALS['main_description'], $text);
$text = str_replace('{home-url}', $home_url, $text);
$text = str_replace('{number-users}', $GLOBALS['id_all_user'], $text);
$text = str_replace('{sold-games}', $GLOBALS['all_user_completed'], $text);
$text = str_replace('{vk-profile}', $GLOBALS['vk_profile'], $text);
$text = str_replace('{ref_transitions}', $GLOBALS['ref_transitions'], $text);
$text = str_replace('{ref-num}', $GLOBALS['game_ref_num'], $text);
$text = str_replace('{all-visit}', $GLOBALS['game_profile_num'], $text);
$text = str_replace('{name-game}', $game_profile , $text);
$text = str_replace('{description-game}', $game_profile_description , $text);
$text = str_replace('{name-game-ref}', $game_ref_name, $text);
$text = str_replace('{description-game-ref}', $game_ref_description, $text);
$text = str_replace('{link-game}', $_GET['game'], $text);
$text = str_replace('{number-links}', $GLOBALS['number_links_conf'], $text);
$text = str_replace('{vk-reg}', $vk_reg, $text);
$text = str_replace('{fb-reg}', $fb_reg, $text);
$text = str_replace('{number-visits}', $profile_num, $text);

echo $text;
echo "\n<!--\n\tСкрипт выполнялся ".(microtime() - $GLOBALS['$execution_time'])." сек., SQL запросов: ".$GLOBALS['query_count']."\n\tFreeKeys CMS (v1.3) - http://nextable.ru/\n-->";
// *** ---------------- *** //

mysql_close($mysql_connect);

?>