<?php
// #############################################################################
// datastore class

/**
* Class for fetching and storing various items from the database
*/
class DBSEO_Datastore
{
	/**
	* Unique prefix for item's title, required for multiple forums on the same server using the same classes that read/write to memory
	*
	* @protected	string
	*/
	protected $prefix = '';

	/**
	* Constructor - establishes the database object to use for datastore queries
	*/
	public function __construct()
	{
		$prefix = DBSEO::$configFile['Datastore']['prefix'];
		if (DBSEO::$configFile['Datastore']['prefix'])
		{
			if (!preg_match('#[^a-zA-Z0-9]#', substr(DBSEO::$configFile['Datastore']['prefix'], -1)))
			{
				$prefix .= '_';
			}
		}

		$this->prefix = $prefix . 'dbseo_';
	}

	/**
	* Fetches the data from shared memory and detects errors
	*
	* @param	string	title of the datastore item
	*
	* @return	boolean
	*/
	public function fetch($title)
	{
		return false;
	}

	/**
	* Updates the appropriate cache file
	*
	* @param	string	title of the datastore item
	* @param	mixed	The data associated with the title
	*
	* @return	void
	*/
	public function build($title, $data) {}

	/**
	* Flushes the contents of the cache
	*
	* @return	void
	*/
	public function flush() {}
}

/**
* Class for fetching and storing various items from eAccelerator
*/
class DBSEO_Datastore_eAccelerator extends DBSEO_Datastore
{
	/**
	* Fetches the data from shared memory and detects errors
	*
	* @param	string	title of the datastore item
	*
	* @return	boolean
	*/
	public function fetch($title)
	{
		if (!function_exists('eaccelerator_get'))
		{
			trigger_error('eAccelerator not installed', E_USER_ERROR);
		}

		$ptitle = $this->prefix . $title;

		if (($data = eaccelerator_get($ptitle)) === null)
		{
			// appears its not there
			return false;
		}

		return $data;
	}

	/**
	* Updates the appropriate cache file
	*
	* @param	string	title of the datastore item
	* @param	mixed	The data associated with the title
	*
	* @return	void
	*/
	public function build($title, $data)
	{
		$ptitle = $this->prefix . $title;

		eaccelerator_rm($ptitle);
		eaccelerator_put($ptitle, $data, 3600);
	}

	/**
	* Flushes the contents of the cache
	*
	* @return	void
	*/
	public function flush()
	{
		eaccelerator_clear();
	}
}

// #############################################################################
// Memcached

/**
* Class for fetching and storing various items from a Memcache Server
*/
class DBSEO_Datastore_Memcached extends DBSEO_Datastore
{
	/**
	* The Memcache object
	*
	* @private	Memcache
	*/
	private $memcache = null;

	/**
	* To prevent locking when the memcached has been restarted we want to use add rather than set
	*
	* @private	boolean
	*/
	private $memcache_set = true;

	/**
	* To verify a connection is still active
	*
	* @private	boolean
	*/
	private $memcache_connected = false;

	/**
	* Constructor - establishes the database object to use for datastore queries
	*/
	public function __construct()
	{
		parent::__construct();

		if (!class_exists('Memcache', false))
		{
			trigger_error('Memcache is not installed', E_USER_ERROR);
		}

		$this->memcache = new Memcache;
	}

