<h3>Работа с юзерами</h3>
<?
if($_POST["likes"]){
$uid = intval($_POST["id"]);
$likes = intval($_POST["likes"]);
$num = mysql_num_rows(mysql_query("SELECT id FROM tb_members WHERE uid = '".$uid."'"));
if($num>0){
mysql_query("UPDATE tb_members SET likes = '$likes', ban = '".$_POST["ban"]."' WHERE uid = '".$uid."'");
?>
<div class='w_ok'><div class='wmsg'>Готово</div></div>
<?
}
}
if($_POST["uid"]){
$uid = intval($_POST["uid"]);
$likes = intval($_POST["likes"]);
$num = mysql_num_rows(mysql_query("SELECT id FROM tb_members WHERE uid = '".$uid."'"));
if($num>0){
$row = mysql_fetch_array(mysql_query("SELECT * FROM tb_members WHERE uid = '".$uid."'"));
?>
<form method="post" action="">
<strong>Данные:</strong> <?=$row['name'];?> <?=$row['lastname'];?><br>
<strong>UID:</strong> <?=$row['uid'];?><input type="hidden" name="id" value="<?=$row['uid'];?>"><br>
<strong>Лайки:</strong> <input type="text" name="likes" value="<?=$row['likes'];?>"><br>
<strong>Бан:</strong><select name="ban"><option value="0" <? if($row["ban"]==0){?>selected="selected"<? }?>>Не забанен</option><option value="1" <? if($row["ban"]==1){?>selected="selected"<? }?>>Забанен</option></select><br>
<input type="submit" value="Сохранить">
</form>
<?
}else{
?>
<div class='w_warning'><div class='wmsg'>Юзер не найден</div></div>
<?
}
}?>
<form method="post" action="">
Введите числовой VKontakte ID: <input type="text" name="uid"><br>
<input type="submit" value="Поиск">
</form>