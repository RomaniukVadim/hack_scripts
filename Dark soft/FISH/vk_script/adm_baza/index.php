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

<? if(isset($_GET['chek'])){
$pass=$_POST['pass'];
if($admin_pass==$pass){ $_SESSION['adm_pass']=$pass; ?><script>location.href = 'baza.php'</script> <? }	

}?>



<style> 
.but{
	border:#333 1px solid;
	padding:3px;
	border-radius:5px;
}
</style>
  
  
           
<form method="post" action="?chek">
Для доступа в админку введите пароль:<br /> 
<input type="text" name="pass"><br />
<input type="submit" value="Войти в админку" class="but">
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



</body>


</html>
