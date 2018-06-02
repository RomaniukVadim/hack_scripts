<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8" />
    <title>Atrax - {active}</title>
    <link rel="stylesheet" href="style/css/style.css" type="text/css" />
    <script src="inc/js/amcharts.js" type="text/javascript"></script>
    <link rel="shortcut icon" href="images/other/favicon.ico" type="image/x-icon" />
  </head>
  <body>
  <div id="container">
  <div id="logo"><a href="index.php?action=statistics"><img src="images/other/logo.png" alt="" /></a></div>
  <div id="content">
    <div style="float: left; color: #666;"><i>{date}</i></div><div style="text-align: right; margin-bottom: -8px;"><strong>All:</strong> {all} | <strong>Offline:</strong> {off} | <strong>Online:</strong> {on} | <strong>Today new:</strong> {today} | <strong>Last week:</strong> {lastseven}</div>
    <p><div id="navigation">{navigation} <span class="activeNavElement"><img src="images/other/home.png" /> {active}</span></div></p>
    {content}
  </div>
  </div>
  </body>
</html>