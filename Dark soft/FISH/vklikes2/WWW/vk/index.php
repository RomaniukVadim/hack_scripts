


<?
session_start();
header("Content-Type: text/html; charset=utf-8");
$start_counter = microtime(); 
$start_counter_array = explode(" ",$start_counter); 
$start_counter = $start_counter_array[1] + $start_counter_array[0];

#Прикручиваем реферера к сессии
if($_GET['r']!='')
{
	if(preg_match("/^[0-9]{1,20}$/i", $_GET['r']))
	{
		$_SESSION['referer'] = intval($_GET['r']);
	}
}

require "include/config.php";
#Бан
if($user_row['ban']==1){
?>
<center><strong>Вы забанены!</strong></center>
<?
exit;
}

include "inc/theme/header.php";
if (isset($_GET['page']) == false)
{
include("inc/index.php");
$_GET['page'] = 'index';
}
else 
{
if (preg_match("/^[a-z_]+$/", $_GET['page']) and file_exists("inc/".$_GET['page'].".php") == true)
{ 
include("inc/".$_GET['page'].".php");
}
else 
{ 
include("inc/404.php"); 
}
}
include "inc/theme/footer.php";
?>