<?php
/** Example skeleton file for TokenSpy
 * Each variable, defines in `info.php`, can be used with one of the following formats:
 *      ((ts:VARIABLE_NAME))        Inserted as is
 *      <ts:VARIABLE_NAME>          Inserted with HTML escaped
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta charset="utf-8">

    <title><?php echo $page->title; ?> -- <ts:title></title>
    
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    
    <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.min.css" rel="stylesheet">
    <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container" id="wrap">
    <header class="row">
        <div class="span2" id="logo">
            <img src="((ts:logo))" />
        </div>
        <div class="span8" id="title">
            <!--[ TITLE ]-->
            <h1><?php echo $page->title; ?></h1>
        </div>
    </header>

    <section class="row">
        <div class="span11" id="main">
            <!--[ BODY ]-->
            <?php echo $page_content; ?>
        </div>
    </section>

    <div id="push"></div>
</div>

<footer class="container">
    <div id="footer">
        &copy; <?php echo htmlspecialchars($botInfo->domain), ' ', date('Y'); ?>. All rights reserved.
    </div>
</footer>






<style type="text/css">
    /* Inline your styles */
    body {
        background: ((ts:background_base));
        }
    #wrap {
        background: ((ts:background_page));
        box-shadow: 0px 0px 10px rgba(0,0,0,.5);
        
        color: ((ts:text_color));
        }
    #wrap > header {
        background: ((ts:background_header));
        box-shadow: 0px 2px 3px rgba(0,0,0,.5);
        margin: 0;
        }
    #wrap > header #logo {}
    #wrap > header #title {}
    #wrap > section {}
    #wrap > section #main {
        padding: 10px 30px;
        }
    #wrap ~ footer {
        background: ((ts:background_header));
        box-shadow: 0px -2px 3px rgba(0,0,0,.5);
        }
    #wrap ~ footer #footer {
        line-height: 60px; text-align: center;
    }

    /* sticky footer */
    html, body { height: 100%;}
    #wrap { min-height: 100%; height: auto !important; height: 100%; margin: 0 auto -60px; }
    #wrap > #push, #wrap ~ footer { height: 60px; }
</style>

<script>
    /* Inline your scripts */
    $(function(){

    });
</script>

</body>
</html>
