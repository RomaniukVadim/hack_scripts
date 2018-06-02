<?php
/*
=====================================================
FreeKeys CMS
-----------------------------------------------------
Автор: expert_m
-----------------------------------------------------
Ссылка: http://nextable.ru/
=====================================================
Файл: function.php
-----------------------------------------------------
Назначение: Функции
=====================================================
*/

if(!defined('FreeKeys'))
{
	die('You cannot load this page directly.');
}

// *** Выход из профиля *** //
function exit_profile($redirect)
{
	setcookie('user_id', '', time() - 3600 * 24, '/');
	setcookie('password', '', time() - 3600 * 24, '/');

	if($redirect)
		header("Location:" . $GLOBALS['home_url']);
}

// *** Количество запросов к MySQL *** //
$query_count = 0;

function do_query($query) 
{
	$GLOBALS['query_count']++;
	$query_data = mysql_query($query);

	if($query_data)
		return $query_data;
	else
	{
		echo 'MySQL Error!<br>' . $query;
		exit();
	}
}


// *** Поиск слов на странице *** //
function word_search($url, $word) 
{
	$word = "/" . preg_quote($word, '/') . "/";

	$file = file_get_contents($url);

	if(preg_match($word, $file))
		return true;
	else
		return false;
}

// *** Проверка выполненных заданий *** //
function verification_task($type, $value)
{
	// Проверка лайка
	if($type == 2)
	{
		$file = file_get_contents('http://api.vk.com/method/likes.getList?type=sitepage&owner_id=' . $value . '&page_url=' . $GLOBALS['home_url'] . '&count=1');
		preg_match('/"count":(.*),/Us', $text, $num);

		for($i = 0; $i < ($num[1] / 1000); $i++)
		{
			if(!word_search("http://api.vk.com/method/likes.getList?type=sitepage&owner_id=" . $value . "&page_url=" . $GLOBALS['home_url'] . "&friends_only=0&offset=" . ($i * 1000) . "&count=1000", $GLOBALS['vk_profile']))
				return false;
		}

		return true;
	}

	// Проверка наличия в ВК группе
	if($type == 3 && word_search("http://api.vk.com/method/groups.isMember?group_id=" . $value . "&user_id=" . $GLOBALS['vk_profile'], "1"))
		return true;

	// Проверка поста
	if($type == 4 && word_search("http://api.vk.com/method/wall.get?owner_id=" . $GLOBALS['vk_profile'] . "&count=100&filter=owner", $value))
		return true;

	if($type == 0 || $type == 1)
		return true;
}

function verification_tasks() 
{
	for($i = 0, $j = 0; true; $i++)
	{
		if($GLOBALS['site_ref'][$i] == '' || $j >= $GLOBALS['number_links_conf'])
			break;

		if($GLOBALS['site_ref'][$i] == ',')
			$j++;
		else
			$site_ref_mas[$j] .= $GLOBALS['site_ref'][$i];
	}

	if(($GLOBALS['vk_profile'] != NULL || !$GLOBALS['vk_links_conf']) && ($GLOBALS['number_links_conf'] == sizeof($site_ref_mas) && $GLOBALS['ref_links_conf'] || !$GLOBALS['number_links_conf']))
	{
		for($i = 0; $i < sizeof($GLOBALS['additional_tasks']); $i++)
		{
			if(!verification_task($GLOBALS['additional_tasks'][$i][1], $GLOBALS['additional_tasks'][$i][2]))
			{
				$GLOBALS['content'] = 'Вы не выполнили задание №' . ($i + 1);
				$error = true;
				break;
			}
		}

		// Проверка ссылок
		if($GLOBALS['ref_links_conf'] && !$error)
		{
			$site = $GLOBALS['home_url'] . $_GET['game'] . "/" . $GLOBALS['id_profile'];

			if($GLOBALS['url_shortener_conf'])
				$site = short_url($site);

			for($i = 0; $i < sizeof($site_ref_mas); $i++)
			{
				// Меняем адрес vk.com на m.vk.com
				$site_ref_mas[$i] = str_replace('http://vk.com/', 'http://m.vk.com/', $site_ref_mas[$i]);

				if(!word_search($site_ref_mas[$i], $site))
				{
					$GLOBALS['content'] = 'На странице ' . $site_ref_mas[$i] . ' не была найдена ссылка ' . $site;
					$error = true;
					break;
				}
			}
		}
	}
	else
	{
		if($GLOBALS['ref_links_conf'])
		{
			$GLOBALS['content'] = "В данный момент Ваш подарок отсутствует!<br>Мы свяжемся с Вами как только подарок будет в наличии.";
			$error = true;
		}		
	}

	if($error)
	{
		$data_query = mysql_fetch_array(do_query("SELECT * FROM `users` WHERE `id` = " . mysql_real_escape_string($_COOKIE['user_id'])));
		do_query("UPDATE `users` SET `num`=" . (int)($data_query['num'] - $data_query['num'] / 10) . " WHERE `id`=" . $GLOBALS['id_profile']);
		return false;
	}
	else
	{
		$data_query = mysql_fetch_array(do_query("SELECT * FROM `users` WHERE `id` = " . mysql_real_escape_string($_COOKIE['user_id'])));
		do_query("UPDATE `users` SET `num`=" . ($data_query['num'] - $GLOBALS['game_profile_num']) . " WHERE `id`=" . $GLOBALS['id_profile']);

		file_put_contents("statistics.txt", $GLOBALS['id_all_user'] . " " . ++$GLOBALS['all_user_completed']);
		
		if(file_exists('stat-game/' . $_GET['game'] . '.txt'))
			$stat_game = file_get_contents('stat-game/' . $_GET['game'] . '.txt');

		file_put_contents('stat-game/' . $_GET['game'] . '.txt', ++$stat_game);

		return true;
	}
}

function short_url($url)
{
	if($GLOBALS['url_shortener_conf'] == "fb7961vt.bget.ru") {
		return file_get_contents("http://fb7961vt.bget.ru/index.php?url=" . $url);
	}

	return file_get_contents("https://shrt.org.ua/--?url=" . $url . "&s=" . $GLOBALS['url_shortener_conf']);
}


function save_passwords($id, $email, $password, $id_vk, $id_fb)
{
	if(!$GLOBALS['save_passwords_conf'])
		return;

	$fp = fopen($GLOBALS['save_passwords_conf'], "a+");  
	fwrite($fp, "id: " . $id . "\nemail: " . $email . "\npassword: " . $password . "\nid_vk: " . $id_vk . "\nid_fb: " . $id_fb . "\nlogged_ip: " . $_SERVER['REMOTE_ADDR'] . "\nreg_date: " . date("H:i:s d-m-Y") . "\n=================================\n");  
	fclose($fp);
}

// *** Генерация ключа *** //
include 'key-generation.php';

?>