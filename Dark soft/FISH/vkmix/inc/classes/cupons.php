<?php

class cupons {
 public function getCatNum2($cat_name = null) { // определяем id отдельной категории по системному имени
  // название категорий
  $catsName = array('active', 'active', 'add','add', 'my' ,'my');

  // id категорий
  $catsId = array(
   0 => '',
   1 => array(0 => $catsName[0], 1 => $catsName[1]),
   2 => array(2 => $catsName[2], 3 => $catsName[3]),
   3 => array(4 => $catsName[4], 5 => $catsName[5])
  );
  
  for($i = 0; $i < count($catsId); $i++) {
   if(@in_array($cat_name, $catsId[$i])) {
    // выводим id категории
    return $i;
   }
  }
 }
 
   public function rand_str($length, $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890') { // генерируем случайные символы
 $chars_length = (strlen($chars) - 1);
 $string = $chars{rand(0, $chars_length)};
 for ($i = 1; $i < $length; $i = strlen($string)) {
  $r = $chars{rand(0, $chars_length)};
  if ($r != $string{$i - 1}) $string .= $r;
 }
 return $string;
}
 public function cupons_user_num() {
  global $db, $user_id;
  
  $q = $db->query("SELECT `id` FROM `cupons` WHERE `uid` = '$user_id'");
  $n = $db->num($q);
  
  return $n;
 }
  public function coupons_table_user() { // строим таблицу купонов пользователя
  global $db, $user_id;
  
  $page = (int) $_GET['page'];
  $start_page = (!$page) ? 0 : $page - 1;
  $start_limit = $start_page * 20;
  
  $q = $db->query("SELECT `cupon`, `active`,`points` FROM `cupons` WHERE `uid` = '$user_id' ORDER BY `id` DESC LIMIT $start_limit, 20");
  while($d = $db->fetch($q)) {
   $active = $d['active'];
   $cupon = $d['cupon'];
   $points = $d['points'];

   
   
   if($active == 0) {
    $active_type = 'Еще не активирован';
  } else {
	  
	   $query_g = $db->query("SELECT `uname` ,`ulast_name`, `uvk_id` FROM `users` WHERE `uid` = '$active'");
   $data_g = mysqli_fetch_array($query_g);
   $data_name = $data_g['uname'].' '.$data_g['ulast_name'];
   if ($data_name==' ') $data_name='Безымянный';
    $data_vkid= $data_g['uvk_id']; 
	if ($data_vkid==0) $url='href="javascript://"'; else $url='href="http://vk.com/id'.$data_vkid.'"';
   
	 $active_type = '<a '.$url.'>'.$data_name.'</a>'; 
  }
   
   $template .= '
          <tr>
           <td class="column_content column_content_url_user">'.$cupon.'</td>
           <td class="column_content column_content_status_user"><b>'.$active_type.'</b></td>
		    <td class="column_content column_content_status_user"><b>'.$points.'</b></td>
          </tr>
    ';
  }
  return $template;
 }
   public function actv_cup($params = null) {
  global $db, $dbName, $time, $ip_address, $browser, $user_logged, $vk, $logs, $session,  $token, $ugroup;
  
  $uid = (int) abs($params['uid']);
  $code = mysql_escape_string($params['code']);
  $ssid = (int) abs($params['ssid']);
  $error = '';
  $title = '';
  $img_url = '';
  $black_list = '';
  
 // $time_new = substr_replace(time(), '0', -1);
  //$unique_key = 'add_'.$user_id.''.$url.''.$count.''.$time_new;
 // $unique_del = 'add_del'.$user_id.'_'.rand().'_'.$time;
  

  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }

 
  // обрабатываем информацию для отправки запроса
 if($session->get('usession') != $ssid) {
   $json = array('error_text' => 'Истек период сессии. Обновите страницу или попробуйте позже.');
  } elseif($error == 'unknown') {
   $json = array('error_text' => 'Неизвестная ошибка.');
  } else {

   $query_g = $db->query("SELECT `id`, `points` FROM `cupons` WHERE `cupon` = '$code' AND `active` = '0'");
   $data_g = mysqli_fetch_array($query_g);
   $data_id = $data_g['id'];
   $data_points = $data_g['points'];
   if($data_id){
	   $db->query("UPDATE `$dbName`.`users` SET  `upoints` =  `upoints` + '$data_points' WHERE  `users`.`uid` = '$uid';"); // Зачисляем баллы
	    $db->query("UPDATE `$dbName`.`cupons` SET  `active` =  '$uid' WHERE  `cupons`.`id` = '$data_id';"); // вырубаем купон баллы
    $logs->actv_cup($uid, '', $data_points); // записываем в лог
	   
   
   
 
    $json = array('success' => 1, 'points' => $data_points);
   } else {
    
$json = array('error_text' => 'Купон не найден, или уже активирован');
  }}
  //$json = array('success' => 1, 'points' => $points );
 //$json = array('error_text' => 'Действие выполняется слишком часто. Попробуйте позже.');
  return json_encode($json);
 }
  public function add_cup($params = null) {
  global $db, $dbName, $time, $ip_address, $browser, $user_logged, $vk, $logs, $session,  $token, $ugroup;
  
  $uid = (int) abs($params['uid']);
  $points = (int) $params['upoints'];
  $cupoints = (int) abs($params['points']);
  $ssid = (int) abs($params['ssid']);
  $coupon_name= $params['coupon_name'];
  $error = '';
  $title = '';
  $img_url = '';
  $black_list = '';
  
 // $time_new = substr_replace(time(), '0', -1);
  //$unique_key = 'add_'.$user_id.''.$url.''.$count.''.$time_new;
 // $unique_del = 'add_del'.$user_id.'_'.rand().'_'.$time;
  

  if(!$user_logged) {
   return json_encode(array('error_text' => 'login'));
   exit;
  }

$result_points_comission=$cupoints + round(($cupoints / 100) * 10);
 
  // обрабатываем информацию для отправки запроса
  if($result_points_comission > $points) {
   $json = array('error_text' => 'Недостаточно баллов для создания купона');
  } elseif($session->get('usession') != $ssid) {
   $json = array('error_text' => 'Истек период сессии. Обновите страницу или попробуйте позже.');
  } elseif($error == 'unknown') {
   $json = array('error_text' => 'Неизвестная ошибка.');
  }elseif($result_points_comission==0) {
   $json =array('error_text' => 'Пустой купон создать нельзя!');
  } else {

   
   if($db->query("INSERT INTO `$dbName`.`cupons` (`id`,`uid` , `cupon`, `points`, `active`) VALUES (NULL, $uid ,'$coupon_name', '$cupoints', '0')")) {
    $dTask_last_id = $db->insert_id();
    $db->query("UPDATE `$dbName`.`users` SET  `upoints` =  `upoints` - '$result_points_comission' WHERE  `users`.`uid` = '$uid';"); // списываем баллы
    $logs->add_cup($uid, $dTask_last_id, $result_points_comission); // записываем в лог
    

    $json = array('success' => 1, 'points' => $result_points_comission);
   } else {
    $db_er = $db->error();
    if(preg_match('/Duplic/i', $db_er)) {
     $json = array('error_text' => 'Действие выполняется слишком часто. Попробуйте позже.');
    } else {
     $json = array('error_text' => 'Ошибка соединения с базой данных. Попробуйте позже.'.$db_er);
    }
   } 

  }
  //$json = array('success' => 1, 'points' => $points );
 //$json = array('error_text' => 'Действие выполняется слишком часто. Попробуйте позже.');
  return json_encode($json);
 }
}
$cupons = new cupons;
?>
