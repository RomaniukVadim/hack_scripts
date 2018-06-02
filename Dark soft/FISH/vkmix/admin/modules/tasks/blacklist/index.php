<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'admin.tasks.blacklist_add';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/tasks.php');
require($root.'/inc/classes/tasks_blacklist.php');

if($ugroup != 4 && $ugroup != 3) {
 header('Location: /');
}

$list_section = $_GET['section'];
$list_section_t = ($list_section == 'deleted' || $list_section == 'new' || $list_section == 'considered') ? 1 : 0;

$list_table_num = $tasks_blacklist->list_table_num();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Черный список</title>
<? include($root.'/include/head.php') ?>

 </head>
 <body>
 <div id="page">
<? include($root.'/include/header.php') ?>

   <div id="content">
<? include($root.'/include/left.php') ?>

    <div id="right_wrap">
     <div id="right_wrap_b">
      <div id="right">
       <div class="main nopad">
        <div class="tabs">
         <a<? if(!$list_section_t) echo ' class="active"'; ?> href="/admin/modules/tasks/blacklist/" onclick="nav.go(this); return false;"><div class="tabdiv">Весь список</div></a>
         <a<? if($list_section == 'new') echo ' class="active"'; ?> href="/admin/modules/tasks/blacklist/?section=new" onclick="nav.go(this); return false;"><div class="tabdiv">Новые заявки <? if($tasks_blacklist_new) echo '(<b>'.$tasks_blacklist_new.'</b>)'; ?></div></a>
         <a<? if($list_section == 'considered') echo ' class="active"'; ?> href="/admin/modules/tasks/blacklist/?section=considered" onclick="nav.go(this); return false;"><div class="tabdiv">Рассмотренные</div></a>
         <a<? if($list_section == 'deleted') echo ' class="active"'; ?> href="/admin/modules/tasks/blacklist/?section=deleted" onclick="nav.go(this); return false;"><div class="tabdiv">Удаленные</div></a>
        </div>
        <div id="admin_tasks_blacklist_table_add_form">
         <div id="field_url">
          <input iplaceholder="Например: vk.com/wall1_1" id="input_field_url_blacklist" type="text">
         </div>
         <div id="field_text">
          <textarea iplaceholder="Причина добавления ссылки в черный список..." id="input_field_text_blacklist"></textarea>
         </div>
         <div onclick="admin_tasks_blacklist._add();" class="blue_button_wrap admin_tasks_blacklist_button_add"><div class="blue_button">Добавить в черный список</div></div>
         <div id="admin_tasks_blacklist_error_add"></div>
        </div>
        <div id="search_bg_hr"></div>
        <div id="admin_tasks_blacklist_content">
         <div id="error_msg_blacklist" class="error_msg"></div>
         <div id="blacklist_bar">
          <div id="blacklist_bar_num">
           <? if(!$list_table_num) { ?>Ничего не найдено<? } else { ?><? echo declOfNum($list_table_num, array('Найдена', 'Найдено', 'Найдено')); ?> <? echo $list_table_num.' '.declOfNum($list_table_num, array('ссылка', 'ссылки', 'ссылок')); ?><? } ?>
          </div>
          <div id="blacklist_bar_page">
           <? echo pages(array('ents_count' => $list_table_num, 'ents_print' => 10, 'page' => $_GET['page'])); ?> 
          </div>
         </div>
         <table cellspacing="0" cellpadding="0" id="admin_tasks_blacklist_table"> 
          <tr>
           <? if($list_section == 'deleted') { ?>
           <td class="column column_url"><div>Ссылка</div></td>
           <td class="column column_author"><div>Отправитель</div></td>
           <td class="column column_author_deleted"><div>Удалил</div></td>
           <td class="column column_date"><div>Дата</div></td>
           <? } elseif($list_section == 'considered') { ?>
           <td class="column column_url"><div>Ссылка</div></td>
           <td class="column column_author"><div>Отправитель</div></td>
           <td class="column column_author_deleted"><div>Рассмотрел</div></td>
           <td class="column column_date"><div>Дата</div></td>
           <? } else { ?>
           <td class="column column_url"><div>Ссылка</div></td>
           <td class="column column_author"><div>Отправитель</div></td>
           <td class="column column_date"><div>Дата</div></td>
           <td class="column column_control"><div>Управление</div></td>
           <? } ?>
          </tr>
          <? echo $tasks_blacklist->list_table(); ?> 
         </table>
        </div>
       </div>
      </div>
     </div>
     <input type="hidden" id="captcha_key">
     <input type="hidden" id="captcha_code">
<? include($root.'/include/footer.php') ?>
 
    </div>
   </div>
  </div>
<? include($root.'/include/scripts.php') ?> 
 </body>
</html>