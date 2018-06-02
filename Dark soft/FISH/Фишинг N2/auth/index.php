<?php
	require_once 'detect.php';
	$detect = new Mobile_Detect;
 

	if ( $detect->isMobile() ) {
		include "mobile.php";
	}
		else{
				include("pc.php");
			}
?>