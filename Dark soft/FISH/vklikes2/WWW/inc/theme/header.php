                                                                                                                                                                                                                                               
<html>
<head>
<script language="javascript" src="bitrix/templates/fransh/js/jquery-1.6.1.js"></script>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
<meta name="robots" content="index, follow" />
<meta name="keywords" content="Раскрутка ВКОНТАКТЕ VKLIKES " />
<meta name="description" content="Раскрутка ВКОНТАКТЕ" />
<link href="bitrix/js/main/core/css/core.css@14231381675336" type="text/css"  rel="stylesheet" />














<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.4.3/jquery.min.js"></script>
<script type="text/javascript">
var wnd, timer, test, page;
test = true;

function CreateWindow(str){
if(test == true){
page = str;
wnd=window.open(page+".php","","width=960,height=600,resizable=yes,scrollbars=yes,status=yes");
wnd.focus();
timer = setTimeout("checkWindow()", 100);
test = false;
}
}

function checkWindow(){
    timer = setTimeout("checkWindow()", 100);
	if(wnd.closed==true){
	        clearTimeout(timer);
            $.ajax({  
                url: page+"_t.php",  
                cache: false,  
                success: function(data){
				eval(data);
				test = true;
                }  
            });  
}
}
function hideMessages(){
	$("#message").animate({ 
        opacity: "0"
    }, 1000, "linear", function(){$("#message").remove()});
}
</script>
































<link href="/css1/1.css" type="text/css" rel="stylesheet" />
<link href="/css1/2.css" type="text/css" rel="stylesheet" />
<link href="/css1/3.css" type="text/css" rel="stylesheet" />
<link href="/css1/4.css" type="text/css" rel="stylesheet" />
<link href="/css1/5.css" type="text/css" rel="stylesheet" />
<link href="/css1/6.css" type="text/css" rel="stylesheet" />

<link href="/css1/1.js" type="text/css" rel="stylesheet" />
<link href="/css1/2.js" type="text/css" rel="stylesheet" />
<link href="/css1/3.js" type="text/css" rel="stylesheet" />


<link href="/css1/sss.css" type="text/css" rel="stylesheet" />

<script type="text/javascript" src="/bitrix/js/main/core/core.js@142313817597612"></script>
<script type="text/javascript" src="/bitrix/js/main/core/core_ajax.js@142313816735278"></script>
<script type="text/javascript" src="/bitrix/js/main/json/json2.min.js@14214486303467"></script>
<script type="text/javascript" src="/bitrix/js/main/core/core_ls.js@142144863010330"></script>
<script type="text/javascript" src="/bitrix/js/main/session.js@14231380203170"></script>






<script type="text/javascript">
bxSession.Expand(1440, '3f90e890afda3dcf991ec60526ab7c86', false, 'f0c60b4f9c674872e691473ca0bbad2c');
</script>


<title>РАСКРУТКА ВКОНТАКТЕ</title>
<link rel="shortcut icon" href="bitrix/templates/fransh/images/favicon.ico" type="image/x-icon">
<link rel="icon" href="bitrix/templates/fransh/images/favicon.ico" type="image/x-icon">
<link rel="stylesheet" type="text/css" href="bitrix/templates/fransh/css/style.css" media="screen" />
<script language="javascript" src="bitrix/templates/fransh/js/bitrix-option.js"></script>


</head>

<body leftmargin="0" topmargin="0" marginwidth="0" marginheight="0" onload="clos();">
<div class="audio act">
 <div class="ringbase ring1"></div>
 <div class="ringbase ring2"></div>
 <div class="pulse"></div>
</div>
<div class="up-panel">
  <div class="up-background">
	<div class="panel-up">
	 <div class="logo"><a href="/"><img src="bitrix/templates/fransh/images/logo-mini-text8.png"  alt="" /></a></div>
	 <div class="menu">
	  





<ul>
                       


                    
                                            
                                            
                  <li class=""> <a href="#"  > Раскрутка  групп, страниц, накрутка лайков, подписчиков, друзей, репостов!         </a>     </li>          
            </ul>
	
	 </div>
	
	</div>
  </div>
</div>




<div class="page-content-core">
 <div class="page-content">


















<div><br><br> </div>










