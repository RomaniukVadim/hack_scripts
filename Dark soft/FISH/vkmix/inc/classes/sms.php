<?php
class sms {
 public function insert_table($points = null, $unique_key = null) {
  global $db, $dbName, $time, $user_id;
  
  if($db->query("INSERT INTO `$dbName`.`logs_pay_points` (`id`, `uid`, `type`, `points`, `time`, `unique_key`, `status`) VALUES (NULL, '$user_id', '2', '$points', '$time', '$unique_key', '0');")) {
   return 1;
  } else {
   return 0;
  }
 }
 
 public function send_post($url = null, $post = null) {
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.4 (KHTML, like Gecko) Chrome/22.0.1229.94 Safari/537.4');
		if($post) {
   curl_setopt($ch, CURLOPT_POST, 1);
   curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  }
  $response = curl_exec($ch);
  curl_close($ch);
  return $response;
 }
 
 public function get_sms_text($purse, $order_id, $amount, $country, $operator) {
  $description = 'Покупка баллов MontyTool';
  $sign = md5("$purse::$order_id::$amount::0::$description::modal456");
  $sms_get = sms::send_post('http://bank.smscoin.com/bank/', 's_country='.$country.'&s_provider='.$operator.'&s_purse='.$purse.'&s_order_id='.$order_id.'&s_amount='.$amount.'&s_clear_amount=0&s_description='.$description.'&s_sign='.$sign);
  $sms_get_text = preg_match('/<span class="sms_msg">(.*?)<\/span>/is', $sms_get, $sms_get_text_r);
  $sms_get_num = preg_match('/<span class="sms_num">(.*?)<\/span>/is', $sms_get, $sms_get_num_r);
  $sms_get_price = preg_match('/<span class="sms_price">(.*?)<\/span>/is', $sms_get, $sms_get_price_r);
  $sms_get_price = preg_match('/<span class="sms_price">(.*?)<\/span>/is', $sms_get, $sms_get_price_r);
  
  preg_match('/<input name="s_invoice" type="hidden" value="(.*?)" \/>/is', $sms_get, $sms_get_invoice);
  preg_match('/<input name="s_value" type="hidden" value="(.*?)" \/>/is', $sms_get, $sms_get_value);
  preg_match_all('/<input name="s_sign" type="hidden" value="(.*?)" \/>/is', $sms_get, $sms_get_sign);
  
  $sms_get_price_result = preg_replace('/<br \/>.*/', '', $sms_get_price_r[1]);
  if($sms_get_price_r[1]) {
   $json = array('success' => 1, 'text' => $sms_get_text_r[1], 'num' => $sms_get_num_r[1], 'price' => str_replace(array('рубль', 'UAH', ' (без НДС)', ' (включая НДС)'), array('рублей', 'гривен', '', ''), $sms_get_price_result), 's_invoice' => $sms_get_invoice[1], 's_value' => $sms_get_value[1], 's_sign' => $sms_get_sign[1][2]);
  } else {
   $json = array('error_text' => 'Ошибка соединения с платежным сервисом.');
  }
  
  return json_encode($json);
 }
 
 public function sms_info() {
  global $db, $dbName, $time, $user_id;
  
  $points = (int) $_GET['points'];
  $country = $db->escape($_GET['country']);
  $operator = $db->escape($_GET['operator']);
  
  if($points == 15) {
   $amount = 0.1;
  } elseif($points == 150) {
   $amount = 1;
  } elseif($points == 300) {
   $amount = 2;
  }
  
  if(!$amount) {
   return json_encode(array('error_text' => 'Неверное количество баллов.'));
  } else {
   $unique_key = $time + $user_id + rand(0, 5);
   $table_insert = sms::insert_table($points, $unique_key);
   if($table_insert) {
    return sms::get_sms_text(17682, $unique_key, $amount, $country, $operator);
   } else {
    return json_encode(array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.'));
   }
  }
 }
 
 public function sms_check() {
  global $db, $dbName, $user_id, $logs;
  
  $s_invoice = $_GET['s_invoice'];
  $s_sign = $_GET['s_sign'];
  
  $get_last_unique = $db->query("SELECT `points`, `unique_key` FROM `logs_pay_points` WHERE `uid` = '$user_id' AND `status` = '0' AND `type` = '2' ORDER BY `id` DESC LIMIT 1");
  $data_last_unique = $db->fetch($get_last_unique);
  
  $check_sms = sms::send_post('http://bank.smscoin.com/bank/next/?s_invoice='.$s_invoice.'&s_operator=&s_value=&s_sign='.$s_sign);
  $check_sms_status = preg_match('/было доставлено и успешно обработано/is', $check_sms) ? 1 : 0;
  $unique_key = $data_last_unique['unique_key'];
  
  if($check_sms_status == 1) {
   preg_match('/<input name="s_amount" type="hidden" value="(.*?)" \/>/is', $check_sms, $amount_match);
  }
  
  $amount = $amount_match[1];
  
  if($amount == 0.1 || $amount == 0.10) {
   $points = 15;
  } elseif($amount == 1 || $amount == 1.00) {
   $points = 150;
  } elseif($amount == 2 || $amount == 2.00) {
   $points = 300;
  } else {
   $points = 0;
  }
  
  if($points) {
   if($db->query("INSERT INTO `$dbName`.`pay_points_unique` (`id`, `unique_key`) VALUES (NULL, '$s_invoice');")) {
    $db->query("UPDATE `$dbName`.`logs_pay_points` SET  `status` =  '1' WHERE  `logs_pay_points`.`unique_key` = '$unique_key';");
    $db->query("UPDATE `$dbName`.`users` SET  `upoints` = upoints + $points WHERE  `users`.`uid` = '$user_id';");
    $logs->pay_sms($user_id, $points);
    
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
    $json = array('error_text' => 'Ошибка');
   }
  } else {
   $json = array('error_text' => 'error');
  }
  
  return json_encode($json);
 }
}

$sms = new sms;
?>