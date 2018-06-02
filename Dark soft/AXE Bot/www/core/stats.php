<?php 

define("TPL_TITLE", "Stats");
define("COUNTRY_ON_ROW", 10);

if (!CsrConnectToDb()) die();

$bots_count = CsrSqlQueryRowEx("select count(*) from `bots`");
$online_count = CsrSqlQueryRowEx("select count(*) from `bots` where `rtime_last` >= " . (CURRENT_TIME - ($config["bot_timeout"] * 60)) . "");
$count_per_week = CsrSqlQueryRowEx("select count(*) from `bots` where `rtime_first` >= " . (CURRENT_TIME - 60 * 60 * 24 * 7) . "");
$count_per_24h = CsrSqlQueryRowEx("select count(*) from `bots` where `rtime_first` >= " . (CURRENT_TIME - 60 * 60 * 24) . "");
$online_per_24h = CsrSqlQueryRowEx("select count(*) from `bots` where `rtime_last` >= " . (CURRENT_TIME - 60 * 60 * 24) . "");
$online_per_week = CsrSqlQueryRowEx("select count(*) from `bots` where `rtime_last` >= " . (CURRENT_TIME - 60 * 60 * 24 * 7) . "");
$dead_bots = CsrSqlQueryRowEx("select count(*) as `cnt` from `bots` where `rtime_last` <= " . (CURRENT_TIME - 60 * 60 * 24 * 10) . "");
$count_arch = CsrSqlQueryRows("select count(*) as `cnt`, SUBSTRING(`os_version`, 5, 1) as `ver` from `bots` group by `ver`");
$country_stat = CsrSqlQueryRows("select count(*) as cnt, `country` from `bots` group by `country` order by `cnt` desc");

if (is_array($country_stat)) 
{
	foreach ($country_stat as &$item)
	{
		$item["online"] = CsrSqlQueryRowEx("select count(*) as `cnt` from `bots` where `country` = '" . $item["country"] . "' and `rtime_last` >= " . (CURRENT_TIME - ($config["bot_timeout"] * 60)) . "");
	}
}

$country_stat_online = CsrSqlQueryRows("select count(*) as cnt, `country` from `bots` where `rtime_last` >= " . (CURRENT_TIME - ($config["bot_timeout"] * 60)) . " group by `country` order by `cnt` desc");
$versions = CsrSqlQueryRows("select count(*) as cnt, `os_version` as ver from `bots` group by `ver`");
$antiviruses = CsrSqlQueryRows("select count(*) as cnt, `av` from `bots` group by `av` order by `cnt` desc");

$vers = array();
$vers[2] = array('ver' => 'XP', 0 => 0, 9 => 0);
$vers[3] = array('ver' => 'Server 2003', 0 => 0, 9 => 0);
$vers[4] = array('ver' => 'Vista', 0 => 0, 9 => 0);
$vers[5] = array('ver' => 'Server 2008', 0 => 0, 9 => 0);

$vers[6] = array('ver' => 'Seven', 0 => 0, 9 => 0);
$vers[7] = array('ver' => 'Server 2008 R2', 0 => 0, 9 => 0);

$vers[8] = array('ver' => 'Eight', 0 => 0, 9 => 0);
$vers[9] = array('ver' => 'Server 2012', 0 => 0, 9 => 0);

$vers[10] = array('ver' => 'Eight+', 0 => 0, 9 => 0);
$vers[11] = array('ver' => 'Server 2012 R2', 0 => 0, 9 => 0);
$vers[12] = array('ver' => 'Ten', 0 => 0, 9 => 0);

for ($i = 0; $i < count($versions); ++$i) 
{
	$data = @unpack('Cversion/Csp/Sbuild/Sarch', $versions[$i]['ver']);
	$vers[$data['version']][$data['arch']] += $versions[$i]['cnt'];
} 

ob_start();
?>

<div class="row">
	<div class="col-xs-12">
		<table class="table table-striped table-bordered table-condensed">
			<tr> 
				<th> Bots </th> 
				<th> Online </th> 
				<th> Online per week </th>
				<th> Online per 24 hour </th>
				<th> Dead bots </th>
				<th> Installs per week </th>  
				<th> Installs per 24 hour </th>
			</tr>
			<tr> 
				<td align='center'> <?=$bots_count?> </td> 
				<td align='center'> <?=$online_count?> (<?=GetPercent($online_count, $bots_count)?>%) </td> 
				<td align='center'>	<?=$online_per_week?> (<?=GetPercent($online_per_week, $bots_count)?>%) </td> 
				<td align='center'>	<?=$online_per_24h?> (<?=GetPercent($online_per_24h, $bots_count)?>%) </td> 
				<td align='center'> <?=$dead_bots?> (<?=GetPercent($dead_bots, $bots_count)?>%)  </td> 
				<td align='center'> <?=$count_per_week?> </td> 
				<td align='center'> <?=$count_per_24h?>  </td> 
			</tr>
		</table>
	</div>
