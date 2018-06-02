<?php
class webmoney {
 public function insert_table($points = null, $unique_key = null) {
  global $db, $dbName, $time, $user_id;
  
  if($db->query("INSERT INTO `$dbName`.`logs_pay_points` (`id`, `uid`, `type`, `points`, `time`, `unique_key`, `status`) VALUES (NULL, '$user_id', '1', '$points', '$time', '$unique_key', '0');")) {
   return 1;
  } else {
   return 0;
  }
 }
 
 public function check_unique_key($key = null) {
  global $db;
  
  $q = $db->query("SELECT `id` FROM `logs_pay_points` WHERE `unique_key` = '$key'");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function check_pay() {
  global $db, $dbName, $user_id, $logs;
  
  $get_last_unique = $db->query("SELECT `points`, `unique_key` FROM `logs_pay_points` WHERE `uid` = '$user_id' AND `status` = '0' AND `type` = '1' ORDER BY `id` DESC LIMIT 1");
  $data_last_unique = $db->fetch($get_last_unique);
  
  $wmid = 143647800937;
  $purse = 'R329583887979';
  $unique_key = $data_last_unique['unique_key'];
  $secret_key = '325500100105a';
  $md5_key = md5($wmid.''.$purse.''.$unique_key.''.$secret_key); 
  $request_wm = "<merchant.request>
   <wmid>$wmid</wmid>
   <lmi_payee_purse>$purse</lmi_payee_purse>
   <lmi_payment_no>$unique_key</lmi_payment_no>
   <lmi_payment_no_type></lmi_payment_no_type>
   <sign></sign>
   <md5>$md5_key</md5>
   <secret_key></secret_key>
  </merchant.request>";
  
  $ch = curl_init('https://merchant.webmoney.ru/conf/xml/XMLTransGet.asp');
  curl_setopt($ch, CURLOPT_POST, 1); 
  curl_setopt($ch, CURLINFO_HEADER_OUT, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $request_wm);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $result = curl_exec($ch);
  
  // разбираем ответ
  $xml = new SimpleXMLElement($result);
  $retval = $xml->retval;
  $amount = floatval($xml->operation->amount);
  $points = $amount/0.2;
  
  if($unique_key) {
   if($retval == 0) {
    if($db->query("INSERT INTO `$dbName`.`pay_points_unique` (`id`, `unique_key`) VALUES (NULL, '$unique_key');")) {
     $db->query("UPDATE `$dbName`.`logs_pay_points` SET  `status` =  '1' WHERE  `logs_pay_points`.`unique_key` = '$unique_key';");
     $db->query("UPDATE `$dbName`.`users` SET  `upoints` = upoints + $points WHERE  `users`.`uid` = '$user_id';");
     $logs->pay_webmoney($user_id, $points);
     
     if($points >= 5) {
      // проценты рефералу
      $qRef = $db->query("SELECT `to` FROM `ref` WHERE `from` = '$user_id'");
      $dRef = $db->fetch($qRef);
      
      $dRef_id = $dRef['to'];
      
      if($dRef_id) { // если есть реферал
       $ref_percents = round(($points / 100) * 15); // вычисляем процент
       $db->query("UPDATE `$dbName`.`users` SET  `upoints` =  `upoints` + '$ref_percents' WHERE  `users`.`uid` = '$dRef_id';"); // начисляем баллы
       $logs->add_ref_percents($user_id, $dRef_id, $ref_percents); // записываем в лог
      }
     }
     
     $json = array('success' => 1, 'points' => $points);
    } else {
     $json = array('_error' => 1);
    }
   } else {
    $json = array('_error' => 1);
   }
  } else {
   $json = array('_error' => 1);
  }
  
  return json_encode($json);
 }
}

$webmoney = new webmoney;
?>