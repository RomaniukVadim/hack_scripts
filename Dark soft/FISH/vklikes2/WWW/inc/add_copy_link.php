<?
if(isset($_SESSION['access_token'])){
?>
<h3>Заказ репостов</h3>
<?   
   if($_POST['name']){
   $name = mysql_real_escape_string(htmlspecialchars($_POST['name']));
   $url = mysql_real_escape_string(htmlspecialchars($_POST['url']));
   $balans = intval($_POST['balans'])*2;
   if(strlen($name)<51 and $name!=NULL){
       if(valid_vk_url($url)==true){
	       if($user_row["likes"]>=$balans and $balans>=10){
		       $user = $user_row["id"];
			   $link_id = type($url)."_".first($url)."_".second($url);
		       mysql_query("INSERT INTO tb_ads (user,balans,name,link,link_id,type,for_one) VALUES ('$user','$balans','$name','$url','$link_id','copy','2')");
			   mysql_query("UPDATE tb_members SET likes = likes - $balans WHERE id = '$user'");
			   echo "<div class='w_ok'><div class='wmsg'>Ваш заказ успешно принят! :)</div></div>";
		   }else{
		      echo "<div class='w_warning'><div class='wmsg'>Минимальный заказ 5 репостов, либо у Вас недостаточно лайков на балансе</div></div>";
		   }
	   }else{
	     echo "<div class='w_warning'><div class='wmsg'>Неверная ссылка. Проверьте правильность и попробуйте снова</div></div>"; #Неверная ссылка. Проверьте правильность и попробуйте снова
	   }
   }else{
     echo "<div class='w_warning'><div class='wmsg'>Название превышает 50 символов либо не заполнено</div></div>";#Название превышает 50 символов либо не заполнено
   }
   }
   ?>
<table width="100%" cellspacing="0" class="table">		
<tbody>
<form action="" method="post">
<tr style="background-color:#FFFFFF;"><td align="right">Название</td><td><input type="text" name="name" size="45" maxlength="50"></td><td>Будет отображаться в списке</td></tr>
<tr style="background-color:#F3F3F2;"><td align="right">Ссылка</td><td><input type="text" name="url" size="45" maxlength="200"></td><td>Смотрите ниже, как найти верную ссылку</td></tr>
<tr style="background-color:#FFFFFF;"><td align="right">Репостов</td><td><input type="text" name="balans" size="10" maxlength="6"></td><td>Минимум <strong>5</strong>(1 = 2♥)</td></tr>
<tr style="background-color:#F3F3F2;"><td align="right">Подтвердите</td><td colspan="2"><input type="submit" value="Готово" style="font-family:Tahoma; font-size:11px;"></td></tr>
</form>
</tbody>
</table>   
<br />
<h3>Как получить <strong>верную</strong> ссылку:</h3>

<strong>На запись:</strong><br /><br />
<img src="info/1.jpg" /><br /><br />
<strong>На изображение:</strong><br /><br />
<img src="info/2.jpg" /><br /><br />
<strong>На видео:</strong><br /><br />
  <img src="info/3.jpg" /><br />
   <?
}else{
echo "<h3>Авторизируйтесь</h3><div class='w_warning'><div class='wmsg'>Пройдите авторизацию для доступа к данной странице</div></div>";
}
?>