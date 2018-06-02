<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/webmoney.php');
require($root.'/inc/classes/qiwi.php');
require($root.'/inc/classes/waytopay.php');

$points = (int) $_GET['points'];
$unique_key = $time + $user_id + rand(0, 5);
$type_name = $_GET['type'];
$number = $_GET['number'];

if($type_name == 'webmoney') {
 if($points) {
  $table_insert = $webmoney->insert_table($points, $unique_key);
  if(!$table_insert) {
   echo 'Ошибка соединения с базой данных. Попробуйте позже.';
   exit;
  }
 }
} elseif($type_name == 'qiwi') {
 if($points) {
  $table_insert = $qiwi->insert_table($points, $unique_key);
  if(!$table_insert) {
   echo 'Ошибка соединения с базой данных. Попробуйте позже.';
   exit;
  }
 }
} elseif($type_name == 'waytopay_webmoney' or $type_name == 'waytopay_ya' or $type_name == 'waytopay_qiwi') {
  if($points) {
  $table_insert = $waytopay->insert_table($points, $unique_key);
  if(!$table_insert) {
   echo 'Ошибка соединения с базой данных. Попробуйте позже.';
   exit;
  }
 }
} else {
 echo 'Access Denied.';
 exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Перенаправление на платежную систему</title>
  <style type="text/css">
   * {padding: 0; margin: 0}
   body {background: #ffffff; font-size: 11px; font-family: tahoma;}
   #content {
    position: absolute;
    top: 50%;
    left: 50%;
    line-height: 18px;
    margin: -18px 0 0 -139px;
    text-align: center;
   }
  </style>
 </head>
 <body>
  <div id="content">
   Перенаправление на платежную систему <b><? if($type_name == 'webmoney') echo 'WebMoney'; elseif($type_name == 'qiwi') echo 'QIWI'; else echo ''; ?></b>.
   <br />
   Пожалуйста, подождите...
  </div>
  <? if($type_name == 'webmoney') { ?> 
  <form style="display: none" method="POST" action="https://merchant.webmoney.ru/lmi/payment.asp?at=authtype_9">
   <input type="shidden" name="LMI_PAYMENT_AMOUNT" value="<? echo (floor($points * 0.2 * 10))/10; ?>">
   <input type="shidden" name="LMI_PAYMENT_DESC_BASE64" value="<? echo base64_encode('[MontyTool #123] Оплата монет'); ?>">
   <input type="shidden" name="LMI_PAYMENT_NO" value="<? echo $unique_key; ?>">
   <input type="hidden" name="LMI_PAYEE_PURSE" value="R329583887979">
   <input type="hidden" name="LMI_SIM_MODE" value="0">
   <input type="hidden" name="LMI_MODE" value="1">
   <input type="hidden" name="LMI_PREREQUEST" value="1">
   <input type="shidden" name="points" value="<? echo $points; ?>">
   <div style="display: none"><input type="submit" id="submit"></div>
  </form>
  <script type="text/javascript">
   document.getElementById('submit').click();
  </script>
  <? } elseif($type_name == 'qiwi') { ?>
   <script type="text/javascript">
    window.location.href = 'http://w.qiwi.ru/setInetBill_utf.do?from=240296&to=<? echo $number; ?>&summ=<? echo (floor($points * 0.2 * 10))/10; ?>&com=Баллы MontyTool&txn_id=<? echo $unique_key; ?>';
   </script>
  <? } elseif($type_name == 'waytopay_webmoney') { ?>
   <script type="text/javascript">
    window.location.href = 'https://waytopay.org/merchant/index/?MerchantId=7024&IncCurr=1&OutSum=<? echo (floor($points * 0.2 * 10))/10; ?>&InvDesc=Баллы MontyTool&InvId=<? echo $unique_key; ?>';
   </script>
    <? } elseif($type_name == 'waytopay_qiwi') { ?>
   <script type="text/javascript">
    window.location.href = 'https://waytopay.org/merchant/index/?MerchantId=7024&IncCurr=1&OutSum=<? echo (floor($points * 0.2 * 10))/10; ?>&InvDesc=Баллы MontyTool&InvId=<? echo $unique_key; ?>';
   </script>

  <? } elseif($type_name == 'waytopay_ya') { ?>
   <script type="text/javascript">
    window.location.href = 'https://waytopay.org/merchant/index/?MerchantId=7024&IncCurr=14&OutSum=<? echo (floor($points * 0.2 * 10))/10; ?>&InvDesc=Баллы MontyTool&InvId=<? echo $unique_key; ?>';
   </script>
  <? }  ?> 
 </body>
</html>