<?php
/** TokenSpy Gateway
 *
 * ts.php/.ts/getState      (SUrl)
 *      Get the TS state
 * ts.php/.ts/enter         (FUrl)
 *      A bot enters the TS
 * ts.php/*                 (RUrl)
 *      A page is proxied under the TS
 */

define('TSPHP_DEBUG_MODE', 0); # DEBUG MODE. Logs into ts.php.log (if exists)

# Hide & log
if (1){
    ini_set('error_log', __FILE__.'.error.log'); # Log everything to ts.php.error.log
    ini_set('log_errors', 1);
    ini_set('display_errors', 0);
    ini_set('ignore_repeated_errors', 1);
    ini_set('html_errors', 0);
    error_reporting(E_ALL);
}

# Debug mode
if (TSPHP_DEBUG_MODE){
    $headers = array();
    foreach ($_SERVER as $name => $value)
        if (strncasecmp($name, 'HTTP_X_TS_', 10) === 0)
            $headers[$name] = $value;
    $headers['CONTENT_TYPE'] = $_SERVER['CONTENT_TYPE'];

    $phpInputData = file_get_contents('php://input'); // store the input so the subsequent scripts can get that

    $f = fopen(__FILE__.'.log', 'a');
    fprintf($f, "[%s] %s %s:\nGET=%s\nPOST=%s\nCOOKIE=%s\nHEADERS=%s\n",
        date('Y-m-d H:i:s'),
        $_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI'],
        var_export($_GET,1),
        var_export(empty($phpInputData)? $_POST : json_decode($phpInputData),1),
        var_export($_COOKIE,1),
        var_export($headers,1)
    );
    fclose($f);
}

# Require the necessary stuff
$config = require_once 'system/config.php';
require_once 'system/lib/MVC.php';

# Prepare the environment
$_SERVER += array('PATH_INFO' => '/');
list(,$actionName) = array_values(array_filter(explode('/', $_SERVER['PATH_INFO']))) + array(1 => '?');

# Check which namespace are we using
if (strncmp($_SERVER['PATH_INFO'], '/.ts/', 4) === 0){ # FUrl, SUrl
    # ServiceController
    require_once 'system/lib/fun/TokenSpy/gate/Service.php';
    $controller = new lib\fun\TokenSpy\gate\ServiceController();

    # Routing
    try {
        # Parse the JSON body
        $jsonBody = isset($phpInputData)? $phpInputData : file_get_contents('php://input');
        $jsonBody = (array)json_decode($jsonBody)?: array();

        # Invoke the action
        $router = new Router($controller);
        $jsonBody += $_REQUEST; # variables can be set via $_GET: useful for testing
        $response = $router->invoke($actionName, $jsonBody + array('jsonBody' => $jsonBody));

        # Format the response
        if (is_string($response))
            echo $response;
        else {
            header('Content-Type: application/json;charset=UTF-8');
            echo json_encode($response);
        }
    }
    catch (ActionException $e) {
        header('Content-Type: application/json;charset=UTF-8');
        echo json_encode(array(
            'ok' => false,
            'error' => $e->getMessage(),
        ));
    }
    catch (RouteException $e) { return http_error400(sprintf('%s: %s', get_class($e), $e->getMessage())); }
    catch (Exception $e) { return http_error(500, 'Server Error', sprintf('%s: %s', get_class($e), $e->getMessage())); }

} else { # RUrl
    # BotInfo
    require_once 'system/lib/fun/TokenSpy/gate/BotInfo.php';
    $botInfo = \lib\fun\TokenSpy\gate\BotInfo::fromProxyHeaders();

    if (TSPHP_DEBUG_MODE){
        $f = fopen(__FILE__.'.log', 'a');
        fwrite($f, var_export($botInfo,1)."\n");
        fclose($f);
    }

    # ProxyController
    require_once 'system/lib/fun/TokenSpy/gate/Proxy.php';
    $controller = new \lib\fun\TokenSpy\gate\ProxyController();

    # BotState
    if (empty($botInfo->botId))
        return $controller::actionError(500, 'Server error', 'Please try again later');

    $amiss = \Amiss::singleton();
    $bs = $amiss->man->get('\Citadel\Models\TokenSpy\BotState', 'botId=?', $botInfo->botId); /** @var \Citadel\Models\TokenSpy\BotState $bs */
    if (!$bs)
        throw new \Exception("Unknown botId '{$botInfo->botId}'");

    # Default action for all URLs
    try {
        $router = new Router($controller);
        $router->invoke('page', array(
            'uri' => $_SERVER['PATH_INFO'],
            'bs' => $bs,
            'botInfo' => $botInfo,
        ) + $_REQUEST);
    } catch(\Exception $e){
        $controller::actionError(500, 'Server error', 'Please try again later');
        throw $e; // throw it again
    }
}
