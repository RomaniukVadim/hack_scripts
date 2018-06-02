<?php
$root = $_SERVER['DOCUMENT_ROOT'];

require($root.'/inc/classes/db.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/vk.api.php');
require($root.'/inc/classes/complaints.php');

//echo date('d.m.Y H:i:s', 1380797097);

error_reporting(E_ALL);
ini_set('display_errors', 1);


    $comps = $db->query("SELECT * 
FROM  `complaints` 
WHERE TIME >1424131200
LIMIT 0 , 100");

    $query = '';

    $count = 0;
    while($comp = $db->fetch($comps)) {
     $query .= $comp['to'].',';
     $count++;
    }

    echo $query;
    echo '<br>'.$count;

/*
// подписаться
$calls = array();
$users_result = array();
for($i = 0; $i < 16; $i++) {
 $calls[] = 'API.users.getFollowers({"user_id": 189122992, "count": 1000, "offset": '.($i * 1000).'})';
}
$code = "return [" . @implode(',', $calls) . "];";
$api = json_decode(@vk::_post(complaints::capi($code)));
// друзья
$calls_friends = array();
$users_friends_result = array();
$calls_friends = 'API.friends.get({"user_id": 189122992})';
$code_friends = "return [".$calls_friends."];";
$api_friends = json_decode(@vk::_post(complaints::capi($code_friends)));

if($api->execute_errors || $api_friends->execute_errors || $api->error || $api_friends->error) { // ошибка сервера ВК
 $vk_error = 1;
} else {
 foreach($api->response as $response_key => $response_value) {
  $users = $api->response[$response_key]->items;
  foreach($users as $key => $value) {
   $users_result[] = $value;
  }
 }
 $users_friends_result = $api_friends->response[0];
 $users_result = array_merge($users_result, $users_friends_result);
}

echo '<pre>';
print_r($users_friends_result);
*/
?>