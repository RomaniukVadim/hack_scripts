<?php
define('LNG_AJAX_MAILGRABBER',                                              'Mail Grabber');
define('LNG_AJAX_SELFTEST',                                                 'Авто-тест');
define('LNG_FLASHMSG_NOT_CONFIGURED',                                       'Не указан URL к mailer-скрипту!');

define('LNG_BTN_DOWNLOAD_SCRIPT',                                           '[ Скачать скрипт ]');
define('LNG_BTN_CONFIG',                                                    '[ Конфиг ]');

define('LNG_SVC_MAIL_HISTORY',                                              'История');
define('LNG_SVC_MAIL_HISTORY_TH_DATE',                                      'Дата');
define('LNG_SVC_MAIL_HISTORY_TH_BOTID',                                     'BotID');
define('LNG_SVC_MAIL_HISTORY_TH_COMMENT',                                   'Коммент');
define('LNG_SVC_MAIL_HISTORY_TH_SUBJECT',                                   'Тема');
define('LNG_SVC_MAIL_HISTORY_TH_ADDRESSES',                                 'Отправлено');
define('LNG_SVC_MAIL_HISTORY_TH_STATUS',                                    'Состояние');
define('LNG_SVC_MAIL_HISTORY_TH_BOTNET',                                    'Ботнет');
define('LNG_SVC_MAIL_HISTORY_TH_NEWBOTS',                                   'Новых ботов');

define('LNG_SVC_MAIL_HISTORY_STATUS_IDLE',                                  'Ожидание');
define('LNG_SVC_MAIL_HISTORY_STATUS_PENDING',                               'В обработке');
define('LNG_SVC_MAIL_HISTORY_STATUS_FINISHED',                              'Готово');
define('LNG_SVC_MAIL_HISTORY_STATUS_ERROR',                                 'Ошибка');

define('LNG_SVC_MAIL_MAILING_NEW',                                          'Новая рассылка');
define('LNG_SVC_MAIL_MAILING_NEW_BTN_OR',                                   ' или ');
define('LNG_SVC_MAIL_MAILING_NEW_BTN_PARSE',                                'По BotId');
define('LNG_SVC_MAIL_MAILING_NEW_BTN_MANUAL',                               'Без BotID');
define('LNG_SVC_MAIL_MAILING_NEW_HINT',                                     'Введите BotID чтобы автоматически собрать получателей из отчётов. Или просто нажмите кнопку - и сделайте это вручную!');
define('LNG_SVC_MAIL_MAILING_NEW_FLASHMSG_BOTID',                           'Вы уже делали рассылку по BotId "{botId}"!');
define('LNG_SVC_MAIL_MAILING_NEW_COMMENT',                                  'Коммент (для истории)');
define('LNG_SVC_MAIL_MAILING_NEW_BOTNET',                                   'Название ботнета (для статистики)');
define('LNG_SVC_MAIL_MAILING_NEW_RECIPIENTS',                               'Получатели ("email" или "name ; email")');
define('LNG_SVC_MAIL_MAILING_NEW_RECIPIENTS_HINT',                          'Формат: по одному адресу на строку, или "имя получателя ; адрес"');
define('LNG_SVC_MAIL_MAILING_NEW_SENDER',                                   'Отправитель ("email" или "name ; email")');
define('LNG_SVC_MAIL_MAILING_NEW_SENDER_HINT',                              'Формат: "email" или "name ; email". Используйте макрос "{hostname}" для вставки имени хоста отправляющего письма');
define('LNG_SVC_MAIL_MAILING_NEW_SUBJECT',                                  'Тема');
define('LNG_SVC_MAIL_MAILING_NEW_SUBJECT_HINT',                             'Используйте макрос {name} для вставки имени получателя');
define('LNG_SVC_MAIL_MAILING_NEW_MESSAGE_FORMAT',                           'Формат письма');
define('LNG_SVC_MAIL_MAILING_NEW_MESSAGE',                                  'Текст письма');
define('LNG_SVC_MAIL_MAILING_NEW_MESSAGE_HINT',                             'Доступные макросы: <ul><li>{name} - Имя получателя<li>{email} - E-mail получателя<li>{random} - случайные символы<li>{rand0m} - случайное длинное число</ul>');
define('LNG_SVC_MAIL_MAILING_NEW_ATTACHMENT',                               'Вложение');
define('LNG_SVC_MAIL_MAILING_NEW_ATTACHMENT_FILENAME',                      'Имя файла');
define('LNG_SVC_MAIL_MAILING_NEW_BUTTON_SEND',                              'Отправить');
