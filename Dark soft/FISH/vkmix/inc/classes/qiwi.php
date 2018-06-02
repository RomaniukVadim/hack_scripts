<?php
class qiwi {
 public function insert_table($points = null, $unique_key = null) {
  global $db, $dbName, $time, $user_id;
  
  if($db->query("INSERT INTO `$dbName`.`logs_pay_points` (`id`, `uid`, `type`, `points`, `time`, `unique_key`, `status`) VALUES (NULL, '$user_id', '3', '$points', '$time', '$unique_key', '0');")) {
   return 1;
  } else {
   return 0;
  }
 }
 
 public function check_pay() {
  global $db, $dbName, $user_id, $logs;
  
  $get_last_unique = $db->query("SELECT `points`, `unique_key` FROM `logs_pay_points` WHERE `uid` = '$user_id' AND `status` = '0' AND `type` = '3' ORDER BY `id` DESC LIMIT 1");
  $data_last_unique = $db->fetch($get_last_unique);
  
  $unique_key = $data_last_unique['unique_key'];

  $shop_id = 302642;
  $password = 'mirik50327QW';
  $request_qiwi = '<?xml version="1.0" encoding="utf-8"?>
  <request>
      <protocol-version>4.00</protocol-version>
      <request-type>33</request-type>
      <extra name="password">'.$password.'</extra>
      <terminal-id>'.$shop_id.'</terminal-id>
      <bills-list>
      <bill txn-id="'.$unique_key.'"/>
      </bills-list>
  </request>';
  
  $ch = curl_init('http://ishop.qiwi.ru/xml');
  curl_setopt($ch, CURLOPT_POST, 1); 
  curl_setopt($ch, CURLINFO_HEADER_OUT, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $request_qiwi);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  $result = curl_exec($ch);
  
  // разбираем ответ
  $xml = new SimpleXMLElement($result);
  $bl = 'bills-list';
  $retval = (int) $xml->$bl->bill[0]['status'];
  $amount = floatval($xml->$bl->bill[0]['sum']);
  $points = $amount/0.2;
  
  if($unique_key && $points >= 1) {
   if($retval == 60) {
    if($db->query("INSERT INTO `$dbName`.`pay_points_unique` (`id`, `unique_key`) VALUES (NULL, '$unique_key');")) {
     $db->query("UPDATE `$dbName`.`logs_pay_points` SET  `status` =  '1' WHERE  `logs_pay_points`.`unique_key` = '$unique_key';");
     $db->query("UPDATE `$dbName`.`users` SET  `upoints` = upoints + $points WHERE  `users`.`uid` = '$user_id';");
     $logs->pay_qiwi($user_id, $points);
     
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

$qiwi = new qiwi;
?>