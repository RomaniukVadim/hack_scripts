<?php

require_once('inc/require.php');
require_once('inc/load.php');


if(isset($_POST['scan']) && isset($_POST['file']))
{



$pfile = mysql_real_escape_string($_POST['file']);

$check =(int) mysql_num_rows(mysql_query("SELECT `id` FROM `uploads` WHERE `hash`='".$pfile."'"));
if($check==0) exit('hack attempt. access denied');
$file = $_vars['uploadDir'].$pfile.'.exe';
if(!file_exists($file)) exit ('file not exists.');

$uagent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; SV1; .NET CLR 1.1.4322)";


$id=$_vars['scan4you']['id'];
$token=$_vars['scan4you']['token'];
$url=$_vars['scan4you']['url'].'/remote.php';
$type='file';


$options=getopt('t:d:e:l');

$link=0;
$format='json'; // json - for JSON return
$disable='';
$enable='';
if (@$options['t']) $type = $options['t'];
if (@$options['l'] === false) $link = 1;
if (@$options['d']) $disable = @$options['d'];

$ch = curl_init();
curl_setopt($ch, CURLOPT_HEADER, 0);
curl_setopt($ch, CURLOPT_VERBOSE, 0);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
$post = array('id'=>$id,'token'=>$token,'action'=>$type);
if ($disable) $post['av_disable']=$disable;
if ($enable) $post['av_enabled']=$enable;
if ($link) $post['link']=1;
if ($type != 'file') $post[$type]=$file;
else {
    if (class_exists('CURLFile')){
	$cfile = new CURLFile($file,'application/octet-stream',$type);
	$post['uppload']=$cfile;
    } else {
	$post['uppload']='@'.$file;
    }
}
$post['frmt']=$format;
curl_setopt($ch, CURLOPT_POSTFIELDS, $post);

$response = curl_exec($ch);
if ($response === false || curl_errno($ch) || curl_getinfo($ch, CURLINFO_HTTP_CODE) != 200){
    echo 'ERROR:'.curl_error($ch);
    exit;
}




function remove_strs($str)
{
$str= str_replace("\n","",$str);
$str = str_replace('<EMB-PE>\\','',$str);
$str = str_replace('\\','',$str);
$str = str_replace('":{"', '":"', $str);
$str = str_replace('html":"',"", $str);
$str = str_replace(']","index(', 'index(', $str);
$str = str_replace('[iFrame]"}', '[iFrame]"', $str);

return str_replace("\r","",$str);

}


$response = remove_strs($response);
$arr = json_decode($response);


	$res=0;
	$count = 0;
	
while (list($key,$val) = each($arr))
{
if($val!='OK') ++$res;
++$count;
}

$result = $res.'/'.$count;


	
	
	
$store_results=  mysql_real_escape_string($response);
	
$sql = "UPDATE `uploads` set virus_check='".$result."', av_result='".$store_results."', last_check='".$time."'  WHERE `hash`='".$pfile."'";echo $sql;
if(!mysql_query($sql)) exit('can\'t store .') ;
 exit;


}


if(isset($_GET['delete']))
{


$s= "SELECT filename, `hash`, `ext` FROM uploads WHERE ( id='".(int)$_GET['delete']."')";

$sql = mysql_query($s);
if(mysql_num_rows($sql)>0)
{
$array = mysql_fetch_Array($sql);

$fpath = $_vars['uploadDir'].$array['hash'].'.'.$array['ext'];

if(file_exists($fpath)) unlink($fpath);

 mysql_query("DELETE FROM uploads WHERE id='".(int)$_GET['delete']."'");
exit ('<script>location.href="?files=true"</script>');
}

else

{
exit ('file not found.');
}


}





if(isset($_POST['upload']))
{

if($_POST['remote']==0)
{
$file = $_FILES['file']['tmp_name'];
$fileName = $_FILES['file']['name'];
$from = 'file';
if(empty($file)) exit ('file not selected');
}
else
{
$file = $_POST['remote_url'];
$fileName =basename($_POST['remote_url']);
$from = 'url';
if(substr($file, 0,4)!="http" || $file=="") exit ('url bad');
}

 $ext = pathinfo($fileName, PATHINFO_EXTENSION);

if($ext!='exe')  $msg = "<div>Extension file not allowed</div>";
else
{

$hash = substr(str_shuffle(MD5(microtime())), 0, 8);

$copy_base_path = $_vars['uploadDir'].$hash.'.'.$ext;


if(@copy($file, $copy_base_path))
{

$md5 = md5_file($copy_base_path);
$fsize = filesize($copy_base_path);

$sql = "INSERT INTO uploads (`member`, `filename`, `hash`, `filesize`, `md5`, `from`, `url`, `ext`, `count`, `date`) values 
('".$_SESSION['member_id']."', '".$fileName."', '".$hash."', '".$fsize."', '".$md5."', '".$from."', '".mysql_real_escape_string($file)."', '".$ext."', 0, '".$time."') ";



if(@mysql_query($sql)) $msg = "<div>Copy file success.</div>"; else $msg = "File exists already.";
 
 
 
 } else $msg=  "<div>Copy file error</div>";

}
}

