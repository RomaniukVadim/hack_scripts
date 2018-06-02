<?
session_start();
require 'include/config.php';
if(isset($_SESSION['access_token'])){
      if($_SESSION['copy_link_id']!=""){
      $num = mysql_num_rows(mysql_query("SELECT id FROM tb_ads_views WHERE user = '".$user_row['id']."' and ad_id = '".$_SESSION['copy_link_id']."' and ad_type = 'copy'"));
	  if($num == 0){
	      $num_2 = mysql_num_rows(mysql_query("SELECT id FROM tb_ads WHERE balans>=for_one and user != '".$user_row['id']."' and type = 'copy' and link_id = '".$_SESSION['copy_link_id']."'"));
		  if($num_2>0){
		      $row = mysql_fetch_array(mysql_query("SELECT * FROM tb_ads WHERE balans>=for_one and user != '".$user_row['id']."' and type = 'copy' and link_id = '".$_SESSION['copy_link_id']."'"));
		      $req = file_get_contents("https://api.vk.com/method/likes.getList?type=".type($row["link"])."&owner_id=".first($row["link"])."&item_id=".second($row["link"])."&filter=copies");
			  if(substr_count($req, $_SESSION['user_id'])>0){
			  mysql_query("UPDATE tb_ads SET balans = balans - 2 WHERE id = '".$row['id']."'");
			  mysql_query("UPDATE tb_members SET likes = likes + 2 WHERE id = '".$user_row['id']."'");
			  mysql_query("INSERT INTO tb_ads_views (user,ad_id,ad_type) VALUES ('".$user_row['id']."','".$row['link_id']."','copy')");
			  $_SESSION['copy_link_id']="";
			  ?>
			  $(".info").empty().append("<?=$user_row["likes"]+2;?> ♥");$("#body").append('<div id="message" onclick="hideMessages();">Задание выполнено, +2<div>');var message = setTimeout("hideMessages()", 2500);
			  <?
			  }else{
			  $_SESSION['copy_link_id']="";
			  mysql_query("INSERT INTO tb_ads_views (user,ad_id,ad_type) VALUES ('".$user_row['id']."','".$row['link_id']."','copy')");
	     ?>
		 $("#body").append('<div id="message" onclick="hideMessages();">Репост не найден<div>');var message = setTimeout("hideMessages()", 2500);
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