 <div id="login">
            <div class="login-wrapper" data-active="log">
                <div id="log">
                    <div class="page-header">
                        <h3 class="center">Setup || Step 1 [MySQL Database]</h3>
                    <?php if(isset($msg1)) echo '<div>'.$msg1.'</div>'; ?>
                    </div>
                    <form class="form-horizontal" action="?step=2" method="post">
                  	<input type="hidden" name="step" value="2" />
                        <div class="row-fluid">
                            <div class="control-group">
                                <div class="controls-row">
                                    
                                    <input class="span12" type="text" name="database_host" placeholder="MySQL host (localhost)" value="">
                                </div>
                            </div><!-- End .control-group  -->
                            
                            
                            
                        <div class="row-fluid">
                            <div class="control-group">
                                <div class="controls-row">
                                    
                                    <input class="span12" type="text" name="database_user" placeholder="MySQL username" value="">
                                </div>
                            </div><!-- End .control-group  -->
                            
                                 <div class="row-fluid">
                            <div class="control-group">
                                <div class="controls-row">
                                    
                                    <input class="span12" type="text" name="database_pass" placeholder="MySQL password" value="">
                                </div>
                            </div><!-- End .control-group  -->
                            
                               <div class="row-fluid">
                            <div class="control-group">
                                <div class="controls-row">
                                    
                                    <input class="span12" type="text" name="database" placeholder="MySQL database" value="">
                                </div>
                            </div><!-- End .control-group  -->
                            
                            
                            
                        
                            <div class="form-actions full">
                               
                                <button name="continue" type="submit" class="btn btn-primary pull-right span5">Continue</button>
                            </div>
                        </div><!-- End .row-fluid  -->
                    </form>
             
   </div>
                
            <div class="clearfix"></div>
        </div>
    </div>
  </body>
</html>