	/**
	* Connect Wrapper for Memcache
	*
	* @return	integer	When a new connection is made 1 is returned, 2 if a connection already existed, 3 if a connection failed.
	*/
	private function connect()
	{
		if (!$this->memcache_connected)
		{
			if (is_array(DBSEO::$configFile['Misc']['memcacheserver']))
			{
				if (method_exists($this->memcache, 'addServer'))
				{
					foreach (array_keys(DBSEO::$configFile['Misc']['memcacheserver']) AS $key)
					{
						$this->memcache->addServer(
							DBSEO::$configFile['Misc']['memcacheserver'][$key],
							DBSEO::$configFile['Misc']['memcacheport'][$key],
							DBSEO::$configFile['Misc']['memcachepersistent'][$key],
							DBSEO::$configFile['Misc']['memcacheweight'][$key],
							DBSEO::$configFile['Misc']['memcachetimeout'][$key],
							DBSEO::$configFile['Misc']['memcacheretry_interval'][$key]
						);
					}
				}
				else if (!$this->memcache->connect(DBSEO::$configFile['Misc']['memcacheserver'][1], DBSEO::$configFile['Misc']['memcacheport'][1], DBSEO::$configFile['Misc']['memcachetimeout'][1]))
				{
					return 3;
				}
			}
			else if (!$this->memcache->connect(DBSEO::$configFile['Misc']['memcacheserver'], DBSEO::$configFile['Misc']['memcacheport']))
			{
				return 3;
			}
			$this->memcache_connected = true;
			return 1;
		}
		return 2;
	}

	/**
	* Close Wrapper for Memcache
	*/
	private function close()
	{
		if ($this->memcache_connected)
		{
			$this->memcache->close();
			$this->memcache_connected = false;
		}
	}

	/**
	* Fetches the data from shared memory and detects errors
	*
	* @param	string	title of the datastore item
	*
	* @return	mixed
	*/
	public function fetch($title)
	{
		$this->connect();

		if (!$this->memcache_connected)
		{
			return false;
		}

		//this line must stay under the potential return statement above.
		//this flag is intended to temporarily change the behavior of another function while
		//this function is active (it has to do with the way things are overridden from the
		//parent class).  If we leave this function with the flag set to false bad things can
		//happen.
		$this->memcache_set = false;

		$ptitle = $this->prefix . $title;

		if (($data = $this->memcache->get($ptitle)) === false)
		{
			// appears its not there
			return false;
		}

		$this->memcache_set = true;

		return $data;
	}

	/**
	* Updates the appropriate cache file
	*
	* @param	string	title of the datastore item
	*
	* @return	void
	*/
	public function build($title, $data, $expire = 3600)
	{
		$ptitle = $this->prefix . substr($title, 0, 50);
		$check = $this->connect();
		if ($check == 3)
		{
			// Connection failed
			trigger_error('Unable to connect to memcache server', E_USER_ERROR);
		}

		if ($this->memcache_set)
		{
			$this->memcache->set($ptitle, $data, MEMCACHE_COMPRESSED, $expire);
		}
		else
		{
			$this->memcache->add($ptitle, $data, MEMCACHE_COMPRESSED, $expire);
		}
	}

	/**
	* Flushes the contents of the cache
	*
	* @return	void
	*/
	public function flush()
	{
		$this->memcache->flush();
	}
}

// #############################################################################
// APC

/**
* Class for fetching and storing various items from APC
*/
class DBSEO_Datastore_APC extends DBSEO_Datastore
{
	/**
	* Fetches the data from shared memory and detects errors
	*
	* @param	string	title of the datastore item
	*
	* @return	mixed
	*/
	public function fetch($title)
	{
		if (!function_exists('apc_fetch'))
		{
			trigger_error('APC not installed', E_USER_ERROR);
		}

		$ptitle = $this->prefix . $title;

		if (($data = apc_fetch($ptitle)) === false)
		{
			// appears its not there
			return false;
		}

		return $data;
	}

	/**
	* Updates the appropriate cache file
	*
	* @param	string	title of the datastore item
	* @param	mixed	The data associated with the title
	*
	* @return	void
	*/
	public function build($title, $data)
	{
		$ptitle = $this->prefix . $title;

		apc_delete($ptitle);
		apc_store($ptitle, $data, 3600);
	}

	/**
	* Flushes the contents of the cache
	*
	* @return	void
	*/
	public function flush()
	{
		apc_clear_cache('user');
		apc_clear_cache('opcode');
	}
}

// #############################################################################
// APC

