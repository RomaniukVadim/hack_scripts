<html>
<title>Connects Control</title>
<form action="control.php" method=POST>
<input type=submit name=delete value="Wipe Logs"> | &nbsp; <input type=submit name=tasksclean value="Kill Tasks">

<body >

<div id="content"></div>


<?php

function background_exec($command)
{
	if(substr(php_uname(), 0, 7) == 'Windows')
	{
		pclose(popen('start "background_exec" ' . $command, 'r'));
	}
	else
	{
		exec($command . ' > /dev/null &');
	}
}

$file = fopen("log.txt","r");
$ew = fread($file,filesize("log.txt"));
echo "<pre>$ew</pre>";
fclose($file);

if (isset($_POST['delete'])) {
$file = fopen("log.txt","w+");
fwrite($file, " ");
fclose($file); 
echo "<script language=JavaScript>window.location.href=\"/control.php\";</script>";

}

if (isset($_POST['tasksclean'])) {
background_exec("taskkill /F /IM abcs.exe");
echo "<script language=JavaScript>window.location.href=\"/control.php\";</script>";

}

?>
