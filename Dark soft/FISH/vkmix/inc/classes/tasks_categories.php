<?php
class tasks_categories {
 public function replace($text = null) {
  return str_replace(array('\"', "\'"), array('"', "'"), $text);
 }
 
 public function my_num() {
  global $db, $user_id;
  
  $q = $db->query("SELECT `tcid` FROM `tasks_categories` WHERE `tcuid` = '$user_id' AND `tcdel` = '0'");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function my_tasks($params = null) {
  global $db, $user_id, $redis, $querys;
  
  $uid = (int) abs($params['uid']);
  $active_id = (int) abs($params['active_id']);
  
  // выводим данные из кэша
  $categories_ids = $redis->lRange('mylist:tasks_categories'.$user_id, 0, -1);
  $categories_list = array();
  
  foreach ($categories_ids as $categories_key => $categories_value) {
   $categories_list[] = $redis->hgetall('tasks_categories'.$user_id.':'.$categories_value);
  }
 
  sort($categories_list);
  // формируем категории
  for($i = 0; $i < count($categories_list); $i++) {
   $id = $categories_list[$i]['id'];
   $name = fxss(tasks_categories::replace($categories_list[$i]['name']));
   $active = $active_id == $id ? ' class="active"' : '';
   
   $template .= '
          <a id="categorie_id'.$id.'"'.$active.' href="'.querys('/tasks/my', 'list').''.$id.'" onclick="nav.go(this); return false"><div class="blue_tab_a">'.$name.'</div></a>';
  }
  return $template;
  
  /*
  $q = $db->query("SELECT `tcid`, `tcuid`, `tcname` FROM `tasks_categories` WHERE `tcuid` = '$uid' AND `tcdel` = '0' ORDER BY `tcid` ASC");
  while($d = $db->fetch($q)) {
   $id = $d['tcid'];
   $user_id = $d['tcuid'];
   $name = fxss(tasks_categories::replace($d['tcname'], 0));
   $active = $active_id == $id ? ' class="active"' : '';
   
   $template .= '
          <a id="categorie_id'.$id.'"'.$active.' href="'.querys('/tasks/my', 'list').''.$id.'" onclick="nav.go(this); return false"><div class="blue_tab_a">'.$name.'</div></a>';
  }
  return $template;
  */
 }

 public function my_select($params = null) {
  global $db, $redis;
  
  $uid = (int) abs($params['uid']);
  
  // выводим данные из кэша
  $categories_ids = $redis->lRange('mylist:tasks_categories'.$uid, 0, -1);
  $categories_list = array();
 
  foreach ($categories_ids as $categories_key => $categories_value) {
   $categories_list[] = $redis->hgetall('tasks_categories'.$uid.':'.$categories_value);
  }
 
  sort($categories_list);
  // формируем категории
  for($i = 0; $i < count($categories_list); $i++) {
   $id = $categories_list[$i]['id'];
   $name = fxss(json_encode($categories_list[$i]['name']));
   
   $template .= ',["'.$id.'", '.$name.']';
  }
  return $template;

  /*
  $q = $db->query("SELECT `tcid`, `tcname` FROM `tasks_categories` WHERE `tcuid` = '$uid' AND `tcdel` = '0' ORDER BY `tcid` ASC");
  while($d = $db->fetch($q)) {
   $id = $d['tcid'];
   $name = fxss(tasks_categories::replace($d['tcname'], 1));
   
   $template .= ', ["'.$id.'", "'.$name.'"]';
  }
  
  return $template;
  */
 }
 
 public function add($params = null) {
  global $db, $dbName, $time, $user_logged, $session, $redis;
  
  $uid = (int) abs($params['uid']);
  $name = $db->escape(trim($params['name']));
  $ssid = (int) abs($params['ssid']);
  
  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  if(mb_strlen($name, 'UTF-8') < 1) {
   $json = array('error_text' => 'Название слишком короткое.');
  } elseif(mb_strlen($name, 'UTF-8') > 60) {
   $json = array('error_text' => 'Название слишком длинное.');
  } elseif(tasks_categories::my_num() >= 20) {
   $json = array('error_text' => 'Слишком много категорий.');
  } elseif($session->get('usession') != $ssid) {
   $json = array('error_text' => 'Истек период сессии.');
  } else {
   if($db->query("INSERT INTO `$dbName`.`tasks_categories` (`tcid`, `tcuid`, `tcname`, `tctime`, `tcdel`) VALUES (NULL, '$uid', '$name', '$time', '0');")) {
    $last_cat_id = $db->insert_id();
    
    $redis->hset('tasks_categories_id'.$last_cat_id, 'id', $last_cat_id);
    $redis->hset('tasks_categories_id'.$last_cat_id, 'name', $name);
    $redis->hset('tasks_categories_id'.$last_cat_id, 'user_id', $uid);
    
    $redis->hmset('tasks_categories'.$uid.':'.$last_cat_id, array('id' => $last_cat_id, 'name' => $name, 'user_id' => $uid));
    $redis->lPush('mylist:tasks_categories'.$uid, $last_cat_id);
    
    $json = array('success' => 1, 'cid' => $last_cat_id);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  }
  return jdecoder(json_encode($json));
 }
 
 public function delete($params) {
  global $db, $dbName, $time, $user_logged, $user_id, $session, $redis;
  
  $id = (int) abs($params['id']);
  $uid = (int) abs($params['uid']);
  $ssid = (int) abs($params['ssid']);

  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }
  
  $qCat = $db->query("SELECT `tcid` FROM `tasks_categories` WHERE `tcuid` = '$uid' AND `tcid` = '$id' AND `tcdel` = '0'");
  $dCat = $db->fetch($qCat);
  
  if($dCat['tcid']) {
   if($db->query("UPDATE `$dbName`.`tasks_categories` SET `tcdel` =  '1' WHERE  `tasks_categories`.`tcid` = '$id' LIMIT 1 ;")) {
    
    $redis->del('tasks_categories'.$user_id.':'.$id);
    $redis->lrem('mylist:tasks_categories'.$user_id, $id);
    $redis->hdel('tasks_categories_id'.$id, 'id');
    $redis->hdel('tasks_categories_id'.$id, 'name');
    $redis->hdel('tasks_categories_id'.$id, 'user_id');
    
    $json = array('success' => 1);
   } else {
    $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.');
   }
  } else {
   $json = array('error_text' => 'Ошибка доступа.');
  }
  
  return jdecoder(json_encode($json));
 }
}

$tasks_categories = new tasks_categories;
?>