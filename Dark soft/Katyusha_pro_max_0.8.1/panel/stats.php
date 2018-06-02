<?php
	$page_title = "Статистика";

	include_once ("config.php");

	if ($_SESSION["logged"] != "YES")
		header ("Location: login.php");

	$act = cleang("act");

	if ($act == "refresh")
	{
		$f = parse_ini_file ($kpath."stats.txt");
		$out = "<svg class=\"glyph stroked eye\" style=\"width: 40px; height: 40px\"><use xlink:href=\"#stroked-eye\"/></svg> Сайтов в базе: ".$f["SITES_IN_DB"]."<hr width='300px'align='left'>";
		$out .= "<svg class=\"glyph stroked upload\" style=\"width: 40px; height: 40px\"><use xlink:href=\"#stroked-upload\"/></svg> Сайтов обработано: ".($f["SITES_LEFT"])."<hr width='300px'align='left'>";
		$out .= "<svg class=\"glyph stroked download\" style=\"width: 40px; height: 40px\"><use xlink:href=\"#stroked-download\"/></svg> Сайтов осталось: ".($f["SITES_IN_DB"]-$f["SITES_LEFT"])."<hr width='300px'align='left'>";
		$out .= "<svg class=\"glyph stroked gear\" style=\"width: 40px; height: 40px\"><use xlink:href=\"#stroked-gear\"/></svg> Reports Count: ".$f["REPORTS_COUNT"]."<hr width='300px'align='left'>";
		$out .= "<svg class=\"glyph stroked gear\" style=\"width: 40px; height: 40px\"><use xlink:href=\"#stroked-gear\"/></svg> Reports Requests: ".$f["REPORTS_REQUESTS"]."<hr width='300px'align='left'>";
		$out .= "<svg class=\"glyph stroked hourglass\" style=\"width: 40px; height: 40px\"><use xlink:href=\"#stroked-hourglass\"/></svg> Период Обновления Статистики: ".round($timeout/1000, 0)." сек.<br>";
		print $out;
		exit;
	}

	include ("templates/stats.php");
?>
