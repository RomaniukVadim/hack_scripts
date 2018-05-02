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

// #############################################################################
// DBSEO DB class
class DBSEO_Database
{
	/**
	* The database object
	*
	* @protected	$db
	*/
	protected $connection_master = null;
	protected $connection_slave = null;
	protected $connection_recent = null;

	/**
	* Array of configuration items
	*
	* @protected	$this->config
	*/
	protected $config;

	/**
	* The query result we executed
	*
	* @protected	MySQL_Result
	*/
	protected $result;

	/**
	* Whether we're debugging output
	*
	* @public	boolean
	*/
	public $debug = false;

	/**
	* Whether we're debugging output
	*
	* @public	boolean
	*/
	public $cache = array();

	/**
	* Whether we're logging debugging output
	*
	* @public	boolean
	*/
	public $debugLog = false;

	/**
	* The debug time flag
	*
	* @public	integer
	*/
	protected $debugTime = 0;

	/**
	* The contents of the most recent SQL query string.
	*
	* @var	string
	*/
	protected $sql = '';

	/**
	* Whether or not to show and halt on database errors
	*
	* @var	boolean
	*/
	protected $reporterror = true;

	/**
	* Number of queries executed
	*
	* @var	integer	The number of SQL queries run by the system
	*/
	protected $querycount = 0;

	/**
	* Application name
	*
	* @var	string	The full name of the application
	*/
	//protected $appname = 'DragonByte SEO';
	protected $appname = 'vBulletin';

	/**
	* Array of function names, mapping a simple name to the RDBMS specific function name
	*
	* @var	array
	*/
	protected $functions = array(
		'connect'            => 'mysql_connect',
		'pconnect'           => 'mysql_pconnect',
		'connect_error'      => 'mysql_error',
		'select_db'          => 'mysql_select_db',
		'query'              => 'mysql_query',
		'query_unbuffered'   => 'mysql_unbuffered_query',
		'fetch_row'          => 'mysql_fetch_row',
		'fetch_array'        => 'mysql_fetch_array',
		'fetch_field'        => 'mysql_fetch_field',
		'free_result'        => 'mysql_free_result',
		'data_seek'          => 'mysql_data_seek',
		'error'              => 'mysql_error',
		'errno'              => 'mysql_errno',
		'affected_rows'      => 'mysql_affected_rows',
		'num_rows'           => 'mysql_num_rows',
		'num_fields'         => 'mysql_num_fields',
		'field_name'         => 'mysql_field_name',
		'insert_id'          => 'mysql_insert_id',
		'escape_string'      => 'mysql_real_escape_string',
		'real_escape_string' => 'mysql_real_escape_string',
		'close'              => 'mysql_close',
		'client_encoding'    => 'mysql_client_encoding',
		'ping'               => 'mysql_ping',
	);


	/**
	* Does important checking before anything else should be going on
	*
	* @param	array		Configuration array
	*/
	public function __construct(&$config)
	{
		// Set this
		$this->config =& $config;

		if ($this->config['DBSEO']['debug'])
		{
			// Store this for debug purposes
			$this->debugTime = time();
			$this->debugLog = true;
		}

		// Close the DB connection
		register_shutdown_function(array($this, 'close'));
	}

	/**
	* Connects to the specified database server(s)
	*
	* @param	string	Name of the database that we will be using for select_db()
	* @param	string	Name of the master (write) server - should be either 'localhost' or an IP address
	* @param	integer	Port for the master server
	* @param	string	Username to connect to the master server
	* @param	string	Password associated with the username for the master server
	* @param	boolean	Whether or not to use persistent connections to the master server
	* @param	string	(Optional) Name of the slave (read) server - should be either left blank or set to 'localhost' or an IP address, but NOT the same as the servername for the master server
	* @param	integer	(Optional) Port of the slave server
	* @param	string	(Optional) Username to connect to the slave server
	* @param	string	(Optional) Password associated with the username for the slave server
	* @param	boolean	(Optional) Whether or not to use persistent connections to the slave server
	* @param	string	(Optional) Parse given MySQL config file to set options
	* @param	string	(Optional) Connection Charset MySQLi / PHP 5.1.0+ or 5.0.5+ / MySQL 4.1.13+ or MySQL 5.1.10+ Only
	*
	* @return	none
	*/
	public function connect($database, $w_servername, $w_port, $w_username, $w_password, $w_usepconnect = false, $r_servername = '', $r_port = 3306, $r_username = '', $r_password = '', $r_usepconnect = false, $configfile = '', $charset = '')
	{
		$this->database = $database;

		$w_port = $w_port ? $w_port : 3306;
		$r_port = $r_port ? $r_port : 3306;

		$this->connection_master = $this->db_connect($w_servername, $w_port, $w_username, $w_password, $w_usepconnect, $configfile, $charset);
		$this->multiserver = false;
		$this->connection_slave =& $this->connection_master;

		if ($this->connection_master)
		{
			$this->select_db($this->database);
		}
	}

