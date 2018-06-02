<?php
class refs {
 public function notmy_num($id_user = null) {
  global $db, $user_id;
  
  $q = $db->query("SELECT `id` FROM `ref` WHERE `to` = '$id_user'");
  $n = $db->num($q);
  
  return $n;
 }
  public function my_num() {
  global $db, $user_id;
  
  $q = $db->query("SELECT `id` FROM `ref` WHERE `to` = '$user_id'");
  $n = $db->num($q);
  
  return $n;
 }
 
 public function my() {
  global $db, $user_id, $noavatar;
  
  $page = (int) abs($_GET['page']) * 10;
  
  $q = $db->query("
   SELECT ref.points, ref.time, users.uid, users.uname, users.ulast_name, users.uavatar, users.uvk_id FROM `ref`
    INNER JOIN `users` ON ref.from = users.uid
   WHERE ref.to = '$user_id'
   ORDER BY `id` DESC
   LIMIT $page, 10
  ");
  $i = 0;
  while($d = $db->fetch($q)) {
   $uname = $d['uname'];
   $ulast_name = $d['ulast_name'];
   $ufull_name = $uname ? $uname.' '.$ulast_name : 'Безымянный';
   $uavatar = $d['uavatar'];
   $ufull_avatar = $uavatar ? $uavatar : $noavatar;
   $uvk_id = $d['uvk_id'];
   $uresult_url = $uvk_id ? '<a href="http://vk.com/id'.$uvk_id.'" target="_blank">' : '<a href="javascript://">';
   $ref_points = $d['points'];
   $ref_time = $d['time'];
   $rclass = $i % 2 ? ' active' : '';
   
   $template .= '
           <div class="settings_ref_user'.$rclass.'">
            <div class="settings_ref_user_avatar">'.$uresult_url.'<img src="'.$ufull_avatar.'"></a></div>
            <div class="settings_ref_user_name">
             '.$uresult_url.''.$ufull_name.'</a>
             <div class="settings_ref_user_date">'.new_time($ref_time).'</div>
            </div>
            <div class="settings_ref_user_points">+'.$ref_points.' '.declOfNum($ref_points, array('балл', 'балла', 'баллов')).'</div>
           </div>
   ';
   $i++;
  }
  return $template;
 }
  public function notmy($id_user = null) {
  global $db, $user_id, $noavatar;
  
  $page = (int) abs($_GET['page']) * 10;
  
  $q = $db->query("
   SELECT ref.points,users.uip_address, ref.time, users.uid, users.uname, users.ulast_name, users.uavatar, users.uvk_id FROM `ref`
    INNER JOIN `users` ON ref.from = users.uid
   WHERE ref.to = '$id_user'
   ORDER BY `uip_address` DESC
  ");
  $i = 0;
  while($d = $db->fetch($q)) {
   $uname = $d['uname'];
   $ulast_name = $d['ulast_name'];
   $ufull_name = $uname ? $uname.' '.$ulast_name : 'Безымянный';
   $uavatar = $d['uavatar'];
   $ufull_avatar = $uavatar ? $uavatar : $noavatar;
   $uvk_id = $d['uvk_id'];
   $uresult_url = $uvk_id ? '<a href="http://vk.com/id'.$uvk_id.'" target="_blank">' : '<a href="javascript://">';
   $ref_points = $d['points'];
   $ref_time = $d['time'];
   $uip_address=$d['uip_address'];
   $rclass = $i % 2 ? ' active' : '';
   
   $template .= '
           <div class="settings_ref_user'.$rclass.'">
            <div class="settings_ref_user_avatar">'.$uresult_url.'<img src="'.$ufull_avatar.'"></a></div>
            <div class="settings_ref_user_name">
             '.$uresult_url.''.$ufull_name.'</a>
             <div class="settings_ref_user_date">'.new_time($ref_time).'</div>
            </div>
            <div class="settings_ref_user_points">IP: '.$uip_address.' </div>
			 
           </div>
   ';
   $i++;
  }
  return $template;
 }
}

$refs = new refs;
?>