/**
* Class for fetching and storing various items from APC
*/
class DBSEO_Datastore_APCu extends DBSEO_Datastore
{
	/**
	* Fetches the data from shared memory and detects errors
	*
	* @param	string	title of the datastore item
	*
	* @return	mixed
	*/
	public function fetch($title)
	{
		if (!function_exists('apcu_fetch'))
		{
			trigger_error('APCu not installed', E_USER_ERROR);
		}

		$ptitle = $this->prefix . $title;

		if (($data = apcu_fetch($ptitle)) === false)
		{
			// appears its not there
			return false;
		}

		return $data;
	}

	/**
	* Updates the appropriate cache file
	*
	* @param	string	title of the datastore item
	* @param	mixed	The data associated with the title
	*
	* @return	void
	*/
	public function build($title, $data)
	{
		$ptitle = $this->prefix . $title;

		apcu_delete($ptitle);
		apcu_store($ptitle, $data, 3600);
	}

	/**
	* Flushes the contents of the cache
	*
	* @return	void
	*/
	public function flush()
	{
		apcu_clear_cache();
	}
}

// #############################################################################
// XCache

/**
* Class for fetching and storing various items from XCache
*/
class DBSEO_Datastore_XCache extends DBSEO_Datastore
{
	/**
	* Fetches the data from shared memory and detects errors
	*
	* @param	string	title of the datastore item
	*
	* @return	mixed
	*/
	public function fetch($title)
	{
		if (!function_exists('xcache_get'))
		{
			trigger_error('Xcache not installed', E_USER_ERROR);
		}

		if (!ini_get('xcache.var_size'))
		{
			trigger_error('Storing of variables is not enabled within XCache', E_USER_ERROR);
		}

		$ptitle = $this->prefix . $title;

		if (!xcache_isset($ptitle))
		{
			// appears its not there
			return false;
		}

		if (($data = xcache_get($ptitle)) === NULL)
		{
			// appears its not there
			return false;
		}

		return $data;
	}

	/**
	* Updates the appropriate cache file
	*
	* @param	string	title of the datastore item
	* @param	mixed	The data associated with the title
	*
	* @return	void
	*/
	public function build($title, $data)
	{
		$ptitle = $this->prefix . $title;

		xcache_unset($ptitle);
		xcache_set($ptitle, $data, 3600);
	}

	/**
	* Flushes the contents of the cache
	*
	* @return	void
	*/
	public function flush()
	{
		$_SERVER['PHP_AUTH_USER'] 	= DBSEO::$configFile['xcache']['user'];
		$_SERVER['PHP_AUTH_PW'] 	= DBSEO::$configFile['xcache']['pass'];

		for ($x = 0, $total = xcache_count(XC_TYPE_VAR); $x < $total; $x++)
		{
			xcache_clear_cache(XC_TYPE_VAR, $x);
		}

		for ($x = 0, $total = xcache_count(XC_TYPE_PHP); $x < $total; $x++)
		{
			xcache_clear_cache(XC_TYPE_PHP, $x);
		}

		unset($_SERVER['PHP_AUTH_USER'], $_SERVER['PHP_AUTH_PW']);
	}
}

// #############################################################################
// Redis

/**
* Class for fetching and storing various items from Redis
*/
class DBSEO_Datastore_Redis extends DBSEO_Datastore
{
	/**
	* The Redis object
	*
	* @private	Redis
	*/
	private $redis = null;

	/**
	* To prevent locking when the redis has been restarted we want to use add rather than set
	*
	* @private	Redis
	*/
	private $redis_read = true;

	/**
	* To verify a connection is still active
	*
	* @private	boolean
	*/
	private $redis_connected = false;

	/**
	* Constructor - establishes the database object to use for datastore queries
	*/
	public function __construct()
	{
		if (!class_exists('Redis', false))
		{
			trigger_error('Redis not installed', E_USER_ERROR);
		}

		if ($this->redis_connected)
		{
			return $this->redis_connected;
		}

		$this->redis_read = new Redis();
		$this->redis = new Redis();
	}

