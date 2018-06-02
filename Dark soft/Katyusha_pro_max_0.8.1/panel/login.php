<?php
	include_once ("config.php");

	if ($_SESSION["logged"] == "YES")
		header ("Location: index.php");

	$act = cleang("act");
	
	if ($act == "login")
	{
		if (!((cleanp("username") == $adminuser) && (cleanp("password") == $adminpass)))
			print "Не верное имя пользователя или пароль!";
		else
			$_SESSION["logged"] = "YES";
		exit;
	}

	if ($act == "logout")
	{
		@session_destroy();
		header ("Location: index.php");
	}

	include ("templates/login.php");
?>