	/**
	* Initialize database connection(s)
	*
	* Connects to the specified master database server, and also to the slave server if it is specified
	*
	* @param	string	Name of the database server - should be either 'localhost' or an IP address
	* @param	integer	Port of the database server (usually 3306)
	* @param	string	Username to connect to the database server
	* @param	string	Password associated with the username for the database server
	* @param	boolean	Whether or not to use persistent connections to the database server
	* @param	string  Not applicable; config file for MySQLi only
	* @param	string  Force connection character set (to prevent collation errors)
	*
	* @return	boolean
	*/
	protected function db_connect($servername, $port, $username, $password, $usepconnect, $configfile = '', $charset = '')
	{
		if (!$link = $this->functions[$usepconnect ? 'pconnect' : 'connect']("$servername:$port", $username, $password))
		{
			// Connection error
			$this->halt($this->functions['connect_error']());
		}

		if (!empty($charset))
		{
			if (function_exists('mysql_set_charset'))
			{
				mysql_set_charset($charset);
			}
			else
			{
				$this->sql = "SET NAMES $charset";
				$this->execute_query(true, $link);
			}
		}

		return $link;
	}

	/**
	* Selects a database to use
	*
	* @param	string	The name of the database located on the database server(s)
	*
	* @return	boolean
	*/
	public function select_db($database)
	{
		if ($check_write = @$this->select_db_wrapper($database, $this->connection_master))
		{
			$this->connection_recent =& $this->connection_master;
			return true;
		}
		else
		{
			$this->connection_recent =& $this->connection_master;

			$this->halt('Cannot use database ' . $database);

			return false;
		}
	}

	/**
	* Simple wrapper for select_db(), to allow argument order changes
	*
	* @param	string	Database name
	* @param	integer	Link identifier
	*
	* @return	boolean
	*/
	protected function select_db_wrapper($database = '', $link = null)
	{
		return $this->functions['select_db']($database, $link);
	}

	/**
	* Executes a data-writing SQL query through the 'master' database connection
	*
	* @param	string	The text of the SQL query to be executed
	* @param	boolean	Whether or not to run this query buffered (true) or unbuffered (false). Default is buffered.
	*
	* @return	string
	*/
	public function query_write($sql, $buffered = true)
	{
		$this->sql =& $sql;
		return $this->execute_query($buffered, $this->connection_master);
	}

	/**
	* Executes a data-reading SQL query through the 'master' database connection
	* we don't know if the 'read' database is up to date so be on the safe side
	*
	* @param	string	The text of the SQL query to be executed
	* @param	boolean	Whether or not to run this query buffered (true) or unbuffered (false). Default is buffered.
	*
	* @return	string
	*/
	public function query_read($sql, $buffered = true)
	{
		$this->sql =& $sql;
		return $this->execute_query($buffered, $this->connection_master);
	}

	/**
	* Executes a data-reading SQL query through the 'slave' database connection
	*
	* @param	string	The text of the SQL query to be executed
	* @param	boolean	Whether or not to run this query buffered (true) or unbuffered (false). Default is buffered.
	*
	* @return	string
	*/
	public function query_read_slave($sql, $buffered = true)
	{
		$this->sql =& $sql;
		return $this->execute_query($buffered, $this->connection_master);
	}

	/**
	* Fetches a row from a query result and returns the values from that row as an array
	*
	* The value of $type defines whether the array will have numeric or associative keys, or both
	*
	* @param	string	The query result ID we are dealing with
	* @param	integer	One of DBARRAY_ASSOC / DBARRAY_NUM / DBARRAY_BOTH
	*
	* @return	array
	*/
	public function fetch_array($queryresult)
	{
		return @$this->functions['fetch_array']($queryresult, MYSQL_ASSOC);
	}

