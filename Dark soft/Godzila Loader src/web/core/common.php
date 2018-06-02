<?php
defined('CP') or die();
$syslang = 'ru';

if(empty($_GET['lang'])) { 
    if(!empty($_COOKIE['lang'])) { 
        $syslang = $_COOKIE['lang']; 
    } 
    else
        $syslang = 'en'; 
    
} 
else{ 
    $syslang = (string)$_GET['lang']; 
    setcookie('lang', $syslang, time() + 60*60*24*30); 

	if (!empty($_SERVER['HTTP_REFERER']))
    header("Location: ".$_SERVER['HTTP_REFERER']);
}


$lang['ru']['countrylist'] = array(
"AT" => "Австрия", "IT" => "Италия", "AU" => "Австралия", "ES" => "Испания", "DE" => "Германия", "GB" => "Великобритания", 
"NZ" => "Новая Зеландия", "US" => "США", "FR" => "Франция", "PT" => "Португалия", "JP" => "Япония", "CA" => "Канада",
"SE" => "Швеция", "BR" => "Бразилия", "TR" => "Турция", "NL" => "Нидерланды", "NO" => "Норвегия", "GR" => "Греция", 
"PL" => "Польша", "RU" => "Россия", "UA" => "Украина", "CN" => "Китай", "BY" => "Беларусь", "KZ" => "Казахстан", "MIX" => "Микс");
$lang['ru']['stats'] = "Статистика";
$lang['ru']['tasks'] = "Задачи";
$lang['ru']['logout'] = "Выход";
$lang['ru']['addtask'] = "Добавить задачу";
$lang['ru']['previous'] = "Назад";
$lang['ru']['next'] = "Продолжить";
$lang['ru']['checkAll'] = "Выделить все";
$lang['ru']['uncheckAll'] = "Очистить все";
$lang['ru']['uploadfile'] = "Загрузить файл";
$lang['ru']['settings'] = "Настройки";
$lang['ru']['amount'] = "Количество";
$lang['ru']['roundtheclock'] = "Круглосуточно";
$lang['ru']['selectdays'] = "В определенные дни";
$lang['ru']['monday'] = "Пн.";
$lang['ru']['tuesday'] = "Вт.";
$lang['ru']['wednesday'] = "Ср.";
$lang['ru']['thursday'] = "Чт.";
$lang['ru']['friday'] = "Пт.";
$lang['ru']['saturday'] = "Сб.";
$lang['ru']['sunday'] = "Вс.";
$lang['ru']['dbusername'] = "DB логин";
$lang['ru']['dbpassword'] = "DB пароль";
$lang['ru']['dbhost'] = "DB хост";
$lang['ru']['dbname'] = "База данных";
$lang['ru']['username'] = "Логин";
$lang['ru']['password'] = "Пароль";
$lang['ru']['leaveblank'] = "Оставьте пустым чтобы не менять";
$lang['ru']['savesettings'] = "Сохранить настройки";
$lang['ru']['exitnosave'] = "Выйти без сохранения";
$lang['ru']['plzwait'] = "Пожалуйста, подождите";
$lang['ru']['lastbots'] = "Последние 5 ботов";
$lang['ru']['unlimited'] = "Без ограничений";
$lang['ru']['hour'] = "час";
$lang['ru']['day'] = "сутки";
$lang['ru']['total'] = "всего";
$lang['ru']['taskadded'] = "Задача добавлена";
$lang['ru']['countries'] = "Страны";
$lang['ru']['stop'] = "Стоп";
$lang['ru']['start'] = "Старт";
$lang['ru']['delete'] = "Удалить";
$lang['ru']['time'] = "Время";
$lang['ru']['file'] = "Файл";
$lang['ru']['os'] = "ОС";
$lang['ru']['all'] = "Все";
$lang['ru']['worktime'] = "В рабочее время";
$lang['ru']['remalltasks'] = "Вы действительно хотите удалить все задачи?";
$lang['ru']['cancel'] = "Отмена";
$lang['ru']['yes'] = "Да";
$lang['ru']['delalltaskbtn'] = "Удалить все задачи";
$lang['ru']['options'] = "Параметры";
$lang['ru']['file'] = "Файл";
$lang['ru']['action'] = "Действие";
$lang['ru']['status'] = "Статус";
$lang['ru']['date'] = "Дата";
$lang['ru']['inactive'] = "Не активный";
$lang['ru']['active'] = "Активный";
$lang['ru']['passwordstrength'] = "Надежность пароля";
$lang['ru']['enterpassword'] = "Введите пароль";
$lang['ru']['database'] = "База данных";
$lang['ru']['authorization'] = "Авторизация";
$lang['ru']['controlpanel'] = "Панель управления";
$lang['ru']['timezone'] = "Часовой пояс";
$lang['ru']['colorscheme'] = "Цветовая схема(тема)";
$lang['ru']['day1'] = "День";
$lang['ru']['night'] = "Ночь";
$lang['ru']['notification'] = "Уведомления";
$lang['ru']['on'] = "Включено";
$lang['ru']['off'] = "Выключено";
$lang['ru']['osstr'] = "Операционные системы";
$lang['ru']['general'] = "Общая";
$lang['ru']['remallstats'] = "Вы действительно хотите очистить статистику?";
$lang['ru']['nodata'] = "Нет данных";
$lang['ru']['gueststats'] = "Гостевая статистика";
$lang['ru']['on'] = "Вкл";
$lang['ru']['off'] = "Выкл";
$lang['ru']['resident'] = "Установка в систему(резидент)";
$lang['ru']['opennewwindow'] = "открыть в новом окне";
$lang['ru']['time'] = "Время";
$lang['ru']['generator'] = "DGA генератор";
$lang['ru']['domain'] = "Домен";
$lang['ru']['build'] = "Создать";
$lang['ru']['perweek'] = "за неделю";
$lang['ru']['perday'] = "за день";
$lang['ru']['newbots'] = "Только новые";
$lang['ru']['update'] = "Обновление";
$lang['ru']['save'] = "Сохранить";
$lang['ru']['or'] = "или";
$lang['ru']['aupdtsk'] = "Автоматически обновлять задачу";
$lang['ru']['interval'] = "Интервал";
$lang['ru']['bots'] = "Боты";
$lang['ru']['country'] = "Страна";
$lang['ru']['timeadd'] = "Время добавления";
$lang['ru']['os'] = "ОС";
$lang['ru']['nobotsnomoney'] = "Ботов пока нет...";
$lang['ru']['archos'] = "Архитектуры ОС";
$lang['ru']['logs'] = "Логи";
$lang['ru']['modules'] = "Плагины";
$lang['ru']['to'] = "до";
$lang['ru']['searchstr'] = "Искомая строка";
$lang['ru']['search'] = "Поиск";
$lang['ru']['downloadlogs'] = "Скачать все логи одним файлом";
$lang['ru']['used'] = "занято";
$lang['ru']['totallogs'] = "Всего логов";
$lang['ru']['spreader'] = "Распространение";
$lang['ru']['noinfected'] = "Заражений пока нет...";
$lang['ru']['residentmodule'] = "Резидентный модуль";
$lang['ru']['nonresidentmodule'] = "Нерезидентный модуль";
$lang['ru']['edit'] = "Изменить";
$lang['ru']['modulename']['1'] = "Распространение через USB FLASH, Removable HDD, Network shares";
$lang['ru']['modulename']['2'] = "Клавиатурный шпион, граббинг буфера обмена";

