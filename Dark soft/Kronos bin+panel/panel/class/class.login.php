<?php

$username = mysql_real_escape_string($_POST['user']);
$password = md5($_POST['password']);
$security_numb = $_POST['security_number'];

		if(@strtolower($security_numb) !=strtolower($_SESSION['image_text']))
		{
	
		$Message = "You entered wrong security code";

		return false;
	
		}
		
		
		
	$result = mysql_query("			SELECT * FROM access WHERE (	username='$username' and pass='$password'		)		");
	if(mysql_num_rows($result)==1)
	{ 
		//true details
		$_SESSION['access'] = 'have';
		$field = mysql_fetch_row($result);
		$_SESSION['member_id'] = $field[0];
		$_SESSION['first_time'] = 'true';
		
		if($field[0]==1) $_SESSION['level_id'] = 1;
		
			header ("location: stats.php");
				die();
	}
			else
					{
						$Message = "Login or password failed";
						reloadIpList();
						
					}


?>