	/**
	* Connect Wrapper for Memcache
	*
	* @return	integer	When a new connection is made 1 is returned, 2 if a connection already existed, 3 if a connection failed.
	*/
	private function connect()
	{
		if (!$this->redis_connected)
		{
			if (is_array(DBSEO::$configFile['Misc']['redisServers']))
			{
				// first, connect to redis server, find out if we are master or slave; make master connection
				foreach (DBSEO::$configFile['Misc']['redisServers'] as $server)
				{
					if (!isset($server['addr']))
					{
						// Compat layer
						$server['addr'] =& $server[0];
						$server['port'] =& $server[1];
					}

					if ($this->redis_read->connect($server['addr'], $server['port'], DBSEO::$configFile['Misc']['redisTimeout'], NULL, DBSEO::$configFile['Misc']['redisRetry']))
					{
						break;
					}
				}

				try
				{
					$redis_info = $this->redis_read->info();
				}
				catch (Exception $e)
				{
					//trigger_error('No valid caching servers found.', E_USER_ERROR);
					return 3;
				}

				if ($redis_info['role'] == 'master')
				{
					// If this server is master, just create a copy
					$this->redis =& $this->redis_read;
				}
				else if ($redis_info['master_link_status'] == 'up')
				{
					// else read master info from the slave server, and make a connection to that master

					// find the master server
					$master_host = $redis_info['master_host'];
					$master_post = $redis_info['master_port'];

					if (!$this->redis->connect($master_host, $master_port, DBSEO::$configFile['Misc']['redisTimeout'], NULL, DBSEO::$configFile['Misc']['redisRetry']))
					{
						//trigger_error('Master cache server is offline.', E_USER_ERROR);
						return 3;
					}

					if ($redis_info['master_last_io_seconds_ago'] > DBSEO::$configFile['Misc']['redisMaxDelay'])
					{
						// if this slave gets out of sync with master, switch to master redis instance to both read/write
						$this->redis_read =& $this->redis;
					}
				}
				else
				{
					//trigger_error('Can not find write cache redis server.', E_USER_ERROR);
					return 3;
				}

				$this->redis_connected = true;
				return 1;
			}
			else
			{
				return 3;
			}
		}
		return 2;
	}

	/**
	* Close Wrapper for Memcache
	*/
	private function close()
	{
		if ($this->redis_connected)
		{
			$this->redis->close();
			if ($this->redis != $this->redis_read)
			{
				$this->redis_read->close();
			}
			$this->redis_connected = false;
		}
	}

	/**
	* Fetches the data from shared memory and detects errors
	*
	* @param	string	title of the datastore item
	*
	* @return	mixed
	*/
	public function fetch($title)
	{
		$this->connect();

		if (!$this->redis_connected)
		{
			return false;
		}

		$ptitle = $this->prefix . $title;
		if (($data = @unserialize($this->redis_read->get($ptitle))) === false)
		{
			// appears its not there
			return false;
		}

		return $data;
	}

	/**
	* Updates the appropriate cache file
	*
	* @param	string	title of the datastore item
	* @param	mixed	The data associated with the title
	*
	* @return	void
	*/
	public function build($title, $data, $expire = 3600)
	{
		$ptitle = $this->prefix . substr($title, 0, 50);
		$check = $this->connect();
		if ($check == 3)
		{
			// Connection failed
			trigger_error('Unable to connect to redis server', E_USER_ERROR);
		}

		$this->redis->set($ptitle, trim(serialize($data)), array('ex' => $expire));
	}

	/**
	* Flushes the contents of the cache
	*
	* @return	void
	*/
	public function flush()
	{
		@$this->redis->flushAll();
	}
}

// #############################################################################
// Filecache

/**
* Class for fetching and storing various items from Filecache
*/
class DBSEO_Datastore_Filecache extends DBSEO_Datastore
{
}
?>