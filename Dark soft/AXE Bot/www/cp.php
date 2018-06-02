<?php

error_reporting(0);

define("CORE_FILE", basename(__FILE__));
define("DEFAULT_URI", CORE_FILE . "?act=stats");
define("CORE", "core/");


if (!isset($_GET["act"])) {
	header("Location: " . DEFAULT_URI);
}

require_once(CORE . "config.php");
require_once(CORE . "global.php");

if (!CsrCheckAuth(CsrGetCookie("user"), CsrGetCookie("pass"))) {
	require_once(CORE . "login.php");
	die();
}

CsrSetCookie("user", CsrGetCookie("user"), LIVE_AUTH_COOKIE);
CsrSetCookie("pass", CsrGetCookie("pass"), LIVE_AUTH_COOKIE);



$act = $_GET["act"];
$modules = array("ajax", "bot", "bots", "dga", "edit_config", "login", "report", "reports", "script", "scripts", "stats", "updater");
$path = CORE . $act . ".php";

if (in_array($act, $modules) && file_exists($path)) {
	require_once($path);
}
else if ($act == "logout") {
	CsrRemoveCookie("user");
	CsrRemoveCookie("pass");
	header("Location: " . DEFAULT_URI);
}
else {
	header("Location: " . DEFAULT_URI);
}

?>