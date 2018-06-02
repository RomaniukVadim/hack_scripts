<?
header("Content-Type: text/html; charset=utf-8");
#ID приложения
define("CLIENT_ID", "3835199");
#Защищенный ключ
define("CLIENT_SECRET", "21UkmUFlwbT7tvKKR4Ci");
#Домен вашего сайта
define("URL", "olikers.com");
#Список прав (менять не нужно)
define("SCOPE", "friends,offline");

$how_r_for_one_l = "0.1"; //Сколько рублей стоит 1 лайк? Сейчас 10 копеек
$admin_wmr = "R133254718180"; //Ваш WMR кошелек
$admin_uid = "187538528"; //Ваш цифровой VKontakte ID
$referer_likes = "8"; //Лайков за реферала
$referer_money = "0.30"; //Денег за реферала

#Подключение к БД
$bd_host = "oliker00.mysql.ukraine.com.ua";
$bd_user = "oliker00_546";
$bd_password = "gf5yp4w3";
$bd_base = "oliker00_546";
$con = mysql_connect($bd_host, $bd_user, $bd_password) or die(mysql_error()); 
mysql_select_db($bd_base, $con) or die(mysql_error());
mysql_query("set names 'utf8'") or die(mysql_error());
if(isset($_SESSION["access_token"])){
$user_row = mysql_fetch_array(mysql_query("SELECT * FROM tb_members WHERE id = ".$_SESSION["site_id"]));
}
include "functions.php";
?>
