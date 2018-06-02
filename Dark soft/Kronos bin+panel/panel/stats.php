<?php

require_once('inc/require.php');
require_once('inc/load.php');


//define('STATS_TABLE' , 1);

require_once('inc/head_new.inc');
require_once('inc/menu.html');
?>
   
  
   
   
   
        <section id="content">
            <div class="wrapper">
                <div class="crumb">
                    <ul class="breadcrumb">
                      <li><a href="#"><i class="icon16 i-home-4"></i>Home</a> <span class="divider">/</span></li>

                      <li class="active">Data</li>
                    </ul>
                </div>
                
                <div class="container-fluid">
                    <div id="heading" class="page-header">
                        <h1><i class="icon20 i-dashboard"></i> Stats</h1>
                    </div>
                    
                    
                    
                      <ul id="myTab" class="nav nav-tabs">
                                <li class="active"><a href="#main" data-toggle="tab">Main</a></li>
                                <li><a href="#summary" data-toggle="tab">Summary</a></li>
                                <li><a href="#new" data-toggle="tab">New bots</a></li>
                                <li><a href="#os" data-toggle="tab">Operating System</a></li>
                                <li><a href="#perms" data-toggle="tab">Permissions</a></li>
                                <li><a href="#arch" data-toggle="tab">Architecture</a></li>
                                <li><a href="#logs" data-toggle="tab">Logs</a></li>
                            </ul>

                            <div class="tab-content">
                               
                                <div class="tab-pane fade in active" id="main">
                                 <div class="span6" >
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-stats"></i></div> 
                                    <h4>Online today</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content"  style="width:100%; height:100%">
                             <?php 
							 
							 
							 
							 echo' 
                             
                             <table style="width:500px;" cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover">'."
						
									<tr>
								<td>Online last 15 mins</td>
								<td>".calculateTimeStamp('15 minutes')."</td>
								</tr>
								
								<tr>
								<td>Online last 30 mins</td>
								<td>".calculateTimeStamp('30 minutes')."</td>
								</tr>
										
								<tr><td>Online last 1 hour</td>
								<td>".calculateTimeStamp('1 hour')."</td>
								</tr>
								
									<tr><td>Online last 3 hour</td>
								<td>".calculateTimeStamp('3 hours')."</td>
								</tr>
							
								<tr><td>Online last 6 hours</td>
								<td>".calculateTimeStamp('6 hours')."</td>
								</tr>
							
								<tr>
								<td>Online last 12 hours</td>
								<td>".calculateTimeStamp('12 hours')."</td>
								</tr>
						
								<tr><td>Online last 24 hours</td>
								<td>".calculateTimeStamp('24 hours')."</td>
							</tr>
						</table>
						
						
                                </div>
                            </div><!-- End .widget -->
                        </div><!-- End .span6  --> 
						
						".'
						
						           <div class="span6" >
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-stats"></i></div> 
                                    <h4>Online this week</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content"  style="width:100%; height:100%">
						
						   
                             
								
								<table style="width:500px;" cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover" id="dataTable">'."
							<tr>
								<td>Online today</td>
								<td>".calculateTimeStamp('today')."</td>
								</tr>
								
									<tr>
								<td>Online last 2 days</td>
								<td>".calculateTimeStamp('2 days')."</td>
								</tr>
								
								<tr>
								<td>Online last 3 days</td>
								<td>".calculateTimeStamp('3 days')."</td>
								</tr>
										
								<tr><td>Online last 4 days</td>
								<td>".calculateTimeStamp('4 days')."</td>
								</tr>
								
									<tr><td>Online last 5 days</td>
								<td>".calculateTimeStamp('5 days')."</td>
								</tr>
							
								<tr><td>Online last 6 days</td>
								<td>".calculateTimeStamp('6 days')."</td>
								</tr>
							
								<tr>
								<td>Online this week</td>
								<td>".calculateTimeStamp('7 days')."</td>
								
								
							</tr>
						</table>
							";
