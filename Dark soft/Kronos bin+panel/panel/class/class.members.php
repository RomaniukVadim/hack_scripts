<?php



function getMembers()
{
global $_vars;

if(isset($_GET['denied']) && $_GET['denied']=='true') {  
$type= "Denied"; 
$where = "WHERE deny=1 ";
 $addp= 'denied=1';
 } else{ 
 $type = "Members";
 $where=" WHERE deny=0"; 
 }
										
?>
     <div class="row-fluid">

                        <div class="span12">

                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-table"></i></div> 
                                    <h4><?=$type?></h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                    
                                    <table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover" id="dataTable">
                                              <thead>
                                            <tr>
                                                <th width="5%">ID</th>
                                                <th width="20%">Username</th>
                                                <th width="13%">Added</th>
                                                <th width="13%">Last Online</th>
                                                <th width="12%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        
                                        <?php
										
										
										$strSQL = "SELECT COUNT(*) FROM access ".$where;

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

											$objQuery = mysql_query("SELECT * FROM access $where ORDER BY knock_time DESC LIMIT $Page_Start , $Per_Page");
                                        while($row= mysql_fetch_array($objQuery))
                                        {
                                     echo '
                                        
                                            <tr class="gradeA">
                                                <td class="center vcenter">'.$row['id'].'</td>
                                                <td class="center vcenter">'.$row['username'].'</td>
                                                <td class="center vcenter">'.date("H:i:s m/d/Y",$row['added']).'</td>
                                                <td class="center vcenter">'.date("H:i:s m/d/Y",$row['knock_time']).'</td>
                                                
                                                <td class="center vcenter">
                                                    <div class="btn-group">';
                                                  
												  if($row['id']!=1) 
													{
													echo '  <a href="javascript:void(0)" onClick="EditUser('.$row['id'].')" class="btn tip">edit</a>';
											
											if($row['deny']==1) echo '<a href="?task=members&deny=0&deny_id='.$row['id'].'" class="btn tip" title="Move to access list">move</i></a>	';
												
												else echo '<a href="?task=members&deny=1&deny_id='.$row['id'].'" class="btn tip" title="">deny</i></a>';
												
												echo '  <a href="?task=members&delete='.$row['id'].'" class="btn tip" title="">del</i></a>'; 
													}
													 else echo 'main admin'; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                           <?php
										   }
										   ?>
                                           
                                        </tbody>
                                      
                                    </table>
                                    
                                    
                                    <?php
									
echo"                                    Showing $Page_Start to $Per_Page of $Num_Rows entries";


									if ($Prev_Page) {
    echo " <a href ='$_SERVER[SCRIPT_NAME]?Page=$Prev_Page&$addp'><< Back</a> ";
}

for ($i=1; $i <= $Num_Pages; $i++) {
    if ($i != $Page) {
        echo " <a href ='$_SERVER[SCRIPT_NAME]?Page=$i&$addp'>$i</a> ";
    } else {
        echo "<b> $i </b>"; 
    }
}

if ($Page!=$Num_Pages) {
    echo " <a href ='$_SERVER[SCRIPT_NAME]?Page=$Next_Page&$addp'>Next>></a> ";        
}
?>
                                    
                                </div><!-- End .widget-content -->
                            </div><!-- End .widget -->
                        </div><!-- End .span12  --> 
                                            
                    </div><!-- End .row-fluid  -->
                 
                                
                                
                                
                                
                                <?php
								
								
								}
								
								?>