?>
  <div class="row-fluid" style="width:500px">
                        <div class="span12">
                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-stack-checkmark"></i></div> 
                                    <h4>Upload new file</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                    <?php if(isset($msg)) echo$msg;?>
                                <div class="widget-content">
                                    <form method="post" action="?files=true" class="form-horizontal" enctype="multipart/form-data">
                                        <div class="control-group">
                                            <label class="control-label" for="required">  <input type='radio' name='remote' value='0' checked="checked" />  From PC</label>
                                            <div class="controls controls-row">
                                               <input type="file" id="file" name="file" />
                                            </div>
                                        </div><!-- End .control-group  -->
                                        <div class="control-group">
                                            <label class="control-label" for="required">  <input type='radio' name='remote' value='1' />   From Remote URL</label>
                                            <div class="controls controls-row">
                                            <input type="text" name="remote_url" />
                                            </div>
                                        </div><!-- End .control-group  -->
                                     
                                        
                                            <button type="submit" name="upload" class="btn btn-primary">Upload</button>
                                           
                                    </form>
                                </div><!-- End .widget-content -->
                            </div><!-- End .widget -->
                        </div><!-- End .span12  --> 
                        
                        </div>
                        
                        
                        
                        
                        
                        
                            <div class="row-fluid">

                        <div class="span12">

                            <div class="widget">
                                <div class="widget-title">
                                    <div class="icon"><i class="icon20 i-table"></i></div> 
                                    <h4>Files</h4>
                                    <a href="#" class="minimize"></a>
                                </div><!-- End .widget-title -->
                            
                                <div class="widget-content">
                                    
                                    <table cellpadding="0" cellspacing="0" border="0" class="table table-striped table-bordered table-hover" id="dataTable">
                                              <thead>
                                            <tr>
                                                <th width="12%">FileName</th>
                                                <th width="5%">Size</th>
                                                <th width="10%">MD5 Hash</th>
                                                <th width="5%">Count</th>
                                                <th width="4%">Ext</th>
                                                <th width="10%">Results</th>
                                                <th width="10%">Last scan</th>
                                                <th width="10%">Uploaded</th>
                                                <th width="7%">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                        
                                        <?php
										
										
										
										$strSQL = "SELECT COUNT(*) FROM uploads ";

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
if ($Num_Rows <= $Per_Page) {
    $Num_Pages = 1;
} elseif (($Num_Rows % $Per_Page) == 0) {
    $Num_Pages = ($Num_Rows / $Per_Page) ;
} else {
    $Num_Pages = ($Num_Rows / $Per_Page) + 1;
    $Num_Pages = (int) $Num_Pages;
}

											$objQuery = mysql_query("SELECT * FROM uploads ORDER BY id DESC LIMIT $Page_Start , $Per_Page");
                                        while($row= mysql_fetch_array($objQuery))
                                        {
                                       echo '
                                        
                                            <tr class="gradeA">
                                                <td class="center vcenter">'.$row['filename'].'</td>
                                                <td class="center vcenter">'.$row['filesize'].'</td>
                                                <td class="center vcenter">'.$row['md5'].'</td>
                                                <td class="center vcenter">'.$row['count'].'</td>
                                                <td class="center vcenter">'.$row['ext'].'</td>
                                                ';
 if($row['virus_check']=='') 
 { 
 $results = "N/A"; 
 $lastCheck = $results;
  } 
 else
{
 $results = $row['virus_check'];
$ex = explode('/', $results);
if($ex[0]==0) $color = 'Lime'; else $color ='Red'; 
$lastCheck =date("H:i:s m/d/Y ",$row['last_check']); 
 $results='<a href="javascript: showResults(\''.$row['hash'].'\');" class="btn tip" title=""><font color="'.$color.'">'.$results.'</font></a>';
 }
 
echo'
                                                <td class="center vcenter">'.$results.'</td>
                                                <td class="center vcenter">'.$lastCheck.'</td>
                                                <td class="center vcenter">'.date("H:i:s m/d/Y ",$row['date']).'</td>
                                          
                                                
                                                <td class="center vcenter">
                                                    <div class="btn-group">
                                                        <a href="javascript: CheckFile(\''.$row['hash'].'\')" class="btn tip" title="">check</i></a>
                                                        <a href="?files=true&delete='.$row['id'].'" class="btn tip" title="">del</i></a>
                                                    </div>
                                                </td>
                                            </tr>
                                          ';
										   }
										   ?>
                                           
                                        </tbody>
                                      
                                    </table>
                                    
                                    
                                    <?php
									
echo"                                    Showing $Page_Start to $Per_Page of $Num_Rows entries";


									if ($Prev_Page) {
    echo " <a href ='$_SERVER[SCRIPT_NAME]?files=true&Page=$Prev_Page'><< Back</a> ";
}

for ($i=1; $i <= $Num_Pages; $i++) {
    if ($i != $Page) {
        echo " <a href ='$_SERVER[SCRIPT_NAME]?files=true&Page=$i'>$i</a> ";
    } else {
        echo "<b> $i </b>"; 
    }
}

if ($Page!=$Num_Pages) {
    echo " <a href ='$_SERVER[SCRIPT_NAME]?files=true&Page=$Next_Page'>Next>></a> ";        
}
?>
                                    
                                </div><!-- End .widget-content -->
                            </div><!-- End .widget -->
                        </div><!-- End .span12  --> 
                                            
                    </div><!-- End .row-fluid  -->
                    
                    
                    
                    
                    				 				 


                     <!-- Boostrap modal dialog -->
                     <a href="#myModal" style="display:none" id="myModalEvent" class="btn gap-right20" data-toggle="modal"></a>
                                    <div id="myModal" class="modal hide fade" style="display: none; ">
                                        <div class="modal-header">
                                          <button type="button" class="close" data-dismiss="modal"><i class="icon16 i-close-2"></i></button>
                                          <h4 id="modal_title">Scanning file</h4>
                                        </div>
                                        <div class="modal-body">
                                        
                                          <p id="ajax_body">
    		                          <center> <img id="loader" src="images/preloaders/blue/6.gif" alt="preloader" />
                                      </center>   </p>
                                     
                                    
                                      </div>                                  
                          </div>
                    
                    