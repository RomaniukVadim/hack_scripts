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
require($root.'/inc/classes/balance.php');

$balance_my_history_num = $balance->my_history_num();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Баланс</title>
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
         <a href="/settings/ref?menu=1" onclick="nav.go(this); return false;"><div class="tabdiv">Партнерская программа</div></a>
         <a class="active" href="/settings/balance" onclick="nav.go(this); return false;"><div class="tabdiv">Баланс</div></a>
        </div>
        <div id="balance_description">
         <div id="balance_description_inner">
          <div id="balance_description_title">Состояние личного счета</div>
          <div id="balance_description_text">
           <b>Баллы</b> - валюта для оплаты заданий Piar.Name, Обратите внимание, что услуга считается оказанной в момент зачисления баллов, возврат средств невозможен.
           <div align="center">
            <div id="balance_counter" class="overflow_field">
             <div class="label">На Вашем счете:</div>
             <div class="field">
              <b><? echo $upoints; ?> <? echo declOfNum(abs($upoints), array('балл', 'балла', 'баллов')); ?></b> <br />
              <div onclick="pay._show_type();" id="settings_password_button" class="blue_button_wrap small_blue_button"><div class="blue_button">Пополнить баланс</div></div>
             </div>
            </div>
           </div>
          </div>
         </div>
        </div>
        <? if($balance_my_history_num) { ?><? echo $balance->my_history(); ?><? } ?> 
        <? if($balance_my_history_num > 10) { ?> 
        <div id="next_page_small_c"></div> 
        <div style="display: block;" onclick="balance._next();" id="next_page_small_t">Показать предыдущие платежи</div> 
        <div id="next_page_small_d">1</div>
        <div id="balance_footer_support">
         Если у Вас возникли проблемы, обратитесь в <a href="/support/new" onclick="nav.go(this); return false">поддержку</a>.
        </div>
        <? } else { ?> 
        <div id="balance_footer_support_white">
         Если у Вас возникли проблемы, обратитесь в <a href="/support/new" onclick="nav.go(this); return false">поддержку</a>.
        </div>
        <? } ?> 
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