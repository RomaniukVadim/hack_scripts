<?php

require_once('inc/require.php');
require_once('inc/load.php');
require_once('class/class.members.php');

if($_SESSION['member_id']!=1) exit ('its not admin.');

if(!isset($_POST['get_form'])) require_once('inc/head_new.inc');
                                
if(isset($_GET['delete'])) mysql_query("DELETE FROM access WHERE id>1 and id=".(int)$_GET['delete']);
if(isset($_GET['deny_id'])) mysql_query("UPDATE access set deny='".(int)$_GET['deny']."' WHERE id>1 and id=".(int)$_GET['deny_id']);





if(isset($_POST['access_id']))
{
$sqlObj = mysql_query("SELECT * FROM access WHERE id=".(int)$_POST['access_id']);
if(mysql_num_rows($sqlObj)==1) $member = mysql_fetch_array($sqlObj); else exit ('not found');
if($member['id']==1) exit ('its admin');


if($_POST['get_form']=='true')
{
 echo ' User name: <input type="text" name="username" required="required" value="'.$member['username'].'" /><br />
    		                             New password: <input type="text" name="password" />
                                         <input type="hidden" name="access_id" id="access_id" value="'.$member['id'].'" />';
										 exit;

}
 



if(isset($_POST['edit']))
{

$username = trim(mysql_real_escape_string(htmlspecialchars($_POST['username'])));

$sqlRes = "UPDATE access SET username='$username'";

if($_POST['password']!="") $sqlRes .=", pass='". md5($_POST['password'])."'";
$sqlRes.=" WHERE id=".$member['id'];
$sqlRes = mysql_query($sqlRes);
if($sqlRes){ $msg = '<div>Member edited</div>'; } else $msg = '<div>That username is used</div>';
}

}



else
								
  if(isset($_GET['new_access']))
{

if(isset($_POST['save']))
{

$username = trim(mysql_real_escape_string(htmlspecialchars($_POST['username'])));
$pass = md5($_POST['password']);

$sqlRes = mysql_query("INSERT INTO access SET username='$username', pass='$pass', added='$time'");
if($sqlRes) $msg = 'Member added'; else $msg = 'That username is used';

 $msg= "<div>$msg</div>";
}
 }
 
 ?>
 




<section id="content">
            <div class="wrapper">
                <div class="crumb">
                    <ul class="breadcrumb">
                      <li><a href="#"><i class="icon16 i-home-4"></i>Home</a> <span class="divider">/</span></li>
                       <li class="active">Members</li>
                   
                    </ul>
                </div>
                
   <ul id="myTab" class="nav nav-tabs">
                                <li <?php if(!isset($_GET['denied'])) print "class='active'"; ?>><a href="?task=members">Members</a></li>
                                <li><a href="#addm" data-toggle="tab">Add new</a></li>
                                <li <?php if(isset($_GET['denied'])) print "class='active'"; ?>><a href="?denied=true">Denied members</a></li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane fade in active" id="members">
                                
               <?php getMembers(); ?>
                                
                                
                                </div>
                                <div class="tab-pane fade" id="addm">
     
                

                    <div class="row-fluid" style="width:500px">
                        <div class="span12">
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-stack-checkmark"></i></div> 
                                    <h4>Member details</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                    <form method="post" action="?new_access=true" class="form-horizontal">
                                        <div class="control-group">
                                            <label class="control-label" for="required">Username</label>
                                            <div class="controls controls-row">
                                                <input type="text" id="required" name="username" class="required span12" required="required" />
                                            </div>
                                        </div><!-- End .control-group  -->
                                        <div class="control-group">
                                            <label class="control-label" for="required">Password</label>
                                            <div class="controls controls-row">
                                                <input type="password" name="password" class="required span12" minlength="6" required="required" />
                                            </div>
                                        </div><!-- End .control-group  -->
                                     
                                        
                                            <button type="submit" name="save" class="btn btn-primary">Save changes</button>
                                           
                                    </form>
                                </div><!-- End .widget-content -->
                            </div><!-- End .widget -->
                        </div><!-- End .span12  --> 
         


                                </div>
                                </div>
                            
                             
                            </div>    
                            </section>
                            
                            
                                        
										 
										 


                     <!-- Boostrap modal dialog -->
                     <a href="#myModal" style="display:none" id="myModalEvent" class="btn gap-right20" data-toggle="modal"></a>
                                    <div id="myModal" class="modal hide fade" style="display: none; ">
                                        <div class="modal-header">
                                          <button type="button" class="close" data-dismiss="modal"><i class="icon16 i-close-2"></i></button>
                                          <h4>Edit member</h4>
                                        </div>
                                        <div class="modal-body">
                                          <form action="?task=edit_member" method="post">
                                          <p id="ajax_body">
    		                          <center> <img id="loader" src="images/preloaders/blue/6.gif" alt="preloader" />
                                      </center>   </p>
                                        <div class="modal-footer">
                                          <a href="#" class="btn" data-dismiss="modal">Close</a>
                                          <button type="submit" name="edit" class="btn btn-primary">Save changes</button>
                                        </div>
                                        </form>
                                      </div>                                  
                          </div>
                    
                    
                    