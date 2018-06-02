<?php
class logs {
 public function add($params = null) {
  global $db, $dbName, $time, $ip_address, $browser;
  
  $from = (int) $params['from']; // от кого
  $to = (int) $params['to']; // кому
  $module = (int) $params['module']; // id модуля
  $module_type = (int) $params['module_type']; // тип
  $mid = (int) $params['mid']; // id материала
  $user1 = (int) $params['user1']; // пользователь 1, который видит историю(если есть)
  $user2 = (int) $params['user2'];// пользователь 2, который видит историю(если есть)
  $text = $db->escape($params['text']); // текст
  $points = (int) $params['points']; // кол-во поинтов
  $history = (int) $params['history']; // отображать в истории(1 или 0)
  $api = (int) $params['api']; // 1 или 0
  $app_id = (int) $params['app_id']; // id приложения
  $read = (int) $params['read']; // метка о прочтении(1 или 0)
  $admin = (int) $params['admin']; // действие администратора
  
  $query = "INSERT INTO  `$dbName`.`logs` (
   `lid` ,
   `lfrom` ,
   `lto` ,
   `lip_address` ,
   `lbrowser` ,
   `ltime` ,
   `lmodule` ,
   `lmodule_type` ,
   `lmid` ,
   `lview1` ,
   `lview2` ,
   `ltext` ,
   `lpoints` ,
   `lhistory` ,
   `lapi` ,
   `lapp_id` ,
   `lread`,
   `ladmin`
   )
   VALUES (
   NULL ,  '$from',  '$to',  '$ip_address',  '$browser',  '$time',  '$module', '$module_type',  '$mid',  '$user1',  '$user2',  '$text',  '$points',  '$history',  '$api',  '$app_id',  '$read', '$admin'
   );";
   $db->query($query);
 }
 
 public function add_task($uid, $mid, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 4,
   'module_type' => 1,
   'mid' => $mid,
   'user1' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 1
  ));
 }
 
 public function delete_task($uid, $mid, $text, $points, $admin) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 4,
   'module_type' => 2,
   'mid' => $mid,
   'text' => $text,
   'user1' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 1,
   'admin' => $admin
  ));
 }
 
 public function ignored_task($uid, $mid) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 4,
   'module_type' => 5,
   'mid' => $mid,
   'text' => '',
   'user1' => '',
   'points' => '',
   'history' => 0,
   'read' => 1
  ));
 }
 
 public function return_task($uid, $mid, $admin) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 4,
   'module_type' => 4,
   'mid' => $mid,
   'text' => '',
   'user1' => '',
   'points' => '',
   'history' => 0,
   'read' => 1,
   'admin' => $admin
  ));
 }
 
 public function edit_task($uid, $mid, $text, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 4,
   'module_type' => 3,
   'mid' => $mid,
   'text' => $text,
   'user1' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 1
  ));
 }
 
 public function done_task($uid, $mid, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 4,
   'module_type' => 6,
   'mid' => $mid,
   'text' => '',
   'user1' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 1
  ));
 }

 public function addurl_in_blacklist($uid, $text) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 5,
   'module_type' => 1,
   'mid' => '',
   'text' => $text,
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1,
   'admin' => 1
  ));
 }
 
 public function addurl_in_blacklist_user($uid, $text) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 5,
   'module_type' => 5,
   'mid' => '',
   'text' => $text,
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1
  ));
 }
 
 public function delurl_in_blacklist($uid, $mid) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 5,
   'module_type' => 2,
   'mid' => $mid,
   'text' => '',
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1,
   'admin' => 1
  ));
 }

 public function rejecturl_in_blacklist($uid, $mid) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 5,
   'module_type' => 3,
   'mid' => $mid,
   'text' => '',
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1,
   'admin' => 1
  ));
 }

 public function considerurl_in_blacklist($uid, $mid) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 5,
   'module_type' => 4,
   'mid' => $mid,
   'text' => '',
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1,
   'admin' => 1
  ));
 }
 
 public function support_new_question($uid, $mid) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 6,
   'module_type' => 1,
   'mid' => $mid,
   'text' => '',
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1
  ));
 }
 
 public function support_new_comment($uid, $mid) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 6,
   'module_type' => 2,
   'mid' => $mid,
   'text' => '',
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1
  ));
 }
 
 public function support_del_post($uid, $mid) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 6,
   'module_type' => 3,
   'mid' => $mid,
   'text' => '',
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1
  ));
 }
 
 public function support_rate_comment($uid, $mid) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 6,
   'module_type' => 4,
   'mid' => $mid,
   'text' => '',
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1
  ));
 }
 
 public function support_edit_status($uid, $mid, $status_id) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 6,
   'module_type' => 5,
   'mid' => $mid,
   'text' => $status_id,
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1,
   'admin' => 1
  ));
 }
 
 public function change_password($uid, $mid, $text) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 1,
   'module_type' => 1,
   'mid' => $mid,
   'text' => $text,
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1
  ));
 }
 
 public function change_login($uid, $mid, $text) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 1,
   'module_type' => 2,
   'mid' => $mid,
   'text' => $text,
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1
  ));
 }
 
 public function delete_account($uid, $mid) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 1,
   'module_type' => 3,
   'mid' => $mid,
   'text' => '',
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1
  ));
 }
 
 public function return_account($uid, $mid) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 1,
   'module_type' => 4,
   'mid' => $mid,
   'text' => '',
   'user1' => '',
   'points' => 0,
   'history' => 0,
   'read' => 1
  ));
 }
 
 public function edit_points($uid, $toid, $mid, $type, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $toid,
   'module' => 1,
   'module_type' => 5,
   'mid' => $mid,
   'text' => $type,
   'user2' => $toid,
   'points' => $points,
   'history' => 1,
   'read' => 1,
   'admin' => 1
  ));
 }
 
 public function add_ref($uid, $toid, $mid, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $toid,
   'module' => 1,
   'module_type' => 6,
   'mid' => $mid,
   'user2' => $toid,
   'points' => $points,
   'history' => 1,
   'read' => 1
  ));
 }
 
 public function user_login($uid) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 1,
   'module_type' => 7,
   'mid' => $uid,
   'user2' => $uid,
   'points' => '',
   'history' => 0,
   'read' => 0
  ));
 }
 
 public function user_edit_info($from, $to, $text) {
  logs::add(array(
   'from' => $from,
   'to' => $to,
   'module' => 1,
   'module_type' => 8,
   'text' => $text,
   'mid' => $to,
   'user2' => $to,
   'points' => '',
   'history' => 0,
   'read' => 0,
   'admin' => 1
  ));
 }
 
 public function add_ref_percents($from, $to, $points) {
  logs::add(array(
   'from' => $from,
   'to' => $to,
   'module' => 1,
   'module_type' => 9,
   'text' => '',
   'mid' => $to,
   'user2' => $to,
   'points' => $points,
   'history' => 1,
   'read' => 0
  ));
 }
 
 public function site_page_delete($uid, $id) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 7,
   'module_type' => 1,
   'text' => '',
   'mid' => $id,
   'user2' => $uid,
   'points' => '',
   'history' => 0,
   'read' => 0,
   'admin' => 1
  ));
 }
 
 public function site_page_return($uid, $id) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 7,
   'module_type' => 2,
   'text' => '',
   'mid' => $id,
   'user2' => $uid,
   'points' => '',
   'history' => 0,
   'read' => 0,
   'admin' => 1
  ));
 }
 
 public function site_page_edit($uid, $id, $text) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 7,
   'module_type' => 3,
   'text' => $text,
   'mid' => $id,
   'user2' => $uid,
   'points' => '',
   'history' => 0,
   'read' => 0,
   'admin' => 1
  ));
 }
 
 public function pay_webmoney($uid, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 9,
   'module_type' => 1,
   'text' => '',
   'mid' => '',
   'user2' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 0,
   'admin' => 0
  ));
 }
 public function pay_waytopay($uid, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 9,
   'module_type' => 5,
   'text' => '',
   'mid' => '',
   'user2' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 0,
   'admin' => 0
  ));
 }
 
 public function pay_sms($uid, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 9,
   'module_type' => 2,
   'text' => '',
   'mid' => '',
   'user2' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 0,
   'admin' => 0
  ));
 }
 
 public function pay_qiwi($uid, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 9,
   'module_type' => 3,
   'text' => '',
   'mid' => '',
   'user2' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 0,
   'admin' => 0
  ));
 }
 
 public function complaints_return($uid, $mid, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 8,
   'module_type' => 2,
   'text' => '',
   'mid' => $mid,
   'user2' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 0,
   'admin' => 0
  ));
 }
  public function game_lose($uid, $mid, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 10,
   'module_type' => 1,
   'mid' => $mid,
   'user1' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 1
  ));
 }
   public function game_win($uid, $mid, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 10,
   'module_type' => 2,
   'mid' => $mid,
   'user1' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 1
  ));
 }
    public function add_cup($uid, $mid, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 11,
   'module_type' => 1,
   'mid' => $mid,
   'user1' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 1
  ));
 }
     public function actv_cup($uid, $mid, $points) {
  logs::add(array(
   'from' => $uid,
   'to' => $uid,
   'module' => 11,
   'module_type' => 2,
   'mid' => $mid,
   'user1' => $uid,
   'points' => $points,
   'history' => 1,
   'read' => 1
  ));
 }
}

$logs = new logs;
?>