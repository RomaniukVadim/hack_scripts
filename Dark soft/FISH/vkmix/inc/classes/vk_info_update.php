<?php
class vk_info_update {
 public function update() {
  global $db, $vk;
  
  $vk_id_list = array();
  $limit = time() - (15 * 60);

  $q = $db->query("SELECT `uvk_id` FROM `users` WHERE `uvk_id` > 0 AND `vk_time_update` < $limit ORDER BY `uid` ASC LIMIT 400");
  while($d = $db->assoc($q)) {
   $vk_id_list[] = $d['uvk_id'];
  }
  
  $users = implode(',', $vk_id_list);
  $users_count = count($vk_id_list);
  
  if($users_count) {
   $users_get = json_decode($vk->_post('https://api.vk.com/method/users.get?uids='.$users.'&fields=photo_50,gender,sex,city,year&lang=ru&access_token='.$token), true);
   echo '<pre>';
   print_r($users_get);
  }
 }
}

$vk_info_update = new vk_info_update;
?>