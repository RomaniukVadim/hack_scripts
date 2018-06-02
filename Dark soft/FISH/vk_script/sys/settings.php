<?


$host='localhost';//хост
$user='aldersds';//пользователь mysql
$pass='qwerty140398';// пароль пользователя mysql
$dbase='aldersds_ftp';// название базы данных 

if(@!$db = mysql_connect ("$host","$user","$pass")){ ?>Проблемы с подключением к базе данных. Администраця уже работает над устранением проблем.<? exit;}

mysql_select_db ("$dbase",$db);
mysql_query('SET NAMES "utf8"'); 


$name1='имя1'; // Имя 1-го участника
$name2='имя2'; // Имя 2-го участника

$admin_pass='123456'; // пароль для доступа в админку

?>