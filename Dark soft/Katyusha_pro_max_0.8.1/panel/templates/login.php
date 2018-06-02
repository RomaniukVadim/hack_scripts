<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Вход в панель управления</title>

<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/datepicker3.css" rel="stylesheet">
<link href="css/styles.css" rel="stylesheet">

<!--[if lt IE 9]>
<script src="js/html5shiv.js"></script>
<script src="js/respond.min.js"></script>
<![endif]-->

</head>

<body>
	
	<div class="row">
		<div class="col-xs-10 col-xs-offset-1 col-sm-8 col-sm-offset-2 col-md-4 col-md-offset-4">
			<div class="login-panel panel panel-default">
				<div class="panel-heading">Веедите данные для входа</div>
				<div class="panel-body">
						<fieldset>
							<div class="form-group">
								<input class="form-control" placeholder="Имя пользователя" id="username" autofocus="" type="text">
							</div>
							<div class="form-group">
								<input class="form-control" placeholder="Пароль" id="password" type="password" value="">
							</div>
							<button class="btn btn-primary">Войти</button>
						</fieldset>
				</div>
			</div>
		</div><!-- /.col-->
	</div><!-- /.row -->	
	
		

	<script src="js/jquery-1.11.1.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script>
		!function ($) {
			$(document).on("click","ul.nav li.parent > a > span.icon", function(){		  
				$(this).find('em:first').toggleClass("glyphicon-minus");	  
			}); 
			$(".sidebar span.icon").find('em:first').addClass("glyphicon-plus");
		}(window.jQuery);

		$(window).on('resize', function () {
		  if ($(window).width() > 768) $('#sidebar-collapse').collapse('show')
		})
		$(window).on('resize', function () {
		  if ($(window).width() <= 767) $('#sidebar-collapse').collapse('hide')
		})

		$("button").click(function(){
			var btn = $(this);
			btn.attr ("disabled", true);
			$.ajax({
				type: "POST",
				url: "login.php?act=login",
				data: "username=" + $("#username").val() + "&password=" + $("#password").val(),
				success: function(msg){
					if (msg)
					{
						alert (msg);
						btn.attr ("disabled", false);
					}
					else window.location.href="index.php";
				}
			})
		})
	</script>	
</body>

</html>