	/**
	* Escapes a string to make it safe to be inserted into an SQL query
	*
	* @param	string	The string to be escaped
	*
	* @return	string
	*/
	public function escape_string($string)
	{
		return $this->functions['real_escape_string']($string, $this->connection_master);
	}

	/**
	* Escapes a string using the appropriate escape character for the RDBMS for use in LIKE conditions
	*
	* @param	string	The string to be escaped
	*
	* @return	string
	*/
	public function escape_string_like($string)
	{
		return str_replace(array('%', '_') , array('\%' , '\_') , $this->escape_string($string));
	}

	/**
	* Executes an SQL query through the specified connection
	*
	* @param	boolean	Whether or not to run this query buffered (true) or unbuffered (false). Default is unbuffered.
	* @param	string	The connection ID to the database server
	*
	* @return	string
	*/
	protected function &execute_query($buffered = true, &$link)
	{
		$this->connection_recent =& $link;
		$this->querycount++;

		if ($queryresult = $this->functions[$buffered ? 'query' : 'query_unbuffered']($this->sql, $link))
		{
			// unset $sql to lower memory .. this isn't an error, so it's not needed
			$this->sql = '';

			return $queryresult;
		}
		else
		{
			$this->halt();

			// unset $sql to lower memory .. error will have already been thrown
			$this->sql = '';
		}
	}

	/**
	* Frees all memory associated with the specified query result
	*
	* @param	string	The query result ID we are dealing with
	*
	* @return	boolean
	*/
	public function free_result($queryresult)
	{
		$this->sql = '';
		return @$this->functions['free_result']($queryresult);
	}

	/**
	* Closes the database link
	*/
	public function close()
	{
		@$this->functions['close']($this->connection_master);
	}


	/**
	* Returns the text of the error message from previous database operation
	*
	* @return	string
	*/
	public function error()
	{
		if ($this->connection_recent === null)
		{
			$this->error = '';
		}
		else
		{
			$this->error = $this->functions['error']($this->connection_recent);
		}
		return $this->error;
	}

	/**
	* Returns the numerical value of the error message from previous database operation
	*
	* @return	integer
	*/
	public function errno()
	{
		if ($this->connection_recent === null)
		{
			$this->errno = 0;
		}
		else
		{
			$this->errno = $this->functions['errno']($this->connection_recent);
		}
		return $this->errno;
	}

