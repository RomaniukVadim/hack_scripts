<?
if(isset($_SESSION['access_token'])){
if($_GET['act']=="up"){
$id = intval($_GET['id']);
$num = mysql_num_rows(mysql_query("SELECT id FROM tb_ads WHERE id = $id and user = '".$user_row["id"]."'"));
if($num==1){
$row = mysql_fetch_array(mysql_query("SELECT * FROM tb_ads WHERE id = $id"));
?>
<h3>Пополнение баланса задания #<?=$row['id']?></h3>
<?
   if($_POST['balans']){
   $balans = intval($_POST['balans']);
   if($balans>=10){
      if($user_row['likes']>=$balans){
	     mysql_query("UPDATE tb_members SET likes = likes - $balans WHERE id = '".$user_row["id"]."'");
		 mysql_query("UPDATE tb_ads SET balans = balans + $balans WHERE id = $id");
		 echo '<meta http-equiv="refresh" content="0; index.php?page=all_ads&act=ok" />';
	  }else{
	      echo "<div class='w_warning'><div class='wmsg'>У Вас недостаточно лайков на балансе</div></div>";
	  }
   }else{
      echo "<div class='w_warning'><div class='wmsg'>Минимальный заказ 10 лайков</div></div>";
   }
}
?>
	<table width="100%" cellspacing="0" class="table">		
<tbody>
<form action="" method="post">
<tr style="background-color:#F3F3F2;"><td width="50%" align="right">Добавить лайков</td>
<td><input type="text" size="8"  name="balans"></td></tr>
<tr style="background-color:#FFFFFF;"><td width="50%" align="right">Подтвердите</td>
<td><input type="submit" value="Готово"></td></tr>
</form>
	</tbody></table>
<?
}else{
  echo "<div class='w_warning'><div class='wmsg'>Заказ не найден</div></div>";
}
?>
<?
}
if($_GET['act']=="del"){
  $id = intval($_GET['id']);
  $num = mysql_num_rows(mysql_query("SELECT id FROM tb_ads WHERE id = $id and user = '".$user_row["id"]."'"));
  if($num==1){
     $row = mysql_fetch_array(mysql_query("SELECT * FROM tb_ads WHERE id = $id"));
	 mysql_query("UPDATE tb_members SET likes = likes + '".$row['balans']."' WHERE id = '".$user_row["id"]."'");
     mysql_query("DELETE FROM tb_ads WHERE id = $id");
	 echo '<meta http-equiv="refresh" content="0; index.php?page=all_ads&act=ok_del" />';
  }else{
  echo "<div class='w_warning'><div class='wmsg'>Заказ не найден</div></div>";
  }
}
?>
<h3>Список Ваших заказов</h3>
<? 
   if($_GET['act']=="ok"){echo "<div class='w_ok'><div class='wmsg'>Баланс задания успешно пополнен! :)</div></div>";}
   if($_GET['act']=="ok_del"){echo "<div class='w_ok'><div class='wmsg'>Задание успешно удалено</div></div>";}
?>
<table width="100%" cellspacing="0" cellpadding="0" class="table">		
<tbody>
<tr align="center" class="tab"><td width="20">#</td><td>Название</td><td align="center" width="100">Тип</td><td width="70">Осталось ♥</td><td width="60">Действия</td></tr>
<?
$i=0;
$res = mysql_query("SELECT * FROM tb_ads WHERE user = '".$user_row["id"]."'");
while($row=mysql_fetch_array($res)){
$i++;
?>
<tr style="background-color:#<? if ($i & 1){echo 'F3F3F2';}else{echo 'FFFFFF';}?>;"><td align="center"><?=$row['id'];?></td><td><a href="<?=$row['link'];?>" target="_blank"><?=$row['name'];?></a></td><td align="center"><? if($row['type']=="like"){echo "Мне нравится";} if($row['type']=="friend"){echo "Накрутка друзей";} if($row['type']=="group"){echo "Накрутка группы";} if($row['type']=="copy"){echo "Репосты";}?></td><td align="center"><strong><?=$row['balans'];?></strong></td><td align="center"><a href="index.php?page=all_ads&act=up&id=<?=$row['id'];?>"><img src="inc/theme/images/coins.png" style=" padding:0px;margin-bottom:-2px;" title="Пополнить баланс"></a><a href="index.php?page=all_ads&act=del&id=<?=$row['id'];?>"><img src="inc/theme/images/cancel.png" style=" padding:0px;margin-bottom:-2px;" title="Удалить"></a></td></tr>
<?
}
?>
</tbody></table>
<?
}else{
echo "<h3>Авторизируйтесь</h3><div class='w_warning'><div class='wmsg'>Пройдите авторизацию для доступа к данной странице</div></div>";
}
?>