<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Lumino - Dashboard</title>

<link href="css/bootstrap.min.css" rel="stylesheet">
<link href="css/styles.css" rel="stylesheet">

<!--Icons-->
<script src="js/lumino.glyphs.js"></script>

<!--[if lt IE 9]>
<script src="js/html5shiv.js"></script>
<script src="js/respond.min.js"></script>
<![endif]-->

</head>

<body>
	<nav class="navbar navbar-inverse navbar-fixed-top" role="navigation">
		<div class="container-fluid">
			<div class="navbar-header">
				<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#sidebar-collapse">
					<span class="sr-only">Toggle navigation</span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
					<span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="#"><span>КАТЯ</span>&nbsp;0.8</a>
				<ul class="user-menu">
					<li class="pull-right">
						<a href="login.php?act=logout"><svg class="glyph stroked cancel"><use xlink:href="#stroked-cancel"></use></svg> Выйти</a>
					</li>
				</ul>
			</div>
							
		</div><!-- /.container-fluid -->
	</nav>
		
	<div id="sidebar-collapse" class="col-sm-3 col-lg-2 sidebar">
		<ul class="nav menu" id="main-navigation-menu">
			<li class="active" id="listats"><a href="#" onclick="showpage('stats.php')"><svg class="glyph stroked dashboard-dial"><use xlink:href="#stroked-dashboard-dial"></use></svg> Статистика</a></li>
			<li id="lirequests"><a href="#" onclick="showpage('requests.php')"><svg class="glyph stroked calendar"><use xlink:href="#stroked-calendar"></use></svg> SQLMap Инъекции</a></li>
			<li id="lireports"><a href="#" onclick="showpage('reports.php')"><svg class="glyph stroked line-graph"><use xlink:href="#stroked-line-graph"></use></svg> Файлы Репортов</a></li>
			<li id="lidumps"><a href="#" onclick="showpage('dumps.php')"><svg class="glyph stroked table"><use xlink:href="#stroked-table"></use></svg> Dumps</a></li>
			<li><a href="#" onclick="$('#input-file').click()"><svg class="glyph stroked download"><use xlink:href="#stroked-download"></use></svg> Загрузить Список</a></li>
		</ul>
		<hr size="1">
	</div><!--/.sidebar-->

	<input type="file" id="input-file" name="userfile" style="position: fixed; top: -100em"/>
