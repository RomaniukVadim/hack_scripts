<link rel="stylesheet" media="all" type="text/css" href="http://code.jquery.com/ui/1.9.1/themes/smoothness/jquery-ui.css" />
<link rel="stylesheet" media="all" type="text/css" href="inc/js/jquery-ui-timepicker-addon.css" />

<script type="text/javascript" src="http://code.jquery.com/jquery-1.8.2.min.js"></script>
<script type="text/javascript" src="http://code.jquery.com/ui/1.9.1/jquery-ui.min.js"></script>
<script type="text/javascript" src="inc/js/jquery-ui-timepicker-addon.js"></script>

<script type="text/javascript">
$(function(){
	$('.picker').datetimepicker({
	    dateFormat: "yy-mm-dd",
		timeFormat: "HH:mm:ss"
	});
});
</script>

<h3>Over time</h3>
<p>Start:</p>
<input type="text" name="start" class="picker" value="{date}" />

<p>Stop:</p>
<input type="text" name="stop" class="picker" value="{date}" />
<!--<p>Start:</p> <input type="text" id="start" name="start" value="{date}" />
<p>Stop:</p> <input type="text" id="stop" name="stop" value="{date}" />-->
<input type="hidden" name="overtime" value="yes" />