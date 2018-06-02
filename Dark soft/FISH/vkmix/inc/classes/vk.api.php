<?php
class vk {
 public function url($url = null) {
  /*
   Проверяем URL на правильность
   Пример вызова: $vk->url('vk.com/id1');
  */
  if(!preg_match('/^https?:\/\/vk.com/', $url) && !preg_match('/^https?:\/\/vkontakte.ru/', $url) && !preg_match('/^vkontakte.ru/', $url) && !preg_match('/^vk.com/', $url)) {
   $response = 0;
  } else {
   $response = 1;
  }
  return $response;
 }
 
 public function explode($url = null) {
  /*
   Получаем подробную часть о ссылке
   Пример вызова: print_r(json_decode($vk->explode('vk.com/wall1_1')));
  */
  $explode = explode('/', $url);
  $explode_end = $explode[count($explode) - 1];
  
  if(!$url) {
   return '';
   exit;
  }
  
  if(preg_match('/photo-?([0-9]+)_([0-9]+)/', $explode_end, $matches)) {
   $response = 1;
   $full_url_explode = explode('_', str_replace('photo', '', $matches[0]));
   $user_id = $full_url_explode[0];
   $photo_id = $matches[2];
   $full_id = $user_id.'_'.$photo_id;
   $json = array('response' => $response, 'url' => $full_id, 'user_id' => $user_id, 'photo_id' => $photo_id, 'type' => 'photo');
  } elseif(preg_match('/video-?([0-9]+)_([0-9]+)/', $explode_end, $matches)) {
   $response = 1;
   $full_url_explode = explode('_', str_replace('video', '', $matches[0]));
   $user_id = $full_url_explode[0];
   $video_id = $matches[2];
   $full_id = $user_id.'_'.$video_id;
   $json = array('response' => $response, 'url' => $full_id, 'user_id' => $user_id, 'video_id' => $video_id, 'type' => 'video');
  } elseif(preg_match('/topic-?([0-9]+)_([0-9]+)/', $explode_end, $matches)) {
   $response = 1;
   $full_url_explode = explode('_', str_replace('topic', '', $matches[0]));
   $group_id = $full_url_explode[0];
   $topic_id = $matches[2];
   $full_id = $group_id.'_'.$topic_id;
   $json = array('response' => $response, 'url' => $full_id, 'group_id' => $group_id, 'topic_id' => $topic_id, 'type' => 'topic');
  } elseif(preg_match('/wall-?([0-9]+)_([0-9]+)\?reply=([0-9]+)/', $explode_end, $matches)) {
   $response = 1;
   $full_url_explode = explode('_', str_replace('wall', '', $matches[0]));
   $user_id = $full_url_explode[0];
   $wall_id = $matches[2];
   $reply_id = $matches[3];
   $full_id = $user_id.'_'.$wall_id;
   $json = array('response' => $response, 'url' => $full_id, 'user_id' => $user_id, 'wall_id' => $wall_id, 'reply' => $reply_id, 'type' => 'wall_comment');
  } elseif(preg_match('/wall-?([0-9]+)_([0-9]+)/', $explode_end, $matches)) {
   $response = 1;
   $full_url_explode = explode('_', str_replace('wall', '', $matches[0]));
   $user_id = $full_url_explode[0];
   $wall_id = $matches[2];
   $full_id = $user_id.'_'.$wall_id;
   $json = array('response' => $response, 'url' => $full_id, 'user_id' => $user_id, 'wall_id' => $wall_id, 'type' => 'wall');
  } elseif(preg_match('/id([0-9]+)/', $explode_end, $matches)) {
   // определяем id пользователя
   $response = 1;
   $user_id = $matches[1];
   $json = array('response' => $response, 'url' => $user_id, 'type' => 'user');
  } elseif(preg_match('/public([0-9]+)/', $explode_end, $matches) || preg_match('/club([0-9]+)/', $explode_end, $matches)) {
   // определяем id группы
   $response = 1;
   $group_id = $matches[1];
   $json = array('response' => $response, 'url' => $group_id, 'type' => 'group');
  } else {
   // ничего не определилось
   $error = 1;
   $json = array('error' => $error, 'url' => $explode_end, 'type' => 'unknown');
  }
  
  return json_encode($json);
 }
 
