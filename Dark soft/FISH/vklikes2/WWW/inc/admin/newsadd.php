<h3>Добавление новостей</h3>
<br><br>

<?
if(isset($_POST["newstext"]))
{
	$newstext=nl2br($_POST["newstext"]);
	$nazv=$_POST['nazv'];
	$d=date('d.m.Y');

	mysql_query("insert into tb_news (data,newstext,nazv) values ('$d','$newstext','$nazv')");

	echo "<div class='w_ok'><div class='wmsg'>Новость добавлена</div></div>";
}
?>

<form action="" method="POST">
<br>
Название: <input type="text" name="nazv">
<br>
<textarea rows="20" cols="100" name="newstext">Текст новости</textarea><br>
<input type="submit" value="Добавить" class="button"></td></tr>

</form>