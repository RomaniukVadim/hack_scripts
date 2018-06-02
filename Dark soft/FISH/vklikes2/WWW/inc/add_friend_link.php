<?
if(isset($_SESSION['access_token'])){
?>
<h3>Заказ друзей/подписчиков</h3>
<?   
   if($_POST['for_one']){
   $for_one = intval($_POST['for_one']);
   $kolvo = intval($_POST['kolvo']);
   $balans = $for_one*$kolvo;
   if($kolvo>=5){
   if($for_one>=2){
	       if($user_row["likes"]>=$balans and $balans>=10){
		       $user = $user_row["id"];
			   $link_id = $user_row["uid"];
			   $url = "http://vk.com/id".$user_row["uid"];
		       mysql_query("INSERT INTO tb_ads (user,balans,name,link,link_id,type,for_one) VALUES ('$user','$balans','Накрутка друзей/подписчиков','$url','$link_id','friend','$for_one')");
			   mysql_query("UPDATE tb_members SET likes = likes - $balans WHERE id = '$user'");
			   echo "<div class='w_ok'><div class='wmsg'>Ваш заказ успешно принят! :)</div></div>";
		   }else{
		      echo "<div class='w_warning'><div class='wmsg'>Минимальный заказ 10 лайков, либо у Вас недостаточно лайков на балансе</div></div>";
		   }
		   }else{
		   echo "<div class='w_warning'><div class='wmsg'>Минимальная цена друга/подписчика 2 лайка</div></div>";
		   }
		   }else{
		   echo "<div class='w_warning'><div class='wmsg'>Минимум 5 друзей/подписчиков</div></div>";
		   }
   }
   ?>
   <script type="text/javascript">
function getZakaz(frm)
{
    frm.summa.value = parseInt(parseInt(frm.for_one.value)*parseInt(frm.kolvo.value));
}
</script>
<table width="100%" cellspacing="0" class="table">		
<tbody>
<form action="" method="post" onChange="getZakaz(this.form)">
<tr style="background-color:#FFFFFF;"><td align="right">Ваша оплата за 1 друга/подписчика</td><td><input  onKeyDown="getZakaz(this.form)" onKeyUp="getZakaz(this.form)" type="text" name="for_one" size="6" maxlength="3" value="2" ></td><td>Сколько платить юзеру(минимум 2♥)</td></tr>
<tr style="background-color:#F3F3F2;"><td align="right">Кол-во друзей/подписчиков</td><td><input onKeyDown="getZakaz(this.form)" onKeyUp="getZakaz(this.form)" type="text" name="kolvo" size="6" maxlength="6" value="5"></td><td>Сколько накрутить юзеров(минимум 5)</td></tr>
<tr style="background-color:#FFFFFF;"><td align="right">Лайков</td><td><input type="text" size="10" maxlength="6" name="summa" readonly="" value="10"></td><td>Стоимость Вашего заказа</td></tr>
<tr style="background-color:#F3F3F2;"><td align="right">Подтвердите</td><td colspan="2"><input type="submit" value="Готово" style="font-family:Tahoma; font-size:11px;"></td></tr>
</form>
</tbody>
</table>   
   <?
}else{
echo "<h3>Авторизируйтесь</h3><div class='w_warning'><div class='wmsg'>Пройдите авторизацию для доступа к данной странице</div></div>";
}
?>