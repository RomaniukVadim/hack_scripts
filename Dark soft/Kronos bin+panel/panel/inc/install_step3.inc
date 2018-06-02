 <div id="login">
            <div class="login-wrapper" data-active="log">
                <div id="log">
                    <div class="page-header">
                        <h3 class="center">Setup || Step 3 [Create Main-Admin]</h3>
                     <?php if(isset($msg3)) echo '<div>'.$msg3.'</div>'; ?>
                    </div>
                    <form class="form-horizontal" action="?step=finish" method="post">
                  	<input type="hidden" name="step" value="finish" />
                          <div class="row-fluid">
                            <div class="control-group">
                                <div class="controls-row">
                                     <div class="icon"><i class="icon20 i-user"></i></div>
                                    <input class="span12" type="text" name="user" id="user" placeholder="Username" value="">
                                </div>
                            </div><!-- End .control-group  -->
                            
                            <div class="control-group">
                                <div class="controls-row">
                                       <div class="icon"><i class="icon20 i-key"></i></div>
                                    <input class="span12" type="password" name="password" id="password" placeholder="Password" value="">
                                </div>
                            </div><!-- End .control-group  -->  
                            
                        
                            <div class="form-actions full">
                               
                                <button name="continue" type="submit" class="btn btn-primary pull-right span5">Finish</button>
                            </div>
                        </div><!-- End .row-fluid  -->
                    </form>
             
   </div>
                
            <div class="clearfix"></div>
        </div>
    </div>
  </body>
</html>