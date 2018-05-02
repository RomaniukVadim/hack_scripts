<?php
require_once(dirname(__FILE__) . '/autoload.php');

$client = new Google_Client();
	$client->setApprovalPrompt('force');
	$client->setAccessType('offline');
	$client->setClientId('236943477594-9h4gtucdnif005j436odqgql934es0uo.apps.googleusercontent.com');
	$client->setClientSecret('8n1ufZDCcAMD0eVBuShqqObX');
	$client->setRedirectUri('urn:ietf:wg:oauth:2.0:oob');
	$client->setScopes(array(
		Google_Service_Analytics::ANALYTICS,
		Google_Service_Analytics::ANALYTICS_EDIT,
		Google_Service_Analytics::ANALYTICS_READONLY,
		Google_Service_Webmasters::WEBMASTERS,
		Google_Service_Webmasters::WEBMASTERS_READONLY,
		Google_Service_SiteVerification::SITEVERIFICATION,
		Google_Service_SiteVerification::SITEVERIFICATION_VERIFY_ONLY
	));

	//$client->setClassConfig('Google_Logger_File', 'file', DIR . '/debug.txt');
	//$client->setLogger(new Google_Logger_File($client));


if (isset($_REQUEST['oauth_logout']) OR !isset($vbulletin->dbtech_dbseo_oauth))
{
	// Debug purposes, or default install
	$vbulletin->dbtech_dbseo_oauth = '';
	build_datastore('dbtech_dbseo_oauth', trim($vbulletin->dbtech_dbseo_oauth), 0);
}
?>