</div>

<? if ($bots_count > 0) { ?>

<div class='row'>
	<div class="col-xs-6">
		<table class="table table-striped table-bordered table-condensed">
			<tr> 
				<th> Windows </th>
				<th> X32 </th>
				<th> X64 </th>
				<th> </th>
			</tr>
			<?
			foreach ($vers as $ver)
			{
				if ($ver[0] + $ver[9] == 0) continue;
			?>
			<tr>
				<td> <?=$ver['ver']?> </td> 
				<td> <?=$ver[0]?> (<?=GetPercent($ver[0], $bots_count)?>%) </td>
				<td> <?=$ver[9]?> (<?=GetPercent($ver[9], $bots_count)?>%) </td>
				<td> <?=$ver[0] + $ver[9]?> (<?=GetPercent(($ver[0] + $ver[9]), $bots_count)?>%) </td>
			</tr>
			<? } ?>
			<tr>
				<td> </td>
				<td> <?=@$count_arch[0]['cnt']?> (<?=@GetPercent($count_arch[0]['cnt'], $bots_count)?>%) </td>
				<td> <?=@$count_arch[1]['cnt']?> (<?=@GetPercent($count_arch[1]['cnt'], $bots_count)?>%) </td>
				<td> </td>
			</tr>
		</table>
	</div>
</div>

<div class='row'>
	<div class="col-xs-4">
		<table class="table table-striped table-bordered table-condensed">
			<tr>
				<th> Antivirus </th>
				<th> All </th>
				<th> Online </th>
			</tr>
			<? foreach ($antiviruses as $av) {
					?>
					<tr>
						<td><?=CsrGetAv($av["av"])?></td> 
						<td>
							<?=$av["cnt"]?> (<?=GetPercent($av["cnt"], $bots_count)?>%)
						</td>
						<td> <font color='green'> <?=CsrSqlQueryRowEx("select count(*) from `bots` where `av` = {$av["av"]} and `rtime_last` >= " . (CURRENT_TIME - ($config["bot_timeout"] * 60)) . " limit 1") ?> </font> </td>
					</tr>
					<? } ?>
		</table>
	</div>
	
	<div class="col-xs-3">
		<table class="table table-striped table-bordered table-condensed">
			<tr>
				<th> Country </th>
				<td> </td>
			</tr>
		<?
			$cnt = count($country_stat);
			for ($i = 0; $i < $cnt; ++$i) 
			{
				?>
				<tr>
					<td align='center'> <?=$country_stat[$i]['country']?> <img src="template/img/country_16/<?=$country_stat[$i]['country'] == "--" ? "XX" : $country_stat[$i]['country']?>.png"/></td>
					<td> 
						<?=$country_stat[$i]['cnt']?> (<?=GetPercent($country_stat[$i]['cnt'], $bots_count)?>%) / <font color="green"> <?=$country_stat[$i]["online"]?> </font>
					</td>
				</tr>
				<?
			}
		?>
		</table>
	</div>
	<div class="col-xs-4">
		<table class="table table-striped table-bordered table-condensed">
			<tr>
				<th> Report </th>
				<th> All </th>
				<th> Today </th>
			</tr>
			<tr id='stats_reports_waiting'>
				<td colspan=3> Waiting... </td>
			</tr>
		</table>
	</div>
</div>

<script type="text/javascript">

$(document).ready(function()
{
	var url = "<?=CORE_FILE?>?act=ajax&do=stats_reports";
	$.ajax(url).done(function(res) 
	{
		$("#stats_reports_waiting").css('display', 'none');
		$("#stats_reports_waiting").after(res);
	});
});

</script>

<? } ?>

<?php
$content = ob_get_contents();
ob_end_clean();

$header_tpl = file_get_contents(TPL_PATH . "header.html");
$header_tpl = str_replace(array("{TPL}", "{SCRIPTS}", "{TITLE}"), array(TPL_PATH, "<script src=\"template/js/jquery.js\" type=\"text/javascript\"> </script>\r\n<script src=\"template/js/bootstrap.min.js\" type=\"text/javascript\"></script>", TPL_TITLE), $header_tpl);

$menu_tpl = file_get_contents(TPL_PATH . "menu.html");
$menu_tpl = str_replace(array("{CORE_FILE}", "{CUR_TIME}", "{STATS_ACTIVE}"), array(CORE_FILE, gmdate("M d Y H:i:s", mktime()), "active"), $menu_tpl);

$body_tpl = file_get_contents(TPL_PATH . "body.html");
$body_tpl = str_replace(array("{MENU}", "{BODY_CLASS}", "{CONTENT}"), array($menu_tpl, "bots", $content), $body_tpl);
$footer_tpl = file_get_contents(TPL_PATH . "footer.html");

echo str_replace(array("{HEADER}", "{BODY}", "{FOOTER}"), array($header_tpl, $body_tpl, $footer_tpl), file_get_contents(TPL_PATH . "main.html"));
?>