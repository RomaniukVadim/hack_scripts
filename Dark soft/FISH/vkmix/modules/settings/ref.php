<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'my_settings';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
require($root.'/inc/classes/users.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/tasks_blacklist.php');
require($root.'/inc/classes/refs.php');

$my_refs_num = $refs->my_num();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Мои настройки</title>
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
         <a href="/settings" onclick="nav.go(this); return false;"><div class="tabdiv">Общее</div></a>
         <a class="active" href="/settings/ref?menu=1" onclick="nav.go(this); return false;"><div class="tabdiv">Партнерская программа</div></a>
         <a href="/settings/balance" onclick="nav.go(this); return false;"><div class="tabdiv">Баланс</div></a>
        </div>
        <div id="settings_ref_content">
         <div id="settings_ref_content_main_text">
          Вы можете приглашать друзей на сайт и получать за это бонусные баллы.
          <br /><br />
          Мы выплачиваем: <br />
          — <b>15</b> баллов за каждого человека, который зарегистрировался по Вашей реферальной ссылке.
          <br />
          — <b>15%</b> от <b>суммы</b> баллов, которую <b>приобрел</b> Ваш реферал.
          <br /><br />
          Всё, что должен сделать Ваш друг, это зарегистрироваться по следующей ссылке:
          <div id="settings_ref_content_url">
           <a target="_blank" href="http://Piar.Name/ref<? echo $user_id; ?>">http://Piar.Name/ref<? echo $user_id; ?></a>
          </div>
         </div>
         <div id="settings_ref_c">
          <div id="settings_ref_count">
           <? if($my_refs_num) { ?>Вы уже пригласили <? echo $my_refs_num; ?> <? echo declOfNum($my_refs_num, array('реферала', 'рефералов', 'рефералов')); ?>.<? } ?> 
          </div>
          <div id="settings_ref_body">
           <? if($my_refs_num) { ?><? echo $refs->my(); ?><div id="next_page_small_c"></div><? } else { ?><div id="refs_none">Вы ещё не приглашали друзей на сайт по реферальной ссылке.</div><? } ?> 
            <? if($my_refs_num > 10) { ?><div style="display: block;" onclick="refs._next();" id="next_page_small_t">Показать еще рефералов</div><? } ?> 
            <div id="next_page_small_d">1</div>
          </div>
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