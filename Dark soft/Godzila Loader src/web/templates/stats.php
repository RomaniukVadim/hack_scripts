<?php
defined('CP') or die();
$markerstyle = $_SESSION['theme'] == "2" ? "#000000" : "#2fa4e7";
$markerclass = $_SESSION['theme'] == "2" ? "black" : "blue"; 
$gueststatsURL = $PANEL_GUESTSTATS == FALSE ? "" : "<span class=\"text-muted small\">(<a href=\"guest.php?access_token={$PANEL_GUESTSTATS}\" class=text-muted  target=\"_blank\">{$lang[$syslang]['opennewwindow']}</a>)</span>";
if(!empty($_GET['clearstats']))
	{
		mysqli_query($database, "TRUNCATE TABLE `bots`") or die(mysqli_error($database));
		header('Location: ?cp=stats');
	}
	
	$sql = "SELECT COUNT(*) FROM `bots` WHERE `wow64` = '0'"; 
	$result = mysqli_query($database, $sql) or die(mysqli_error($database)); 
	$x32 = mysqli_fetch_row($result);
	mysqli_free_result($result);
	$sql = "SELECT COUNT(*) FROM `bots` WHERE `wow64` = '1'"; 
	$result = mysqli_query($database, $sql) or die(mysqli_error($database)); 
	$x64 = mysqli_fetch_row($result);
	mysqli_free_result($result);
	
	if(!isset($x32[0]))$x32[0] = 0;
	if(!isset($x64[0]))$x64[0] = 0;
	
	$total = $x32[0] + $x64[0];
	$x32percent =  @round($x32[0]/($total/100), 1);
	$x64percent =  @round($x64[0]/($total/100), 1);
		$archdiv = <<<DATA
				<div class="parentarch">
				<div class="stats-header">
					<h4>{$lang[$syslang]['archos']}</h4>
					
					<img src="media/img/32.png"> <span style="font-size:140%"> <b>{$x32[0]}</b> <span class="text-muted">({$x32percent}%)</span></span> </br> 
					<img src="media/img/64.png"> <span style="font-size:140%"> <b>{$x64[0]}</b> <span class="text-muted">({$x64percent}%)</span></span>
				</div>
DATA;



	
echo <<<DATA
		
    <div class="tab-content" id="stats_main">
    <div role="tabpanel" class="tab-pane active" id="live" style="position:relative;">
	<div id="disable_del_stat" style="display:none"><a href="#clearstats" data-toggle="modal" style="position:absolute;line-height: 1.428571429;border-radius: 20px; width: 40px;height: 40px;text-align: center;padding: 2px 0;font-size: 22px;right:5%;" class="btn btn-danger"><i class="fa fa-trash-o"></i> </a></div>
	<a href="#piechart" aria-controls="piechart" role="tab" data-toggle="tab" class="btn-xlarge" style="right:1px;"><i class="fa fa-arrow-right"></i></a>

		<div id="preloader" style="display:none;" align='center'>
			<p class="text-info">{$lang[$syslang]['plzwait']}...</p>
		</div>
		<div id="marker" class="{$markerclass}" style="display:none;background-color:{$markerstyle}"></div>


		<div id="bots" align='center' style="display:none;">
			<b class="text-primary">{$lang[$syslang]['hour']}: </b><span style="font-size:150%;">0 <span class="text-muted">(0%) </span> </span>
			<b class="text-primary">{$lang[$syslang]['day']}: </b><span style="font-size:150%;"> 0 <span class="text-muted">(0%) </span> </span>
			<b class="text-primary">{$lang[$syslang]['total']}: </b><span style="font-size:150%;"> 0 <span class="text-muted">(0%) </span> </span>
			
		</div>


		<div id="worldmap" style="position:absolute;z-index: 2"></div>
		<div id="stats" style="display:none;">
			<h4>{$lang[$syslang]['lastbots']}</h4>
			<div id="enable_del_stat"><p>{$lang[$syslang]['nodata']}</p></div>
			<ul id="list" class="list-group" style="list-style-type:none;">
			</ul>
		</div>
	</div>
    <div role="tabpanel" class="tab-pane" id="piechart" style="position:relative;">
	<div id="disable_del_stat" style="display:none"><a href="#clearstats" data-toggle="modal" style="position:absolute;line-height: 1.428571429;border-radius: 20px; width: 40px;height: 40px;text-align: center;padding: 2px 0;font-size: 22px;left:4%;" class="btn btn-danger"><i class="fa fa-trash-o"></i> </a></div>
		<a href="#live" aria-controls="live" role="tab" data-toggle="tab" class="btn-xlarge"><i class="fa fa-arrow-left"></i></a> 
		
		<div class="parentchart">
			<div class="stats-header">
				<h4>{$lang[$syslang]['osstr']}</h4>
			</div>
				<i class="fa fa-windows fa-5x" style="position:absolute;z-index:0;margin: 21% 0 0 41%;color:{$markerstyle};"></i>
			<div id="os_chart"></div>
		</div>
				{$archdiv}
		</div>
		<div class="parentcountry">
			<div class="stats-header">
				<h4>{$lang[$syslang]['countries']}</h4>
			</div>
			<div id="enable_del_stat2"><p>{$lang[$syslang]['nodata']}</p></div>
		<div id="topCountries" class="{$syslang}" style=""position:absolute;z-index:1;margin: 21% 0 0 45%;"></div>
		</div>
	</div>
</div>
<script>
function BotsLive(hour, day, total, dayresident, weekresident){
	bots.innerHTML = "<b class=\"text-primary\">{$lang[$syslang]['hour']}: </b><span style=\"font-size:150%;\">" + hour + " <span class=\"text-muted\">(" + (hour/(total/100)).toFixed(2) + "%)</span> </span>" + 
"<b class=\"text-primary\">{$lang[$syslang]['day']}: </b><span style=\"font-size:150%;\"> " + day + " <span class=\"text-muted\">(" + (day/(total/100)).toFixed(2) + "%)</span> </span>" +
"<b class=\"text-primary\">{$lang[$syslang]['total']}: </b><span style=\"font-size:150%;\"> " + total + "</span><span style=\"font-size:150%;\">  <span class=\"text-muted\">(100%)</span> </span></br>" + 
"<b class=\"text-primary\">{$lang[$syslang]['perday']}: </b><span style=\"font-size:150%;\">" + dayresident + " <span class=\"text-muted\">(" + (dayresident/(total/100)).toFixed(2) + "%) </span> </span>" + 
"<b class=\"text-primary\">{$lang[$syslang]['perweek']}: </b><span style=\"font-size:150%;\">" + weekresident + " <span class=\"text-muted\">(" + (weekresident/(total/100)).toFixed(2) + "%) </span> </span>";
}


</script>	
		<div id="clearstats" class="modal modal-message modal-danger fade" style="display: none;" aria-hidden="true">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">
							<i class="fa fa-exclamation-triangle"></i>
						</div>
					<div class="modal-body">{$lang[$syslang]['remallstats']}</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> {$lang[$syslang]['cancel']}</button>
							<a href="?cp=stats&clearstats=true" class="btn btn-danger"><i class="fa fa-check"></i> {$lang[$syslang]['yes']}</a>
						</div>
					</div> 
				</div> 
			</div>
DATA;
?>
