<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>{title}</title>
	<meta name="keywords" content="{keywords}" />
	<meta name="description" content="{description}" />
	<meta name="generator" content="FreeKeys CMS" />
	<meta http-equiv="content-type" content="text/html;UTF-8">
	<meta http-equiv="content-language" content="ru">
	<link href="{home-url}template/style.css" rel="stylesheet">
	{meta}
</head>

<body>

<div style="display:none">
	<script src="//mc.yandex.ru/metrika/watch.js" type="text/javascript"></script><script type="text/javascript">try { var yaCounter21435736 = new Ya.Metrika({id:21435736}); } catch(e) { }</script><noscript><div><img src="//mc.yandex.ru/watch/21435736" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
	<script type="text/javascript">document.write("<a href='http://www.liveinternet.ru/click' target=_blank><img src='//counter.yadro.ru/hit?t45.6;r" + escape(document.referrer) + ((typeof(screen)=="undefined")?"":";s"+screen.width+"*"+screen.height+"*"+(screen.colorDepth?screen.colorDepth:screen.pixelDepth)) + ";u" + escape(document.URL) + ";" + Math.random() + "' border=0 width=31 height=31 alt='' title='LiveInternet'><\/a>")</script>
</div>

<div class="wrapper">

	<header class="header">
		<div class="header-fix">
			<div class="logo"><a href="{home-url}">REF GOODS</a></div>

			<div class="menu">
				<a href="{home-url}">Главная</a>
				<a href="{home-url}faq.html">FAQ</a>
				<a href="{home-url}contacts.html">Контакты</a>
			</div>

			<div class="panel">
				[login]
					[login-type=usual]<a href="{home-url}list.html">Список подарков</a> | <a href="{home-url}exit.html">Выход</a>[/login-type]
					[login-type=email]<a href="{home-url}list.html">Список подарков</a> | <a href="{home-url}exit.html">Выход</a>[/login-type]
					[login-type=vk]<a href="{home-url}list.html">Список подарков</a> | <a href="{home-url}exit.html">Выход</a>[/login-type]
					[login-type=fb]<a href="{home-url}list.html">Список подарков</a> | <a href="{home-url}exit.html">Выход</a>[/login-type]
					[login-type=ip]<a href="{home-url}list.html">Список подарков</a>[/login-type]
				[/login]
				[no-login]
					[login-type=usual]<a href="{home-url}login.html">Вход</a> | <a href="{home-url}register.html">Регистрация</a>[/login-type]
					[login-type=email]<a href="{home-url}login.html">Вход</a> | <a href="{home-url}register.html">Регистрация</a>[/login-type]
					[login-type=vk]<a href="{vk-reg}">Войти через ВКонтакте</a>[/login-type]
					[login-type=fb]<a href="{fb-reg}">Войти через Facebook</a>[/login-type]
				[/no-login]
			</div>
		</div>
	</header>

	[notice]<div class="notice">{notice}</div>[/notice]

	<main class="content">
		[replace-content]<div class="replace_content">{replace-content}</div>[/replace-content]
		{content}
	</main>

	<footer class="footer">
		©	<script>
			(function() {
				var firstYear = 2015;
				var year = (new Date()).getFullYear();
				if (year !== firstYear) {
					year = '' + firstYear + '&ndash;' + year;
				}
				document.write(year);
			})();
			</script>
	</footer>

</div>

</body>
</html>