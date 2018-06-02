<script src="//ulogin.ru/js/ulogin.js"></script><div id="uLogin_bfdbf3d9" data-uloginid="bfdbf3d9"></div>

<?
$s = file_get_contents('http://ulogin.ru/token.php?token=' . $_POST['token'] .
    '&host=' . $_SERVER['HTTP_HOST']);
$user = json_decode($s, true);

echo $_POST['token'];

function preview(token){
    $.getJSON("//ulogin.ru/token.php?host=" + encodeURIComponent(location.toString()) + "&token=" + token + "&callback=?", function(data){
        data = $.parseJSON(data.toString());
        if(!data.error){
            alert("Привет, "+data.first_name+" "+data.last_name+"!");
        }
    });
}
?>