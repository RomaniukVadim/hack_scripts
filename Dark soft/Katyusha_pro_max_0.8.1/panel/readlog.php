<?php
	include_once("config.php");

	$lines=array();
	$fp = fopen($logfile, "r");
	while(!feof($fp))
	{
	   $line = fgets($fp, 4096);
	   array_push($lines, $line);
	   if (count($lines)>20)
	       array_shift($lines);
	}
	fclose($fp);

	foreach ($lines as $line)
		print $line;

?>