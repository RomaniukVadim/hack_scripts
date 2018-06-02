<!--<p class="clear">
    <a href="index.php?action=tasks&addtask" style="float: left;" class="btngreen">Create new task</a>
</p>-->
<script type="text/javascript">
    function popup (url) {
        fenster = window.open(url, "", "width=500,height=500,resizable=yes");
        fenster.focus();
        return false;
    }
</script>

<table id="tablecss">
  <tr>
    <th>ID</th>
	<th>Command</th>
	<th>Parameter</th>
    <th>Countries</th>
    <th>Start</th>
    <th>Stop</th>
	<th>R / E / F / L*</th>
    <th>Action</th>
  </tr>
  {tasks}
</table>

<p class="clear">
    <a href="index.php?action=tasks&delalltasks" style="float: right;" class="btnred">Delete all finished tasks</a>
    <a href="index.php?action=tasks&addtask" style="float: right; margin-right: 10px;" class="btngreen">Create new task</a>
</p>

<p>
<div style="float: left;">
  <p><small>* =  Received / Executed / Failed / Limit</small></p>
  <span style="padding: 5px; height: 15px; width: 75px; background-color: #DCFFAD;">Not finished</span>
  <span style="padding: 5px; height: 15px; width: 75px; background-color: #FFC4C4;">Task finished</span>
</div>
</p>