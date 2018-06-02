<?php

define("TPL_TITLE", "Login");

if (isset($_POST["login"])) 
{
	foreach ($_POST as $k => $v) $_POST[$k] = trim($v);
	
	extract($_POST);
	
	$md5_pass = md5($pass);
	if (!CsrCheckAuth($user, $md5_pass)) {
		$errMes = 'Invalid authentication data.';
	} else {
		CsrCleanTmp();
		CsrSetCookie("user", $user, LIVE_AUTH_COOKIE);
		CsrSetCookie("pass", $md5_pass, LIVE_AUTH_COOKIE);
		header("Location: " . $_SERVER["REQUEST_URI"]);
	}
}

ob_start();
?>
<form class="form-horizontal" method='post'>
	<div class="row">
		User: <input class="form-control input-sm" type="text" name='user' value='<?=@$user?>'>
	</div>
	
	<div class="row">
		Password: <input type="password" class="form-control input-sm" name='pass' value='<?=@$pass?>'>
	</div>	
	
	<div class="row">
		<button type="submit" name='login' class="btn-sm btn-default">Enter</button> 
		<span class="text-danger controls">&nbsp&nbsp<?=@$errMes?></span>
	</div>
</form>

<?php
$content = ob_get_contents();
ob_end_clean();

$header_tpl = file_get_contents(TPL_PATH . "header.html");
$header_tpl = str_replace(array("{TPL}", "{SCRIPTS}", "{TITLE}"), array(TPL_PATH, "", TPL_TITLE), $header_tpl);

$body_tpl = file_get_contents(TPL_PATH . "body.html");
$body_tpl = str_replace(array("{MENU}", "{BODY_CLASS}", "{CONTENT}"), array('', "login", $content), $body_tpl);
$footer_tpl = file_get_contents(TPL_PATH . "footer.html");

echo str_replace(array("{HEADER}", "{BODY}", "{FOOTER}"), array($header_tpl, $body_tpl, $footer_tpl), file_get_contents(TPL_PATH . "main.html"));
?>