<?
if(isset($_SESSION['access_token'])){
?>
<h3>Пополнение баланса</h3>
<?
if($_POST['likes']){
   $likes = intval($_POST['likes']);
   $money = $likes*$how_r_for_one_l;
   $uid = $user_row['uid'];
   if($likes>=100){
        mysql_query("INSERT INTO tb_pay (uid,likes,money) VALUES ('$uid','$likes','$money')");
		$row = mysql_fetch_array(mysql_query("SELECT id FROM tb_pay WHERE uid = '$uid' ORDER BY id DESC"));
		?>
		<div class='w_info'><div class='wmsg'>Ваша заявка принята и будет обработана вручную после оплаты</div></div>
		Переведите <strong><?=$money;?></strong>руб. на кошелек <strong><?=$admin_wmr;?></strong> указав в описании <strong>100 лайков за <?=$money;?>руб. #<?=$row['id'];?></strong><br><br>
		<?
   }else{
      echo "<div class='w_warning'><div class='wmsg'>Минимальный заказ 100 лайков</div></div>";
   }
}
?>
<script type="text/javascript">
var celoe;
var drob;
var res;
function okrugl(nums)
{
	celoe=Math.floor(nums);
	drob=(nums-celoe)*100;
	drob=Math.floor(drob);
	if(drob>=10)
	{
		res=celoe+'.'+drob;
	}else{
		res=celoe+'.0'+drob;
	}
	return res;
}

function getZakaz(frm)
{
    frm.summa.value = okrugl(frm.likes.value*<?=$how_r_for_one_l;?>);
}
</script>
<table width="100%" cellspacing="0" class="table">		
<tbody>
<form action="" method="post" onChange="getZakaz(this.form)">
<tr style="background-color:#FFFFFF;"><td align="right">Лайков</td><td><input type="text" onKeyDown="getZakaz(this.form)" onKeyUp="getZakaz(this.form)" name="likes" size="10" maxlength="6"></td><td>Будет отображаться в списке</td></tr>
<tr style="background-color:#F3F3F2;"><td align="right">Цена</td><td><input type="text" name="summa" size="10" readonly="" ></td><td>В рублях</td></tr>
<tr style="background-color:#FFFFFF;"><td align="right">Подтвердите</td><td colspan="2"><input type="submit" value="Готово" style="font-family:Tahoma; font-size:11px;"></td></tr>
</form>
</tbody>
</table>   
  <br />
<div class='w_info'><div class='wmsg'>Минимальная сумма на пополнение составляет <strong>100</strong> лайков.</div></div> 
   <?
}else{
echo "<h3>Авторизируйтесь</h3><div class='w_warning'><div class='wmsg'>Пройдите авторизацию для доступа к данной странице</div></div>";
}
?>