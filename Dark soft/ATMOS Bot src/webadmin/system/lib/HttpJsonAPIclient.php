<?php
/** HTTP JSON API client
 */
class HttpJsonAPIclient {
    protected $_endpoint;
    protected $_timeout;

    /** Custom headers
     * @var string[]
     */
    public $headers = array();

    /** Initialize the API client upon a known endpoint
     * @param string $endpoint The API endpoint URL
     * @param int $timeout Socket timeout
     */
    function __construct($endpoint, $timeout = 2){
        $this->_endpoint = $endpoint;
        $this->_timeout = $timeout;
    }

    /** Clone a namespaced API client
     * @param string $namespace
     * @return HttpJsonAPIclient
     */
    function of($namespace){
        $api = clone $this;
        $api->_endpoint .= "/$namespace";
        return $api;
    }

    /** Include the NodeJS authentication cookie
     * @param string $login
     */
    function nodejsAuthCookie($login){
        $this->headers[] = "Cookie: authToken=".urlencode(nodejs_generate_token($login));
        return $this;
    }

    /** HTTP POST JSON
     * @param string $url
     * @param mixed $post
     * @return mixed
     * @throws Exception
     */
    protected function _httpJsonPost($url, $post){
        $headers = $this->headers;
        $headers[] = "Content-Type: application/json";

        # Prepare
        $ctx = stream_context_create($a = array(
            'http' => array(
                'method'  => 'POST',
                'content' => json_encode($post),
                'timeout' => $this->_timeout,
                'header' => implode("\r\n", $headers),
            )
        ));

        # Request
        $f = fopen($url, 'r', FALSE, $ctx);
        if (!$f){
            $e = error_get_last();
            $err = $e['message'];
            throw new \Exception($err);
        }

        # Response
        $response = stream_get_contents($f);
        return json_decode($response);
    }

    /** Call an API method
     * @param string $method
     * @param mixed $data
     * @return mixed
     */
    function callMethod($method, $data){
        return $this->_httpJsonPost("{$this->_endpoint}/{$method}", $data);
    }
}