?>							

					
					
                             
                             
                                </div>
                                
                            </div><!-- End .widget -->
                        </div><!-- End .span6  --> 
                                </div>
                             
                                
                                
                                                   <div class="tab-pane" id="summary">
                               
        <div class="span6" style="width:800px">
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-stats"></i></div> 
                                    <h4>Online today by hour</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                 <div id="chart_online_today" style="width: 100%; height:250px;">Loading...</div>
                                  </div><!-- End .widget-content -->
                                
                            </div><!-- End .widget -->
                        </div><!-- End .span6  --> 
                        
                        
                        
                        
                        <div class="clear">&nbsp;</div>
                        
                        
                        
        <div class="span6" style="width:800px">
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-stats"></i></div> 
                                    <h4>Online by day this week</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                 <div id="chart_online_week" style="width: 100%; height:250px;">Loading...</div>
                                  </div><!-- End .widget-content -->
                                
                            </div><!-- End .widget -->
                        </div><!-- End .span6  --> 
                        
                        
                        
                        
                                </div>
                                
                                
                                
                                
                                
                                
                                
                                
                                
                                     <div class="tab-pane" id="new">
                               
        <div class="span6" style="width:800px">
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-stats"></i></div> 
                                    <h4>Online today by hour</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                 <div id="chart_new_today" style="width: 100%; height:250px;">Loading...</div>
                                  </div><!-- End .widget-content -->
                                
                            </div><!-- End .widget -->
                        </div><!-- End .span6  --> 
                        
                        
                        
                        
                        <div class="clear">&nbsp;</div>
                        
                        
                        
        <div class="span6" style="width:800px">
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-stats"></i></div> 
                                    <h4>Online this week by week</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                 <div id="chart_new_week" style="width: 100%; height:250px;">Loading...</div>
                                  </div><!-- End .widget-content -->
                                
                            </div><!-- End .widget -->
                        </div><!-- End .span6  --> 
                        
                        
                        
                        
                                </div>
                                
                                
                                
                                
                                <div class="tab-pane" id="os">
                            
                                
        <div class="span6">
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-pie-5"></i></div> 
                                    <h4>Operating System</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                    <div id="chart_pie_os" style="width: 100%; height:250px;">Loading...</div>
                                </div><!-- End .widget-content -->
                            </div><!-- End .widget -->
                        </div><!-- End .span6  --> 
                         </div>
                         
                          
                                <div class="tab-pane" id="perms">
                            
                                                         
        <div class="span6">
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-pie-5"></i></div> 
                                    <h4>Permissions</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                    <div id="chart_pie_perms" style="width: 100%; height:250px;">Loading...</div>
                                </div><!-- End .widget-content -->
                            </div><!-- End .widget -->
                        </div><!-- End .span6  --> 
                         </div>
                         
                        
                                <div class="tab-pane" id="arch">
                                    
                                                         
        <div class="span6">
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-pie-5"></i></div> 
                                    <h4>Architecture</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                    <div id="chart_pie_arch" style="width: 100%; height:250px;">Loading...</div>
                                </div><!-- End .widget-content -->
                            </div><!-- End .widget -->
                        </div><!-- End .span6  --> 
                                                       
                                </div>
                              
                              
                              
                                        <div class="tab-pane" id="logs">
                                    
                                        
                        
                        
                        
        <div class="span6" style="width:800px">
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-pie-5"></i></div> 
                                    <h4>Day</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
     <div id="line_logs_today">Loading...</div> 
                                </div><!-- End .widget-content -->
                            </div><!-- End .widget -->
                        </div><!-- End .span6  --> 
                        
                        
                        
                        
                        <div class="clear">&nbsp;</div>
                        
                                         
        <div class="span6" style="width:800px">
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-pie-5"></i></div> 
                                    <h4>Week</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                 
     <div id="line_logs_week">Loading...</div> 
                           
                                 
                                </div><!-- End .widget-content -->
                            </div><!-- End .widget -->
                        </div><!-- End .span6  --> 
                        
                        
                                                       
                                </div>
                              
                              
                              
                              
                              
                              
                              
                              
                              
                            </div>    
                                       
                        
                </div> <!-- End .container-fluid  -->
            </div> <!-- End .wrapper  -->
        </section>
<?php		
		
include('inc/stat_incs.php');

?>