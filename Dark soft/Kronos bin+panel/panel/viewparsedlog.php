<?php

require_once('inc/require.php');
require_once('inc/load.php');

if(!isset($_POST['ajax_response']))
{

if(isset($_GET['delete_id']))
{
	$res = mysql_query("DELETE FROM parsed_logs WHERE id='".(int)$_GET['delete_id']."'");
	if($res ) exit ('<script>location.href="?rule_id='.(int)$_GET['rule_id'].'"</script>');
}

if(isset($_GET['delete_selected']))
{
	$delete_id_arr = $_POST['delete_ids'];

	foreach($delete_id_arr as $id) 
	{
		$query="DELETE FROM parsed_logs WHERE id='".(int)$id."'";
		mysql_query($query);
	}
	exit ('<script>location.href="?rule_id='.(int)$_GET['rule_id'].'"</script>');
}

require_once('inc/head_new.inc');
require_once('inc/menu.html');

if(!isset($_GET['rule_id']))
	exit();

?>
   <section id="content">
            <div class="wrapper">
                <div class="crumb">
                    <ul class="breadcrumb">
                      <li><a href="#"><i class="icon16 i-home-4"></i>Home</a> <span class="divider">/</span></li>
                      <li class="active">Parsed Logs</li>
                    </ul>
                </div>
                
				<div class="container-fluid">
                    <div id="heading" class="page-header">
                        <h1><i class="icon20 i-table-2"></i> Parsed Logs</h1>
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
									<?php 
										if(isset($_GET['query'])) 
											$cquery = htmlspecialchars(strip_tags($_GET['query']));
										else
											$cquery = "";
											
										echo('<input id="query" type="text" placeholder="Search" onKeyUp="searchStartA('.$_GET['rule_id'].', this.value)"  onChange="searchStartA('.$_GET['rule_id'].', this.value)" onKeyPress="searchStartA('.$_GET['rule_id'].', this.value)" onEnter="searchStartA('.$_GET['rule_id'].', this.value)"  value="'.$cquery.'"/>');
									?>
									<a href="javascript:CheckAll();" class="btn tip" style="float: right;">Select All</i></a>
									<span id="container">
									<?php }?>
									<?php echo('<form method="post" action="?rule_id='.(int)$_GET['rule_id'].'&delete_selected=1">'); ?>
                                    <table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover" id="dataTable">
                                         <thead>
                                            <tr>
                                                <th width="90%">Parsed Log</th>
                                                <th width="5%">Action</th>
												<th width="5%">Select</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        
                                        <?php
										
										$where = " WHERE rule_id='".(int)$_GET['rule_id']."'";

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
										
											$where = "WHERE log LIKE '%$query%' AND rule_id='".(int)$_GET['rule_id']."'";
											$q = 'query='.urlencode($query);										
										}
										
										$strSQL = "SELECT COUNT(*) FROM parsed_logs ".$where;

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

										$objQuery = mysql_query("SELECT * FROM parsed_logs $where ORDER BY id DESC LIMIT $Page_Start, $Per_Page");
								
                                        while($row= mysql_fetch_array($objQuery))
                                        {
											echo '
                                            <tr class="gradeA">
												<td class="vcenter">' .str_replace("\n", "<br/>", htmlspecialchars($row['log'])). '</td>
                                                <td class="center vcenter">
                                                    <div class="btn-group">
                                                        <a href="?rule_id='.(int)$_GET['rule_id'].'&delete_id='.$row['id'].'" class="btn tip" title="Delete this log" onClick="return confirm(\'You sure?\')">Delete log</i></a>
                                                    </div>
                                                </td>
												<td class="center vcenter"><input type="checkbox" name="delete_ids[]" value="'.(int)$row['id'].'"></td>
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
										echo " <a href ='$_SERVER[SCRIPT_NAME]?rule_id=".(int)$_GET['rule_id']."&Page=1&$q'><<</a> ";
										echo " &nbsp;<a href ='$_SERVER[SCRIPT_NAME]?rule_id=".(int)$_GET['rule_id']."&Page=$Prev_Page&$q'><</a> &nbsp;";
									}

									$Start_Page = max(1, $Page - 4);
									$End_Page = min($Num_Pages, $Start_Page + 8);

									for ($i = $Start_Page; $i <= $End_Page; $i++) {
										
										if ($i != $Page) {
											echo " <a href ='$_SERVER[SCRIPT_NAME]?rule_id=".(int)$_GET['rule_id']."&Page=$i&$q'>$i</a> ";
										} else {
											echo "<b> $i </b>"; 
										}
									}

									if ($Page!=$Num_Pages) {
										echo " &nbsp;<a href ='$_SERVER[SCRIPT_NAME]?rule_id=".(int)$_GET['rule_id']."&Page=$Next_Page&$q'>></a> ";    
										echo " &nbsp;<a href ='$_SERVER[SCRIPT_NAME]?rule_id=".(int)$_GET['rule_id']."&Page=$Num_Pages&$q'>>></a> ";      
									}

									if(isset($_POST['ajax_response'])) exit;
									?>

									</span>
                                </div>
                            </div>
                        </div>       
                    </div>
                    
                    
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