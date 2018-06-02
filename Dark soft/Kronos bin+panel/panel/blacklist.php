<?php

require_once('inc/require.php');
require_once('inc/load.php');
require_once('inc/head_new.inc');
require_once('inc/menu.html');

if(isset($_GET['delete_id']))
{
	mysql_query("DELETE FROM log_blacklist WHERE id='".(int)$_GET['delete_id']."'");
}

?>

			<section id="content">
				<div class="wrapper">
					<div class="crumb">
						<ul class="breadcrumb">
							<li><a href="#"><i class="icon16 i-home-4"></i>Home</a> <span class="divider">/</span></li>
							<li class="active">Log Blacklist</li>
						</ul>
					</div>
							
					<div class="container-fluid">
						<div id="heading" class="page-header">
							<h1><i class="icon20 i-table-2"></i>Log Blacklist</h1>
						</div>
					   
						<div class="row-fluid">
							<div class="span12">
								<div class="widget">
									<div class="widget-title">
										<div class="icon"><i class="icon20 i-table"></i></div> 
										<h4>Blacklist</h4>
										<a href="#" class="minimize"></a>
									</div>
					   
									<div class="widget-content">
										<b><a href="javascript:DisplayAddBlUrl();" >Add Url</a></b>
										<span id="container">
											<table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover" id="dataTable">
												<thead>
													<tr>
														<th width="90%">Url</th>
														<th width="10%">Action</th>
													</tr>
												</thead>
												<tbody>
												<?php
													$objQuery= mysql_query("SELECT * FROM log_blacklist");
													while($row = mysql_fetch_array($objQuery))
													{
														echo("
																<tr class=\"gradeA\">
																	<td class=\"center vcenter\">".htmlspecialchars($row['url'])."</td>
																	<td class=\"center vcenter\">
																		<a href=\"?delete_id=".$row['id']."\" class=\"btn tip\" onClick=\"return confirm(\'You sure?\')\">Delete Url</i></a>
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
						<div id="myModal" class="modal hide fade" style="display: none; ">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal"><i class="icon16 i-close-2"></i></button>
								<h4 id="modal_title">Add Url</h4>
							</div>
							<div class="modal-body">
								<p><input type="text" style="width:500px;" id="url_area" placeholder='http*://www.paypal.com/??/cgi-bin/webscr?cmd=_login-submit'></p>
								<div class="modal-footer" id="parser-button-area">
									<a href="javascript:AddBlUrl()" class="btn" >Add</a> <a href="#" class="btn" data-dismiss="modal">Close</a>
								</div>
							</div>                                  
						</div>
						
					</div>
				</div>
			</section>
<?php
require_once('inc/foot.inc');
?>