$lang['en']['countrylist'] = array("AT" => "Austria", "IT" => "Italy", "AU" => "Australia", "ES" => "Spain", "DE" => "Germany", "GB" => "United Kingdom",
"NZ" => "New Zealand", "US" => "United States", "FR" => "France", "PT" => "Portugal", "JP" => "Japan", "CA" => "Canada",
"SE" => "Sweden", "BR" => "Brazil", "TR" => "Turkey", "NL" => "Netherlands", "NO" => "Norway", "GR" => "Greece",
"PL" => "Poland", "RU" => "Russia", "UA" => "Ukraine", "CN" => "China", "BY" => "Belarus", "KZ" => "Kazakhstan", "MIX" => "MIX");
$lang['en']['stats'] = "Stats";
$lang['en']['tasks'] = "Tasks";
$lang['en']['logout'] = "Logout";
$lang['en']['addtask'] = "Add task";
$lang['en']['previous'] = "Previous";
$lang['en']['next'] = "Next";
$lang['en']['checkAll'] = "Select all";
$lang['en']['uncheckAll'] = "Clear all";
$lang['en']['uploadfile'] = "Upload file";
$lang['en']['settings'] = "Settings";
$lang['en']['amount'] = "Amount";
$lang['en']['roundtheclock'] = "Round the clock";
$lang['en']['selectdays'] = "On select days";
$lang['en']['monday'] = "MO";
$lang['en']['tuesday'] = "TU";
$lang['en']['wednesday'] = "WE";
$lang['en']['thursday'] = "TH";
$lang['en']['friday'] = "FR";
$lang['en']['saturday'] = "SA";
$lang['en']['sunday'] = "SU";
$lang['en']['dbusername'] = "Database Username";
$lang['en']['dbpassword'] = "Database Password";
$lang['en']['dbhost'] = "Database Host";
$lang['en']['dbname'] = "Database Name";
$lang['en']['username'] = "Username";
$lang['en']['password'] = "Password";
$lang['en']['leaveblank'] = "Leave blank to no change";
$lang['en']['savesettings'] = "Save Settings";
$lang['en']['exitnosave'] = "Exit Without Saving";
$lang['en']['plzwait'] = "Please wait";
$lang['en']['lastbots'] = "Last 5 bots";
$lang['en']['unlimited'] = "Unlimited";
$lang['en']['hour'] = "hour";
$lang['en']['day'] = "day";
$lang['en']['total'] = "total";
$lang['en']['taskadded'] = "Task added";
$lang['en']['countries'] = "Countries";
$lang['en']['stop'] = "Stop";
$lang['en']['start'] = "Start";
$lang['en']['delete'] = "Delete";
$lang['en']['time'] = "Time";
$lang['en']['file'] = "File";
$lang['en']['os'] = "OS";
$lang['en']['all'] = "ALL";
$lang['en']['worktime'] = "At work time";
$lang['en']['remalltasks'] = "Do you really want remove all tasks?";
$lang['en']['cancel'] = "Cancel";
$lang['en']['yes'] = "Yes";
$lang['en']['delalltaskbtn'] = "Delete all tasks";
$lang['en']['options'] = "Options";
$lang['en']['file'] = "File";
$lang['en']['action'] = "Action";
$lang['en']['status'] = "Status";
$lang['en']['date'] = "Data";
$lang['en']['inactive'] = "Inactive";
$lang['en']['active'] = "Active";
$lang['en']['passwordstrength'] = "Password Strength";
$lang['en']['enterpassword'] = "Enter Password";
$lang['en']['database'] = "Database";
$lang['en']['authorization'] = "Authorization";
$lang['en']['controlpanel'] = "Control panel";
$lang['en']['timezone'] = "Time zone";
$lang['en']['colorscheme'] = "Color scheme(theme)";
$lang['en']['day1'] = "Day";
$lang['en']['night'] = "Night";
$lang['en']['notification'] = "Notification";
$lang['en']['on'] = "On";
$lang['en']['off'] = "Off";
$lang['en']['osstr'] = "Operating System";
$lang['en']['general'] = "General";
$lang['en']['remallstats'] = "Do you really want clear stats?";
$lang['en']['nodata'] = "no data";
$lang['en']['gueststats'] = "Guest stats";
$lang['en']['on'] = "On";
$lang['en']['off'] = "Off";
$lang['en']['resident'] = "Install in system(Resident)";
$lang['en']['opennewwindow'] = "open in new window";
$lang['en']['time'] = "Time";
$lang['en']['generator'] = "DGA generator";
$lang['en']['domain'] = "Domain";
$lang['en']['build'] = "Create";
$lang['en']['perweek'] = "per week";
$lang['en']['perday'] = "per day";
$lang['en']['newbots'] = "Only new bots";
$lang['en']['update'] = "Update";
$lang['en']['save'] = "Save";
$lang['en']['or'] = "or";
$lang['en']['aupdtsk'] = "Auto update task";
$lang['en']['interval'] = "Interval";
$lang['en']['bots'] = "Bots";
$lang['en']['country'] = "Country";
$lang['en']['timeadd'] = "Time add";
$lang['en']['os'] = "OS";
$lang['en']['nobotsnomoney'] = "No bots...";
$lang['en']['archos'] = "OS Architecture";
$lang['en']['logs'] = "Logs";
$lang['en']['modules'] = "Plugins";
$lang['en']['to'] = "to";
$lang['en']['searchstr'] = "search string";
$lang['en']['search'] = "Search";
$lang['en']['downloadlogs'] = "Download all logs in one file";
$lang['en']['used'] = "used";
$lang['en']['totallogs'] = "Total logs";
$lang['en']['spreader'] = "Spreader";
$lang['en']['noinfected'] = "Are no new infections ...";
$lang['en']['residentmodule'] = "Resident module";
$lang['en']['nonresidentmodule'] = "Non resident module";
$lang['en']['edit'] = "Change";
$lang['en']['modulename']['1'] = "Spreader via USB FLASH, removable HDD, network shares";
$lang['en']['modulename']['2'] = "Keylogger, clipboard grabber";

