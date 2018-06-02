<?php
defined('CP') or die();


function GetOSVersion($osstr){
		switch($osstr){		
			case 1: 
			return "<img src=\"media/img/xp.png\"> Windows XP";
			case 2: 
			return "<img src=\"media/img/vista.png\"> Windows Vista";
			case 3: 
			return "<img src=\"media/img/7.png\"> Windows 7";
			case 4: 
			return "<img src=\"media/img/8.png\"> Windows 8";
			case 5:  
			return "<img src=\"media/img/10.png\"> Windows 10";
			default:
			return "<img src=\"media/img/unknown.png\">  Unknown";
			
		}
}

$DBtasks = mysqli_query($database, "SELECT * FROM `task`") or die(mysqli_error());

$activetasks  = array();
if(mysqli_num_rows($DBtasks) > 0)
{
	while($row = mysqli_fetch_array($DBtasks))
	{
		array_push($activetasks, $row['id']);
	}
}

if(!empty($_GET["page"]))$shift = 10 * ($_GET["page"] - 1);
else $shift = 0;

$DBbots = mysqli_query($database, "SELECT * FROM `bots` LIMIT $shift, 10") or die(mysqli_error());

if(mysqli_num_rows($DBbots) > 0)
{
	$rs_result = mysqli_query($database, "SELECT COUNT('id') FROM `bots`") or die(mysqli_error($database)); 
	$row = mysqli_fetch_row($rs_result) or die(mysqli_error($database)); 
	mysqli_free_result($rs_result);
	
	$Pagination =  pagination($row[0], 10, $_SERVER["REQUEST_URI"]);
	$columns = "";
	
	while($row = mysqli_fetch_array($DBbots))
	{
		$timeAdd = date("Y.m.d H:i", $row['timeAdd']);
		$ip = long2ip($row['ip']);
		$gi = geoip_open(__DIR__.'/../core/geoip/GeoLiteCity.dat', GEOIP_STANDARD);
		$record = geoip_record_by_addr($gi, $ip);
		$country_name = isset($record->{'country_name'}) ? $record->{'country_name'} : "Unknown";
		$country_city = isset($record->{'city'}) ? ', '.$record->{'city'} : '';
		$osstr = GetOSVersion($row['os']);
		$osstr .= $row['wow64'] == 1 ? " x64" : "";
		$taskcomplete = explode(",", substr($row['taskcomplete'], 0, -1));
		
		$taskslinks = "";
		foreach($taskcomplete as $value) {
			if(in_array($value, $activetasks))
				$taskslinks .= "<a href=\"?cp=tasks&taskid={$value}\" class=\"btn-md  active\">{$value}</a>, ";
			else
				$taskslinks .= $value.", ";
		}
		$taskslinks = substr($taskslinks, 0, -2);
		
		
		$columns .= <<<DATA
			<tr>
				<th scope="row">{$row['id']}</th>
				<td>{$ip}</td>
				<td><i class="f-{$row['cc']}"></i> <mark>[{$row['cc']}]</mark> {$country_name}{$country_city} </td>
				<td>{$osstr}</td>
				<td>{$timeAdd}</td>
				<td>{$taskslinks}</td>
			</tr>
DATA;
	}
	
	print <<<DATA
	 <div class="panel panel-primary">
	 <table class="table table-bordered table-hover table-condensed">
		 <thead>
			 <tr class="info">
				 <th>#</th>
				 <th class="text-center">IP</th>
				 <th class="text-center">{$lang[$syslang]['country']}</th>
				 <th class="text-center">{$lang[$syslang]['os']}</th>
				 <th class="text-center">{$lang[$syslang]['timeadd']}</th>
				 <th class="text-center">{$lang[$syslang]['tasks']}</th>
			 </tr>
		 </thead>
		<tbody> {$columns}</tbody>
	 </table>
	  
 </div>
<div class="text-center"> {$Pagination}</div>
DATA;
}else
{
	print "<p class=\"text-center text-info\">".$lang[$syslang]['nobotsnomoney']."</p>";
}
?>