<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'all.next_task';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/sessions.php');
require($root.'/inc/classes/vk.api.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks.php');

$flag_my = $_GET['my'];
$page = $_GET['page'];
$personal_cat_id = $_GET['list'];
$sort = $_GET['sort'];
$search = trim($_GET['search']);
$cat_id = $tasks->getCatNum($_GET['section']); // id категории
$tasks_num = $flag_my ? $tasks->my_tasks_num($user_id, $cat_id, $personal_cat_id, $search) : $tasks->all_tasks_num($user_id, $cat_id, $search); // количество заданий

if($flag_my) { // если "Мои задания"
 echo $tasks->my_tasks(array(
  'section' => $cat_id,
  'tasks_num' => $tasks_num,
  'uid' => $user_id,
  'page' => $page,
  'cat' => $personal_cat_id,
  'search' => $search,
  'usession' => $usession
 ));
} else {
 echo $tasks->all_tasks(array(
  'section' => $cat_id,
  'tasks_num' => $tasks_num,
  'uid' => $user_id,
  'page' => $page,
  'search' => $search,
  'sort' => $sort,
  'usession' => $usession
 ));
}
?>