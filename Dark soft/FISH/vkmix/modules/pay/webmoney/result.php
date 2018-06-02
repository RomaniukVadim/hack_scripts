<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/webmoney.php');

$points = (int) $_POST['points'];

if(!$webmoney->check_unique_key($_POST['LMI_PAYMENT_NO'])) echo 'Ошибка доступа';
elseif($points < 1) echo 'Неверное значение.';
elseif((floor($points * 0.2 * 10))/10 != $_POST['LMI_PAYMENT_AMOUNT']) echo 'Неизвестная ошибка.';
else echo 'YES';
?>