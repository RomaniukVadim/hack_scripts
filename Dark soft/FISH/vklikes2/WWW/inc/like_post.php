<?
if(isset($_SESSION['access_token'])){
?>
<h3>Перевод лайков</h3>
<?
if($_POST['likes']){
  $likes = intval($_POST['likes']);
  $user = intval($_POST['user']);
  $uid = $user_row['uid'];
  $num = mysql_num_rows(mysql_query("SELECT id FROM tb_members WHERE uid = $user"));
  if($num==1){
     if($user_row['likes']>=$likes and $likes>=10){
	    mysql_query("UPDATE tb_members SET likes = likes - $likes WHERE uid = $uid");
		$likes = round($likes - $likes*0.10,0);
		mysql_query("UPDATE tb_members SET likes = likes + $likes WHERE uid = $user");
		echo "<div class='w_ok'><div class='wmsg'>Успешный перевод</div></div>";
	 }else{
	  echo "<div class='w_warning'><div class='wmsg'>Недостаточно лайков на балансе или меньше минималки(10)</div></div>";
	 }
  }else{
  echo "<div class='w_warning'><div class='wmsg'>Пользователь не найден</div></div>";
  }

}
?>
<table width="100%" cellspacing="0" class="table">		
<tbody>
<form action="" method="post">
<tr style="background-color:#FFFFFF;"><td align="right">Лайков</td><td><input type="text" name="likes" size="10" maxlength="6"></td><td>Сколько перевести</td></tr>
<tr style="background-color:#F3F3F2;"><td align="right">Кому</td><td><input type="text" name="user" size="20" maxlength="80"></td><td>VK id получателя</td></tr>
<tr style="background-color:#FFFFFF;"><td align="right">Подтвердите</td><td colspan="2"><input type="submit" value="Готово" style="font-family:Tahoma; font-size:11px;"></td></tr>
</form>
</tbody>
</table> 
<br />
<div class='w_info'><div class='wmsg'>Минимальная сумма на перевод <strong>10</strong> лайков. Комисия составляет <strong>10%</strong> с получателя.</div></div>
   <?
}else{
echo "<h3>Авторизируйтесь</h3><div class='w_warning'><div class='wmsg'>Пройдите авторизацию для доступа к данной странице</div></div>";
}
?>