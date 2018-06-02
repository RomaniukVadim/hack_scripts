<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'cupons';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/cupons.php');


$cat_id = $cupons->getCatNum2($_GET['section']); // id категории
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Купоны</title>
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
         <a<? if($cat_id <= 1) echo ' class="active"'; ?> href="/cupons/?section=active" onclick="nav.go(this); return false;"><div class="tabdiv">Активировать</div></a>
         <a<? if($cat_id == 2) echo ' class="active"'; ?> href="/cupons/?section=add" onclick="nav.go(this); return false;"><div class="tabdiv">Создать</div></a>
		  <a<? if($cat_id == 3) echo ' class="active"'; ?> href="/cupons/?section=my" onclick="nav.go(this); return false;"><div class="tabdiv">Мои купоны</div></a>
        </div>
        <div id="task_add_bg">
         <div id="mini_rules_add_task">
         <ul>
          <li>Вы можете создавать купон и поделится с ним с другом</li>
          <li>За создание купона взимается комиссия <b>10%</b>.</li>
          <li>В случае, если у Вас возникли проблемы при создании, обратитесь в <a href="/support/new" onclick="nav.go(this); return false">поддержку</a>.</li>
          </ul>
         </div>
         <div id="task_add_error" class="error_msg error"></div>
		  </div>
		  <div class="cupons_add_bg">
         <? if($cat_id <= 1) { ?>
         
         <div id="form_add_task">
          <div class="overflow_field">
           <div class="label">Код купона:</div>
           <div class="field">
            <input type="text" id="add_cup_code">
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_url_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_url">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите код купона</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_url').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Если у вас нет купона, то можете преобрести баллы, нажав на свой баланс. 
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
         </div>
		    
        <div id="add_task_save_hr"></div>
        <div id="add_task_save_body">
         <div class="body">
          <div onclick="cupons._actv({ssid: <? echo $usession; ?>})" id="add_task_button" class="blue_button_wrap"><div class="blue_button">Активировать</div></div>
          
         </div>
        </div>
         <? } elseif($cat_id == 2) { ?>
         
         <div id="form_add_task">
 
          <div class="overflow_field">
           <div class="label">Стоимость купона:</div>
           <div class="field">
            <input type="text" maxlength="5" id="add_cup_amount"><span id="amount_right" class="field_right">баллов</span>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_amount_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_amount">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите стоимость</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_amount').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                За активацию Вашего купона, пользователь получит на счет указанную сумму баллов.
                
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
         </div>
		     </div>
        <div id="add_task_save_hr"></div>
        <div id="add_task_save_body">
         <div class="body">
          <div onclick="cupons._add({ssid: <? echo $usession; ?>})" id="add_task_button" class="blue_button_wrap"><div class="blue_button">Создать купон</div></div>
          
         </div>
        </div>
		   <? } elseif($cat_id == 3) {
$cupons_user_num = $cupons->cupons_user_num();
		   ?>
         
          <div id="admin_complaints_content">
         <? if($cupons_user_num) { ?> 
         <div id="blacklist_bar">
          <div id="blacklist_bar_num">
           <? if(!$cupons_user_num) { ?>Ничего не найдено<? } else { ?>У вас <? echo $cupons_user_num.' '.declOfNum($cupons_user_num, array('купон', 'купонов', 'купонов')); ?><? } ?> 
          </div>
          <div id="blacklist_bar_page">
           <? echo pages(array('ents_count' => $cupons_user_num, 'ents_print' => 20, 'page' => $_GET['page'])); ?> 
          </div>
         </div>
         <table cellspacing="0" cellpadding="0" id="admin_tasks_blacklist_table"> 
          <tr>
           <td class="column column_url_user"><div>Купон</div></td>
           <td class="column column_status_user"><div>Активировал:</div></td>
		   <td class="column column_status_user"><div>Стоимость</div></td>
          </tr>
          <? echo $cupons->coupons_table_user(); ?> 
         </table>
         <? } else { ?> 
          <div id="my_complaints_none">Вы еще не создали купоны.</div>
         <? } ?> 
        </div>
		     </div>
        <div id="add_task_save_hr"></div>
       
         <? } ?> 
         
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