<?php if(!defined('__CP__'))die();

define('LNG_TITLE',          'CP');
define('LNG_YES',            'Да');
define('LNG_NO',             'Нет');
define('LNG_ACCESS_DEFINED', 'Доступ запрещен.');
define('LNG_ACTION_APPLY',   '&#62;&#62;');

//Форматы данных.
define('LNG_FORMAT_DATE',   'd.m.Y');
define('LNG_FORMAT_TIME',   'H:i:s');
define('LNG_FORMAT_DT',     'd.m.Y H:i:s');
define('LNG_FORMAT_NOTIME', '--:--:--');

//Список ботнетов.
define('LNG_BOTNET_ALL',   '[Все]');
define('LNG_BOTNET_APPLY', '&#62;&#62;');

//Список страниц.
define('LNG_PAGELIST_TITLE', 'Страницы:');
define('LNG_PAGELIST_NEXT',  'Вперед');
define('LNG_PAGELIST_PREV',  'Назад');
define('LNG_PAGELIST_FIRST', '&#171;');
define('LNG_PAGELIST_LAST',  '&#187;');

//Меню действий.
define('LNG_MBA_SEPARATOR',         '--------------------------------------');

define('LNG_MBA_FULLINFO',        'Полная информация');
define('LNG_MBA_FULLINFOSS',      'Полная информация + скриншот');
define('LNG_MBA_TODAY_DBREPORTS', 'Отчеты за сегодня');
define('LNG_MBA_WEEK_DBREPORTS',  'Отчеты за последние 7 дней');
define('LNG_MBA_ALL_DBREPORTS',   'Все отчёты');
define('LNG_MBA_FILES',           'Файлы');
define('LNG_MBA_COOKIES',         'Cookies');

define('LNG_MBA_REMOVE',          'Удалить из базы данных');
define('LNG_MBA_REMOVE_REPORTS',  'Удалить из базы данных, включая отчеты');

define('LNG_MBA_PORT_SOCKS',      'Проверить сокс');
define('LNG_MBA_NEWSCRIPT',       'Создать команду');

define('LNG_BA_FULLINFO_TITLE',       'Полная информация о ботах');
define('LNG_BA_FULLINFO_EMPTY',       '-- Информация отсутствует --');
define('LNG_BA_FULLINFO_BOTID',       'Bot ID:');
define('LNG_BA_FULLINFO_BOTID_FAV',   'Make Favorite');
define('LNG_BA_FULLINFO_BOTID_FAV_UN','Clear Favorite');
define('LNG_BA_FULLINFO_BOTNET',      'Ботнет:');
define('LNG_BA_FULLINFO_FLAGS',       'Флаги:');
define('LNG_BA_FULLINFO_VERSION',     'Версия:');
define('LNG_BA_FULLINFO_OS',          'Версия ОС:');
define('LNG_BA_FULLINFO_OSLANG',      'Язык ОС:');
define('LNG_BA_FULLINFO_TIMEBIAS',    'GMT:');
define('LNG_BA_FULLINFO_COUNTRY',     'Страна:');
define('LNG_BA_FULLINFO_WHOIS',       'Whois:');
define('LNG_BA_FULLINFO_IPV4',        'IP:');
define('LNG_BA_FULLINFO_LATENCY',     'Лаг:');
define('LNG_BA_FULLINFO_TCPPORT_S1',  'Socks5:');
define('LNG_BA_FULLINFO_TFIRST',      'Время первого отчета:');
define('LNG_BA_FULLINFO_TLAST',       'Время последнего отчета:');
define('LNG_BA_FULLINFO_TONLINE',     'Время в онлайн:');
define('LNG_BA_FULLINFO_NEW',         'В списке новых ботов:');
define('LNG_BA_FULLINFO_USED',        'В списке использованных:');
define('LNG_BA_FULLINFO_COMMENT',     'Комментарий:');
define('LNG_BA_FULLINFO_SCREENSHOT',  'Скриншот:');
define('LNG_BA_FULLINFO_ACTION_SAVE', 'Сохранить');

define('LNG_BA_FULLINFO_WEBINJECTS_HISTORY',            'История WebInjects');
define('LNG_BA_FULLINFO_WEBINJECTS_TH_BUNDLE',          'Кампания');
define('LNG_BA_FULLINFO_WEBINJECTS_TH_LOADED',          'Загружен');
define('LNG_BA_FULLINFO_WEBINJECTS_TH_ERRORS',          'Ошибки');
define('LNG_BA_FULLINFO_WEBINJECTS_PENDING',            'ожидание');

define('LNG_BA_REMOVE_TITLE',    'Удаление ботов из БД');
define('LNG_BA_REMOVE_Q1',       'Вы действительно хотите удалить выбранных ботов из БД (%u шт.)?');
define('LNG_BA_REMOVE_Q2',       'Вы действительно хотите удалить выбранных ботов из БД (%u шт.), включая отчеты?');
define('LNG_BA_REMOVE_ABORTED',  'Удаление отменено пользователем.');
define('LNG_BA_REMOVE_REMOVED',  'Удалено %u записей.');
define('LNG_BA_REMOVE_FREMOVED', 'Удалено.');
define('LNG_BA_REMOVE_FERROR',   'ОШИБКА.');

define('LNG_BA_PORT_SOCKS_TITLE',     'Проверка соксов');
define('LNG_BA_PORT_SOCKS_CHECKING',  'ПРОВЕРКА...');
define('LNG_BA_PORT_SOCKS_SUCCESS',   'ДОСТУПЕН');
define('LNG_BA_PORT_SOCKS_FAILED',    'НЕ ДОСТУПЕН');
define('LNG_BA_PORT_SOCKS_ERROR',     'ОШИБКА!');

