<?php
class backups {
 public function redis_categories() {
  global $db, $redis;
  
  // открываем все категории, кроме удаленных
  $q = $db->query("SELECT `tcid`, `tcuid`, `tcname` FROM `tasks_categories` WHERE `tcdel` = '0' ORDER BY `tcid` ASC");
  /*while($d = $db->fetch($q)) {
   echo '<pre>';
   $id = $d['tcid'];
   $uid = $d['tcuid'];
   $name = $d['tcname'];
   
   $redis->hset('tasks_categories_id'.$id, 'id', $id);
   $redis->hset('tasks_categories_id'.$id, 'name', $name);
   $redis->hset('tasks_categories_id'.$id, 'user_id', $uid);
    
   $redis->hmset('tasks_categories'.$uid.':'.$id, array('id' => $id, 'name' => $name, 'user_id' => $uid));
   $redis->lPush('mylist:tasks_categories'.$uid, $id);
  }*/
  
  return json_encode(array('response' => 1));
 }
}

$backups = new backups;
?>