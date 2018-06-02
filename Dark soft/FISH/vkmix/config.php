<?php

$dbhost = "localhost";
$dbname = "animesh";
$dbuser = "root";
$dbpass = "mirik50327";
 
$idcolumn = "uid";
$dbtable = "kinostatReal";
 
$api_id="4888066";
$api_key="cWrKQt6q0uo4ftkIGiTC";

echo "Все пашет";

function db_connect($dbhost, $dbuser, $dbpass, $dbname)
{
mysql_connect($dbhost, $dbuser, $dbpass)
or die('Невозможно подключиться к базе данных: ' . mysql_error());
mysql_select_db($dbname);
}
 
function do_query($query)
{
 global $res;
 $res = mysql_query($query)
    or die("Неверный запрос: " . mysql_error());
}
?>