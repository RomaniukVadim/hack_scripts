<?php


include_once( 'config.php');
include_once('includes/start.php');
include_once('includes/init.php');

if($_SERVER['REQUEST_METHOD']=='POST'){

$pfile =checkStr( $_POST['file']);
$file = $cnf['uppath'].$pfile;

if(!file_exists($file)) exit ('hack attempt. file not exists.');


$sql = mysql_query("SELECT filename, av_result, virus_check, last_check FROM files WHERE user='".$_SESSION['UID']."' and hash='".$pfile."'");
if(mysql_num_rows($sql)>0)
{

$arr = mysql_fetch_array($sql);

if($arr['av_result']=='') exit ('File not scanned yet.');


printf('<center><h4 class="title">%s</h4></center>', sprintf($lng['CheckFileFor'], $arr['filename']));

echo '<hr />
<div class="ScanInfo">';

$data = $arr['av_result'];

if(strstr($data, '<td>'))
{

echo $data;

}

else
{

$json_arr = json_decode($data);

while(list($key, $val) = each($json_arr))
{

if($val!='OK') 
{
$color = 'Red';
}
else 
$color ='Lime';

$re= '<span class="caption">%s</span> <span class="data" style="color: %s">%s</span><br>'."\r\n";


printf($re, $key, $color, $val); 

}

}



echo '<hr /><span class="caption">'.$lng['ScanTime'].'</span> <span class="data">'.date("m/d/Y H:i:s", $arr['last_check']).'</span><br>'."\r\n";

exit;

}
else

{ 
AddBlackIP($ip);

}





}
else

exit('Post request required. FAK');

?>