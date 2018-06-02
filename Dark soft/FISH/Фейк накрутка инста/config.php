<? session_start();
$nameDB = "a0181684_belka";//Название БД
$nameSERVER = "localhost";//Сервер
$nameUSER = "a0181684_belka";//Имя пользователя БД
$passUSER = "Sharuhanchik";//Пароль пользователя БД
@mysql_select_db($nameDB, mysql_connect($nameSERVER,$nameUSER,$passUSER));	
//ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ (БД)

$db_table_to_show = 'settings';
$result = mysql_query("select * from " . $db_table_to_show);
$myrow = @mysql_fetch_array($result);

$perc = $myrow[pers]; 
$time_dep = $myrow[time_dep]; 
$ref = $myrow[ref];
$start = $myrow[start];
$kosh = $myrow[kosh];
$sitename = $myrow[sitename];
$email_admin = $myrow[email_admin];
function generatelink($value){
$pass = substr($value,4,9);
return (int)$pass;
}
?>