 public function error($code = null) {
  /*
   Обрабатываем информацию об ошибках API ВКонтакте
   Пример вызова: echo $vk->error(100);
  */
  if($code == 100) {
   // неверно переданы параметры
   $error = 'parameters';
  } elseif($code == 113) {
   // неверный id пользователя
   $error = 'uid';
  } elseif($code == 125) {
   // неверный id группы
   $error = 'gid';
  } elseif($code == 5) {
   // неверный token 
   $error = 'authorization failed';
  } elseif($code == 2) {
   // если приложение выключено
   $error = 'application is disabled';
  } elseif($code == 6) {
   // много запросов с одного IP или токена
   $error = 'too many requests';
  } elseif($code == 7) {
   // нет прав для просмотра
   $error = 'permission to perform';
  } elseif($code == 200) {
   // нет прав для просмотра
   $error = 'access denied';
  }
  
  return $error;
 }
 
 public function user_info($id = null, $token = null) {
  /*
   Получаем информацию о пользователе
   Пример вызова: print_r(json_decode($vk->user_info(1)));
  */
  $api = json_decode(vk::_post('https://api.vk.com/method/users.get?user_ids='.$id.'&fields=photo_50,city,sex,bdate&lang=ru&access_token='.$token), true);
  $api_error_code = $api['error']['error_code'];
  if($api_error_code) {
   // в случае, если возникла ошибка
   $json = array(
     'error' => vk::error($api_error_code)
   );
  } elseif($api[0]['response'] == 0) {
   // в случае успеха
   $uid = $api['response'][0]['uid']; // id
   $first_name = $api['response'][0]['first_name']; // имя
   $last_name = $api['response'][0]['last_name']; // фамилия
   $avatar = $api['response'][0]['photo_50']; // аватар 50х50
   $deactivated = $api['response'][0]['deactivated']; // метка о существовании профиля
   $city = $api['response'][0]['city'];
   $gender = $api['response'][0]['sex'];
   $age = $api['response'][0]['age'];
   $bdate = explode('.', $api['response'][0]['bdate']);
   $year = $bdate[2];
   
   if($deactivated == 'deleted') {
    // если пользователя не существует
    $json = array(
     'error' => 'deleted',
     'error_text' => 'user is deleted'
    );
   } elseif($deactivated == 'banned') {
    // если пользователь заблокирован
    $json = array(
     'error' => 'banned',
     'error_text' => 'user is banned'
    );
   } else {
    $json = array(
     'response' => 1,
     'id' => $uid,
     'first_name' => $first_name,
     'last_name' => $last_name,
     'avatar' => $avatar,
     'city' => $city,
     'gender' => $gender,
     'year' => $year
    );
   }
  } else {
   $json = array(
    'error' => 'unknown error'
   );
  }
  return json_encode($json);
 }
 
 public function group_info($id = null, $token = null) {
  /*
   Получаем информацию о группе
   Пример вызова: print_r(json_decode($vk->user_group(1)));
  */
  $api = json_decode(vk::_post('https://api.vk.com/method/groups.getById?gid='.$id.'&fields=description&access_token='.$token), true);
  $api_error_code = $api['error']['error_code'];
  if($api_error_code) {
   // в случае, если возникла ошибка
   $json = array(
     'error' => vk::error($api_error_code)
   );
  } elseif($api) {
   // в случае успеха
   $gid = $api['response'][0]['gid']; // id
   $name = $api['response'][0]['name']; // название группы
   $text = $api['response'][0]['description']; // название группы
   $avatar = $api['response'][0]['photo']; // аватар 50х50
   $closed = $api['response'][0]['is_closed']; // метка о закрытие группы
   
   if(!$gid) {
    $json = array(
     'error' => 'deleted',
     'error_text' => 'group is deleted'
    ); 
   } else {
    $json = array(
     'response' => 1,
     'id' => $gid,
     'name' => $name,
     'text' => $text,
     'closed' => $closed,
     'avatar' => $avatar
    );
   }
  } else {
   $json = array(
    'error' => 'unknown error'
   );
  }
  return json_encode($json);
 }
 
