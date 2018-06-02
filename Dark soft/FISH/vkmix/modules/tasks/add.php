<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$page_name = 'add_task';

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
include($root.'/inc/system/profile.php');
include($root.'/inc/system/profile_redirect.php');
require($root.'/inc/classes/sessions.php');
include($root.'/inc/system/usession.php');
require($root.'/inc/classes/tasks.php');
require($root.'/inc/classes/tasks_categories.php');
require($root.'/inc/classes/tasks_blacklist.php');

$cat_id = $tasks->getCatNum($_GET['section']); // id категории
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html>
 <head>
  <title>Новое задание</title>
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
         <a<? if($cat_id <= 1) echo ' class="active"'; ?> href="/tasks/add?section=likes" onclick="nav.go(this); return false;"><div class="tabdiv">Мне нравится</div></a>
         <a<? if($cat_id == 2) echo ' class="active"'; ?> href="/tasks/add?section=reposts" onclick="nav.go(this); return false;"><div class="tabdiv">Рассказать друзьям</div></a>
         <a<? if($cat_id == 3) echo ' class="active"'; ?> href="/tasks/add?section=comments" onclick="nav.go(this); return false;"><div class="tabdiv">Комментарии</div></a>
         <a<? if($cat_id == 4) echo ' class="active"'; ?> href="/tasks/add?section=friends" onclick="nav.go(this); return false;"><div class="tabdiv">Друзья</div></a>
         <a<? if($cat_id == 5) echo ' class="active"'; ?> href="/tasks/add?section=groups" onclick="nav.go(this); return false;"><div class="tabdiv">Сообщества</div></a>
         <a<? if($cat_id == 6) echo ' class="active"'; ?> href="/tasks/add?section=polls" onclick="nav.go(this); return false;"><div class="tabdiv">Опросы</div></a>
        </div>
        <div id="task_add_bg">
         <div id="mini_rules_add_task">
         <ul>
          <li>Вы можете создавать задание с <b>закрытой группой</b>.</li>
          <li>За создание задания взимается комиссия <b>5%</b>.</li>
          <li>Мы также автоматически фильтруем вредоносные задания и исключаем их из общего списка.</li>
          <li>В случае, если у Вас возникли проблемы при создании, обратитесь в <a href="/support/new" onclick="nav.go(this); return false">поддержку</a>.</li>
          </ul>
         </div>
         <div id="task_add_error" class="error_msg error"></div>
         <? if($cat_id <= 1) { ?>
         
         <div id="form_add_task">
          <div class="overflow_field">
           <div class="label">Ссылка:</div>
           <div class="field">
            <input type="text" id="add_task_url">
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_url_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_url">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите ссылку</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_url').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Вы можете указать ссылку на запись, комментарий, фото или видео.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Стоимость выполнения:</div>
           <div class="field">
            <input type="text" maxlength="2" id="add_task_amount"><span id="amount_right" class="field_right">баллов</span>
            <div class="tooltip_field_append">Рекомендованное значение – от <b>1</b> до <b>3-х</b> баллов</div>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_amount_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_amount">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите стоимость</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_amount').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                За выполнение Вашего задания, пользователь получит на счет указанную сумму баллов.
                <br /> <br />
                Рекомендованное значение – от <b>1</b> до <b>3-х</b> баллов.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Количество:</div>
           <div class="field">
            <input type="text" maxlength="5" id="add_task_count"><span id="count_right" class="field_right">отметок «Мне нравится»</span>
            <div class="tooltip_field_append">Рекомендованное значение – от <b>5</b> до <b>10000</b> отметок</div>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_count_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_count">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите количество</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_count').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Укажите, скольким людям нужно выполнить Ваше задание.
                <br /> <br />
                Рекомендованное значение – от <b>5</b> до <b>10000</b> отметок.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
         </div>
         <? } elseif($cat_id == 2) { ?>
         
         <div id="form_add_task">
          <div class="overflow_field">
           <div class="label">Ссылка:</div>
           <div class="field">
            <input type="text" id="add_task_url">
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_url_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_url">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите ссылку</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_url').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Вы можете указать ссылку на запись, фото или видео.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Стоимость выполнения:</div>
           <div class="field">
            <input type="text" maxlength="2" id="add_task_amount"><span id="amount_right" class="field_right">баллов</span>
            <div class="tooltip_field_append">Рекомендованное значение – от <b>3-х</b> до <b>6</b> баллов</div>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_amount_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_amount">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите стоимость</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_amount').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                За выполнение Вашего задания, пользователь получит на счет указанную сумму баллов.
                <br /> <br /> Рекомендованное значение – от <b>3-х</b> до <b>6</b> баллов.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Количество:</div>
           <div class="field">
            <input type="text" maxlength="5" id="add_task_count"><span id="count_right" class="field_right">репостов</span>
            <div class="tooltip_field_append">Рекомендованное значение – от <b>5</b> до <b>10000</b> репостов</div>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_count_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_count">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите количество</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_count').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Укажите, скольким людям нужно выполнить Ваше задание.
                <br /> <br />
                Рекомендованное значение – от <b>5</b> до <b>10000</b> репостов.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
         </div>
         <? } elseif($cat_id == 3) { ?>
         
         <div id="form_add_task">
          <div class="overflow_field">
           <div class="label">Ссылка:</div>
           <div class="field">
            <input type="text" id="add_task_url">
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_url_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_url">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите ссылку</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_url').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Вы можете указать ссылку на запись, фото или видео.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Комментарий:</div>
           <div class="field">
            <input class="add_task_comment" type="text">
            <input type="hidden" id="add_task_comments_value" type="text">
            <div id="add_task_comment_form"></div>
            <div onclick="tasks._add_comment_field();" id="add_task_comment_add_button" class="blue_button_wrap">
             <div class="blue_button">[+] новый комментарий</div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Стоимость выполнения:</div>
           <div class="field">
            <input type="text" maxlength="2" id="add_task_amount"><span id="amount_right" class="field_right">баллов</span>
            <div class="tooltip_field_append">Рекомендованное значение – от <b>3-х</b> до <b>6</b> баллов</div>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_amount_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_amount">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите стоимость</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_amount').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                За выполнение Вашего задания, пользователь получит на счет указанную сумму баллов.
                <br /> <br />
                Рекомендованное значение – от <b>3-х</b> до <b>6</b> баллов.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Количество:</div>
           <div class="field">
            <input type="text" maxlength="5" id="add_task_count"><span id="count_right" class="field_right">комментариев</span>
            <div class="tooltip_field_append">Рекомендованное значение – от <b>5</b> до <b>10000</b> комментариев</div>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_count_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_count">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите количество</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_count').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Укажите, скольким людям нужно выполнить Ваше задание.
                <br /> <br />
                Рекомендованное значение – от <b>5</b> до <b>10000</b> комментариев.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
         </div>
         <? } elseif($cat_id == 4) { ?>
         
         <div id="form_add_task">
          <div class="overflow_field">
           <div class="label">Ссылка:</div>
           <div class="field">
            <input type="text" id="add_task_url">
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_url_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_url">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите ссылку</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_url').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Укажите ссылку на человека, на которого необходимо подписаться.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Стоимость выполнения:</div>
           <div class="field">
            <input type="text" maxlength="2" id="add_task_amount"><span id="amount_right" class="field_right">баллов</span>
            <div class="tooltip_field_append">Рекомендованное значение – от <b>2-х</b> до <b>4-х</b> баллов</div>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_amount_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_amount">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите стоимость</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_amount').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                За выполнение Вашего задания, пользователь получит на счет указанную сумму баллов.
                <br /> <br />
                Рекомендованное значение – от <b>2-х</b> до <b>4-х</b> баллов.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Количество:</div>
           <div class="field">
            <input type="text" maxlength="5" id="add_task_count"><span id="count_right" class="field_right">подписчиков</span>
            <div class="tooltip_field_append">Рекомендованное значение – от <b>5</b> до <b>10000</b> подписчиков</div>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_count_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_count">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите количество</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_count').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Укажите, скольким людям нужно выполнить Ваше задание.
                <br /> <br />
                Рекомендованное значение – от <b>5</b> до <b>10000</b> подписчиков.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
         </div>
         <? } elseif($cat_id == 5) { ?>
         
         <div id="form_add_task">
          <div class="overflow_field">
           <div class="label">Ссылка:</div>
           <div class="field">
            <input type="text" id="add_task_url">
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_url_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_url">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите ссылку</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_url').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Укажите ссылку на группу или паблик, в который необходимо вступить.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Стоимость выполнения:</div>
           <div class="field">
            <input type="text" maxlength="2" id="add_task_amount"><span id="amount_right" class="field_right">баллов</span>
            <div class="tooltip_field_append">Рекомендованное значение – от <b>2-х</b> до <b>4-х</b> баллов</div>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_amount_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_amount">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите стоимость</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_amount').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                За выполнение Вашего задания, пользователь получит на счет указанную сумму баллов.
                <br /> <br />
                Рекомендованное значение – от <b>2-х</b> до <b>4-х</b> баллов.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Количество:</div>
           <div class="field">
            <input type="text" maxlength="5" id="add_task_count"><span id="count_right" class="field_right">вступивших</span>
            <div class="tooltip_field_append">Рекомендованное значение – от <b>5</b> до <b>10000</b> вступивших</div>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_count_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_count">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите количество</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_count').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Укажите, скольким людям нужно выполнить Ваше задание.
                <br /> <br />
                Рекомендованное значение – от <b>5</b> до <b>10000</b> вступивших.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
         </div>
         <? } elseif($cat_id == 6) { ?>
         
         <div id="form_add_task">
          <div class="overflow_field">
           <div class="label">Ссылка:</div>
           <div class="field">
            <input type="text" id="add_task_url">
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_url_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_url">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите ссылку</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_url').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Укажите ссылку на <b>запись на стене</b>, в которой присутствует опрос.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Проголосовать за:</div>
           <div class="field">
            <input id="add_task_comments_value" type="text"><span class="field_right">-й вариант в опросе</span>
            <div class="tooltip_field_append">Это поле является необязательным</div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Стоимость выполнения:</div>
           <div class="field">
            <input type="text" maxlength="2" id="add_task_amount"><span id="amount_right" class="field_right">баллов</span>
            <div class="tooltip_field_append">Рекомендованное значение – от <b>1</b> до <b>4-х</b> баллов</div>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_amount_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_amount">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите стоимость</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_amount').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                За выполнение Вашего задания, пользователь получит на счет указанную сумму баллов.
                <br /> <br />
                Рекомендованное значение – от <b>1</b> до <b>4-х</b> баллов.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
          <div class="overflow_field">
           <div class="label">Количество:</div>
           <div class="field">
            <input type="text" maxlength="5" id="add_task_count"><span id="count_right" class="field_right">голосов</span>
            <div class="tooltip_field_append">Рекомендованное значение – от <b>5</b> до <b>10000</b> голосов</div>
            <div class="big_tooltip_wrap_border big_tooltip_wrap_w" id="tooltip_task_add_count_c">
             <div class="big_tooltip_wrap" id="tooltip_task_add_count">
              <div class="big_tooltip_narrow"><div class="big_tooltip_narrow_c"></div></div>
              <div class="big_tooltip">
               <div class="big_tooltip_head">
                <div class="big_tooltip_head_title">Введите количество</div>
                <div class="big_tooltip_head_closed"><div onclick="$('#tooltip_task_add_count').remove()" class="icons_tab icons_tab_del1"></div></div>
               </div>
               <div class="big_tooltip_message">
                Укажите, скольким людям нужно выполнить Ваше задание.
                <br /> <br />
                Рекомендованное значение – от <b>5</b> до <b>10000</b> голосов.
               </div>
              </div>
             </div>
            </div>
           </div>
          </div>
         </div>
         <? } ?> 
         
        </div>
        <div id="add_task_save_hr"></div>
        <div id="add_task_save_body">
         <div class="body">
          <div onclick="tasks._add('<? echo $cat_name; ?>', {ssid: <? echo $usession; ?>})" id="add_task_button" class="blue_button_wrap"><div class="blue_button">Создать задание</div></div>
          <div id="add_task_save_body_points_result">— <b>0</b> баллов</div>
         </div>
        </div>
        <div id="tasks_my_categories_hide">["0", <? echo json_encode('- Не выбрано -'); ?>]<? echo $tasks_categories->my_select(array('uid' => $user_id)); ?></div>
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