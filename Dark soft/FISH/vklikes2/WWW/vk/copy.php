<?
session_start();
require 'include/config.php';
if(isset($_SESSION['access_token'])){
if(!isset($_SESSION['time_copy']) or $_SESSION['time_copy']<time()){
$_SESSION['ad_copy_id'] = 0;
$_SESSION['time_copy'] = time()+60*5;
}
if(!isset($_SESSION['ad_copy_id'])){
$res = mysql_query("SELECT * FROM tb_ads WHERE balans>=for_one and user != '".$user_row['id']."' and type = 'copy' ORDER BY id ASC");
while($row = mysql_fetch_array($res)){
    $num = mysql_num_rows(mysql_query("SELECT id FROM tb_ads_views WHERE user = '".$user_row['id']."' and ad_id = '".$row['link_id']."' and ad_type = 'copy'"));
	if($num==0){
	$_SESSION['ad_copy_id'] = $row['id'];
	$_SESSION['copy_link_id'] = $row['link_id'];
	   ?>
<meta http-equiv="refresh" content="0; url=<?=$row["link"];?>">
	   <?
	exit();
	}
}
}else{
$res = mysql_query("SELECT * FROM tb_ads WHERE balans>=for_one and user != '".$user_row['id']."' and type = 'copy' and id > '".$_SESSION['ad_copy_id']."' ORDER BY id ASC");
while($row = mysql_fetch_array($res)){
    $num = mysql_num_rows(mysql_query("SELECT id FROM tb_ads_views WHERE user = '".$user_row['id']."' and ad_id = '".$row['link_id']."' and ad_type = 'copy'"));
	if($num==0){
	$_SESSION['ad_copy_id'] = $row['id'];
	$_SESSION['copy_link_id'] = $row['link_id'];
	   ?>
<meta http-equiv="refresh" content="0; url=<?=$row["link"];?>">
	   <?
	exit();
	}
}
}
?>
<html>
<head>
<title>Работы нет :(</title>
<script type="text/javascript">setTimeout('window.close();', 1500);</script>
</head>
<b><center>Работы пока что нет =(</center>
<center>Окно сейчас будет закрыто</center></b>
</html>
<?
}else{
?>
<script type="text/javascript">
setTimeout('window.close();', 0);
</script>
<?
}
?>