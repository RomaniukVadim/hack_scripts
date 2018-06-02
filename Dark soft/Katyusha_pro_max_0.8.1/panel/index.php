<?php
	include_once ("config.php");

	if ($_SESSION["logged"] != "YES")
		header ("Location: login.php");

    if (isset($_GET["page"]))
		$subpage = cleang("page");
	else
		$subpage = "stats";

	$page = "$subpage.php";

	include ("templates/index.php");
?>