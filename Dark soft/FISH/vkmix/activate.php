<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');

$url_key = $db->escape($_GET['key']);
$url_uid = (int) $_GET['uid'];

$url_user_get = $db->query("SELECT `ulogin`, `uemail`, `ureg_time`, `uemail_activated` FROM `users` WHERE `uid` = '$url_uid'");
$url_user_data = $db->fetch($url_user_get);
$url_user_data_login = $url_user_data['ulogin'];
$url_user_data_email = $url_user_data['uemail'];
$url_user_data_reg_time = $url_user_data['ureg_time'];
$url_user_data_email_activated = $url_user_data['uemail_activated'];

$url_unique_key = md5("$url_user_data_reg_time+$url_user_data_login+$url_user_data_email");
$url_action = $user_id ? '/tasks' : '/';

if($url_key == $url_unique_key) {
 if($url_user_data_email_activated) {
  echo '<div style="margin-top: 60px; font-family: tahoma; font-size: 14px; font-weight: bold; color: red" align="center">Регистрация уже подтверждена.</div>';
 } elseif($db->query("UPDATE  `$dbName`.`users` SET  `uemail_activated` =  '1' WHERE  `users`.`uid` = '$url_uid' LIMIT 1 ;")) {
  echo '
   <form action="'.$url_action.'" method="post">
    <input name="activated_email" value="1" type="hidden">
    <div style="display: none"><input type="submit" id="submit"></div>
   </form>
   <script type="text/javascript">
    document.getElementById(\'submit\').click();
   </script>
  ';
 } else {
  echo '<div style="margin-top: 60px; font-family: tahoma; font-size: 14px; font-weight: bold; color: red" align="center">Ошибка соединения с базой данных.</div>';
 }
} else {
 echo '<div style="margin-top: 60px; font-family: tahoma; font-size: 14px; font-weight: bold; color: red" align="center">Ссылка недействительна.</div>';
}
?>