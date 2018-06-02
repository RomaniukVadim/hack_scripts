<?
include_once("config.php");
 
if ($_POST) {
$first = $_POST['fromid'];
$tbname = $_POST['fromtb'];
 
if (trim($first) == '') {$first = "0";}
$userids = "";
$symbol = "";
 
db_connect($dbhost, $dbuser, $dbpass, $dbname);
mysql_query("SET NAMES 'utf-8'");
$result = mysql_query("SELECT * FROM $tbname LIMIT $first, 100");
while ($row =  mysql_fetch_array($result)) {
if ($userids !== "") {$symbol = ",";}
$userids = $userids.$symbol.$row[$idcolumn];
}
 
$mesage= $_POST['yourtext'];
 
$rand = rand();
$timestamp = time()+300;
 
$sig = md5("api_id=".$api_id."message=".$mesage."method=secure.sendNotificationrandom=".$rand."timestamp=".$timestamp."uids=".$userids."v=2.0".$api_key);
$postvars="api_id=".$api_id."&message=".$mesage."&method=secure.sendNotification&random=".$rand."&timestamp=".$timestamp."&uids=".$userids."&v=2.0&sig=".$sig;
 
 
$chp = curl_init('http://api.vkontakte.ru/api.php');
curl_setopt($chp, CURLOPT_HEADER,0);
curl_setopt($chp, CURLOPT_RETURNTRANSFER ,1);
curl_setopt($chp, CURLOPT_POST, 1);
curl_setopt($chp, CURLOPT_POSTFIELDS,  $postvars);
$res = curl_exec($chp);
curl_close($chp);
 
$datetime = date("[H:i:s] ");
$len = strlen($res);
if ($len !== 51) {
$check = strpos($res, 'Invalid');
if ($check == false) {echo "$datetime Отправились уведомления до: $res";} else {echo "$datetime В данной позиции ID не найдены.";}
} else
{echo "$datetime Ни одного уведомления не доставлено.";}
} else echo "ERROR.";
?>