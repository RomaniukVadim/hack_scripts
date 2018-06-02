[head=title]Реферал[/head]
[head=meta]
	<meta name="title" content="Большая раздача ключей {name-game-ref}">
	<meta name="description" content="Я уже получил свой ключ для {name-game-ref}! А ты?">
	<link rel="image_src" href="{home-url}template/game/{link-game}.png">
	<script src="http://vk.com/js/api/share.js?90" charset="windows-1251"></script>
[/head]

<h1>Реферал #{id_ref}</h1><br>

<progress value="{ref_transitions}" max="{ref-num}"></progress>

<div class="progress_text center">
	<script>
		var num = {ref_transitions};
		if(num  >= {ref-num}) { num = "Пользователь набрал все переходы"; }
		if(num  == 1) { num = "Уже набран " + num + " переход"; }
		if(num  >= 2 && num  <= 4) { num = "Уже набрано " + num + " перехода"; }
		if(num  >= 5) { num = "Уже набрано " + num + " переходов"; }
		document.write(num);
	</script>
</div><br>

<div class="right">
	<script type="text/javascript" src="//yandex.st/share/share.js" charset="utf-8"></script>
	<div class="yashare-auto-init" data-yashareL5n="ru" data-yashareQuickServices="vkontakte,facebook,twitter,gplus" data-yashareTheme="counter"></div> 
</div>
<b>Q:</b> Что это?<br><b>A:</b> Это - реферальная ссылка одного из участников нашего сервиса<br><br>
<b>Q:</b> Как такую получить?<br><b>A:</b> Легко! <a href="{home-url}register.html">Регистрация</a>

[no-login]<br><br><a href="{home-url}register.html"><div class="button_r center" style="background:#1EACE1">Получить свой ключ</div>[/no-login]