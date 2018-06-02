<h3>Заявки на вывод</h3>  
<?
if($_GET["act"]){
$id = $_GET["id"];
if($_GET["act"]=="del" and $_GET["id"]){
  mysql_query("DELETE FROM tb_pay_me WHERE id = '$id'");
  ?>
  <div class='w_ok'><div class='wmsg'>Заявка удалена</div></div>
  <?
}
}
?>
<table width="100%" cellspacing="0" cellpadding="0" class="table">		
<tbody>
<tr align="center" class="tab"><td width="20">#</td><td width="140">Юзер</td>
<td width="240">Выплата</td>
<td align="center">Кошель</td>
<td align="center">Оплатить</td>
<td width="60">Удалить</td>
</tr> 
<?
$i=0;
$res = mysql_query("SELECT * FROM tb_pay_me ORDER BY id ASC");
while($row=mysql_fetch_array($res)){
$i++;
?>
<tr style="background-color:#<? if ($i & 1){echo 'F3F3F2';}else{echo 'FFFFFF';}?>;"><td align="center"><?=$row['id'];?></td><td  align="center"><a href="http://vk.com/id<?=$row['uid'];?>" target="_blank"><?=$row['uid'];?></a></td><td align="center"><?=$row['money'];?></td><td align="center"><?=$row['wmr'];?></td><td align="center"><a href="wmk:payto?Purse=<?=$row['wmr'];?>&bringtofront=Y&Amount=<?=$row['money'];?>&Desc=Выплата с olikers.com. Ваш UID - <?=$row['uid'];?>">Выплатить</a></td><td align="center"><a href="index.php?page=vquick_admin&s=vivod&act=del&id=<?=$row['id'];?>">Удалить</a></td></tr>
<?
}
?>
</tbody></table>