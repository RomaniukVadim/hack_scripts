<?php if(!defined('__CP__'))die();
define('LNG_REPORTS', 'Search in database');

define('LNG_REPORTS_BOTSACTION', 'Bots action:');

// Connect
define('LNG_REPORTS_CONNECT_ANOTHERDB',           'Connect to another DB');
define('LNG_REPORTS_CONNECT_DBNAME',              'Database');
define('LNG_REPORTS_CONNECT_SETUP',               'Setup');
define('LNG_REPORTS_CONNECT_THIS',                'Current');

//Фильтр.
define('LNG_REPORTS_FILTER_TITLE',      'Filter');

define('LNG_REPORTS_FILTER_DATE_P1',    'Search from date (dd.mm):');
define('LNG_REPORTS_FILTER_DATE_P2',    'to date:');
define('LNG_REPORTS_FILTER_DATE_P3',    '');

define('LNG_REPORTS_FILTER_BOTS',       'Bots:');
define('LNG_REPORTS_FILTER_BOTNETS',    'Botnets:');
define('LNG_REPORTS_FILTER_IPS',        'IP-addresses:');
define('LNG_REPORTS_FILTER_COUNTRIES',  'Countries:');
define('LNG_REPORTS_FILTER_QUERY',      'Search string:');
define('LNG_REPORTS_FILTER_QUERYSTOP',  'Stop-words:');
define('LNG_REPORTS_FILTER_HIGHLIGHT',  'Highlight:');
define('LNG_REPORTS_FILTER_URLMASKS',   'URL masks:');
define('LNG_REPORTS_FILTER_REPORTTYPE', 'Type of report:');
define('LNG_REPORTS_FILTER_FAVORITES',  'Favorites only.');
define('LNG_REPORTS_FILTER_CS',         'Case sensitive.');
define('LNG_REPORTS_FILTER_SMART',      'SMART-search');
define('LNG_REPORTS_FILTER_DATAMINER',                          'Data-Miner');
define('LNG_REPORTS_FILTER_DATAMINER_POST_FIELD_MASKS',         'POST field masks to search:');
define('LNG_REPORTS_FILTER_DATAMINER_POST_FIELD_MASKS_HINT',    'POST field name masks names followed by "=". Wildcard supported: <code>*login*=</code> , <code>login=*@*</code>');
define('LNG_REPORTS_FILTER_GROUPQUERY', 'Exclude retries of contents (for one day only).');
define('LNG_REPORTS_FILTER_NONAMES',    'Show only reports (don\'t show names of bots).');
define('LNG_REPORTS_FILTER_PLAIN',      'Show as text (text/plain).');
define('LNG_REPORTS_FILTER_ONLINE',     'Only online bots.');

define('LNG_REPORTS_FILTER_ALL',        '--');

define('LNG_REPORTS_FILTER_RESET',      'Reset form');
define('LNG_REPORTS_FILTER_SUBMIT',     'Search');
define('LNG_REPORTS_FILTER_REMOVE',     'Remove');
define('LNG_REPORTS_FILTER_REMOVE_Q',   'You really want remove reports using current search filter?');

define('LNG_REPORTS_RESULT', 'Result:');

define('LNG_REPORTS_DATE_EMPTY',     'Selected date not founded.');
define('LNG_REPORTS_DATE_NOREPORTS', 'Reports for this date not founded.');
define('LNG_REPORTS_DATE_ERROR',     'ERROR!');
define('LNG_REPORTS_DATE_REMOVED',   'Removed %u rows.');
define('LNG_REPORTS_DATE_DROPPED',   'Table dropped.');

define('LNG_REPORTS_VIEW_NOT_EXISTS', 'Report not exists.');
define('LNG_REPORTS_VIEW_TITLE',      'View report');
define('LNG_REPORTS_VIEW_TITLE2',     'View report (%s, %s bytes)');
define('LNG_REPORTS_VIEW_BOTID',      'Bot ID:');
define('LNG_REPORTS_VIEW_BOTNET',     'Botnet:');
define('LNG_REPORTS_VIEW_VERSION',    'Version:');
define('LNG_REPORTS_VIEW_OS',         'OS Version (language):');
define('LNG_REPORTS_VIEW_OSLANG',     'OS Language:');
define('LNG_REPORTS_VIEW_TIME',       'Local time:');
define('LNG_REPORTS_VIEW_TIMEBIAS',   'GMT:');
define('LNG_REPORTS_VIEW_TICK',       'Session time:');
define('LNG_REPORTS_BOT_RTIME_LAST',  'Bot was online:');
define('LNG_REPORTS_VIEW_RTIME',      'Report time:');
define('LNG_REPORTS_VIEW_COUNTRY',    'Country:');
define('LNG_REPORTS_VIEW_IPV4',       'IPv4:');
define('LNG_REPORTS_VIEW_COMMENT',    'Comment for bot:');
define('LNG_REPORTS_VIEW_COMMENT_SAVE',    'Save');
define('LNG_REPORTS_VIEW_USED',       'In the list of used:');
define('LNG_REPORTS_VIEW_PROCNAME',   'Process name:');
define('LNG_REPORTS_VIEW_PROCINFO',   'Process info:');
define('LNG_REPORTS_VIEW_REFERAL',    'Referal link:');
define('LNG_REPORTS_VIEW_PROCUSER',   'User of process:');
define('LNG_REPORTS_VIEW_SOURCE',     'Source:');
define('LNG_REPORTS_VIEW_TYPE',       'Type:');
define('LNG_REPORTS_VIEW_SIZE',       'Size (bytes):');
define('LNG_REPORTS_VIEW_DOWNLOAD',   'Download file "%s" (%s bytes).');
define('LNG_REPORTS_VIEW_NEXTPAGE',   'Load more');

define('LNG_REPORTS_ASIDE_WHOIS',     'Whois');
define('LNG_REPORTS_ASIDE_FAVORITE0', 'In favorites');
define('LNG_REPORTS_ASIDE_FAVORITE1', 'Add to favorites');
define('LNG_REPORTS_ASIDE_SAVE',      'Save');
                                              

define('LNG_REPORTS_SMART_RESULTS_DATES',               'Dates');
define('LNG_REPORTS_SMART_RESULTS_BOTID',               'BotID');
define('LNG_REPORTS_SMART_RESULTS_HIT_COUNT',           'Hits');
define('LNG_REPORTS_SMART_RESULTS_LINK',                'URL');

define('LNG_REPORTS_DATAMINER_STAT_REPORTS_ANALYZED',            'Analyzed reports:');
define('LNG_REPORTS_DATAMINER_STAT_REPORTS_WFIELDS',             'Reports with fields:');
define('LNG_REPORTS_DATAMINER_STAT_VALUES_DUPLICATE',            'Unique field values:');
define('LNG_REPORTS_DATAMINER_STAT_VALUES_UNIQUE',               'Duplicate field values:');
define('LNG_REPORTS_DATAMINER_STAT_COMPLETE',                    'Complete field sets:');
define('LNG_REPORTS_DATAMINER_STAT_INCOMPLETE',                  'Incomplete field sets (ignored):');
