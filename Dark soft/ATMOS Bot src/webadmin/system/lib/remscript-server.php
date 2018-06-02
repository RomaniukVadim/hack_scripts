<?php
/** Remote script HTTP-RPC server
 */
class RemScriptServer {
    function __construct(){
    }

    /** Debug mode enabled? Includes logging
     * @var bool
     */
    public $debug;

    /** Error log filename
     * @var string|null
     */
    public $error_log;

    function _handle_fatal_error(){
            $error = error_get_last();
            if($error !== NULL)
                echo $this->_errorResponse('FATAL: '.$error['message']);
    }

    /** Configure error logging
     * @param bool $debug Debug mode
     * @param string $error_log Error logging file. Suggestion: __FILE__.'.log'
     * @return RemScriptServer
     */
    function config_err($debug = 0, $error_log = null){
        $this->debug = $debug;
        $this->error_log = $error_log;

        # Error reporting
        ini_set('display_errors', 0);
        ini_set('html_errors', 0);

        if ($debug && $error_log){
            ini_set('error_log', $error_log);
            ini_set('log_errors', 1);
        }

        # Fatal error handling
        register_shutdown_function(array($this, '_handle_fatal_error'));

        return $this;
    }

    /** Log something
     * @param string $line
     */
    protected function _log($line){
        if (!$this->debug || !$this->error_log)
            return;

        if ($f = @fopen($this->error_log, 'a')){
            fwrite($f, $line);
            fclose($f);
        }
    }

    /** Restrict access
     * @return RemScriptServer
     */
    function config_access($restricted = true){
        if($restricted && php_sapi_name()!='cli' && @$_SERVER['REQUEST_METHOD'] !== 'POST'){
            header('HTTP/1.0 404 Not found');
            die('Not found');
        }
        return $this;
    }

    /** Disable output buffering
     * @return RemScriptServer
     */
    function config_ob(){
        do { @ob_end_clean(); } while(ob_get_level()>0);
        @ob_end_flush();
        ini_set("output_buffering", 0);
        ini_set("zlib.output_compression", 0);
        return $this;
    }

    /** Disable magic quotes
     * @return RemScriptServer
     */
    function config_mquot(){
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
        return $this;
    }

    /** Configure a session as a persistent storage.
     * Usage: session_start(); <operate> session_write_close();
     * @param string $name Session name. Suggestion: md5(__FILE__.'salt');
     * @return RemScriptServer
     */
    function config_session($name = 'a'){
        # Make sure there's no active session
        @session_destroy();
        @session_write_close();

        # Prepare the session
        ini_set('session.use_cookies', '0');
        session_cache_limiter( FALSE );
        session_name($name);
        session_id($name);
        return $this;
    }

    /** Respond with arbitrary data
     * @param mixed $data
     */
    protected function _response($data){
        # Add PHPerrors data, if any
        $phperrors = $this->_collectedPhpErrors(10, 20);
        if (!empty($phperrors))
            $data['.phperr'] = $phperrors;
        $this->_collectPhpErrors(false); # disable the possibly enabled collector

        # Respond
        $sdata = serialize($data);
        return '(REMSCRIPT=)'.sprintf("%09d", strlen($sdata)).$sdata; # TODO: maybe, always send an object with 'err' possible key + 'warn' array + 'ret' return data?
    }

    /** Respond with an error
     * @param string $message The error message
     * @param array $extra Any extra data
     * @return string The response
     */
    protected function _errorResponse($message, array $extra = array()){
        $data = $extra + array('.err' => $message);
        return $this->_response($data);
    }

    /** When a long-running background method is to be launched, we need to print junk unless the client is disconnected:
     * some webservers, like nginx, buffer the output.
     */
    private function _responseJunk(){
        ignore_user_abort(1);
        $junk = str_repeat("\n\n\n\n\n\n\n\n", 128);
        for ($i=0;;$i++){
            echo $junk; # 1 Kbyte
            if (connection_aborted() || $i>30) break; # success or reasonable limit
        }
    }

