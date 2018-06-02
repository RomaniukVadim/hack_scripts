<?
if(isset($_SESSION['access_token'])){
?>
<h3>Заказ выплаты</h3>
<?
if($_POST['wmr']){
$money = $user_row['money'];
$wmr = $_POST['wmr'];
$uid = $user_row['uid'];

if(substr($wmr,0,1)=='R' and strlen($wmr)==13 and is_numeric(substr($wmr,1,13)))
{
if($money>=10){
mysql_query("INSERT INTO tb_pay_me (`uid`,`wmr`,`money`) VALUES ('$uid','$wmr','$money')");
mysql_query("UPDATE tb_members SET money = 0 WHERE uid = '".$user_row['uid']."'");
?>
<div class='w_ok'><div class='wmsg'>Ваша заявка принята! Деньги поступят на Ваш кошелек сразу после проверки администратора! :)</div></div>
<?
}else{
?><div class='w_warning'><div class='wmsg'>Недостаточно денег на балансе для вывода</div></div><?
}
}else{
?><div class='w_warning'><div class='wmsg'>Неверно введен кошелек</div></div><?
}
}
?>
<table width="100%" cellspacing="0" class="table">		
<tbody>
<form action="" method="post">
<tr style="background-color:#FFFFFF;"><td align="right">На балансе</td><td><?=$user_row['money'];?></td><td>Сколько поступит на Ваш кошелек</td></tr>
<tr style="background-color:#FFFFFF;"><td align="right">WMR кошелек</td><td><input type="text" name="wmr" size="15" maxlength="13"></td><td>Ваш рублевый кошелек в системе WM</td></tr>
<tr style="background-color:#FFFFFF;"><td align="right">Подтвердите</td><td colspan="2"><input type="submit" value="Готово" style="font-family:Tahoma; font-size:11px;"></td></tr>
</form>
</tbody>
</table>   <br />
<div class='w_info'><div class='wmsg'>Минимальная сумма выплаты <strong>10</strong> руб.</div></div>
   <?
}else{
echo "<h3>Авторизируйтесь</h3><div class='w_warning'><div class='wmsg'>Пройдите авторизацию для доступа к данной странице</div></div>";
}
?>