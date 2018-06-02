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

<h3>Edit task with ID '{ID}'</h3>

<form method="post">
    <p><label style="width: 6em;">Countries:</label> <input type="text" name="countries" value="{Countries}" /></p>
    <p><label style="width: 6em;">Command:</label> <input type="text" name="command" value="{Command}" /></p>
    <p><label style="width: 6em;">Parameter:</label> <input type="text" name="parameter" value="{Parameter}" /></p>
    <p><label style="width: 6em;">Start:</label> <input type="text" {Disabled} class="picker" name="start" value="{Start}" /></p>
    <p><label style="width: 6em;">Stop:</label> <input type="text" {Disabled} class="picker" name="stop" value="{Stop}" /></p>
    <p><label style="width: 6em;">Count:</label> <input type="text" name="count" value="{Count}" /></p>

    <input type="hidden" name="id" value="{ID}" />
    <input type="submit" name="submit" value="Save" class="btngreen" />
</form>