define('SID', session_id());

function logout()
{
    unset($_SESSION['uid']); 
    die(header('Location: ' . $_SERVER['PHP_SELF']));
}


function login($database, $username, $password)
{
	$password = hash('sha256', $password);
    $result = mysqli_query($database, "SELECT * FROM `users` WHERE `username`='$username' AND `password`='$password';") or die(mysqli_error($database)); 


    $USER = mysqli_fetch_array($result, 1); 

    if (isset($USER)) {
        $_SESSION = @array_merge($_SESSION, $USER); 
        mysqli_query($database, "UPDATE `users` SET `sid`='" . SID . "' WHERE `uid`='" . $USER['uid'] . "';");
		
        return true;
    } else {
        return false;
    }
}

function check_user($database, $uid)
{
    $result = mysqli_query($database, "SELECT `sid` FROM `users` WHERE `uid`='$uid';") or die(mysqli_error($database));
    $sid = $result->fetch_object()->sid;
	mysqli_free_result($result);
    return $sid == SID ? true : false;
}

if (!empty($_SESSION['uid'])) {
    define('USER_LOGGED', true);
	 $UserName = $_SESSION['username'];
	 $UserID = $_SESSION['uid'];
} else {
    define('USER_LOGGED', false);
}
function check_login($database)
{
	if (isset($_POST['login'])) {

        if (get_magic_quotes_gpc()) {
            $_POST['user'] = stripslashes($_POST['user']);
            $_POST['password'] = stripslashes($_POST['password']);
        }
        $user = mysqli_real_escape_string($database, $_POST['user']);
        $pass = mysqli_real_escape_string($database, $_POST['password']);

        if (login($database, $user, $pass)) {
            header('Refresh: 0');
        }
    
	}

}
if (isset($_GET['logout'])) {
	
    logout();
}


