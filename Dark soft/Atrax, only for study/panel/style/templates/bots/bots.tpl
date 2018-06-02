<div class="clear">
    <span style="float: left; width: 70%;"><form action="" method="post" style="display: inline;">{filterForm}</form></span>
    <a href="{navigationMainOfflineButton}" style="float: right; margin-left: 10px; padding: 8px;" class="btnnormal">{offlineShowHide} offline bots</a>
    <a href="{navigationMainExtendedButton}" style="float: right; padding: 8px;" class="btnnormal">Show Extended List</a>
</div>

<table id="tablecss">
  <tr>
    <th>IP</th>
    <th>GUID</th>
    <th>Build ID</th>
	<th>Region</th>
	<th>City</th>
	<th>PC Name</th>
	<th>OS</th>
  </tr>
  {bots}
</table>

<p>
<div style="float: left; font-weight: bold;">
    {PageNavigation}
</div>
<div style="float: right;">
  <span style="padding: 5px; height: 15px; width: 75px; background-color: #DCFFAD;">Bot free</span>
  <span style="padding: 5px; height: 15px; width: 75px; background-color: #FFEEEE;">Bot busy</span>
  <span style="padding: 5px; height: 15px; width: 75px; background-color: #FFC4C4;">Bot offline</span>
</div>
</p>