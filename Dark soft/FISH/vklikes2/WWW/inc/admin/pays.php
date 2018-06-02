<h3>Заявки на пополнение баланса</h3>  
<?
if($_GET["act"]){
$id = $_GET["id"];
if($_GET["act"]=="up" and $_GET["id"]){
  $num = mysql_num_rows(mysql_query("SELECT id FROM tb_pay WHERE id ='$id'"));
  if($num>0){
  $row = mysql_fetch_array(mysql_query("SELECT * FROM tb_pay WHERE id = '$id'"));
  mysql_query("UPDATE tb_members SET likes = likes + '".$row["likes"]."' WHERE uid = '".$row["uid"]."'");
  mysql_query("DELETE FROM tb_pay WHERE id = '$id'");
  ?>
  <div class='w_ok'><div class='wmsg'>Заявка подтверждена</div></div>
  <?
  }else{
  ?>
  <div class='w_warning'><div class='wmsg'>Заявка не найдена</div></div>
  <?
  }
}
if($_GET["act"]=="del" and $_GET["id"]){
  mysql_query("DELETE FROM tb_pay WHERE id = '$id'");
  ?>
  <div class='w_ok'><div class='wmsg'>Заявка удалена</div></div>
  <?
}
}
?>
<table width="100%" cellspacing="0" cellpadding="0" class="table">		
<tbody>
<tr align="center" class="tab"><td width="20">#</td><td width="140">Юзер</td><td width="240">Лайков</td><td align="center">Оплата</td><td width="60">Действия</td></tr> 
<?
$i=0;
$res = mysql_query("SELECT * FROM tb_pay ORDER BY id ASC");
while($row=mysql_fetch_array($res)){
$i++;
?>
<tr style="background-color:#<? if ($i & 1){echo 'F3F3F2';}else{echo 'FFFFFF';}?>;"><td align="center"><?=$row['id'];?></td><td  align="center"><a href="http://vk.com/id<?=$row['uid'];?>" target="_blank"><?=$row['uid'];?></a></td><td align="center"><?=$row["likes"];?></td><td align="center"><?=$row['money'];?></td><td align="center"><a href="index.php?page=vquick_admin&act=up&s=pays&id=<?=$row['id'];?>"><img src="inc/theme/images/coins.png" style=" padding:0px;margin-bottom:-2px;" title="Пополнить баланс"></a><a href="index.php?page=vquick_admin&s=pays&act=del&id=<?=$row['id'];?>"><img src="inc/theme/images/cancel.png" style=" padding:0px;margin-bottom:-2px;" title="Удалить"></a></td></tr>
<?
}
?>
</tbody></table>