 public function wall_info($id = null, $token = null) {
  /*
   Получаем информацию о записи на стене
   Пример вызова: print_r(json_decode($vk->wall_info('1_1')));
  */
  $api = json_decode(vk::_post('https://api.vk.com/method/wall.getById?posts='.$id.'&access_token='.$token), true);
  $api_error_code = $api['error']['error_code'];
  $copy_owner_id = $api['response'][0]['copy_owner_id'];
  if($api_error_code) {
   // в случае, если возникла ошибка
   $json = array(
     'error' => vk::error($api_error_code)
   );
  } elseif($api) {
   // в случае успеха
   $wall_id = $api['response'][0]['id']; // id
   $from_id = $api['response'][0]['to_id']; // from id
   $text = $api['response'][0]['text']; // текст
   $attachments = $api['response'][0]['attachments'];

   for($i = 0; $i < count($attachments); $i++) {
    $poll_id = $attachments[$i]['poll']['poll_id'];
    if($poll_id) {
     $poll_id = $poll_id;
     break;
    }
   }
 
   if(!$wall_id) {
    // если запись не найдена
    $json = array(
     'error' => 'deleted',
     'error_text' => 'wall is deleted'
    );
   } else {
    if($_GET['answer_poll']) {
     $json = array(
      'response' => 1,
      'id' => $wall_id,
      'from' => $copy_owner_id ? $copy_owner_id : $from_id,
      'full_id' => $from_id.'_'.$wall_id,
      'text' => $text,
      'poll_id' => $poll_id
     );
    } else {
     $json = array(
      'response' => 1,
      'id' => $wall_id,
      'from' => $from_id,
      'full_id' => $from_id.'_'.$wall_id,
      'text' => $text,
      'poll_id' => $poll_id
     );
    }
   }
  } else {
   $json = array(
    'error' => 'unknown error'
   );
  }
  return json_encode($json);  
 }
 
 public function wall_poll_check($wall_url = null, $poll_id = null, $answer = null, $token = null) {
  $wall_url = explode('_', $wall_url);
  $api = json_decode(vk::_post('https://api.vk.com/method/polls.getById?owner_id='.$wall_url[0].'&poll_id='.$poll_id.'&access_token='.$token), true);
  
  $votes1 = $api['response']['answers'][0]['votes'];
  $votes2 = $api['response']['answers'][1]['votes'];
  
  if($votes1 > $votes2 && $answer == 1) {
   return 1;
  } elseif($votes1 < $votes2 && $answer == 2) {
   return 1;
  } elseif($votes1 == $votes2 && $answer == 3) {
   return 1;
  } else {
   return 0;
  }
 }
 
 public function photo_info($id = null, $token = null) {
  /*
   Получаем информацию о фотографии
   Пример вызова: print_r(json_decode($vk->photo_info('123_1')));
  */
  $api = json_decode(vk::_post('https://api.vk.com/method/photos.getById?photos='.$id.'&access_token='.$token), true);
  $api_error_code = $api['error']['error_code'];
  if($api_error_code) {
   // в случае, если возникла ошибка
   $json = array(
     'error' => vk::error($api_error_code)
   );
  } elseif($api) {
   // в случае успеха
   $photo_id = $api['response'][0]['pid']; // id
   $from_id = $api['response'][0]['owner_id']; // from id
 
   if(!$photo_id) {
    // если фотография не найдена
    $json = array(
     'error' => 'deleted',
     'error_text' => 'photo is deleted'
    );
   } else {
    $json = array(
     'response' => 1,
     'id' => $photo_id,
     'from' => $from_id,
     'full_id' => $from_id.'_'.$photo_id
    );
   }
  } else {
   $json = array(
    'error' => 'unknown error'
   );
  }
  return json_encode($json);  
 }
 
 public function video_info($id = null, $token = null) {
  /*
   Получаем информацию о видеозаписи
   Пример вызова: print_r(json_decode($vk->video_info('123_1')));
  */
  $api = json_decode(vk::_post('https://api.vk.com/method/video.get?videos='.$id.'&access_token='.$token), true);
  $api_error_code = $api['error']['error_code'];
  if($api_error_code) {
   // в случае, если возникла ошибка
   $json = array(
     'error' => vk::error($api_error_code)
   );
  } elseif($api) {
   // в случае успеха
   $video_id = $api['response'][1]['vid']; // id
   $from_id = $api['response'][1]['owner_id']; // from id
   $title = $api['response'][1]['title']; // title
   $text = $api['response'][1]['description']; // text
 
   if(!$video_id) {
    // если видеозапись не найдена
    $json = array(
     'error' => 'deleted',
     'error_text' => 'video is deleted'
    );
   } else {
    $json = array(
     'response' => 1,
     'id' => $video_id,
     'from' => $from_id,
     'full_id' => $from_id.'_'.$video_id,
     'title' => $title,
     'text' => $text
    );
   }
  } else {
   $json = array(
    'error' => 'unknown error'
   );
  }
  return json_encode($json);  
 }
 