	/**
	* Halts execution of the entire system and displays an error message
	*
	* @param	string	Text of the error message. Leave blank to use $this->sql as error text.
	*
	* @return	integer
	*/
	protected function halt($errortext = '')
	{
		if ($this->connection_recent)
		{
			$this->error = $this->error($this->connection_recent);
			$this->errno = $this->errno($this->connection_recent);
		}

		if ($this->reporterror)
		{
			if ($errortext == '')
			{
				$this->sql = "Invalid SQL:\r\n" . chop($this->sql) . ';';
				$errortext =& $this->sql;
			}

			$vboptions      = array('bbtitle' => $this->appname, 'templateversion' => '2.0.39');
			$technicalemail = $this->config['DBSEO']['technicalemail'] ? $this->config['DBSEO']['technicalemail'] : $this->config['Database']['technicalemail'];
			$bbuserinfo 	= array('username' => 'N/A');
			$requestdate    = date('l, F jS Y @ h:i:s A', time());
			$date           = date('l, F jS Y @ h:i:s A');
			$scriptpath 	= str_replace('&amp;', '&', $_SERVER['REQUEST_URI']);
			$referer 		= $_SERVER['HTTP_REFERER'];
			$ipaddress		= $_SERVER['REMOTE_ADDR'];
			$classname      = str_replace('DBSEO_', 'vBulletin', get_class($this));

			// Try and stop e-mail flooding.
			$tempdir = ini_get('upload_tmp_dir');

			$unique = md5($_SERVER['HTTP_HOST']);
			$tempfile = $tempdir."zdberr$unique.dat";

			/* If its less than a minute since the last e-mail
			and the error code is the same as last time, disable e-mail */
			if ($data = @file_get_contents($tempfile))
			{
				$errc = intval(substr($data, 10));
				$time = intval(substr($data, 0, 10));
				if ($time AND (time() - $time) < 60
					AND intval($this->errno) == $errc)
				{
					$technicalemail = '';
				}
				else
				{
					$data = time().intval($this->errno);
					@file_put_contents($tempfile, $data);
				}
			}
			else
			{
				$data = time().intval($this->errno);
				@file_put_contents($tempfile, $data);
			}

			eval('$message = "' . str_replace('"', '\"', file_get_contents(DBSEO_CWD . '/includes/database_error_message.html')) . '";');

			// add a backtrace to the message
			if ($this->config['Misc']['debug'] OR ($technicalemail AND $this->config['DBSEO']['debug']))
			{
				$trace = debug_backtrace();
				$trace_output = "\n";

				foreach ($trace AS $index => $trace_item)
				{
					$param = (in_array($trace_item['function'], array('require', 'require_once', 'include', 'include_once')) ? $trace_item['args'][0] : '');

					// remove path
					$param = str_replace(DBSEO_CWD, '[path]', $param);
					$trace_item['file'] = str_replace(DBSEO_CWD, '[path]', $trace_item['file']);

					$trace_output .= "#$index $trace_item[class]$trace_item[type]$trace_item[function]($param) called in $trace_item[file] on line $trace_item[line]\n";
				}

				$message .= "\n\nStack Trace:\n$trace_output\n";
			}

			if ($technicalemail != '')
			{
				@mail($technicalemail, $this->appname . ' Database Error!', preg_replace("#(\r\n|\r|\n)#s", (@ini_get('sendmail_path') === '') ? "\r\n" : "\n", $message), "From: $technicalemail");
			}

			if (defined('STDIN'))
			{
				echo $message;
				exit;
			}

			if (!headers_sent())
			{
				if (SAPI_NAME == 'cgi' OR SAPI_NAME == 'cgi-fcgi')
				{
					header('Status: 503 Service Unavailable');
				}
				else
				{
					header($_SERVER['SERVER_PROTOCOL'] . ' 503 Service Unavailable');
				}
			}

			// Check what func we can use
			$message = function_exists('htmlspecialchars_uni') ? htmlspecialchars_uni($message) : htmlspecialchars($message, ENT_COMPAT|ENT_IGNORE|ENT_HTML401);

			// display error message on screen
			$message = '<form><textarea rows="15" cols="70" wrap="off" id="message">' . $message . '</textarea></form>';

			if (DBSEO::$config['_bburl'])
			{
				$imagepath = DBSEO::$config['_bburl'];
			}
			else
			{
				// this might not work with too many slashes in the archive
				$imagepath = (VB_AREA == 'Forum' ? '.' : '..');
			}

			eval('$message = "' . str_replace('"', '\"', file_get_contents(DBSEO_CWD . '/includes/database_error_page.html')) . '";');

			// This is needed so IE doesn't show the pretty error messages
			$message .= str_repeat(' ', 512);
			die($message);
		}
		else if (!empty($errortext))
		{
			$this->error = $errortext;
		}
	}

	public function fetchSettings($force = false)
	{
		if (isset($this->cache['settings']) AND !$force)
		{
			// We can return the settings from cache
			return $this->cache['settings'];
		}

		if (($config = DBSEO::$datastore->fetch('settings')) === false OR $force)
		{
			// The config we're gonna return
			$config = array();

			$query = "
				SELECT varname, value
				FROM " . $this->config['Database']['tableprefix'] . "setting
			";

			if ($this->debugLog)
			{
				// Write to file and append if needed
				@file_put_contents(DBSEO_CWD . '/dbtech/dbseo/debuglog/' . $this->debugTime . '.txt', "\n\n\nfetchSettings:\n" . $query, FILE_APPEND);
			}

			$result = $this->query_read_slave($query);
			while ($array = $this->fetch_array($result))
			{
				// Store configuration
				$config[$array['varname']] = $array['value'];
			}
			$this->free_result($result);

			// Build the cache
			DBSEO::$datastore->build('settings', $config);
		}

		// Now set the cache
		$this->cache['settings'] = $config;

		return $this->cache['settings'];
	}

