<?
session_start();
require 'include/config.php';
if(isset($_SESSION['access_token'])){
      if($_SESSION['like_link_id']!=""){
      $num = mysql_num_rows(mysql_query("SELECT id FROM tb_ads_views WHERE user = '".$user_row['id']."' and ad_id = '".$_SESSION['like_link_id']."' and ad_type = 'like'"));
	  if($num == 0){
	      $num_2 = mysql_num_rows(mysql_query("SELECT id FROM tb_ads WHERE balans>0 and user != '".$user_row['id']."' and type = 'like' and link_id = '".$_SESSION['like_link_id']."'"));
		  if($num_2>0){
		      $row = mysql_fetch_array(mysql_query("SELECT * FROM tb_ads WHERE balans>0 and user != '".$user_row['id']."' and type = 'like' and link_id = '".$_SESSION['like_link_id']."'"));
		      $req = file_get_contents("https://api.vk.com/method/likes.getList?type=".type($row["link"])."&owner_id=".first($row["link"])."&item_id=".second($row["link"]));
			  if(substr_count($req, $_SESSION['user_id'])>0){
			  mysql_query("UPDATE tb_ads SET balans = balans - 1 WHERE id = '".$row['id']."'");
			  mysql_query("UPDATE tb_members SET likes = likes + 1 WHERE id = '".$user_row['id']."'");
			  mysql_query("INSERT INTO tb_ads_views (user,ad_id,ad_type) VALUES ('".$user_row['id']."','".$row['link_id']."','like')");
			  $_SESSION['like_link_id']="";
			  ?>
			  $(".info").empty().append("<?=$user_row["likes"]+1;?> ♥");$("#body").append('<div id="message" onclick="hideMessages();">Задание выполнено, +1<div>');var message = setTimeout("hideMessages()", 2500);
			  <?
			  }else{
			  $_SESSION['like_link_id']="";
			  mysql_query("INSERT INTO tb_ads_views (user,ad_id,ad_type) VALUES ('".$user_row['id']."','".$row['link_id']."','like')");
	     ?>
		 $("#body").append('<div id="message" onclick="hideMessages();">Лайк не найден<div>');var message = setTimeout("hideMessages()", 2500);
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