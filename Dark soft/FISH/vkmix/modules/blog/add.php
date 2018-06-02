<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'blog_add';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/tasks_blacklist.php');

if($ugroup != 4) {
 header('Location: /');
 exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Создание новости в блоге</title>
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
        <div id="admin_site_page_add_content_title_overflow">
         <div id="admin_site_page_add_content_title_overflow_left">
          <div id="admin_site_page_add_content_title">Создание новости в блоге</div>
         </div>
         <div id="admin_site_page_add_content_title_overflow_right">
          <a href="/blog" onclick="nav.go(this); return false">Вернуться ко всем новостям</a>
         </div>
        </div>
        <div id="admin_site_page_add_content">
         <div id="admin_site_page_add_short_url_content">
          <input iplaceholder="Введите заголовок новости. Например: Обзор нововведений" maxlength="100" id="admin_site_page_add_name" type="text">
          <div></div>
          <a onmousedown="wiki._b(); return false" class="wiki_icon_wrap"><div class="wiki_icons wiki_icons_bold"></div></a>
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
         </div>
         <div id="admin_site_page_add_save_hr"></div>
         <div id="admin_site_page_add_save">
          <div class="block_add_check_overflow">
           <div class="block_add_check_left"><input id="blog_allow_admin" type="checkbox"></div>
           <div class="block_add_check_right">Новость доступна только администраторам</div>
          </div>
          <div class="block_add_check_overflow">
           <div class="block_add_check_left"><input id="blog_left_menu" type="checkbox"></div>
           <div class="block_add_check_right">Разместить новость под левым меню</div>
          </div>
          <div onclick="admin_blog._add();" id="admin_site_page_add_save_button" class="blue_button_wrap small_blue_button"><div class="blue_button">Создать новость</div></div>
          <div id="admin_site_page_add_error"></div>
         </div>
        </div>
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