<?
session_start();
require 'include/config.php';
if(isset($_SESSION['access_token'])){
      if($_SESSION['friend_link_id']!=""){
      $num = mysql_num_rows(mysql_query("SELECT id FROM tb_ads_views WHERE user = '".$user_row['id']."' and ad_id = '".$_SESSION['friend_link_id']."' and ad_type = 'friend'"));
	  if($num == 0){
	      $num_2 = mysql_num_rows(mysql_query("SELECT id FROM tb_ads WHERE balans >= for_one and user != '".$user_row['id']."' and type = 'friend' and link_id = '".$_SESSION['friend_link_id']."'"));
		  if($num_2>0){
		      $row = mysql_fetch_array(mysql_query("SELECT * FROM tb_ads WHERE balans >= for_one and user != '".$user_row['id']."' and type = 'friend' and link_id = '".$_SESSION['friend_link_id']."'"));
		      $req = file_get_contents("https://api.vk.com/method/friends.areFriends?access_token=".$_SESSION['access_token']."&uids=".$row['link_id']);
			  $data = json_decode($req, true);
			  if($data["response"][0]["friend_status"]!=0){
			  mysql_query("UPDATE tb_ads SET balans = balans - '".$row['for_one']."' WHERE id = '".$row['id']."'");
			  mysql_query("UPDATE tb_members SET likes = likes + '".$row['for_one']."' WHERE id = '".$user_row['id']."'");
			  mysql_query("INSERT INTO tb_ads_views (user,ad_id,ad_type) VALUES ('".$user_row['id']."','".$row['link_id']."','friend')");
			  $_SESSION['friend_link_id']="";
			  ?>
			  $(".info").empty().append("<?=$user_row["likes"]+$row["for_one"];?> ♥");$("#body").append('<div id="message" onclick="hideMessages();">Задание выполнено, +<?=$row["for_one"];?><div>');var message = setTimeout("hideMessages()", 2500);
			  <?
			  }else{
			  $_SESSION['friend_link_id']="";
			  mysql_query("INSERT INTO tb_ads_views (user,ad_id,ad_type) VALUES ('".$user_row['id']."','".$row['link_id']."','friend')");
	     ?>
		 $("#body").append('<div id="message" onclick="hideMessages();">Задание не выполнено<div>');var message = setTimeout("hideMessages()", 2500);
		 <?
			  }			  
		  }else{
	     ?>
		 $("#body").append('<div id="message" onclick="hideMessages();">Баланс задания исчерпан<div>');var message = setTimeout("hideMessages()", 2500);
		 <?
		  }
	  }}
}else{
?>
$("#body").append('<div id="message" onclick="hideMessages();">Пройдите авторизацию!<div>');var message = setTimeout("hideMessages()", 2500);
<?
}
?>