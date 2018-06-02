[head=title]Профиль[/head]

[status=waiting]
	<h1>Привет, ID{id-profile}</h1><br>
	Твоя персональная ссылка: <a><b>{ref-link}</b></a> — именно эту ссылку ты должен распространять для того, чтобы набрать нужное количество переходов.<br><br>
	Чтобы получить подарок {name-game} тебе нужно собрать <script> var num = {all-visit}; if(num  == 1) { num = num + " переход"; } if(num  >= 2 && num  <= 4) { num = num + " перехода"; } if(num  >= 5) { num = num + " переходов"; } document.write(num); </script>, после чего система уточнит Ваш адрес для отправки Вам подарка {name-game}!<br><br>

	<progress value="{number-visits}" max="{all-visit}"></progress>

	<div class="progress_text">
		Ты набрал
		<script> var num = {number-visits}; if(num  == 1) { num = num + " переход"; } if(num  >= 2 && num  <= 4) { num = num + " перехода"; } if(num  >= 5) { num = num + " переходов"; } document.write(num); </script>
		, осталось набрать ещё <script> var num = {all-visit} - {number-visits}; if(num  == 1) { num = num + " переход"; } if(num  >= 2 && num  <= 4) { num = num + " перехода"; } if(num  >= 5) { num = num + " переходов"; } document.write(num); </script>
	</div>

	

	<div class="clr"></div>
	
	<br>{tasks}<br>{settings-profile}
[/status]

[status=obtaining]
	{tasks}<br>{settings-profile}<br>
	<form method="post"><input type="submit" name="keys" class="button_r" style="background:#1EACE1" value="Получить подарок" /></form>
	<br>
[/status]