<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'blog_archive';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/blog.php');
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');

if($uban_type) {
 header('Location: /blocked');
 exit;
}

$archive_num = $blog->archive_num();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Блог</title>
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
       <div id="site_page" class="main nopad">
        <div id="blog_title_hr"><? if($archive_num) { ?><? echo declOfNum($archive_num, array('Найдена', 'Найдено', 'Найдено')); ?> <? echo $archive_num; ?> <? echo declOfNum($archive_num, array('запись', 'записи', 'записей')); ?><? } else { ?><? echo 'Ничего не найдено'; ?><? } ?></div>
        <div id="blog_content">
         <table>
           <tr>
             <td id="blog_content_left">
              <? if($archive_num) echo $blog->archive_all(); else echo '<div id="archive_blog_none">Не найдено ни одной новости.</div>'; ?> 
             </td>
             <td id="blog_content_right">
              <div id="blog_content_right_menu">
               <? if($ugroup == 4 && $user_logged) { ?><div id="blog_add_new_b" class="blue_button_wrap" onclick="nav.go('', '/blog/add'); return false"><div class="blue_button">Создать новость</div></div><? } ?> 
               <? echo $blog->entry_menu_all(); ?> 
               <div id="blog_menu_hr"></div>
               <a class="active" href="/blog/archive" onclick="nav.go(this); return false">Архив новостей</a> 
              </div>
             </td>
           </tr>
         </table>
        </div>
       </div>
      </div>
     </div>
     <input type="hidden" value="<? echo $usession; ?>" id="ssid">
<? include($root.'/include/footer.php') ?>
 
    </div>
   </div>
  </div>
<? include($root.'/include/scripts.php') ?> 
 </body>
</html>