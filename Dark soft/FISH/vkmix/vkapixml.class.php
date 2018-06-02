<?php
 
/**
 * VKAPI class for vk.com social network
 *
 * @package server API methods
 * @link http://vk.com/developers.php
 * @autor Oleg Illarionov
 * @version 1.0
 */
 
class vkapi {
    var $api_secret;
    var $app_id;
    var $api_url;
   
    function vkapi($app_id, $api_secret, $api_url = 'api.vk.com/api.php') {
        $this->app_id = $app_id;
        $this->api_secret = $api_secret;
        if (!strstr($api_url, 'http://')) $api_url = 'http://'.$api_url;
        $this->api_url = $api_url;
    }
   
    function api($method,$params=false) {
        if (!$params) $params = array();
        $params['api_id'] = $this->app_id;
        $params['v'] = '3.0';
        $params['method'] = $method;
        $params['timestamp'] = time();
        $params['format'] = 'xml';
//      $params['format'] = 'json';
        $params['random'] = rand(0,10000);
        ksort($params);
        $sig = '';
        foreach($params as $k=>$v) {
            $sig .= $k.'='.$v;
        }
        $sig .= $this->api_secret;
        $params['sig'] = md5($sig);
        $query = $this->api_url.'?'.$this->params($params);
        $res = file_get_contents($query);
        return $res;
//      return json_decode($res, true);
    }
   
    function params($params) {
        $pice = array();
        foreach($params as $k=>$v) {
            $pice[] = $k.'='.urlencode($v);
        }
        return implode('&',$pice);
    }
}
?>