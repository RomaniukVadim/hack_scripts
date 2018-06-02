<?
 
  include('../db.php');
  $delid = $_POST['delid'];
  mysql_query("DELETE FROM vk WHERE id=$delid;");
  header('Location: /admin/vk.php');

?>