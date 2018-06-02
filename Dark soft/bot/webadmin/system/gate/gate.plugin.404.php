<?php
/** Display 404 error page for banned countries
 */


/** Simulate 404 page for blacklisted countries
 * NOTE: this function DOES NO DIE so we can actually log the bot presence!
 */
function e404plugin_display(){
	$sapi_name = php_sapi_name();
	if ($sapi_name == 'cgi' || $sapi_name == 'cgi-fcgi')
		header('Status: 404 Not Found');
		else
		header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found');
	
	echo <<<HTML
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL {$_SERVER['REQUEST_URI']} was not found on this server.</p>
<hr>
<address>{$_SERVER['SERVER_SOFTWARE']} Server at {$_SERVER['HTTP_HOST']} Port {$_SERVER['SERVER_PORT']}</address>
</body></html>
HTML;
	return '';
	}

function e404plugin_check($country){
	$country_allowed = true;
	if (!empty($GLOBALS['config']['allowed_countries_enabled']))
		$country_allowed = in_array( $country, explode(',', $GLOBALS['config']['allowed_countries']) );

	if (!$country_allowed)
		e404plugin_display(); # will die() later :)
	
	return $country_allowed;
	}