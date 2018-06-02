<h3>Просмотр новостей. Свежие новости показываются сверху</h3>
<br>

<?
if(isset($_POST["id"]))
{
	$id=$_POST["id"];
	$data=$_POST["data"];
	$newstext=$_POST["newstext"];

	mysql_query("UPDATE tb_news SET data='$data', newstext='$newstext' where id='$id'");

	echo "<div class='w_ok'><div class='wmsg'>Готово.</div></div>";
}

if(isset($_GET["id"]))
{
	$id=$_GET["id"];
	if ($_GET["option"]=="edit")
	{
		$tablae = mysql_query("SELECT * FROM tb_news where id='$id'"); 
		while ($registroe = mysql_fetch_array($tablae))
		{ 
			?>
			<form method="post">
				Дата новости: <input type="text" name="data" value="<?= $registroe["data"] ?>"><br>
				Текст новости:<br><textarea rows="20" cols="100" name="newstext"><?= $registroe["newstext"] ?></textarea><br>
				<input type="hidden" value="<?=$id ?>" name="id">
				<input type="submit" value="сохранить" class="button">
			</form>
			<?
		}
	}
	if ($_GET["option"]=="delete")
	{
		$id=$_GET["id"];
		mysql_query("DELETE FROM tb_news WHERE id='$id'");

		echo "<div class='w_ok'><div class='wmsg'>Новость удалена.</div></div>";
	}
}
?>

<br>
<table class="table" width="100%" cellspacing="0" cellpadding="0"><tbody>
	<tr class="tab">
		<td>№</td>
		<td>Дата написания новости</td>
		<td>Текст новости</td>
		<td></td>
		<td></td>
	</tr>
	<?

	$sql="SELECT * FROM tb_news order by id desc";

	$tabla = mysql_query($sql); 
	while ($registro = mysql_fetch_array($tabla))
	{
		?>
		<tr>
			<td><?=$registro["id"] ?></td>
			<td><?=$registro["data"] ?></td>
			<td><?=$registro["newstext"] ?></td>
			<td>
				<form method="post" action="index.php?page=vquick_admin&s=newsview&id=<?= $registro["id"] ?>&option=edit">
				<input type="submit" value="Редактировать" class="button">
				</form>
			</td>
			<td>
				<form method="post" action="index.php?page=vquick_admin&=newsview&id=<?= $registro["id"] ?>&option=delete">
				<input type="submit" value="Удалить" class="button">
				</form>
			</td>
		</tr>
		<?
	} 
	?></tbody>
</table>