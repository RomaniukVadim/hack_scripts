<?php


require_once('inc/require.php');

if(@isset($_POST['submit'])) require_once( 'class/class.login.php');
if(isset($_SESSION['have_access'])) die( '<script>window.location.replace="stats.php"</script>'); 


if(isset($_GET['redirect']))
{
$re = explode("redirect=", $_SERVER['QUERY_STRING']);
$_SESSION['redirect'] = urldecode($re[1]);
}
require_once('inc/head.inc');
?>
        <div id="login">
            <div class="login-wrapper" data-active="log">
                <div id="log">
                  
                    <div class="page-header">
                        <h3 class="center">Please login</h3>
                        <?php if(isset($Message)) print $Message; ?>
                    </div>
                    <form id="login-form" class="form-horizontal" action="" method="post">
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
                            <div class="control-group">
                                <div class="controls-row">
                                 <img src="security_number.php" />
                                    <input class="span12" type="text" name="security_number" id="security_number" placeholder="code here" value="" style="width:150px !important">
                                </div>
                            </div><!-- End .control-group  -->
                            <div class="form-actions full">
                               
                                <button id="loginBtn" name="submit" type="submit" class="btn btn-primary pull-right span5">Login</button>
                            </div>
                        </div><!-- End .row-fluid  -->
                    </form>
             
   </div>
                
            <div class="clearfix"></div>
        </div>
    </div>
  </body>
</html>