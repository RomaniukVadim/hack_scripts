<?php namespace lib\fun\TokenSpy;

require_once 'system/global.php';
require_once 'system/lib/HttpJsonAPIclient.php';

/** TokenSpy Node API client
 */
class NodeApiClient {
    /** API client
     * @var \HttpJsonAPIclient
     */
    protected $_api;

    /** Singleton instance
     * @var NodeApiClient
     */
    static protected $_instance;

    protected function __construct(){
        $njs = $GLOBALS['config']['nodejs'];
        $this->_api = new \HttpJsonAPIclient("http://{$njs['host']}:{$njs['port']}/TokenSpy", 2);
        $this->_api->nodejsAuthCookie('gate');
        static::$_instance = $this;
    }

    static function get(){
        if (is_null(static::$_instance))
            static::$_instance = new static;
        return static::$_instance;
    }

    /** Emit an event into a room
     * @param string $room
     * @param string $event
     * @param mixed $msg
     * @return mixed
     */
    function emitEvent($room, $event, $msg){
        return $this->_api->callMethod("event/{$room}/{$event}", $msg);
    }
}
