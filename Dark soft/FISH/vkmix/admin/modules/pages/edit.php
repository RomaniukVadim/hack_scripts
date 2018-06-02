<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'site_page_add';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/tasks.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/pages.php');

if($ugroup != 4) {
 header('Location: /');
 exit;
}

$page_edit_info = $pages->pedit_info();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Редактирование страницы</title>
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
        <? if($page_edit_info['id']) { ?> 
        <div id="admin_site_page_add_content_title_overflow">
         <div id="admin_site_page_add_content_title_overflow_left">
          <div id="admin_site_page_add_content_title">Редактирование страницы</div>
         </div>
         <div id="admin_site_page_add_content_title_overflow_right">
          <a href="/admin/modules/pages/" onclick="nav.go(this); return false">Вернуться к списку страниц</a>
         </div>
        </div>
        <div id="admin_site_page_add_content">
         <div id="admin_site_page_add_short_url_content">
          <input value="<? echo $page_edit_info['name']; ?>" iplaceholder="Введите название страницы. Например: Правила" id="admin_site_page_add_name" type="text">
          <div></div>
          <div onmousedown="wiki._b(); return false" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_bold"></div></div>
          <a onmousedown="wiki._i(); return false;" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_i"></div></a>
          <a onmousedown="wiki._u(); return false;" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_u"></div></a>
          <a onmousedown="wiki._left(); return false;" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_left"></div></a>
          <a onmousedown="wiki._center(); return false;" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_center"></div></a>
          <a onmousedown="wiki._right(); return false;" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_right"></div></a>
          <a onmousedown="wiki._mark_list(); return false;" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_mark_list"></div></a>
          <a onmousedown="wiki._h1(); return false;" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_h1"></div></a>
          <a onmousedown="wiki._h2(); return false;" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_h2"></div></a>
          <a onmousedown="wiki._h3(); return false;" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_h3"></div></a>
          <a onmousedown="wiki._blockquote(); return false;" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_blockquote"></div></a>
          <a onmousedown="wiki._add_link(); return false;" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_link"></div></a>
          <a onmousedown="" id="wiki_add_photoa" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_photo"></div>
           <form method="post" enctype="multipart/form-data" action="/admin/modules/pages/add_img.php" target="pages_upload_iframe">
            <input type="file" onchange="admin_pages._add_img(); return false;" type="file" name="file">
            <input id="pages_upload_iframe_submit" style="display: none;" type="submit">
           </form>
           <iframe id="pages_upload_iframe" name="pages_upload_iframe"></iframe>
          </a>
         </div>
         <div id="admin_site_page_add_text">
          <textarea id="editor"></textarea>
          <textarea style="display: none;" id="editor_html"><? echo $pages->html_code(stripslashes(nl2br($page_edit_info['text']))); ?></textarea>
         </div>
         <div id="admin_site_page_add_save_hr"></div>
         <div id="admin_site_page_add_save">
          <div onclick="admin_pages._edit(<? echo $page_edit_info['id']; ?>);" id="admin_site_page_edit_save_button" class="blue_button_wrap small_blue_button"><div class="blue_button">Сохранить изменения</div></div>
          <div id="admin_site_page_add_error"></div>
         </div>
        </div>
        <? } else { ?> 
        <div id="site_page_content_none">Ошибка доступа.</div>
        <? } ?> 
       </div>
      </div>
     </div>
<? include($root.'/include/footer.php') ?>

    </div>
   </div>
  </div>
<? include($root.'/include/scripts.php') ?> 
 </body>
</html>