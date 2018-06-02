<?php

	if(LoggedUser()==false)   
		{ 
		header("location: login.php?redirect=".urlencode($_SERVER['REQUEST_URI'])); 
		exit;
		} 
		
		//Add the timestamp last online
		@mysql_query("update access SET knock_time=$time WHERE id=".$_SESSION['member_id']);
		
		?>