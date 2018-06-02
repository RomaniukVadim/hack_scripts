<?php
error_reporting(0);

@header('HTTP/1.0 404 Not Found');
@header('Status: 404 Not Found');
$_SERVER['REDIRECT_STATUS'] = 404;
?>
<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN">
<head>
   <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
   <title>404 Not Found</title>
</head>
<body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server. If you entered the URL manually please check your spelling and try again.</p>
</body>
</html>