function pagination($item_count, $limit, $link)
{
	if(!empty($_GET["page"])){ 
		$page_number = filter_var($_GET["page"], FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_HIGH); 
		if(!is_numeric($page_number))die('Invalid page number!');
	}else
		$page_number = 1; 

	$link = preg_replace('/&page=\d*/', "", $link);
	
       $page_count = ceil($item_count/$limit);
       $current_range = array(($page_number-2 < 1 ? 1 : $page_number-2), ($page_number+2 > $page_count ? $page_count : $page_number+2));

       $previous_page = '<li'.($page_number > 1 ? '' : ' class="btn disabled"').'><a  href="'.$link.($page_number > 1 ? "&page=".($page_number-1) : '').'" aria-label="Previous"><span aria-hidden="true">&laquo;</span></a></li> ';
       $next_page =  '<li'.($page_number < $page_count ? '' : ' class="btn disabled"').'><a href="'.$link.($page_number < $page_count ? "&page=".($page_number+1) : '').'" aria-label="Next"><span aria-hidden="true">&raquo;</span></a></li>';

       // Display pages that are in range
	   $pages = '';
       for ($x = $current_range[0];$x <= $current_range[1]; ++$x)
               $pages .= '<li'.($x == $page_number ? ' class="active"' : '').'><a href="'.$link."&page=".$x.'">'.$x.($x == $page_number ? ' <span class="sr-only">(current)</span>' : '').'</a></li>';


       if ($page_count > 1)
               return '<nav><ul class="pagination"> '.$previous_page.$pages.$next_page.'</ul></nav>';
}


