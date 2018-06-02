<?php
define('CP', TRUE);
error_reporting(E_ALL);
set_time_limit(0);
if(!is_readable(__DIR__.'/core/config.php'))
	exit;
include __DIR__.'/core/config.php';  

$checktime = time();

$database = new mysqli($MYSQL_HOST, $MYSQL_LOGIN, $MYSQL_PASSWORD, $MYSQL_DB);
		if(mysqli_connect_error())
			exit;

$DBtasks = mysqli_query($database, "SELECT * FROM `task` WHERE `autoupdate` = '1'") or die();
if(mysqli_num_rows($DBtasks) > 0)
{
	while($row = mysqli_fetch_array($DBtasks))
	{
		
		if(empty($row['updateinterval']) || empty($row['updatelink']))
			continue;

		if(!empty($row['updatelast'])){
			if($row['updatelast']+($row['updateinterval'] * 60) < $checktime)
				continue;
		}
		
		$data = file_get_contents($row['updatelink']);
		if($data !== FALSE){
			@mysqli_query($database, "UPDATE `task` SET `updatelast` = '{$checktime}' WHERE `id` = {$row['id']};");
			file_put_contents(__DIR__.'/files/'.$row['filehash'], $data);
		}
		
		
	}
}
?>