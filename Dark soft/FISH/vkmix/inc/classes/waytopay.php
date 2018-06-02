<?php
class waytopay {
	public function insert_table($points = null, $unique_key = null) {
  global $db, $dbName, $time, $user_id;
  
  if($db->query("INSERT INTO `$dbName`.`logs_pay_points` (`id`, `uid`, `type`, `points`, `time`, `unique_key`, `status`) VALUES (NULL, '$user_id', '5', '$points', '$time', '$unique_key', '0');")) {
   return 1;
  } else {
   return 0;
  }
 }
 
 
}
$waytopay = new waytopay;
?>