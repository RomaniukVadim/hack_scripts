<?php
/** Example template file for TokenSpy
 * 
 * Available variables:
 * @var \lib\fun\TokenSpy\Page $page
 *      The page that's currently rendered.
 *      @see "system/lib/fun/TokenSpy/Page.php"
 * @var \lib\fun\TokenSpy\Template $template
 *      The template used with the page
 *      @see "system/lib/fun/TokenSpy/Template.php"
 * @var string $page_content
 *      Rendered page content, HTML
 * @var \Citadel\Models\TokenSpy\BotState $botState
 *      The current bot's state
 *      @see "system/lib/amiss/models/tokenspy.php"
 * @var \lib\fun\TokenSpy\gate\BotInfo $botInfo
 *      The current bot's info
 *      @see "system/lib/fun/TokenSpy/gate/BotInfo.php"
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta charset="utf-8">

    <title><?php echo $page->title; ?> -- Example 1</title>
    
    <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
    
    <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.min.css" rel="stylesheet">
    <script src="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/js/bootstrap.min.js"></script>
</head>
<body>

<div class="container" id="wrap">
    <header class="row">
        <div class="span2" id="logo">
            <img src="//www.google.com/intl/en/homepage/images/google_favicon_64.png" />
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
        background: #444;
        }
    #wrap {
        background: #EEE;
        box-shadow: 0px 0px 10px rgba(0,0,0,.5);
        
        color: #444;
        }
    #wrap > header {
        background: #DDD;
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
        background: #DDD;
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