 public function topic_info($id = null, $token = null) {
  /*
   Получаем информацию об обсуждении
   Пример вызова: print_r(json_decode($vk->topic_info('123_1')));
  */
  $id_explode = explode('_', $id);
  $id_one = str_replace('-', '', $id_explode[0]);
  $id_two = $id_explode[1];
  
  $api = json_decode(vk::_post('https://api.vk.com/method/board.getTopics?tids='.$id_two.'&gid='.$id_one.'&access_token='.$token), true);
  $api_error_code = $api['error']['error_code'];
  if($api_error_code) {
   // в случае, если возникла ошибка
   $json = array(
     'error' => vk::error($api_error_code)
   );
  } elseif($api) {
   // в случае успеха
   $topic_id = $api['response']['topics'][1]['tid']; // id
   if(!$topic_id) {
    // если топик не найден
    $json = array(
     'error' => 'deleted',
     'error_text' => 'topic is deleted'
    );
   } else {
    $json = array(
     'response' => 1,
     'id' => $topic_id,
     'from' => $id_one,
     'full_id' => $id_one.'_'.$topic_id
    );
   }
  } else {
   $json = array(
    'error' => 'unknown error'
   );
  }
  return json_encode($json);  
 }
 
 public function other_info($name = null) {
  $api = json_decode(vk::_post('https://api.vk.com/method/utils.resolveScreenName?screen_name='.urlencode($name).'&access_token='.$token), true);
  $json = array(
   'response' => 1,
   'type' => $api['response']['type'],
   'url' => $api['response']['object_id']
  );
   return json_encode($json);  
 }
 
 public function screen_name($url = null) {
  $url_json = json_decode(vk::explode($url), true);
  $url_json_nodecode = vk::explode($url);
  $url_type = $url_json['type'];
  $url_url = $url_json['url'];
  $url_reply = $url_json['reply'];
  if($url_type == 'unknown') {
   $other_type = json_decode(vk::other_info($url_url), true);
   $other_type_nodecode = vk::other_info($url_url);
   return $other_type_nodecode;
  } elseif($url_type == 'wall' || $url_type == 'photo' || $url_type == 'video' || $url_type == 'group' || $url_type == 'user') {
   return $url_json_nodecode;
  } elseif($url_type == 'wall_comment') {
   $comment_url_explode = explode('_', $url);
   $comment_url_explode_result = str_replace(array('http://', 'vk.com/', '/', 'wall', 'vkontakte.ru'), '', $comment_url_explode[0]);
   $json = json_encode(array('response' => 1, 'url' => $comment_url_explode_result.'_'.$url_reply, 'type' => 'wall_comment'));
   return $json;
  }
 }
 
 public function check_group($id = null, $uid = null, $token = null) {
  $api = json_decode(vk::_post('https://api.vk.com/method/groups.isMember?group_id='.$id.'&user_id='.$uid.'&extended=1&access_token='.$token), true);
  if($api['response']) {
   if($api['response']['member'] == 1 || $api['response']['request'] == 1) {
    return 1;
   } else {
    return 2;
   }
  } else {
   return 0;
  }
 }

 public function check_like($uid = null, $type = null, $url = null, $token = null) {
  $url_explode = explode('_', $url);
  $type_result = str_replace(array('wall_comment', 'wall'), array('comment', 'post'), $type);
  $api = json_decode(vk::_post('https://api.vk.com/method/likes.isLiked?user_id='.$uid.'&type='.$type_result.'&owner_id='.$url_explode[0].'&item_id='.$url_explode[1].'&access_token='.$token), true);

  if(mb_strlen($api['response'], 'UTF-8') >= 1) {
   if($api['response'] == 1) {
    return 1;
   } else {
    return 2;
   }
  } else {
   return 0;
  }
 }
 
 public function check_follower($uid = null, $to = null, $token = null) {
  $code = 'return {"followers": API.users.getFollowers({"user_id":'.$to.', "count": 1000, "offset": 0}), friends: API.friends.get({"user_id":'.$to.'})};';
  $api = json_decode(vk::_post('https://api.vk.com/method/execute?code='.urlencode($code).'&access_token='.$token));
  $followers = $api->response->followers->items;
  $friends = $api->response->friends;
  $users = @array_merge($followers, $friends);
  if($api->response) {
   if(@in_array($uid, $users)) {
    return 1;
   } else {
    return 2;
   }
  } else {
   return 0;
  }
 }
 
 public function check_repost($uid = null, $type = null, $url = null, $token = null) {
  $url_explode = explode('_', $url);
  $type_result = str_replace(array('wall_comment', 'wall'), array('comment', 'post'), $type);
  $api = json_decode(vk::_post('https://api.vk.com/method/likes.getList?type='.$type_result.'&owner_id='.$url_explode[0].'&item_id='.$url_explode[1].'&filter=copies&count=100&access_token='.$token), true);
  if($api['response']) {
   if(in_array($uid, $api['response']['users'])) {
    return 1;
   } else {
    return 2;
   }
  } else {
   return 0;
  }
 }
 
