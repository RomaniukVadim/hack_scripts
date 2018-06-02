<?

session_start();
header("Content-Type: text/html; charset=utf-8");

$nameDB = "a0181684_belka";//Название БД
$nameSERVER = "localhost";//Сервер
$nameUSER = "a0181684_belka";//Имя пользователя БД
$passUSER = "Sharuhanchik";//Пароль пользователя БД
@mysql_select_db($nameDB, mysql_connect($nameSERVER,$nameUSER,$passUSER));	
//ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ (БД)

$db_table_to_show = 'settings';
$result = mysql_query("select * from " . $db_table_to_show);
$myrow = @mysql_fetch_array($result);
$pass = $myrow[pass_admin];

if(empty($_SESSION['admin'])){
if(isset($_POST['login']) AND $_POST['pass']){	

if($_POST['login'] == 'admin' AND $_POST['pass'] == $pass){
$_SESSION['admin'] = 'admin';
header('Location: /admin/vk.php');
exit;
}else{echo "<div style='background:white; width:500px; margin:0 auto; height:100px; border-radius:10px; border:1px solid #ccc; margin-top:50px'><center><br><br>Неверный логин или пароль. Попробуйте ещё раз</center></div>";}
}
?>

<style>

.login-page {
  width: 360px;
  padding: 10% 0 0;
  margin: auto;
  font-family: "Arial", sans-serif;
}

.form {
  position: relative;
  z-index: 1;
  background: #FFFFFF;
  max-width: 360px;
  margin: 0 auto 100px;
  padding: 35px;
  text-align: center;
  box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.1), 0 5px 5px 0 rgba(0, 0, 0, 0.1);
}

.form input {
  background: #f2f2f2;
  width: 100%;
  border: 0;
  margin: 0 0 15px;
  border:1px solid #ccc;
  padding: 15px;
  box-sizing: border-box;
  font-size: 14px;
}
.form button {
  text-transform: uppercase;
  outline: 0;
  background: #4CAF50;
  border:1px solid #4CAF50;
  width: 100%;
  border: 0;
  padding: 15px;
  color: #FFFFFF;
  font-size: 14px;
  -webkit-transition: all 0.3 ease;
  transition: all 0.3 ease;
  cursor: pointer;
}
.form button:hover,.form button:active,.form button:focus {
  background: #43A047;
}
.form .message {
  margin: 15px 0 0;
  color: #b3b3b3;
  font-size: 12px;
}
.form .message a {
  color: #4CAF50;
  text-decoration: none;
}
.form .register-form {
  display: none;
}
.container {
  position: relative;
  z-index: 1;
  max-width: 300px;
  margin: 0 auto;
}
.container:before, .container:after {
  content: "";
  display: block;
  clear: both;
}
.container .info {
  margin: 50px auto;
  text-align: center;
}
.container .info h1 {
  margin: 0 0 15px;
  padding: 0;
  font-size: 36px;
  font-weight: 600;
  color: #1a1a1a;
}
.container .info span {
  color: #4d4d4d;
  font-size: 12px;
}
.container .info span a {
  color: #000000;
  text-decoration: none;
}
.container .info span .fa {
  color: #EF3B3A;
}
body {
background-image: url(bg1.jpg);  
}

</style>

<title>Вход</title>

<div class="login-page" style="text-align:center;">
  <div class="form">
  <br>
    <form class="login-form" method="post" action="">
      <input type="text" name="login" placeholder="Логин"/>
      <input type="password" name="pass" placeholder="Пароль"/>
      <button type="submit">Войти</button>
    </form>
  </div>
</div>


<?
exit;
}
?>