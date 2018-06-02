<!DOCTYPE html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<title><?php echo $_vars['title_of_the_pages']?></title>
	    <!-- Core stylesheets do not remove -->
		<link href="css/bootstrap/bootstrap.css" rel="stylesheet" />
		<link href="css/bootstrap/bootstrap-responsive.css" rel="stylesheet" />
		<link href="css/icons.css" rel="stylesheet" />

		<!-- Plugins stylesheets -->
		<link href="js/plugins/misc/fullcalendar/fullcalendar.css" rel="stylesheet" /> 
		<link href="js/plugins/forms/uniform/uniform.default.css" rel="stylesheet" /> 
		<link href="js/plugins/ui/jgrowl/jquery.jgrowl.css" rel="stylesheet" /> 

		<!-- app stylesheets -->
		<link href="css/app.css" rel="stylesheet" /> 

		<!-- Custom stylesheets ( Put your own changes here ) -->
		<link href="css/custom.css" rel="stylesheet" /> 

		<!--[if IE 8]><link href="css/ie8.css" rel="stylesheet" type="text/css" /><![endif]-->

		<!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
		<!--[if lt IE 9]>
		  <script src="js/html5shiv.js"></script>
		<!-- Le javascript
		================================================== -->
		<!-- Important plugins put in all pages -->
		<script src="js/jquery.min.js"></script>
		<script src="js/jquery-ui.min.js"></script>
		<script src="js/conditionizr.min.js"></script>  
		<script src="js/bootstrap/bootstrap.js"></script>  
		<script src="js/plugins/core/nicescroll/jquery.nicescroll.min.js"></script>
		<script src="js/plugins/core/jrespond/jRespond.min.js"></script>
		<script src="js/jquery.genyxAdmin.js"></script>

		<!-- Form plugins -->
		<script src="js/plugins/forms/uniform/jquery.uniform.min.js"></script>

		<!-- Misc plugins -->
		<script src="js/plugins/misc/fullcalendar/fullcalendar.min.js"></script>

		<!-- UI plugins -->

		<?php if(defined('STATS_TABLE')) { ?>
	
		<!-- Charts plugins -->
		<script src="js/plugins/charts/flot/jquery.flot.js"></script>
		<script src="js/plugins/charts/flot/jquery.flot.pie.js"></script>
		<script src="js/plugins/charts/flot/jquery.flot.resize.js"></script>
		<script src="js/plugins/charts/flot/jquery.flot.tooltip.min.js"></script>
		<script src="js/plugins/charts/flot/jquery.flot.orderBars.js"></script>
		<script src="js/plugins/charts/flot/jquery.flot.time.min.js"></script>
		<script src="js/plugins/charts/peity/jquery.peity.min.js"></script>
		<script src="js/plugins/charts/flot/date.js"></script>

		<script src="js/pages/charts.js"></script><!-- Init plugins only for page -->
		<?php }; ?>
	
		<!-- Init plugins -->
		<script src="js/app.js"></script><!-- Core js functions -->
		<script src="js/pages/dashboard.js"></script><!-- Init plugins only for page -->
		<script type="text/javascript" src="js/ajax.js"></script>
		<script type="text/javascript" src="js/ggg.js"></script>
	</head>
	<body>
		<header id="header" class="navbar navbar-inverse navbar-fixed-top">
			<div class="navbar-inner">
				<div class="container-fluid">
					<div class="nav-no-collapse">
					</div>
				</div>
			</div>
		</header> 
    
		<div class="main">