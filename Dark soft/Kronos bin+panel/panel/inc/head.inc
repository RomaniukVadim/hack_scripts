<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">	
	<title><?php if(isset($_vars)) echo $_vars['title_of_the_pages'];?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Core stylesheets do not remove -->
    <link href="css/bootstrap/bootstrap.css" rel="stylesheet" />
    <link href="css/bootstrap/bootstrap-responsive.css" rel="stylesheet" />
    <link href="css/icons.css" rel="stylesheet" />

    <!-- Plugins stylesheets -->
    <link href="js/plugins/forms/uniform/uniform.default.css" rel="stylesheet" /> 

    <!-- app stylesheets -->
    <link href="css/app.css" rel="stylesheet" /> 

    <!-- Custom stylesheets ( Put your own changes here ) -->
    <link href="css/custom.css" rel="stylesheet" /> 

    <!--[if IE 8]><link href="css/ie8.css" rel="stylesheet" type="text/css" /><![endif]-->

    <!-- HTML5 shim, for IE6-8 support of HTML5 elements -->
    <!--[if lt IE 9]>
      </script><script src="js/html5shiv.js"></script></script>
    <![endif]-->

    
    <!-- Le javascript
    ================================================== -->
    <!-- Important plugins put in all pages -->
    <script src="js/jquery.min.js"></script>
    <script src="js/bootstrap/bootstrap.js"></script>  
    <script src="js/conditionizr.min.js"></script>  
    <script src="js/plugins/core/nicescroll/jquery.nicescroll.min.js"></script>
    <script src="js/plugins/core/jrespond/jRespond.min.js"></script>
    <script src="js/jquery.genyxAdmin.js"></script>

    <!-- Form plugins -->
    <script src="js/plugins/forms/uniform/jquery.uniform.min.js"></script>
    <script src="js/plugins/forms/validation/jquery.validate.js"></script>

    <!-- Init plugins -->
    <script src="js/app.js"></script><!-- Core js functions -->
    <script src="js/pages/login.js"></script><!-- Init plugins only for page -->

  </head>
  <body>
    <div class="container-fluid">