<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8" />
    <link rel="stylesheet" href="style/css/style.css" type="text/css" />
    <link rel="shortcut icon" href="images/other/favicon.ico" type="image/x-icon" />
</head>
<body>
<div id="container">
    <div id="content">
        <h3>Statistic:</h3>
        <p><label style="width: 5em;">All:</label> {All}</p>
        <p><label style="width: 5em;">Success:</label> {Success}</p>
        <p><label style="width: 5em;">Failed:</label> {Failed}</p>

        <table id="tablecss" style="width: 50%;">
            <tr>
                <th>GUID</th>
                <th>Status</th>
            </tr>
        {Logs}
        </table>
    </div>
</div>
</body>
</html>