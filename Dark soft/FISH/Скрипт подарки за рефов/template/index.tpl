[head=title]{home-title}[/head]
[head=keywords]{main-keywords}[/head]
[head=description]{main-description}[/head]
[head=meta]
	<meta name="title" content="{home-title}">
	<meta name="description" content="{main-description}">
	<link rel="image_src" href="{home-url}template/images/logo.png">

	<meta property="og:title" content="{home-title}" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="{home-url}" />
	<meta property="og:image" content="{home-url}template/images/logo.png" />
[/head]

<h1>Бесплатные подарки за приглашение друзей</h1><br>

Добро пожаловать на сервис предоставляющий возможность получения бесплатных подарков. Мы существуем 
<script>
(function() {
	var firstYear = 2015;
	var year = (new Date()).getFullYear();
	if (year !== firstYear)
	{
		year = year - firstYear;
	}
	if(year == 1) { year = year + ' год'; }
	if(year >= 2 && year <= 4) { year = year + ' года'; }
	if(year >= 5) { year = year + ' лет'; }
	document.write(year);
})();
</script>
и за это время в нашей программе приняло участие {number-users} человек из них {sold-games} получили свой долгожданный подарок, именно поэтому Вам стоит нам доверять.
<br><br> Как же работает наша система?
Мы приобретаем раз в месяц подарки за деньги рекламодателей, получаем от партнёров, акциях.
В среднем за месяц на сайте прибавляется 340 подарков разных типов которые Вы можете получить. Те участники которые не успели получить подарок в этом месяце встают в очередь и вначале следующего месяца первыми получают свой ключ. Любые вопросы и ошибки пишите на наш email admin.ref.goods@gmail.com

<div class="clr"></div>

[no-login]
<br>
<a href="register.html"><div class="button_r center">Зарегистрироваться</div></a>
[/no-login]


[login]<div class="replace_content">Выберете подарок для его получения:</div>[/login]
<div class="blocks_game">{list}</div>

<div class="clr"></div>