	public function fetchProducts($force = false)
	{
		if (isset($this->cache['products']) AND !$force)
		{
			// We can return the products from cache
			return $this->cache['products'];
		}

		if (($config = DBSEO::$datastore->fetch('products')) === false OR $force)
		{
			// The config we're gonna return
			$config = array();

			$query = "
				SELECT productid, active
				FROM " . $this->config['Database']['tableprefix'] . "product
			";

			if ($this->debugLog)
			{
				// Write to file and append if needed
				@file_put_contents(DBSEO_CWD . '/dbtech/dbseo/debuglog/' . $this->debugTime . '.txt', "\n\n\nfetchProducts:\n" . $query, FILE_APPEND);
			}

			$result = $this->query_read_slave($query);
			while ($array = $this->fetch_array($result))
			{
				// Store configuration
				$config[$array['productid']] = $array['active'];
			}
			$this->free_result($result);

			// Build the cache
			DBSEO::$datastore->build('products', $config);
		}

		// Now set the cache
		$this->cache['products'] = $config;

		return $this->cache['products'];
	}

	public function fetchForumCache($force = false)
	{
		if (isset($this->cache['forumcache']) AND !$force)
		{
			// We can return the forumcache from cache
			return $this->cache['forumcache'];
		}

		if (($forumcache = DBSEO::$datastore->fetch('forumcache')) === false OR $force)
		{
			$query = "
				SELECT data
				FROM " . $this->config['Database']['tableprefix'] . "datastore
				WHERE title = 'forumcache'
			";

			if ($this->debugLog)
			{
				// Write to file and append if needed
				@file_put_contents(DBSEO_CWD . '/dbtech/dbseo/debuglog/' . $this->debugTime . '.txt', "\n\n\nfetchForumCache:\n" . $query, FILE_APPEND);
			}

			$result = $this->query_read_slave($query);
			while ($array = $this->fetch_array($result))
			{
				// Store configuration
				$forumcache = $array['data'];
			}
			$this->free_result($result);

			// Now set the cache
			$forumcache = @unserialize($forumcache);
			$forumcache = is_array($forumcache) ? $forumcache : array();

			if (!$forumcache)
			{
				// Most likely a charset problem, re-query the whole sodding thing
				$query = "
					SELECT *
					FROM " . $this->config['Database']['tableprefix'] . "forum
				";

				if ($this->debugLog)
				{
					// Write to file and append if needed
					@file_put_contents(DBSEO_CWD . '/dbtech/dbseo/debuglog/' . $this->debugTime . '.txt', "\n\n\nfetchForumCache (rebuild):\n" . $query, FILE_APPEND);
				}

				$result = $this->query_read_slave($query);
				while ($array = $this->fetch_array($result))
				{
					// Store configuration
					$forumcache[$array['forumid']] = $array;
				}
				$this->free_result($result);
			}

			foreach ($forumcache as $forumid => &$forum)
			{
				// Grab an array of parents in structured order
				$parentList = array_reverse(explode(',', $forum['parentlist']));

				// Init this
				$forum['seopath'] = array();

				foreach ($parentList as $forumId)
				{
					if (!isset($forumcache[$forumId]))
					{
						// Skip this
						continue;
					}

					// Init the replacement array
					$replace = array(
						'%forum_id%' 	=> $forumId,
						'%forum_title%' => DBSEO_Rewrite_Forum::rewriteUrl($forumcache[$forumId], DBSEO::$config['dbtech_dbseo_rewrite_rule_forumpath']),
					);

					// Add to the SEO Path
					$forum['seopath'][] = str_replace(array_keys($replace), $replace, DBSEO::$config['dbtech_dbseo_rewrite_rule_forumpath']);
				}

				// Store the path
				$forum['seopath'] = @implode('/', $forum['seopath']);
			}

			// Build the cache
			DBSEO::$datastore->build('forumcache', $forumcache);
		}

		// Now set the cache
		$this->cache['forumcache'] = $forumcache;

		return $this->cache['forumcache'];
	}

	public function generalQuery($sql, $flatten = true, $force = false)
	{
		// Store a CRC32 hash of the SQL for use in the cache check
		$hashKey = hash('crc32b', $sql);

		if (isset($this->cache[$hashKey]) AND !$force)
		{
			// Shorthand
			$data = $this->cache[$hashKey];

			if (count($data) == 1 AND $flatten)
			{
				// Flatten the array
				$data = $data[0];
			}

			// We can return the settings from cache
			return $data;
		}

		$data = array();

		$query = preg_replace('/\$(\w+)/', $this->config['Database']['tableprefix'] . '$1', $sql);

		if ($this->debugLog)
		{
			// Write to file and append if needed
			@file_put_contents(DBSEO_CWD . '/dbtech/dbseo/debuglog/' . $this->debugTime . '.txt', "\n\n\ngeneralQuery:\n" . $query, FILE_APPEND);
		}

		$result = $this->query_read_slave($query);
		while ($array = $this->fetch_array($result))
		{
			// Store configuration
			$data[] = $array;
		}
		$this->free_result($result);

		// Now set the cache
		$this->cache[$hashKey] = is_array($data) ? $data : array();

		if (count($data) == 1 AND $flatten)
		{
			// Flatten the array
			$data = $data[0];
		}

		return $data;
	}


