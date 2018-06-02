<?php
if($page_name == 'main') {
 $user_get_info = $db->query("SELECT `ulast_time`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'tasks') {
 // список всех заданий
 $user_get_info = $db->query("SELECT `ulogin`, `upoints`, `ulast_time`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `ugroup`, `blacklist_notif`, `udel`, `uvk_id`, `complaints` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'my_tasks') {
 // список моих заданий
 $user_get_info = $db->query("SELECT `ulogin`, `upoints`, `ulast_time`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `ugroup`, `blacklist_notif`, `udel`, `complaints`, `uvk_id` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'add_task') {
 // новое задание
 $user_get_info = $db->query("SELECT `ulogin`, `upoints`, `ulast_time`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `ugroup`, `blacklist_notif`, `udel`, `complaints`, `uvk_id` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'blacklist_task') {
 // мои жалобы
 $user_get_info = $db->query("SELECT `ulogin`, `upoints`, `ulast_time`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `ugroup`, `blacklist_notif`, `udel`, `complaints`, `uvk_id` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'support') {
 // поддержка
 $user_get_info = $db->query("SELECT `ulogin`, `upoints`, `ulast_time`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `ugroup`, `blacklist_notif`, `uavatar`, `uagent_id`, `uagent_avatar`, `udel`, `complaints`, `uvk_id` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'chat') {
 // чат
 $user_get_info = $db->query("SELECT `ulogin`, `upoints`, `ulast_time`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `ugroup`, `blacklist_notif`, `uavatar`, `uvk_id`, `ulast_name`, `uname` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'my_settings') {
 // Мои настройки
 $user_get_info = $db->query("SELECT `ulogin`, `upoints`, `ulast_time`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `blacklist_notif`, `ugroup`, `udel`, `uemail`, `uemail_activated`, `complaints`, `uvk_id` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'del_task') {
 // удалить задание
 $user_get_info = $db->query("SELECT `ulast_time`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'ignored_task') {
 // игнорировать задание
 $user_get_info = $db->query("SELECT `ulast_time`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `udel`, `uvk_id` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'add.post_task') {
 // запрос на добавление задания 
 $user_get_info = $db->query("SELECT `upoints`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `udel`, `ugroup` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'all.next_task') {
 // запрос на подгрузку заданий
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `ugroup`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'edit.form_task') {
 // форма редактирования заданий
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'edit_task') {
 // запрос на редактирование заданий
 $user_get_info = $db->query("SELECT `upoints`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'add.categories_task') {
 // запрос на создание категории
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'delete.categories_task') {
 // запрос на удаление категории
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'admin.tasks.delete') {
 // запрос на удаление задания для админов
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `ugroup`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'secure') {
 // код безопасности
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'admin.tasks.blacklist_add') {
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `ugroup`, `upoints`, `blacklist_notif`, `udel`, `complaints`, `uvk_id` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'admin.tasks.blacklist_add.post') {
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `ugroup`, `upoints`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info); 
} elseif($page_name == 'admin.tasks.blacklist_delete') {
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `ugroup`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'admin.tasks.blacklist_reject') {
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `ugroup`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info); 
} elseif($page_name == 'admin.tasks.blacklist_consider') {
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `ugroup`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info); 
} elseif($page_name == 'blacklist_task_add') {
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `udel` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info); 
} elseif($page_name == 'go_task') {
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `udel`, `uvk_id` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info); 
} elseif($page_name == 'check_task') {
 $user_get_info = $db->query("SELECT `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `udel`, `uvk_id` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info); 
} elseif($page_name == 'my_complaints') {
 // Мои штрафы
 $user_get_info = $db->query("SELECT `ulogin`, `upoints`, `ulast_time`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `ugroup`, `blacklist_notif`, `udel`, `complaints`, `uvk_id` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
} elseif($page_name == 'lot') {
 // Лотерея
 $user_get_info = $db->query("SELECT `upassword`, `ulogin`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `ureg_time`, `ugroup`, `uagent_id`, `uagent_avatar`, `udel`, `upoints`, `blacklist_notif`, `uvk_id`, `uemail`, `complaints` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
 $user_lot_data=$db->query("SELECT 'lot_id' FROM `lot` WHERE `lot_uid` = '$user_id' AND `lot_win` = '0'");
 $user_lot = $db->num($user_lot_data);
}
 else {
 $user_get_info = $db->query("SELECT `upassword`, `ulogin`, `uhash`, `uban_type`, `uban_time`, `uban_text`, `ulast_time`, `ureg_time`, `ugroup`, `uagent_id`, `uagent_avatar`, `udel`, `upoints`, `blacklist_notif`, `uvk_id`, `uemail`, `complaints` FROM `users` WHERE `uid` = '$user_id'");
 $user_get_data = $db->fetch($user_get_info);
}

// информация о пользователе
$ufirst_name = $user_get_data['uname'];
$ulast_name = $user_get_data['ulast_name'];
$uvk_id = $user_get_data['uvk_id'];
$user_login = $user_get_data['ulogin'];
$user_uhash = $user_get_data['uhash'];
$upassword = $user_get_data['upassword'];
$ureg_time = $user_get_data['ureg_time'];
$ulast_time = $user_get_data['ulast_time'];
$ugroup = $user_get_data['ugroup'];
$uavatar = $user_get_data['uavatar'];
$uagent_id = $user_get_data['uagent_id'];
$uagent_avatar = $user_get_data['uagent_avatar'];
$upoints = $user_get_data['upoints'];
$blacklist_notif = $user_get_data['blacklist_notif'];
$udel = $user_get_data['udel'];
$uemail = $user_get_data['uemail'];
$uemail_activated = $user_get_data['uemail_activated'];
$uban_type = $user_get_data['uban_type'];
$uban_time = $user_get_data['uban_time'];
$uban_text = $user_get_data['uban_text'];
$user_logged = ($user_hash == $user_uhash && !$udel && !$uban_type) ? 1 : 0;
$unew_complaints = $user_get_data['complaints'];

// делаем аккаунт в Online
if($ulast_time < $online_limit) {
 $db->query("UPDATE `$dbName`.`users` SET `ulast_time` = '$time' WHERE `users`.`uid` = '$user_id';");
}
?>