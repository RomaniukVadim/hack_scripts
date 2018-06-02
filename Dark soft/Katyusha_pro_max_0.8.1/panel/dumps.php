<?php
	$page_title = "Dumps";

	include_once ("config.php");

	if ($_SESSION["logged"] != "YES")
		header ("Location: login.php");


	$act = cleang ("act");

	if ($act == "view")
	{
		$fname = cleanp("fname");
		$dname = cleanp("dname");
		if ($dname != "") $fname = $dname."/".$fname;
		print file_get_contents ($kpath."sqlmap_dumps/".$fname);
		exit;
	}

	if ($act == "download")
	{
		$fname = cleang("fname");
		$dname = cleang("dname");
	   	header("Content-type: text/plain");
	   	header("Content-Disposition: attachment; filename=$fname");
		if ($dname != "") $fname = $dname."/".$fname;
		print file_get_contents ($kpath."sqlmap_dumps/".$fname);
		exit;
	}

	include ("templates/dumps.php");
?>