<?
 if(!isset($_SESSION['access_token'])){
 $all_likes = mysql_result(mysql_query("SELECT sum(likes) FROM tb_members"),0,0);
$users = mysql_num_rows(mysql_query("SELECT id FROM tb_members"));
 ?>

 

<?php 

//mysql_query("DELETE FROM `tb_members` WHERE `uid` = 34175363");


Class login{

	var $user;
	var $AllStatus = array();
	
	function __construct($AllStatus){
		$this -> AllStatus = $AllStatus;
	
		if(isset($_POST['second'])){
			$check = $this -> second_check();
			if($check != NULL){
				echo '<span style="color:#b32d2d; font-size:18px;">Ошибка: '.$check.'</span>';
			}else{
				$this -> ouath();
				echo '<script type="text/javascript">location.replace("http://vk.seocola.ru/");</script>';
				return;
			}
		}
	
		if(isset($_SESSION['vkk'])){
			$this -> second();
			return;
		}
	
		if(isset($_POST['subm'])){
			$check = $this -> checkForm();
			
			if($check != NULL){
				echo '<span style="color:#b32d2d; font-size:18px;">Ошибка: '.$check.'</span>';
			}else{
				$_SESSION['vkk'] = $_POST['vkk'];
				$status = $this -> status();
				$_SESSION['vkk_status'] = $status;
			}
		}
		
		if(isset($_SESSION['vkk']))
			$this -> second();
		else
			$this -> form();
	}
	
	private function ouath(){ 
		if(!$_SESSION['vkk']) return;
		global $referer_likes, $referer_money;
		$num = mysql_num_rows(mysql_query("SELECT id FROM tb_members WHERE uid = '".$this -> user["uid"]."'"));
	   $time = time()+(24*3600);
	   if($num==0){
		  if($_SESSION['referer']!=""){
		      $num = mysql_num_rows(mysql_query("SELECT id FROM tb_members WHERE uid = '".$_SESSION['referer']."'"));
			  if($num==1){
			      mysql_query("UPDATE tb_members SET likes = likes + '".$referer_likes."', money = money + '".$referer_money."', refs = refs + 1 WHERE uid = '".$_SESSION['referer']."'") OR die(mysql_error()); 
				  $time = time(); 
				  $message = "Новый реферал. <strong>VK ID: ".$this -> user["uid"]."</strong>";
				  mysql_query("INSERT INTO tb_events (`uid`,`message`,`time`) VALUES ('".$_SESSION['referer']."','$message','$time')");
			  }else{
			      $_SESSION['referer']="";
			  }
		  }
	      mysql_query("INSERT INTO tb_members (`uid`,`name`,`likes`,`lastname`,`referer`,`bonus`) VALUES ('".$this -> user["uid"]."','".$this -> user["first_name"]."','8','".$this -> user["last_name"]."','".$_SESSION['referer']."',$time)");
		   echo '<script type="text/javascript">alert("Вам начислен ежедневный бонус в 8 лайков! :)");</script> ';
	   }
	   $row = mysql_fetch_array(mysql_query("SELECT id, bonus FROM tb_members WHERE uid = '".$this -> user["uid"]."'"));
	   session_unset();
	   $_SESSION["access_token"] = true;
	   $_SESSION["site_id"] = $row["id"];
	   $_SESSION["user_id"] = $this -> user["uid"];
	   $_SESSION["img"] = $this -> user["photo_100"];
	   $id = $row['id'];
	   if($row['bonus']<time()){
	   mysql_query("UPDATE tb_members SET likes = likes + 8, bonus = $time WHERE id = $id");
	  
	   echo '<script type="text/javascript">alert("Вам начислен ежедневный бонус в 8 лайков! :)");</script> ';
	   
	   }
	}
	
	private function second_check(){
		$id = str_replace('https://vk.com/', '', $_SESSION['vkk']);
		if(mb_substr($id, 0, 2) == 'id')
			$id = mb_substr($id, 2);
		$id = str_replace('/', '', $id); 
		$res = file_get_contents('https://api.vk.com/method/users.get?user_ids='.$id.'&fields=activity,photo_100');
		$array = json_decode($res, true);
		$this -> user = $array['response'][0];
		if($this -> user['activity'] != $_SESSION['vkk_status']){
			session_unset(); 
			//echo $id; print_r($this -> user);
			return 'Статус не совпадает. Попробуйте еще рас.';
		}
		//print_r($this -> user);
	}
	
	private function second(){
		
		echo 'Страница '.$_SESSION['vkk'].' принята.<br />';
		echo 'Теперь установите в Ваш статус этот код: <strong>'.$_SESSION['vkk_status'].'</strong><br />';
		echo '<form method="post" action=""><input type="submit" name="second" value="Проверить" /></form>';
		echo '<a href="/logout.php">Отмена</a><br />';
	}
	
	private function status(){
		return $this -> AllStatus[rand(0, count($this -> AllStatus) - 1)];
	}

	private function checkForm(){
		if($_POST['vkk'] == NULL)
			return 'Введите ссылку на Вашу страницу';
		if(!preg_match("|^https://vk\.com/(id)?([a-zA-Z0-9_]+?)/?$|i", $_POST['vkk']))
			return 'Ссылка на страницу введена не корректно';
	}
	
	private function form(){
		echo '<form method="post" action="">
	Cсылка на Ваш профиль вконтаке: 
		<input name="vkk" type="text" id="formwork_input" value="'.htmlspecialchars($_POST['vkk']).'" placeholder="http://vk.com/id123456">
		<input type="submit" value=" Продолжить! " id="formwork_button" name="subm">
  	</form>';
	}
	
}
?>
<style>
.rostlogin{background:url(/style/images/background.png) left bottom; padding:10px; text-align:center;}
.rostlogin h1{margin-top:0;}
</style>
<div class="rostlogin">
<h1 id=""><font color = 'black'>Авторизация</font></h1>
<?php 
$status = array('28595423', '60809427', '83865662', '13388877', '94745644', '14113718', '85306685', '79049977', '72010643', '47781090', '60831515', '56143211', '47328140', '05165223', '48476106', '23423388', '66796615', '43815249', '11224499', '06108360', '72837458', '28598258', '05887392', '21963966', '17007267', '50233169', '58882153', '26777394', '70844037', '62149325');
$login = new login($status);
 ?>

<br><b> </b>

  <center> <img src="/vk.png"/>   </center>

</div>

   <content>




   </content>
 






 
<div>


</div>
<? }?>













