<?include('head.php');
?>

<div style='width:1000px; top:20px; background:#fff; padding:13px; border:1px solid #ccc; border-radius:3px; position:relative; margin:0 auto;'>

			<a href="vk.php" style="color:#4b0082">Данные VK</a> | <a href="settings.php">Настройки</a> | <a href="exit.php">Выйти</a>

</div>

<div style='width:1000px; background:#fff; padding:13px; border:1px solid #ccc;border-radius:3px; position:relative; margin:35px auto;'>

<span style="color:#4b0082;">Добро пожаловать | cегодня: <? echo date("d.m.y"); ?></span><br><br>

<table align="center" border="1" cellpadding="7" cellspacing="1" style="width: 100%; border:1px solid #ccc; font-size:14px">
	<tbody>
		<tr>
			<td style="text-align: center;">

<?
$db_table_to_show = 'settings';

  
  //РЕДАКТИРОВАНИЕ ЗАПИСЕЙ
if(isset($_POST['pass_admin']))
{
$result = mysql_query ("UPDATE settings SET pass_admin='$_POST[pass_admin]'");
header("location: settings.php");

}


//РЕДАКТИРОВАНИЕ ЗАПИСЕЙ
  
$result = mysql_query("select * from " . $db_table_to_show);
$myrow = mysql_fetch_array($result);

$db_select=mysql_select_db($db_database);
if(!db_select){
 die("cant choose DB: <br /> " . mysql_error());
}

	echo "
	
	<form action='settings.php' method='post' name='form_edd'>
	<br>
	<p>Пароль администратора: (по умолчанию admin)</p><br>
	<input type='text' name='pass_admin' style='height:25px; padding:5px' value='".$myrow[pass_admin]."'><br> 
    <input type='submit' style='height:25px; cursor:pointer; padding:5px' value='Сохранить'>
	</form>";  
	
	?>

				</td>
		</tr>	
	</tbody>
</table>

<?


include('footer-a.php');?>