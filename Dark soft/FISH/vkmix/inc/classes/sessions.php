<?php
class session {
 public function add($key = null, $value = null, $hash = null, $limit = null) {
  /*
   Создание сессии
   Пример вызова: $session->add('key', 'value', 'hash', limit);
  */
  global $db, $dbName, $time, $user_id, $redis;
  
  $time_limit = $limit ? $limit * 60 : 900;
  
  if($hash) {
   if($redis->hset($key.''.$hash, 'value', $value) && $redis->hset($key.''.$hash, 'hash', $hash)) {
    $redis->expire($key.''.$hash, $time_limit);
    return $value;
   }  
  } else {
   if($redis->hset($key.''.$user_id, 'value', $value) && $redis->hset($key.''.$user_id, 'hash', $hash)) {
    $redis->expire($key.''.$user_id, $time_limit);
    return $value;
   }
  }
  
  /*if($db->query("INSERT INTO `$dbName`.`users_sessions` (`id`, `uid`, `key`, `value`, `hash`, `time`, `limit`) VALUES (NULL, '$user_id', '".$db->escape($key)."', '".$db->escape($value)."', '".$db->escape($hash)."', '$time', '".$db->escape($limit)."');")) {
   return $value;
  }*/
 }
 
 public function get($key = null, $hash = null) {
  /*
   Получение значения
   Пример вызова: $session->get('key', 'hash');
  */
  global $db, $user_id, $redis;
  
  if($hash) {
   return $redis->hget($key.''.$hash, 'value');
  } else {
   return $redis->hget($key.''.$user_id, 'value');
  }
  
  /*
  if(!$hash) {
   $q = $db->query("SELECT `value`, `time`, `limit` FROM `users_sessions` WHERE `key` = '".$db->escape($key)."' AND `uid` = '$user_id' ORDER BY `id` DESC");
  } else {
   $q = $db->query("SELECT `value`, `time`, `limit` FROM `users_sessions` WHERE `key` = '".$db->escape($key)."' AND `hash` = '".$db->escape($hash)."' ORDER BY `id` DESC");
  }
  $d = $db->fetch($q);
  if($d['limit']) {
   if($d['time'] < time() - ($d['limit'] * 60)) {
    return '';
   } else return $d['value'];
  } else return $d['value'];*/
 }
 
 public function delete($key = null, $hash = null) {
  /*
   Удаление сессии
   Пример вызова: $session->delete('key', 'hash');
  */
  global $db, $dbName, $user_id, $redis;
  
  if($hash) {
   $redis->hdel($key.''.$hash, 'value'); 
   $redis->hdel($key.''.$hash, 'hash');
  } else {
   $redis->hdel($key.''.$user_id, 'value'); 
   $redis->hdel($key.''.$user_id, 'hash');
  }
  
  /*
  if(!$user_id) {
   $q = "DELETE FROM `$dbName`.`users_sessions` WHERE `users_sessions`.`key` = '".$db->escape($key)."' AND `hash` = '".$db->escape($hash)."' LIMIT 1;";
  } else {
   $q = "DELETE FROM `$dbName`.`users_sessions` WHERE `users_sessions`.`key` = '".$db->escape($key)."' AND `uid` = '$user_id' AND `hash` = '".$db->escape($hash)."' LIMIT 1;";
  }
  
  if($db->query($q)) {
   return 1;
  }*/
 }
}

$session = new session;
?>