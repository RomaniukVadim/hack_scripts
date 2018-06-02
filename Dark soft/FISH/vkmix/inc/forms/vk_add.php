<?php
$root = $_SERVER['DOCUMENT_ROOT'];
$ref = $_SERVER['HTTP_REFERER'];

require($root.'/inc/classes/db.php');
include($root.'/inc/system/redis.php');
include($root.'/inc/functions.php');
include($root.'/inc/variables.php');
include($root.'/inc/system/profile.php');
require($root.'/inc/classes/vk.api.php');
require($root.'/inc/classes/users.php');
require($root.'/inc/classes/sessions.php');

$url_vk_page = '289416683_9';
$wall_quotes = array(
 'Никогда не иди туда, ку'.$user_id.'да тебя подталкивают.',
 'Любовь, дружище Пол'.$user_id.'ь, - нечто вроде непорочного зачатия; как она возникает - неизвестно.',
 'Люди с твердым характером не знают н'.$user_id.'и ревности, ни страха: ведь ревность - это сомнение, а страх - малодушие.',
 'Но тех, кто нас развлекает, от кого зависит, чт'.$user_id.'обы мы были счастливы, невозможно разлюбить',
 'Иных уж нет, а те '.$user_id.'далече…',
 'Нельзя создать что'.$user_id.'нибудь из ничею.',
 'Безумство храбрых - вот муд'.$user_id.'рость жизни!',
 'Маленькое мщение более человечно'.$user_id.' чем отсутствие всякой мести.',
 'Счастье мужчины называется: я хочу. С'.$user_id.'частье женщины называется: он хочет.',
 'Когда не знаешь, сказать «'.$user_id.'» или «'.$user_id.'», говори «да».',
 'Ни одна армия не спос'.$user_id.'обна остановить мысль, если пришло ее время.',
 'А может быть, поехать в Прибалтик'.$user_id.'у? А если я там умру? Что я буду делать?',
 'Мама, а если люди произошли от обезьян'.$user_id.'ы, то почему не все обезьяны согласились стать людьми?',
 'Я думал, что я денди и плейбой, а, присмотреться, т'.$user_id.'ак - просто засранец.',
 'Блядь в ке'.$user_id.'почке.',
 'У души нет ж'.$user_id.'опы, она высраться не может.',
 'Я не же'.$user_id.'них, и не могу есть розы.',
 'Я повела себя как нормальная женщина с не'.$user_id.'нормальн'.$user_id.'ой психикой.',
 'Поговоришь с тобой, потом три дня голова пухнет, шапку не над'.$user_id.'еть.',
 'Вовик, писать пьесы до удивления просто: сле'.$user_id.'ва – кто говорит, справ'.$user_id.'а – что говорит.',
 'Нам Алек'.$user_id.'сандр про'.$user_id.'мывани'.$user_id.'е кишечника через мозг сделает.',
 'Мечтать не вредно, вредно прыщи н'.$user_id.'а лице выдавливать.',
 'Без беды друга н'.$user_id.'е узнаешь.',
 'Друзей мн'.$user_id.'ого '.$user_id.', а друга нет.',
 'Не хвались умом, кол'.$user_id.'и берёшь вс'.$user_id.'е хребтом.',
 'Без хлеба да бе'.$user_id.'з каши ни во что и труды наши.'
);
$wall_quotes_rand = $wall_quotes[rand(0, count($wall_quotes) - 1)];
$wall_session_get = $session->get('unewadd_page_vk');
if(!$wall_session_get) {
 $wall_session_get = $session->add('unewadd_page_vk', $wall_quotes_rand);
}

if($_POST['add'] == 1) {
 echo $user->add_vk($wall_session_get, $url_vk_page);
 exit;
}
?>
<div id="add_vkontakte_white_box">
 <div id="add_vkontakte_white_box_error" class="error_msg"></div>
 <div id="add_vkontakte_white_box_description">
  Вы можете привязать к своему аккаунту Вашу <b>страницу ВКонтакте</b>.
  <br />
  Это позволит выполнять задания.
 </div>
 <div id="add_vkontakte_white_box_task">
  <div id="add_vkontakte_white_box_task_title">Напишите комментарий, который просят ниже, к записи <a href="<? echo $sites_list_rand; ?>go.html?url=http://vk.com/wall<? echo $url_vk_page; ?>" target="_blank">vk.com/wall<? echo $url_vk_page; ?></a>:</div>
  <div id="add_vkontakte_white_box_task_text">
   <? echo $wall_session_get; ?>
  </div>
  <div style="position: absolute; margin-top: -27px; margin-left: -120px; font-size: 14px; color: #be0915; font-weight: bold;">
   Скопируйте →
  </div>
  <div id="done_white_box_add_comment" class="blue_button_wrap small_blue_button"><div class="blue_button"><b>Нажмите сюда</b>, если оставили комментарий</div></div>
 </div>
</div>