<?php
define('LNG_AJAX_MAILGRABBER',                                              'Mail Grabber');
define('LNG_AJAX_SELFTEST',                                                 'Self-Test');
define('LNG_FLASHMSG_NOT_CONFIGURED',                                       'Mailer script URL is not configured!');

define('LNG_BTN_DOWNLOAD_SCRIPT',                                           '[ Download script ]');
define('LNG_BTN_CONFIG',                                                    '[ Config ]');

define('LNG_SVC_MAIL_HISTORY',                                              'History');
define('LNG_SVC_MAIL_HISTORY_TH_DATE',                                      'Date');
define('LNG_SVC_MAIL_HISTORY_TH_BOTID',                                     'BotID');
define('LNG_SVC_MAIL_HISTORY_TH_COMMENT',                                   'Comment');
define('LNG_SVC_MAIL_HISTORY_TH_SUBJECT',                                   'Subject');
define('LNG_SVC_MAIL_HISTORY_TH_ADDRESSES',                                 'Sent');
define('LNG_SVC_MAIL_HISTORY_TH_STATUS',                                    'Status');
define('LNG_SVC_MAIL_HISTORY_TH_BOTNET',                                    'Botnet');
define('LNG_SVC_MAIL_HISTORY_TH_NEWBOTS',                                   'New bots');

define('LNG_SVC_MAIL_HISTORY_STATUS_IDLE',                                  'Idle');
define('LNG_SVC_MAIL_HISTORY_STATUS_PENDING',                               'Pending');
define('LNG_SVC_MAIL_HISTORY_STATUS_FINISHED',                              'Finished');
define('LNG_SVC_MAIL_HISTORY_STATUS_ERROR',                                 'Error');

define('LNG_SVC_MAIL_MAILING_NEW',                                          'New Mailing');
define('LNG_SVC_MAIL_MAILING_NEW_BTN_OR',                                   ' or ');
define('LNG_SVC_MAIL_MAILING_NEW_BTN_PARSE',                                'For BotId');
define('LNG_SVC_MAIL_MAILING_NEW_BTN_MANUAL',                               'Without BotID');
define('LNG_SVC_MAIL_MAILING_NEW_HINT',                                     'Enter BotID to automatically parse recipients from reports. Or just press the button - and specify the addresses manually!');
define('LNG_SVC_MAIL_MAILING_NEW_FLASHMSG_BOTID',                           'You have already created a mailing for BotId "{botId}"!');
define('LNG_SVC_MAIL_MAILING_NEW_COMMENT',                                  'Comment (for the history)');
define('LNG_SVC_MAIL_MAILING_NEW_BOTNET',                                   'Botnet name (for statistics)');
define('LNG_SVC_MAIL_MAILING_NEW_RECIPIENTS',                               'Recipients ("email" or "name ; email")');
define('LNG_SVC_MAIL_MAILING_NEW_RECIPIENTS_HINT',                          'Format: one e-mail address per line, optionally prefixed by the recipient name and a semicolon');
define('LNG_SVC_MAIL_MAILING_NEW_SENDER',                                   'Sender ("email" or "name ; email")');
define('LNG_SVC_MAIL_MAILING_NEW_SENDER_HINT',                              'Format: "email" or "name ; email". Use "{hostname}" macro to insert the original name of the sending host');
define('LNG_SVC_MAIL_MAILING_NEW_SUBJECT',                                  'Subject');
define('LNG_SVC_MAIL_MAILING_NEW_SUBJECT_HINT',                             'Use {name} macro to insert the name of the receiver');
define('LNG_SVC_MAIL_MAILING_NEW_MESSAGE_FORMAT',                           'Message format');
define('LNG_SVC_MAIL_MAILING_NEW_MESSAGE',                                  'Message text');
define('LNG_SVC_MAIL_MAILING_NEW_MESSAGE_HINT',                             'Available macros: <ul><li>{name} - Receiver name<li>{email} - Receiver E-mail<li>{random} - random chars<li>{rand0m} - random long number</ul>');
define('LNG_SVC_MAIL_MAILING_NEW_ATTACHMENT',                               'Attachment');
define('LNG_SVC_MAIL_MAILING_NEW_ATTACHMENT_FILENAME',                      'Filename');
define('LNG_SVC_MAIL_MAILING_NEW_BUTTON_SEND',                              'Send');
