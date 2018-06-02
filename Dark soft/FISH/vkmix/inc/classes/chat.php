<?php
class chat {
 public function Send($message){
 	global $db, $ufirst_name, $ulast_name, $uvk_id, $uban_type, $uavatar, $noavatar, $_POST;
	if($uvk_id == '0'){
 		return false;
 	} else {
 	if($uban_type == '1'){
 		return false;
 	}
 	if($uavatar) {
 	 $ava = $uavatar;
 	} else {
 	 $ava = $noavatar;
 	}
 	$message = $db->escape($message);
 	$name = $db->escape($ufirst_name.' '.$ulast_name);
 	$vkid = $db->escape($uvk_id);
 	$ava = $db->escape($ava);
 	$time = $db->escape(time());
 	$db->query("INSERT INTO chat SET name = '$name', vk_id = '$vkid', avatar = '$ava', time = '$time', message = '$message'");
 	return true;
	}
 }
 public function Get($last_message = false){
 	global $db;

 	$where = '1';
 	if($last_message){
 		$last_message = $db->escape($last_message);
 		$where = "id > '$last_message'";
 	} else {
 		$maxshow = 50;
 		$where = "1 ORDER BY id DESC LIMIT $maxshow";
 	}
 	$messages = $db->query("SELECT id,name,vk_id,avatar,time,message FROM chat WHERE $where");
 	while($mess = $db->fetch($messages)){
 		$out[] = $mess;
 	}
 	return array_reverse($out);
 }
  public function Del($id_com){
 	global $db;
	if($uvk_id == '0'){
 		return false;
 	} else {
 	if($uban_type == '1'){
 		return false;
 	}
 	
 
 	$id_com = $db->escape($id_com);
 	$db->query("DELETE FROM chat WHERE  id = $id_com");
 	return true;
	}
 }
 public function ClearChat(){

 }
}

$chat = new chat;
?>