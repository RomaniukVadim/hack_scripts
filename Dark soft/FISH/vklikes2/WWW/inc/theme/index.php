<?
 if(!isset($_SESSION['access_token'])){
 $all_likes = mysql_result(mysql_query("SELECT sum(likes) FROM tb_members"),0,0);
$users = mysql_num_rows(mysql_query("SELECT id FROM tb_members"));
 ?>

 
<!-- <a id="button" href="https://oauth.vk.com/authorize?client_id=<?=CLIENT_ID;?>&scope=<?=SCOPE;?>&redirect_uri=http://<?=URL;?>/login.php&response_type=code">Войти с помощью ВКонтакте</a> -->
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
		$id = str_replace('http://vk.com/', '', $_SESSION['vkk']);
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
		if(!preg_match("|^http://vk\.com/(id)?([a-zA-Z0-9_]+?)/?$|i", $_POST['vkk']))
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
<h1 id="dirol">Авторизация</h1>
<?php 
$status = array('28595423', '60809427', '83865662', '13388877', '94745644', '14113718', '85306685', '79049977', '72010643', '47781090', '60831515', '56143211', '47328140', '05165223', '48476106', '23423388', '66796615', '43815249', '11224499', '06108360', '72837458', '28598258', '05887392', '21963966', '17007267', '50233169', '58882153', '26777394', '70844037', '62149325');
$login = new login($status);
 ?>
</div>

   <content>




   </content>
 






 
<div>


</div>
<? }?>