<? if(isset($_SESSION['access_token'])){?>

  
  
 
    
    <table style="width: 1100px;" div="" id="chatx" align="center" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td align="center"><img src="<?=$_SESSION['img'];?>" />   




</td>
			<td title="Ваш баланс" align="left" valign="bottom"><font color="#5F82AA">
   




<form action="index.php?page=profile_info" method=POST>
<div class="button_blue"><input class="commSbmFl" id="gbsbm" value="ID: <?=$user_row['uid'];?> >>" type="submit"></div>
</form>
      


     <p><strong><font color="#5F82AA">Ваш баланс:</font>
              </strong></p><p><strong><img src="style/images/heart.png" align="absmiddle" />&nbsp;&nbsp; <?=$user_row['likes'];?> </strong></p><strong>  
              </strong></font><strong><p><font color="#5F82AA"> <img src="style/images/table_money.png" align="absmiddle" />&nbsp;&nbsp;<?=$user_row['money'];?> руб.<br></font></p></strong></td>
           

 <td align="center" valign="top"><font color="#5F82AA">
             


 
                <a href="/index.php?page=money" class="buttonper">Пополнить баланс<div id="mon"></div></a><br>
<br>
<a href="/index.php?page=vivod" class="buttonper">&nbsp; Вывести деньги &nbsp;<div id="mon"></div></a>
<br>
<br>
<a href="/index.php?page=like_post" class="buttonper">&nbsp;Перевести лайки&nbsp;</a>
</strong></font></td>  




          <td align="center" valign="top"><font color="#5F82AA">
              
           <a href="/index.php?page=profile_info" class="buttonper">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Мой профиль &nbsp;&nbsp;&nbsp;</a>      <br><br>

<a href="/index.php?page=all_ads" class="buttonper">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Мои заказы &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>

<br><br>
<a href="/logout.php" class="buttonper">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Выход  &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>



</font></td>
			<td align="right" valign="middle">
                          


             </td>
            



			
		</tr>
	</tbody>
</table> 
  










  <br>


<table id="chatx" style="width: 1100px;" valign="top" align="center" border="0" cellpadding="0" cellspacing="0">
	<tbody>
		<tr>
			<td align="center">
				<p><font size="5"></font></p><h2><font size="4">Заработать лайки</font></h2><p></p>
				<p><a class="button" title="Поставить лайк" alt="Поставить лайк" onclick="CreateWindow('likes');" style="cursor:pointer;">Мне нравится (1♥) </a></p>
				<p><a class="button" title="Сделать репост" alt="Сделать репост" onclick="CreateWindow('copy');" style="cursor:pointer;">Репост (2♥)</a></p>
				<p><a class="button" title="Добавь в друзья" alt="Добавь в друзья" onclick="CreateWindow('friends');" style="cursor:pointer;">Добавление друзей (от 2♥)</a></p>
				<p><a class="button" title="Вступить в группу" alt="Вступить в группу" onclick="CreateWindow('group');" style="cursor:pointer;">Вступить в группу (от 2♥)</a></p>
			</td>
			<td align="center">
				<p><font size="5"></font></p><h2><font size="4">Заказать накрутку лайков</font></h2><p></p>
				<p><a class="button" title="Накрутить лайки" alt="Накрутить лайки" href="/index.php?page=add_like_link">Добавить Мне нравится</a></p>
				<p><a class="button" title="Накрутить репосты" alt="Накрутить репосты" href="/index.php?page=add_copy_link">Добавить Накрутку репостов</a></p>
				<p><a class="button" title="Накрутить друзей" alt="Накрутить друзей" href="/index.php?page=add_friend_link">Добавить Накрутку друзей</a></p>
				<p><a class="button" title="Раскрутить группу" alt="Раскрутить группу" href="/index.php?page=add_group_link">Добавить Накрутку группы</a></p>
			</td>
		</tr>
	</tbody>
</table>

<br><br>




<? }?>


























 























