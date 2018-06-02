<?php
defined('CP') or die();
$confFile = './core/config.php';


if(file_exists($confFile) && !defined('MYSQL_ERROR'))
{
	$inputdbhost = "";
	$inputdbname = "";
	$inputdbuser = "";
	$inputdbpass = "";
	$inputtheme = "";
	$inputsysinstall = "";
	$inputgueststats = "";
	
	$db_check_usrpwd = '';
	$db_check_db = '';
	$db_check_srv = '';
	
	if(!empty($_POST['username']) || !empty($_POST['password']))
	{
		if(!empty($_POST['username']) && !empty($_POST['password']))
		{
			$password = hash('sha256', $_POST['password']);
			@mysqli_free_result(@mysqli_query($database, "UPDATE `users` SET `password`='" . $password . ", '`username`='" . $_POST['username'] . "' WHERE `uid`='" . $_SESSION['uid'] . "';"));
			 logout();
		}else if(!empty($_POST['username']))
		{
			@mysqli_free_result(@mysqli_query($database, "UPDATE `users` SET `username`='" . $_POST['username'] . "' WHERE `uid`='" . $_SESSION['uid'] . "';"));
			 logout();
		}else
		{
			$password = hash('sha256', $_POST['password']);
			@mysqli_free_result(@mysqli_query($database, "UPDATE `users` SET `password`='" . $password . "' WHERE `uid`='" . $_SESSION['uid'] . "';"));
			 logout();
		}
	}
	
	
	if(!empty($_POST['dbhost']) || !empty($_POST['dbname']) || !empty($_POST['dbusername']) || !empty($_POST['dbpassword']) || !empty($_POST['sysinstall']) || !empty($_POST['gueststats']))
	{
		
		if (empty($_POST['dbhost'])) $inputdbhost = $MYSQL_HOST;
		else $inputdbhost = $_POST['dbhost'];

		if (empty($_POST['dbname'])) $inputdbname = $MYSQL_DB;
		else $inputdbname = $_POST['dbname'];

		if (empty($_POST['dbusername'])) $inputdbuser = $MYSQL_LOGIN;
		else $inputdbuser = $_POST['dbusername'];

		if (empty($_POST['dbpassword'])) $inputdbpass = $MYSQL_PASSWORD;
		else $inputdbpass = $_POST['dbpassword'];

	
		$testConn = @new mysqli($inputdbhost, $inputdbuser, $inputdbpass, $inputdbname);
		if (mysqli_connect_error()) {
			if(mysqli_connect_errno() == 1045){
				$db_check_usrpwd = 'has-error';
			}else if(mysqli_connect_errno() == 1049){
				$db_check_db = 'has-error';
			}else if(mysqli_connect_errno() == 2005){
				$db_check_srv = 'has-error';
			}else{
					$db_check_usrpwd = 'has-error';
					$db_check_db = 'has-error';
					$db_check_srv = 'has-error';
			}
          
		}else
		{
			$testConn->close();
			$b_sysinstall = 0;
			if(!empty($_POST['sysinstall']))
				if($_POST['sysinstall'] == 1)
					$b_sysinstall = 1;
				
			$b_gueststats = false;
			if(!empty($_POST['gueststats']))
				if($_POST['gueststats'] == 1)
					$b_gueststats = true;
				
		
		
		
			$f = fopen($confFile, "w+");
			if($f){
				file_put_contents($confFile, "");
				$write = "<?php\n";
				$write .= '$MYSQL_HOST = "' . $inputdbhost . "\";  // MySQL hostname \n";
				$write .= '$MYSQL_DB = "' . $inputdbname . "\";  // MySQL datebase \n";
				$write .= '$MYSQL_LOGIN = "' . $inputdbuser . "\";  // MySQL username \n";
				$write .= '$MYSQL_PASSWORD = "' . $inputdbpass . "\";  // MySQL password \n";
				if($b_gueststats == true){
					$access_token = sha1(time() ."|". rand());
					$write .= '$PANEL_GUESTSTATS = "' . $access_token . "\";  // Guest Statistics \n";
				}else
					$write .= "\$PANEL_GUESTSTATS = FALSE;  // Guest Statistics \n";
				
					
				
				$write .= "?>";
				fwrite($f, $write);
			fclose($f);
			header("refresh:0;"); 
			}
		}

	}


	if(!empty($_POST['theme']) && $_POST['theme'] != $_SESSION['theme'])
	{
		if($_POST['theme'] == 1 || $_POST['theme'] == 2){
			@mysqli_free_result(@mysqli_query($database, "UPDATE `users` SET `theme`='" . $_POST['theme'] . "' WHERE `uid`='" . $_SESSION['uid'] . "';"));
			$_SESSION['theme'] = $_POST['theme'];
		}
		header("refresh:0;"); 
	}
	$theme_1 = $_SESSION['theme'] == 1 ? "checked" : "";
	$theme_2 = $_SESSION['theme'] == 2 ? "checked" : "";
	$gueststats_1 = "";
	$gueststats_2 = "";
	$PANEL_GUESTSTATS == FALSE ? $gueststats_1 = "checked" : $gueststats_2 = "checked";
	
	$gueststatsURL = $PANEL_GUESTSTATS == FALSE ? "" : "<span class=\"text-muted small\">(<a href=\"guest.php?access_token={$PANEL_GUESTSTATS}\" class=text-muted  target=\"_blank\">{$lang[$syslang]['opennewwindow']}</a>)</span>";

echo <<<DATA
<div class="row">
<form class="form-horizontal" method="post" action="?cp=settings">
	<div class="col-lg-6">
		<div class="page-header">
			<h5>{$lang[$syslang]['database']}</h5>
		</div>

		<div class="form-group {$db_check_usrpwd}">
			<label for="dbusername" class="col-lg-4 control-label">{$lang[$syslang]['dbusername']}</label>
			<div class="col-lg-8"> 
				<input type="text" class="form-control" id="dbusername" name="dbusername" placeholder="{$MYSQL_LOGIN}">
			</div>
		</div>
	
		<div class="form-group {$db_check_usrpwd}"> 
			<label for="dbpassword" class="col-lg-4 control-label">{$lang[$syslang]['dbpassword']}</label>
			<div class="col-lg-8"> 
				<input type="text" class="form-control" id="dbpassword" name="dbpassword" placeholder="{$lang[$syslang]['leaveblank']}">
			</div>
		</div>
	
		<div class="form-group {$db_check_srv}"> 
			<label for="dbhost" class="col-lg-4 control-label">{$lang[$syslang]['dbhost']}</label>
			<div class="col-lg-8">
				<input type="text" class="form-control" id="dbhost" name="dbhost" placeholder="{$MYSQL_HOST}">
			</div>
		</div>
	
		<div class="form-group {$db_check_db}"> 
			<label for="dbname" class="col-lg-4 control-label">{$lang[$syslang]['dbname']}</label>
			<div class="col-lg-8">
				<input type="text" class="form-control" id="dbname" name="dbname" placeholder="{$MYSQL_DB}">
			</div>
		</div>
	
		<div class="page-header">
			<h5>{$lang[$syslang]['authorization']}</h5>
		</div>
	
	
		<div class="form-group">
			<label for="username" class="col-lg-4 control-label">{$lang[$syslang]['username']}</label>
			<div class="col-lg-8"> 
				<input type="text" class="form-control" id="username" name="username" placeholder="">
			</div>
		</div>
	
		<div class="form-group">
			<label for="password" class="col-lg-4 control-label">{$lang[$syslang]['password']}</label>
			<div class="col-lg-8"> 
				<input type="text" class="form-control" id="password" name="password" placeholder="{$lang[$syslang]['leaveblank']}">
			</div>
		</div>
	</div>
	
	<div class="col-lg-6">
		<div style="margin:3% 3% 0 0">
			<div class="page-header">
				<h5>{$lang[$syslang]['controlpanel']}</h5>
			</div>
			
			<div class="control-group"> 
				<label class="control-label">{$lang[$syslang]['colorscheme']}</label>
				<div class="radio">
					<label class="radio-inline">
						<input type="radio" name="theme" value="1" {$theme_1}> 
						<span class="cr"><i class="cr-icon fa fa-circle"></i></span> 
						<b>{$lang[$syslang]['day1']}</b> 
					</label>
					<label class="radio-inline">
						<input type="radio" name="theme" value="2" {$theme_2}>
						<span class="cr"><i class="cr-icon fa fa-circle"></i></span>
						<b>{$lang[$syslang]['night']}</b> 
					</label>
				</div>
			</div>
			
			<div class="control-group"> 
				<label class="control-label">{$lang[$syslang]['gueststats']}</label>
				{$gueststatsURL}
				<div class="radio">
					<label class="radio-inline">
						<input type="radio" name="gueststats" value="1" {$gueststats_2}> 
						<span class="cr"><i class="cr-icon fa fa-circle"></i></span> 
						<b>{$lang[$syslang]['on']}</b> 
					</label>
					<label class="radio-inline">
						<input type="radio" name="gueststats" value="2" {$gueststats_1}>
						<span class="cr"><i class="cr-icon fa fa-circle"></i></span>
						<b>{$lang[$syslang]['off']}</b> 
					</label>
					
				</div>
			
			</div>
			<p>&nbsp;</p>
			<p>&nbsp;</p>
			<p>&nbsp;</p>	
	<p>&nbsp;</p>
			<p>&nbsp;</p>
			<button type="submit" class="btn btn-primary btn-small btn-block">{$lang[$syslang]['savesettings']}</button> 
			<a href="?cp=stats" class="btn btn-default btn-small btn-block">{$lang[$syslang]['exitnosave']}</a>
		</div>
	</div>
</form>
</div>
DATA;

}else
	die('MySQL connection error: (' . mysqli_connect_errno() . ') '
            . mysqli_connect_error());



?>

