<?
if(isset($_SESSION['access_token'])){
$num = mysql_num_rows(mysql_query("SELECT id FROM tb_events WHERE uid = '".$_SESSION['user_id']."'"));
if($num>0){
echo "<h3>События</h3>";
$res = mysql_query("SELECT * FROM tb_events WHERE uid = '".$_SESSION['user_id']."'");
while($row=mysql_fetch_array($res)){
?>
<div class='w_info'><div class='wmsg'><strong><?=date("d.m.Y H:i", $row['time']);?></strong> - <?=$row['message'];?></div></div>
<?
} 
mysql_query("DELETE FROM tb_events WHERE uid = '".$_SESSION['user_id']."'");
}
?>

<h3>Ваш профиль</h3>

    <table class="table" width="100%" cellspacing="0">
    <tbody>
	<tr bgcolor="#FFFFFF"><td align="right" width="50%"><strong>Ваш VK ID</strong></td><td><?=$user_row['uid'];?></td></tr>
    <tr bgcolor="#F3F3F2"><td align="right" width="50%"><strong>Лайков на балансе</strong></td><td><?=$user_row['likes'];?></td></tr>
    <tr bgcolor="#FFFFFF"><td align="right" width="50%"><strong>Авторизаций по Вашей реф. ссылке</strong></td><td><?=$user_row['refs'];?></td></tr>
	<tr bgcolor="#F3F3F2"><td align="right" width="50%"><strong>Ваша реф. ссылка</strong></td><td><input type="text" value="http://<?=URL;?>/?r=<?=$user_row['uid'];?>" size="50" readonly="" /></td></tr>
    </tbody>
    </table>
   <?
}else{
echo "<h3>Авторизируйтесь</h3><div class='w_warning'><div class='wmsg'>Пройдите авторизацию для доступа к данной странице</div></div>";
}
?>