    /** Call: method name
     * @var string
     */
    public $method;

    /** Call: method arguments
     * @var array
     */
    public $args;

    /** Call: config data
     * @var array
     */
    public $config;

    /** Call: payload data
     * @var array|null
     */
    public $payload;

    /** Call: timeout setting
     * @var int
     */
    public $timeout;

    /** Call: async mode: next callback name. The client won't wait for it to finish
     * @var string|null
     */
    public $async;

    /** Collected PHP errors.
     * @var string[]|null
     */
    private $_phperr = null;

    /** Collected PHP errors count.
     * Useful for methods that wish to die on some error rate limit
     * @var int
     */
    protected $_phperr_count = 0;

    /** Toggle collecting PHP errors for this method call.
     * All errors are added to the response message using '.phperr' key
     */
    protected function _collectPhpErrors($enable){ # FIXME: this does not actually enable errors logging. CHECK!
        if ($enable){
            if (is_null($this->_phperr))
                set_error_handler(array($this, '_collectorPhpErrors'));
            $this->_phperr = array();
            $this->_phperr_count = 0;
        } else {
            if (!is_null($this->_phperr))
                restore_error_handler();
            $this->_phperr = null;
            $this->_phperr_count = 0;
        }
    }

    /** Get the collected PHP errors. Performs uniquification & limitation of the result set.
     * @param int $linelimit    Limit for the error count at one line
     * @param int $limit        Limit for the total number of logged errors
     * @return string[]
     */
    private function _collectedPhpErrors($linelimit = 10, $limit = 20){
        if (empty($this->_phperr))
            return array();
        $collected = array();
        foreach ($this->_phperr as $line => $errors)
            foreach (array_slice(array_unique($errors), 0, $linelimit) as $e)
                $collected[] = "@$line: $e";
        return array_slice($collected, 0, $limit);
    }

    /** PHP errors collector */
    private function _collectorPhpErrors($no, $str, $file, $line){
        $this->_phperr_count++;
        if (!isset($this->_phperr[$line]))
            $this->_phperr[$line] = array();
        if (count($this->_phperr[$line]) == 100) # reasonable limit
            return;
        $this->_phperr[$line][] = $str;
    }

    /** Handle a method call using $this->method*() methods
     * @param array $request The request data to handle
     */
    function handleMethodCall($request){
        # Format
        if (!isset($request['method'])){
            echo $this->_errorResponse('Call: `method` key is not set');
            return false;
        }

        # Collect
        $this->method = $request['method'];
        $this->args = empty($request['args'])? array() : $request['args'];
        $this->config = empty($request['config'])? array() : $request['config'];
        $this->payload = empty($request['payload'])? null : $request['payload'];
        $this->timeout = empty($request['timeout'])? 60 : $request['timeout'];

        # Prepare
        $method = 'method'.$this->method;
        if (!method_exists($this, $method)){
            echo $this->_errorResponse('Call: Unknown method '.var_export($method, 1));
            return false;
        }

        # Timeout
        if (!$this->async)
            set_time_limit($this->timeout);

        # Call & handle errors
        try { $ret = call_user_func_array(array($this, $method), $this->args); }
        catch (Exception $e){
            echo $this->_errorResponse($e->getMessage());
            return false;
        }

        # Check whether 'async' was correctly specified
        if ($this->async && !method_exists($this, $this->async)){
            echo $this->_errorResponse('Call: Wrong `async` set: '.var_export($this->async, 1));
            return false;
        }

        # Results
        $content = $this->_response($ret);
        header('Connection: close');
        header('Content-Length: '.strlen($content)); # should make the client disconnect after it reads the first part
        echo $content;

        # For long-running tasks, make sure the client has disconnected
        if ($this->async){
            ignore_user_abort(1);
            $this->_responseJunk();
            $this->{$this->async}();
        }

        return true;
    }
}