//Информация.
define('LNG_INFO',          'Информация:');
define('LNG_INFO_USERNAME', 'Пользователь:');
define('LNG_INFO_DATE',     'GMT дата:');
define('LNG_INFO_TIME',     'GMT время:');

//Главное меню.
define('LNG_MM_STATS',          'Статистика:');
define('LNG_MM_STATS_MAIN',     'Общая');
define('LNG_MM_STATS_OS',       'ОС');
define('LNG_MM_STATS_SOFT',     'Установленный Софт');

define('LNG_MM_BOTNET',         'Ботнет:');
define('LNG_MM_BOTNET_BOTS',    'Боты');
define('LNG_MM_BOTNET_WEBINJECTS',    'Веб-Инжекты');
define('LNG_MM_BOTNET_SCRIPTS', 'Скрипты');
define('LNG_MM_BOTNET_SOCKS',   'SOCKS');
define('LNG_MM_BOTNET_VNC',     'VNC');
define('LNG_MM_BOTNET_TOKENSPY','TokenSpy_DevelMode');

define('LNG_MM_REPORTS',        'Отчеты:');
define('LNG_MM_REPORTS_DB',     'Поиск в базе данных');
define('LNG_MM_REPORTS_NEUROSTAT',     'Нейромодель');
define('LNG_MM_REPORTS_FAV',    'Избранные отчёты');
define('LNG_MM_REPORTS_FILES',  'Поиск в файлах');
define('LNG_MM_REPORTS_IMAGES', 'Просмотр скриншотов');
define('LNG_MM_REPORTS_VIDEOS', 'Просмотр видео');
define('LNG_MM_REPORTS_CMDLIST','CMD Парсер');
define('LNG_MM_REPORTS_DOMAINS','Ссылки');
define('LNG_MM_REPORTS_BALGRABBER',     'Balance Grabber');
define('LNG_MM_REPORTS_JN',     'Jabber notifier');
define('LNG_MM_REPORTS_ACCPARSE', 'Аккаунт-парсер');

define('LNG_MM_SYSTEM',         'Система:');
define('LNG_MM_SYSTEM_INFO',    'Информация');
define('LNG_MM_SYSTEM_OPTIONS', 'Параметры');
define('LNG_MM_SYSTEM_USER',    'Пользователь');
define('LNG_MM_SYSTEM_USERS',   'Пользователи');

define('LNG_MM_SERVICES',       'Сервисы');
define('LNG_MM_SERVICE_NOTES',  'Заметки');
define('LNG_MM_SERVICE_CRYPT',  'Крипт exe');
define('LNG_MM_SERVICE_IFRAMER', 'FTP iframer');
define('LNG_MM_SERVICE_FILEHUNTER', 'Файл Хантер');
define('LNG_MM_SERVICE_MAILER',                 'Mailer');

define('LNG_MM_LOGOUT',         'Выход');

//Типы отчетов.
define('LNG_BLT_UNKNOWN',               'Неизвестный формат');
define('LNG_BLT_COOKIES',               'Кукисы браузеров');
define('LNG_BLT_FILE',                  'Файл');
define('LNG_BLT_DEBUG',                 'Debug');
define('LNG_BLT_HTTPX_REQUEST',         'HTTP или HTTPS запрос');
define('LNG_BLT_HTTP_REQUEST',          'HTTP запрос');
define('LNG_BLT_HTTPS_REQUEST',         'HTTPS запрос');
define('LNG_BLT_LUHN10_REQUEST',        'CC запрос');
define('LNG_BLT_LOGIN_FTP',             'FTP-данные');
define('LNG_BLT_LOGIN_POP3',            'POP3-данные');
define('LNG_BLT_FILE_SEARCH',           'Найденные файлы');
define('LNG_BLT_GRABBED_X',             'Любые захваченные данные');
define('LNG_BLT_GRABBED_UI',            'Захваченные данные [UI]');
define('LNG_BLT_GRABBED_HTTP',          'Захваченные данные [HTTP(S)]');
define('LNG_BLT_GRABBED_WSOCKET'  ,     'Захваченные данные [WinSocket]');
define('LNG_BLT_GRABBED_FTPSOFTWARE',   'Захваченные данные [FTP-клиент]');
define('LNG_BLT_GRABBED_EMAILSOFTWARE', 'Захваченные данные [E-mail]');
define('LNG_BLT_GRABBED_OTHER',         'Захваченные данные [Other]');
define('LNG_BLT_GRABBED_BALANCE',       'Баланс Граббер');
define('LNG_BLT_COMMANDLINE_RESULT',    'Результат CMD команды');
define('LNG_BLT_ANALYTICS_SOFTWARE',    'Информация об установленных продуктах');
define('LNG_BLT_ANALYTICS_FIREWALL',    'Информация об установленном фаерволе');
define('LNG_BLT_ANALYTICS_ANTIVIRUS',   'Информация об установленном антивирусе');
define('LNG_BLT_KEYLOGGER',             'Кейлоггер');
define('LNG_BLT_FLASHINFECT',           'FlashInfect');

define('LNG_HINT_CONTEXT_MENU',		'<div class="hint context-menu-hint">Подсказка: используй правую кнопку мыши для вызова контекстного меню</div>');

define('LNG_FLASHMSG_MUST_BE_WRITABLE',             ':name должна существовать и быть доступной на запись!');
define('LNG_FLASHMSG_READ_FAILED',                  'Ошибка чтения из :name!');
define('LNG_FLASHMSG_WRITE_FAILED',                 'Ошибка записи в :name!');

define('LNG_NO_RESULTS',                            'Ничего не найдено');
define('LNG_LOAD_MORE',                             'Загрузить ещё');