function LDRfilesize($file)
{
	
	if(file_exists("./files/".$file))
	{
		$sz = 'BKMGTP';
		$bytes = filesize("./files/".$file);
		$factor = floor((strlen($bytes) - 1) / 3);
		return sprintf("%.1f", $bytes / pow(1024, $factor)) . @$sz[$factor];
	}else return "<b style=\"color:red;\">file not found</b>";
	
}
function formatOffset($offset) {
        $hours = $offset / 3600;
        $remainder = $offset % 3600;
        $sign = $hours > 0 ? '+' : '-';
        $hour = (int) abs($hours);
        $minutes = (int) abs($remainder / 60);

        if ($hour == 0 AND $minutes == 0) {
            $sign = ' ';
        }
        return 'GMT' . $sign . str_pad($hour, 2, '0', STR_PAD_LEFT) 
                .':'. str_pad($minutes,2, '0');

}

function strip_data($text)
{
    if (!is_array($text)) {
        $text = substr($text, 0, strlen($text));

        $text = preg_replace("/[^a-zA-ZА-Яа-я0-9 @.,-=+\s]/u", "", $text);
        $text = preg_replace('/([^\s]{40})/', "$1 ", $text);

        $text = trim(strip_tags($text));
        $text = htmlspecialchars($text);
        $text = mysql_escape_string($text);
        $quotes = array("\x27", "\x22", "\x60", "\t", "\n", "\r", "*", "%", "<", ">", "?", "!");
        $goodquotes = array("-", "+", "#");
        $repquotes = array("\-", "\+", "\#");

        $text = str_replace($quotes, '', $text);
        $text = str_replace($goodquotes, $repquotes, $text);
        $text = ereg_replace(" +", " ", $text);
        return $text;
    }

    return "_SQL_";
}

function BuildLangURL($langtype)
{
	$vars = explode('&', $_SERVER['QUERY_STRING']);

	$final = array();

	if(!empty($vars)) {
		foreach($vars as $var) {
			$parts = explode('=', $var);

			$key = $parts[0];
			$val = !empty($parts[1]) == 1 ? $parts[1] : "";

			if(!array_key_exists($key, $final) && !empty($val) && $key != "lang")
				$final[$key] = $val;
			if(!empty($langtype))
				$final["lang"] = $langtype;
		}
		echo "?".http_build_query($final);
	}
}

?>