	public function modifyQuery($sql)
	{
		$this->query_write(preg_replace('/\$(\w+)/', $this->config['Database']['tableprefix'] . '$1', $sql));
	}
}

// #############################################################################
// DBSEO DB class
class DBSEO_Database_MySQLi extends DBSEO_Database
{
	/**
	* Array of function names, mapping a simple name to the RDBMS specific function name
	*
	* @var	array
	*/
	var $functions = array(
		'connect'            => 'mysqli_real_connect',
		'pconnect'           => 'mysqli_real_connect', // mysqli doesn't support persistent connections THANK YOU!
		'connect_error'      => 'mysqli_connect_error',
		'select_db'          => 'mysqli_select_db',
		'query'              => 'mysqli_query',
		'query_unbuffered'   => 'mysqli_unbuffered_query',
		'fetch_row'          => 'mysqli_fetch_row',
		'fetch_array'        => 'mysqli_fetch_array',
		'fetch_field'        => 'mysqli_fetch_field',
		'free_result'        => 'mysqli_free_result',
		'data_seek'          => 'mysqli_data_seek',
		'error'              => 'mysqli_error',
		'errno'              => 'mysqli_errno',
		'affected_rows'      => 'mysqli_affected_rows',
		'num_rows'           => 'mysqli_num_rows',
		'num_fields'         => 'mysqli_num_fields',
		'field_name'         => 'mysqli_field_tell',
		'insert_id'          => 'mysqli_insert_id',
		'escape_string'      => 'mysqli_real_escape_string',
		'real_escape_string' => 'mysqli_real_escape_string',
		'close'              => 'mysqli_close',
		'client_encoding'    => 'mysqli_character_set_name',
		'ping'               => 'mysqli_ping',
	);

	/**
	* Initialize database connection(s)
	*
	* Connects to the specified master database server, and also to the slave server if it is specified
	*
	* @param	string  Name of the database server - should be either 'localhost' or an IP address
	* @param	integer	Port of the database server - usually 3306
	* @param	string  Username to connect to the database server
	* @param	string  Password associated with the username for the database server
	* @param	string  Persistent Connections - Not supported with MySQLi
	* @param	string  Configuration file from config.php.ini (my.ini / my.cnf)
	* @param	string  Mysqli Connection Charset PHP 5.1.0+ or 5.0.5+ / MySQL 4.1.13+ or MySQL 5.1.10+ Only
	*
	* @return	object  Mysqli Resource
	*/
	public function db_connect($servername, $port, $username, $password, $usepconnect, $configfile = '', $charset = '')
	{
		$link = mysqli_init();
		# Set Options Connection Options
		if (!empty($configfile))
		{
			mysqli_options($link, MYSQLI_READ_DEFAULT_FILE, $configfile);
		}

		if (!$connect = $this->functions['connect']($link, $servername, $username, $password, '', $port))
		{
			// Connection error
			$this->halt($this->functions['connect_error']());
		}

		if (!empty($charset))
		{
			if (function_exists('mysqli_set_charset'))
			{
				mysqli_set_charset($link, $charset);
			}
			else
			{
				$this->sql = "SET NAMES $charset";
				$this->execute_query(true, $link);
			}
		}

		return (!$connect) ? false : $link;
	}


	/**
	* Executes an SQL query through the specified connection
	*
	* @param	boolean	Whether or not to run this query buffered (true) or unbuffered (false). Default is unbuffered.
	* @param	string	The connection ID to the database server
	*
	* @return	string
	*/
	protected function &execute_query($buffered = true, &$link)
	{
		$this->connection_recent =& $link;
		$this->querycount++;

		if ($queryresult = mysqli_query($link, $this->sql, ($buffered ? MYSQLI_STORE_RESULT : MYSQLI_USE_RESULT)))
		{
			// unset $sql to lower memory .. this isn't an error, so it's not needed
			$this->sql = '';

			return $queryresult;
		}
		else
		{
			$this->halt();

			// unset $sql to lower memory .. error will have already been thrown
			$this->sql = '';
		}
	}