 public function check_wall_comment($uid = null, $url = null, $comment = null, $token = null) {
  $url_explode = explode('_', $url);
  $api = json_decode(vk::_post('https://api.vk.com/method/wall.getComments?owner_id='.$url_explode[0].'&post_id='.$url_explode[1].'&sort=desc&count=100&access_token='.$token), true);
  $count_comments = count($api['response']);
  $comment = fxss($comment);
  if($api['response']) {
   for($i = 1; $i < $count_comments; $i++) {
    $uid_comments = $api['response'][$i]['from_id'];
    $text_comments = $api['response'][$i]['text'];
    if($text_comments == $comment && $uid_comments == $uid) {
     $success = 1;
    }
   }
   if($success == 1) {
    $error = 1;
   } else {
    $error = 2;
   }
  } else {
   $error = 0;
  }
  return $error;
 }
 
  public function check_wall_comment_json($url = null, $comment = null, $token = null) {
  $url_explode = explode('_', $url);
  $api = json_decode(vk::_post('https://api.vk.com/method/wall.getComments?owner_id='.$url_explode[0].'&post_id='.$url_explode[1].'&sort=desc&count=100&access_token='.$token), true);
  $count_comments = count($api['response']);
  $comment = fxss($comment);
  $uid_result = '';
  if($api['response']) {
   for($i = 1; $i < $count_comments; $i++) {
    $uid_comments = $api['response'][$i]['from_id'];
    $text_comments = $api['response'][$i]['text'];
    if($text_comments == $comment) {
     $success = 1;
     $uid_result = $uid_comments;
    }
   }
   if($success == 1) {
    $json = array('error' => 1, 'uid' => $uid_result);
   } else {
    $json = array('error' => 2);
   }
  } else {
   $json = array('error' => 0);
  }
  return json_encode($json);
 }
 
 public function check_video_comment($uid = null, $url = null, $comment = null, $token = null) {
  $url_explode = explode('_', $url);
  $api = json_decode(vk::_post('https://api.vk.com/method/video.getComments?owner_id='.$url_explode[0].'&video_id='.$url_explode[1].'&sort=desc&count=100&access_token='.$token), true);
  $count_comments = count($api['response']);
  $comment = fxss($comment);
  if($api['response']) {
   for($i = 1; $i < $count_comments; $i++) {
    $uid_comments = $api['response'][$i]['from_id'];
    $text_comments = $api['response'][$i]['message'];
    if($text_comments == $comment && $uid_comments == $uid) {
     $success = 1;
    }
   }
   if($success == 1) {
    $error = 1;
   } else {
    $error = 2;
   }
  } else {
   $error = 0;
  }
  return $error;
 }
 
 public function check_photo_comment($uid = null, $url = null, $comment = null, $token = null) {
  $url_explode = explode('_', $url);
  $api = json_decode(vk::_post('https://api.vk.com/method/photos.getComments?owner_id='.$url_explode[0].'&photo_id='.$url_explode[1].'&sort=desc&count=100&access_token='.$token), true);
  $count_comments = count($api['response']);
  $comment = fxss($comment);
  if($api['response']) {
   for($i = 1; $i < $count_comments; $i++) {
    $uid_comments = $api['response'][$i]['from_id'];
    $text_comments = $api['response'][$i]['message'];
    if($text_comments == $comment && $uid_comments == $uid) {
     $success = 1;
    }
   }
   if($success == 1) {
    $error = 1;
   } else {
    $error = 2;
   }
  } else {
   $error = 0;
  }
  return $error;
 }
 
 public function get_status($uid = null, $token = null) {
  $api = json_decode(vk::_post('https://api.vk.com/method/status.get?user_id='.$uid.'&access_token='.$token), true);
  if($api['response']) {
   return $api['response']['text'];
  } else {
   return 0;
  }
 }
 
 public function _post($url = null) {
  
  $sites_list = array('http://google.ru', 'http://yandex.ru', 'http://music.yandex.ru', 'http://direct.yandex.ru', 'http://myrusakov.ru', 'http://ucoz.ru', 'http://narod.ru', 'http://facebook.com', 'http://vk.com', 'http://habrahabr.ru', 'http://php.su', 'http://hashcode.ru', 'http://2lx.ru', 'http://odnoklassniki.ru', 'http://mamba.ru');
  $sites_rand = $sites_list[rand(0, count($sites_list) - 1)];
  
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
  curl_setopt($curl, CURLOPT_REFERER, $sites_rand);
  $response = curl_exec($ch);
  curl_close($ch);
  //print_r($response);
  return $response;
 }
}

$vk = new vk;
?>