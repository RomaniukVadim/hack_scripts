<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'add.cupons';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/logs.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/vk.api.php');
require($root.'/inc/classes/sessions.php');
require($root.'/inc/classes/cupons.php');

if ($_POST['type']==1){
//$cat_id = $cupons->getCatNum($_GET['section']); // id категории
$coupon_name = $cupons->rand_str(5).'-'.$cupons->rand_str(5).'-'.$cupons->rand_str(5).'-'.$cupons->rand_str(5).'-'.$cupons->rand_str(5);

echo $cupons->add_cup(array(
 'upoints' => $upoints,
  'uid' => $user_id,
  'coupon_name' => $coupon_name,
 'points' => $_POST['points'],
 'ssid' => $_POST['ssid']
));
} elseif ($_POST['type']==2){
	echo $cupons->actv_cup(array(
 'code' => $_POST['code'],
  'uid' => $user_id,
 'ssid' => $_POST['ssid']
));
	
} else echo array('error_text' => 'Неизвестная ошибка.');

?>