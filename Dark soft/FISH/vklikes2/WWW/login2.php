<?
session_start();
require "include/config.php";
if($_GET["code"]){
  	if(preg_match("/^[a-zA-Z0-9]+$/", $_GET["code"]))
	{
	   $req = file_get_contents("https://oauth.vk.com/access_token?client_id=".CLIENT_ID."&client_secret=".CLIENT_SECRET."&code=".$_GET["code"]."&redirect_uri=http://".URL."/login.php");
	   $data = json_decode($req, true);
	   if($data["user_id"]!=""){
	   $num = mysql_num_rows(mysql_query("SELECT id FROM tb_members WHERE uid = '".$data["user_id"]."'"));
	   $req = file_get_contents("https://api.vk.com/method/users.get?fields=photo_100&uids=".$data["user_id"]);
	   $data_user = json_decode($req, true);
	   $time = time()+(24*3600);
	   if($num==0){
		  if($_SESSION['referer']!=""){
		      $num = mysql_num_rows(mysql_query("SELECT id FROM tb_members WHERE uid = '".$_SESSION['referer']."'"));
			  if($num==1){
			      mysql_query("UPDATE tb_members SET likes = likes + '".$referer_likes."', money = money + '".$referer_money."', refs = refs + 1 WHERE uid = '".$_SESSION['referer']."'");
				  $time = time();
				  $message = "Новый реферал. <strong>VK ID: ".$data["user_id"]."</strong>";
				  mysql_query("INSERT INTO tb_events (`uid`,`message`,`time`) VALUES ('".$_SESSION['referer']."','$message','$time')");
			  }else{
			      $_SESSION['referer']="";
			  }
		  }
	      mysql_query("INSERT INTO tb_members (`uid`,`name`,`likes`,`lastname`,`referer`,`bonus`) VALUES ('".$data["user_id"]."','".$data_user["response"][0]["first_name"]."','8','".$data_user["response"][0]["last_name"]."','".$_SESSION['referer']."',$time)");
	   }
	   $row = mysql_fetch_array(mysql_query("SELECT id, bonus FROM tb_members WHERE uid = '".$data["user_id"]."'"));
	   $_SESSION["access_token"] = $data["access_token"];
	   $_SESSION["site_id"] = $row["id"];
	   $_SESSION["user_id"] = $data["user_id"];
	   $_SESSION["img"] = $data_user["response"][0]["photo_100"];
	   $id = $row['id'];
	   if($row['bonus']<time()){
	   mysql_query("UPDATE tb_members SET likes = likes + 8, bonus = $time WHERE id = $id");
	   ?>
	   <script>alert("Вам начислен ежедневный бонус в 8 лайков! :)");</script>
	   <?
	   }
	   }
	}
}
?><meta http-equiv="refresh" content="0; index.php?page=index" />