<?php

require_once('inc/require.php');
require_once('inc/load.php');


if($_SESSION['member_id']!=1) exit ('its not admin.');



require_once('inc/head_new.inc');

$task = $_GET['task'];


if($task=='deny_ips')
{

if(isset($_GET['delete'])) mysql_query("DELETE FROM blocked_ip WHERE id=".(int)$_GET['delete']);


if(isset($_POST['addIP']) )
{

$ip = mysql_real_escape_string(htmlspecialchars($_POST['ip']));
if(CheckIP($ip))
{
$country = CountryName($ip, 'code');
$country2 = CountryName($ip);

$sqlRes = mysql_query("INSERT INTO blocked_ip SET ip='$ip', added='$time', country='$country', country_name='$country2'");
}
}


require_once('inc/head_new.inc');
?>
   <section id="content">
            <div class="wrapper">
                <div class="crumb">
                    <ul class="breadcrumb">
                      <li><a href="#"><i class="icon16 i-home-4"></i>Home</a> <span class="divider">/</span></li>
                                         <li class="active">Denied IPs</li>
                    </ul>
                </div>
                
<div class="container-fluid">
                    <div id="heading" class="page-header">
                        <h1><i class="icon20 i-table-2"></i> Denied IPs</h1>
                    </div>

                    <div class="row-fluid">

                        <div class="span12">

                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-table"></i></div> 
                                    <h4>Denied IPs</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                    
                                    <table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover" id="dataTable">
                                              <thead>
                                            <tr>
                                                <th width="5%">ID</th>
                                                <th width="10%">IP</th>
                                                <th width="20%">Country</th>
                                                <th width="13%">Added</th>
                                                <th width="12%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        
                                        <?php
										
										
										
										$strSQL = "SELECT COUNT(*) FROM blocked_ip ";

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

											$objQuery = mysql_query("SELECT * FROM blocked_ip ORDER BY added DESC LIMIT $Page_Start , $Per_Page");
                                        while($row= mysql_fetch_array($objQuery))
                                        {
                                       echo '
                                        
                                            <tr class="gradeA">
                                                <td class="center vcenter">'.$row['id'].'</td>
                                                <td class="center vcenter">'.$row['ip'].'</td>
                                                <td class="center vcenter"><img src="images/png/'.strtolower($row['country']).'.png"> '.$row['country'].'</td>
                                                <td class="center vcenter">'.date("H:i:s m/d/Y ",$row['added']).'</td>
                                                                                            <td class="center vcenter">
                                                    <div class="btn-group">
                                                        <a href="?task=deny_ips&delete='.$row['id'].'" class="btn tip" title="">del</i></a>
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
    echo " <a href ='$_SERVER[SCRIPT_NAME]?task=$task&Page=$Prev_Page'><< Back</a> ";
}

for ($i=1; $i <= $Num_Pages; $i++) {
    if ($i != $Page) {
        echo " <a href ='$_SERVER[SCRIPT_NAME]?task=$task&Page=$i'>$i</a> ";
    } else {
        echo "<b> $i </b>"; 
    }
}

if ($Page!=$Num_Pages) {
    echo " <a href ='$_SERVER[SCRIPT_NAME]?task=$task&Page=$Next_Page'>Next>></a> ";        
}
?>  <br> <a href="#myModal" class="btn gap-right20" data-toggle="modal">Deny new IP</a>    <!-- Boostrap modal dialog -->
                                    <div id="myModal" class="modal hide fade" style="display: none; ">
                                        <div class="modal-header">
                                          <button type="button" class="close" data-dismiss="modal"><i class="icon16 i-close-2"></i></button>
                                          <h4>Add new IP</h4>
                                        </div>
                                        <div class="modal-body">
                                          <form action="?task=deny_ips" method="post">
                                          <p>
    		                             IP: <input type="text" name="ip" required="required" />
                                         </p>
                                        <div class="modal-footer">
                                          <a href="#" class="btn" data-dismiss="modal">Close</a>
                                          <button type="submit" name="addIP" class="btn btn-primary">Add</button>
                                        </div>
                                        </form>
                                      </div>                                  
                          </div>
                    
                    <?php


}

					
?>