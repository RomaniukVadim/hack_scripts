<?php
/**
 * HTTP utilities
 */

/**
 * A single HTTP request forge
 */
class HttpRequest {
    # TODO: handle GET arguments
    # TODO: autoretry
    # TODO: auto-referer
    # TODO: follow redirects
    # TODO: settings: throw errors on HTTP codes, connection errors, socket timeout

    /** The URL to request
     * @var string
     */
    public $url;

    /** The request method
     * @var string
     */
    public $method;

    /** Request options
     * @var array
     */
    public $options;

    /** Request headers to send
     * @var HttpHeaders
     */
    public $headers;

    /** POST data
     * @var array
     */
    public $post = array();

    /** File uploads: [ {path: string, filename: string, mimetype: string } ]
     * @var array
     */
    public $upload = array();

    function __construct($url, $method = 'GET', array $options = array('timeout' => 10)){
        $this->url = $url;
        $this->method = $method;
        $this->headers = new HttpHeaders();
        $this->options = $options;
    }

    /**
     * Mimic a browser with headers
     */
    function mimicBrowser(){
        $this->headers['Accept'] = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
        $this->headers['Accept-Language'] = 'en-us,en;q=0.5';
        $this->headers['User-Agent'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:14.0) Gecko/20100101 Firefox/14.0.1';
    }

    /** Add POST data fields
     * @param array $post
     */
    function post($post){
        $this->post += $post;
    }

    /** Add a file upload
     * @param string $name Field name
     * @param string $path File path
     * @param string? $filename Filename for the request
     * @param string? $mime MIME type for the request
     * @returns bool Whether the file was successfully loaded
     */
    function upload($name, $path, $filename = null, $mime = null){
        if (!file_exists($path) || !is_readable($path))
            return false;

        $upload = new stdClass;
        $upload->name = $name;
        $upload->path = $path;
        $upload->filename = is_null($filename)? basename($path) : $filename;
        $upload->mime = $mime;
        $upload->contents = @file_get_contents($path);
        if ($upload->contents === FALSE)
            return false;

        $this->upload[] = $upload;
        return true;
    }

    protected function _http_build_query_array(array $args, $_pre = '', $_post = ''){
        $ret = array();
        foreach ($args as $k => $v)
            if (is_array($v))
                $ret += $this->_http_build_query_array($v, $_pre.$k.$_post.'[', ']');
            else
                $ret[$_pre.$k.$_post] = $v;
        return $ret;
    }

    /** Recursively URL-encodes the specified array of arguments
     * @param array $args	array('module' => 'news', 'page' => 1)
     * @return string arguments in "a=b&c=d&" form
     */
    protected function _http_build_query(array $args, $separator = '&') {
        $ret = '';
        foreach ($this->_http_build_query_array($args) as $k => $v)
            $ret .= $k.'='.rawurlencode($v).$separator;
        return $ret;
    }

    /**
     * Create a stream context for the request
     */
    protected function _ctx(){
        $content = '';

        # Prepare headers
        $headers = $this->headers->exportPlain();

        # Post data
        if (!empty($this->post) || !empty($this->upload))
            $this->method = 'POST';

        # File uploads
        if (empty($this->upload)){
            // Generic POST
            $content = $this->_http_build_query($this->post);
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } else {
            # Prepare multipart
            $boundary = '--------------------------'.uniqid().uniqid();
            $headers[] = 'Content-Type: multipart/form-data; boundary='.$boundary;

            # Generic fields
            foreach ($this->_http_build_query_array($this->post) as $name => $value){
                $content .= "--".$boundary."\r\n";
                $content .= 'Content-Disposition: form-data; name="'.$name.'"'."\r\n";
                $content .= "\r\n";
                $content .= $value;
                $content .= "\r\n";
            }

            # File uploads
            foreach ($this->upload as $upload){
                $content .= "--".$boundary."\r\n";
                $content .= 'Content-Disposition: form-data; name="'.$upload->name.'"; filename="'.$upload->filename.'"'."\r\n";
                if (!empty($upload->mime))
                    $content .= 'Content-Type: '.$upload->mime."\r\n";
                $content .= "\r\n";
                $content .= $upload->contents;
                $content .= "\r\n";
            }

            # Finalize
            $content .= "--".$boundary."--\r\n";
        }

        // Create the context
        $ctx = stream_context_create(array(
            'http' => array(
                'method'  => $this->method,
                'content' => $content,
                'timeout' => $this->options['timeout'],
                'header' => implode("\r\n", $headers)
            )));
        ini_set('default_socket_timeout', $this->options['timeout']);

        #echo '<pre>', var_export($headers,1), htmlspecialchars($content), '</pre>'; die();

        // Finish
        return $ctx;
    }

    /** Issue a request
     * @throws HttpRequestError
     * @returns HttpResponse
     */
    function open(){
        # Prepare
        $ctx = $this->_ctx();

        # Connect
        $f = fopen($this->url, 'r', false, $ctx);
        if (!$f && !empty($http_response_header)){ # on timeout, the array is defined, but empty
            $e = error_get_last();
            throw new HttpRequestError('Connection to "'.$this->url.'" failed: '.$e['message'], HttpRequestError::CONN_FAILED);
        }

        # Make the object
        return new HttpResponse($this, $f, $http_response_header);
    }
}






/** Http Response object, got from HttpRequest::open()
 */
class HttpResponse {
    function __construct(HttpRequest $request, $f, $http_response_headers){
        $this->request[] = $request;
        $this->f = $f;

        # Process the status header
        list($this->protocol, $this->code, $this->status) = explode(' ', array_shift($http_response_headers), 3);

        # Make the 'headers' object
        $this->headers = new HttpHeaders($http_response_headers);

        # TODO: handle redirects
    }

    /** The original request objects chain
     * When a redirect was handled, contains >1 objects
     * @var HttpRequest[]
     */
    public $request = array();

    /** The response stream
     * @var resource
     */
    public $f;

    /** The response protocol
     * @var string
     */
    public $protocol;

    /** The response code
     * @var int
     */
    public $code;

    /** The response status
     * @var string
     */
    public $status;

    /** The response headers
     * @var HttpHeaders
     */
    public $headers;
}






/** Http Headers collection, both for request & response
 */
class HttpHeaders extends ArrayObject {
    # TODO: manage cookies
    # TODO: optionally, throw exceptions for HTTP error codes

    /** Create the object from PHP `$http_response_headers` variable.
     * NOTE: the first line should be shifted off there!
     * @param array $http_response_headers
     * @return HttpHeaders
     */
    public static function fromResponseVar($http_response_headers){
        $headers = new self;
        # Process headers
        foreach ($http_response_headers as $header){ # TODO: process cookies (repeated header)
            $header = array_map('trim', explode(':', $header, 2));
            $headers[  $header[0]  ] = $header[1];
        }
        return $headers;
    }

    /** Export as a plain array of strings
     * @return string[]
     */
    function exportPlain(){
        $headers = array();
        foreach ($this as $name => $value)
            $headers[] = "$name: $value";
        return $headers;
    }

    function __toString(){
        return implode("\r\n", $this->exportPlain());
    }
}






/**
 * HTTP connection error
 */
class HttpRequestError extends Exception {
    const CONN_FAILED = 1;
}