	/**
	* Simple wrapper for select_db(), to allow argument order changes
	*
	* @param	string	Database name
	* @param	integer	Link identifier
	*
	* @return	boolean
	*/
	protected function select_db_wrapper($database = '', $link = null)
	{
		return $this->functions['select_db']($link, $database);
	}

	/**
	* Fetches a row from a query result and returns the values from that row as an array
	*
	* The value of $type defines whether the array will have numeric or associative keys, or both
	*
	* @param	string	The query result ID we are dealing with
	* @param	integer	One of DBARRAY_ASSOC / DBARRAY_NUM / DBARRAY_BOTH
	*
	* @return	array
	*/
	public function fetch_array($queryresult)
	{
		return @$this->functions['fetch_array']($queryresult, MYSQLI_ASSOC);
	}

	/**
	* Escapes a string to make it safe to be inserted into an SQL query
	*
	* @param	string	The string to be escaped
	*
	* @return	string
	*/
	public function escape_string($string)
	{
		return $this->functions['real_escape_string']($this->connection_master, $string);
	}
}

// #############################################################################
// DBSEO DB class
class DBSEO_Database_Slave extends DBSEO_Database
{
	/**
	* Does important checking before anything else should be going on
	*
	* @param	array		Configuration array
	*/
	public function __construct(&$config)
	{
		// Set this
		$this->config =& $config;

		if ($this->config['DBSEO']['debug'])
		{
			// Store this for debug purposes
			$this->debugTime = time();
			$this->debugLog = true;
		}

		// Close the DB connection
		register_shutdown_function(array($this, 'close'));
	}

	/**
	* Connects to the specified database server(s)
	*
	* @param	string	Name of the database that we will be using for select_db()
	* @param	string	Name of the master (write) server - should be either 'localhost' or an IP address
	* @param	integer	Port for the master server
	* @param	string	Username to connect to the master server
	* @param	string	Password associated with the username for the master server
	* @param	boolean	Whether or not to use persistent connections to the master server
	* @param	string	(Optional) Name of the slave (read) server - should be either left blank or set to 'localhost' or an IP address, but NOT the same as the servername for the master server
	* @param	integer	(Optional) Port of the slave server
	* @param	string	(Optional) Username to connect to the slave server
	* @param	string	(Optional) Password associated with the username for the slave server
	* @param	boolean	(Optional) Whether or not to use persistent connections to the slave server
	* @param	string	(Optional) Parse given MySQL config file to set options
	* @param	string	(Optional) Connection Charset MySQLi / PHP 5.1.0+ or 5.0.5+ / MySQL 4.1.13+ or MySQL 5.1.10+ Only
	*
	* @return	none
	*/
	public function connect($database, $w_servername, $w_port, $w_username, $w_password, $w_usepconnect = false, $r_servername = '', $r_port = 3306, $r_username = '', $r_password = '', $r_usepconnect = false, $configfile = '', $charset = '')
	{
		$this->database = $database;

		$w_port = $w_port ? $w_port : 3306;
		$r_port = $r_port ? $r_port : 3306;

		$this->connection_master = $this->db_connect($w_servername, $w_port, $w_username, $w_password, $w_usepconnect, $configfile, $charset);
		$this->multiserver = true;

		// disable errors and try to connect to slave
		$this->reporterror = false;
		$this->connection_slave = $this->db_connect($r_servername, $r_port, $r_username, $r_password, $r_usepconnect, $configfile, $charset);
		$this->reporterror = true;

		if ($this->connection_slave === false)
		{
			$this->connection_slave =& $this->connection_master;
		}

		if ($this->connection_master)
		{ // slave will be selected automagically when we select the master
			$this->select_db($this->database);
		}
	}

	/**
	* Selects a database to use
	*
	* @param	string	The name of the database located on the database server(s)
	*
	* @return	boolean
	*/
	public function select_db($database)
	{
		$check_write = parent::select_db($database);
		$check_read = @$this->select_db_wrapper($this->database, $this->connection_slave);
		$this->connection_recent =& $this->connection_slave;

		return ($check_write AND $check_read);
	}

