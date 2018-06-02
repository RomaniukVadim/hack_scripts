<?php
require_once __DIR__.'/http.php';

/**
 * Remote script HTTP-RPC client
 */
class RemScriptClient {
    /** Create a remote script client
     * @param string $script_url    URL of the script
     * @param mixed $script_config  Arbitrary, static config data sent alongside with each request
     */
    function __construct($script_url, $script_config){
        $this->script_url = $script_url;
        $this->config = $script_config;
    }

    /** Arbitrary data sent alongside each request.
     * Is implemented by subclasses
     * @var array
     */
    public $config;

    /** Generic request using the protocol:
     * POSTs data & files, checks the magic header
     * @param array $post Arbitrary data to POST
     * @param array $files Files to upload: { name: path | [path, filename] | [path, filename, mimetype] }
     * @return HttpResponse
     * @throws RemScriptProtocolError
     */
    function _request($post, $files, $timeout){
        # Prepare
        $request = new HttpRequest($this->script_url, 'POST', array('timeout' => $timeout));
        $request->mimicBrowser();
        $request->headers['Connection'] = 'Close';
        $request->post($post);
        if (!empty($files))
            foreach ($files as $name => $upload){
                list($path, $filename, $mimetype) = (array)$upload + array(null, null, null);
                $request->upload($name, $path, $filename, $mimetype);
            }

        # Request
        try {
            $response = $request->open();
        } catch (HttpRequestError $e){
            throw new RemScriptProtocolError('Request error: '.$e->getMessage(), RemScriptProtocolError::REQUEST_ERROR, $e);
        }

        # Response: check code
        if ($response->code != 200)
            throw new RemScriptProtocolError('Response code: '.$response->code);

        # Response: check magic
        $expected = self::RESPONSE_MAGIC;
        $actual = fread($response->f, strlen($expected));
        if ($actual !== $expected)
            throw new RemScriptProtocolError('Wrong magic: '.var_export($actual,1));

        # All okay
        return $response;
    }

    const RESPONSE_MAGIC = '(REMSCRIPT=)';
    const DATA_LEN_SZ = 9;

    /** Generic data request using the protocol:
     * POSTs data & files, checks the magic header, reads the data.
     * The data is stored in HttpResponse::$data
     * @param array $post Arbitrary data to POST
     * @param array $files Files to upload: { name: path | [path, filename] | [path, filename, mimetype] }
     * @return HttpResponse
     * @throws RemScriptProtocolError
     */
    function _requestData($post, $files = null, $timeout){
        # Request
        $response = $this->_request($post, $files, $timeout);

        # Read data length
        $len = fread($response->f, self::DATA_LEN_SZ);
        if (strlen($len) != self::DATA_LEN_SZ || !is_numeric($len) || $len == 0)
            throw new RemScriptProtocolError('Wrong data length: ('.strlen($len).')'.var_export($len,1), RemScriptProtocolError::WRONG_LENGTH);
        $len = (int)$len;

        # Read data
        $data = fread($response->f, $len);
        if (strlen($data) != $len)
            throw new RemScriptProtocolError('Not enough data: received '.strlen($data).'/'.$len.' bytes', RemScriptProtocolError::NOT_ENOUGH_DATA);

        # Parse data
        $data = unserialize($data);
        if ($data === FALSE)
            throw new RemScriptProtocolError('Corrupted data response', RemScriptProtocolError::CORRUPTED_DATA);

        # Store data
        $response->data = $data;

        # Finish
        return $response;
    }

    /** Call a remote method
     * @param string $method The remote method to call
     * @param array $arguments Method arguments
     * @param mixed $payload Arbitrary payload data to send alongside with this request
     * @param array $files Files to upload: { name: path | [path, filename] | [path, filename, mimetype] }
     * @return array
     *      The data got from the remote method.
     *      ['.err'] - remote exception string
     *      ['.phperr'] - array of remote PHP error messages
     * @throws RemScriptProtocolError
     * @throws RemScriptRemoteError
     */
    function call($method, $arguments = array(), $payload = null, $files = null, $timeout = 60){
        # Request
        $post = array(
            'method' => $method,
            'args' => $arguments,
            'config' => $this->config,
            'payload' => $payload,
            'timeout' => $timeout,
        );
        $response = $this->_requestData($post, $files, $timeout);
        $data = $response->data;

        # Check type
        if (!is_array($data))
            throw new RemScriptProtocolError('Wrong data format: should be an array, got `'.gettype($data).'`', RemScriptProtocolError::WRONG_DATA_FORMAT);

        # Error?
        if (is_array($data) && isset($data['.err']))
            throw new RemScriptRemoteError($data['.err'], RemScriptRemoteError::REMOTE_ERROR);

        # Return the result
        return $data;
    }
}



/**
 * Base class for all RemScript exceptions
 */
class RemScriptError extends Exception {
}

/** Protocol errors
 */
class RemScriptProtocolError extends RemScriptError {
    const REQUEST_ERROR = 1;

    const WRONG_MAGIC = 10;
    const WRONG_LENGTH = 11;
    const NOT_ENOUGH_DATA = 12;
    const CORRUPTED_DATA = 13;

    const WRONG_DATA_FORMAT = 20;
    const REMOTE_ERROR = 21;
}

/** Remote errors
 */
class RemScriptRemoteError extends RemScriptProtocolError {}