	/**
	* Executes a data-reading SQL query through the 'slave' database connection
	*
	* @param	string	The text of the SQL query to be executed
	* @param	boolean	Whether or not to run this query buffered (true) or unbuffered (false). Default is buffered.
	*
	* @return	string
	*/
	public function query_read_slave($sql, $buffered = true)
	{
		$this->sql =& $sql;
		return $this->execute_query($buffered, $this->connection_slave);
	}

	/**
	* Closes the connection to both the read database server
	*
	* @return	integer
	*/
	public function close()
	{
		$parent = parent::close();
		return ($parent AND @$this->functions['close']($this->connection_slave));
	}
}

// #############################################################################
// DBSEO DB class
class DBSEO_Database_Slave_MySQLi extends DBSEO_Database_MySQLi
{
	/**
	* Does important checking before anything else should be going on
	*
	* @param	array		Configuration array
	*/
	public function __construct(&$config)
	{
		// Set this
		$this->config =& $config;

		if ($this->config['DBSEO']['debug'])
		{
			// Store this for debug purposes
			$this->debugTime = time();
			$this->debugLog = true;
		}

		// Close the DB connection
		register_shutdown_function(array($this, 'close'));
	}

	/**
	* Connects to the specified database server(s)
	*
	* @param	string	Name of the database that we will be using for select_db()
	* @param	string	Name of the master (write) server - should be either 'localhost' or an IP address
	* @param	integer	Port for the master server
	* @param	string	Username to connect to the master server
	* @param	string	Password associated with the username for the master server
	* @param	boolean	Whether or not to use persistent connections to the master server
	* @param	string	(Optional) Name of the slave (read) server - should be either left blank or set to 'localhost' or an IP address, but NOT the same as the servername for the master server
	* @param	integer	(Optional) Port of the slave server
	* @param	string	(Optional) Username to connect to the slave server
	* @param	string	(Optional) Password associated with the username for the slave server
	* @param	boolean	(Optional) Whether or not to use persistent connections to the slave server
	* @param	string	(Optional) Parse given MySQL config file to set options
	* @param	string	(Optional) Connection Charset MySQLi / PHP 5.1.0+ or 5.0.5+ / MySQL 4.1.13+ or MySQL 5.1.10+ Only
	*
	* @return	none
	*/
	public function connect($database, $w_servername, $w_port, $w_username, $w_password, $w_usepconnect = false, $r_servername = '', $r_port = 3306, $r_username = '', $r_password = '', $r_usepconnect = false, $configfile = '', $charset = '')
	{
		$this->database = $database;

		$w_port = $w_port ? $w_port : 3306;
		$r_port = $r_port ? $r_port : 3306;

		$this->connection_master = $this->db_connect($w_servername, $w_port, $w_username, $w_password, $w_usepconnect, $configfile, $charset);
		$this->multiserver = true;

		// disable errors and try to connect to slave
		$this->reporterror = false;
		$this->connection_slave = $this->db_connect($r_servername, $r_port, $r_username, $r_password, $r_usepconnect, $configfile, $charset);
		$this->reporterror = true;

		if ($this->connection_slave === false)
		{
			$this->connection_slave =& $this->connection_master;
		}

		if ($this->connection_master)
		{ // slave will be selected automagically when we select the master
			$this->select_db($this->database);
		}
	}

	/**
	* Selects a database to use
	*
	* @param	string	The name of the database located on the database server(s)
	*
	* @return	boolean
	*/
	public function select_db($database)
	{
		$check_write = parent::select_db($database);
		$check_read = @$this->select_db_wrapper($this->database, $this->connection_slave);
		$this->connection_recent =& $this->connection_slave;

		return ($check_write AND $check_read);
	}

	/**
	* Executes a data-reading SQL query through the 'slave' database connection
	*
	* @param	string	The text of the SQL query to be executed
	* @param	boolean	Whether or not to run this query buffered (true) or unbuffered (false). Default is buffered.
	*
	* @return	string
	*/
	public function query_read_slave($sql, $buffered = true)
	{
		$this->sql =& $sql;
		return $this->execute_query($buffered, $this->connection_slave);
	}

	/**
	* Closes the connection to both the read database server
	*
	* @return	integer
	*/
	public function close()
	{
		$parent = parent::close();
		return ($parent AND @$this->functions['close']($this->connection_slave));
	}
}

// For vB3
function DBSEO_DB_Shutdown() { DBSEO::$db->close(); }
?>