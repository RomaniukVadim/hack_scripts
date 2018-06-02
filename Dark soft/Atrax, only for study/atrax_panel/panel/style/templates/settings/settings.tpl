
<div id="left" style="clear: right; width: 70%;">
 <p style="margin: 0px;">
    <h3>Installed settings</h3>
    <table id="tablecss">
      <tr>
        <th>Setting</th>
    	<th>Parameters</th>
        <th>Deinstall</th>
      </tr>
      {SettingsInstalled}
    </table>
 </p>
</div>

<div id="right">
  <p style="margin: 0px; width: 30%;">
    <h3>Available settings</h3>
    <table id="tablecss">
      <tr>
        <th>Setting</th>
        <th>Install</th>
      </tr>
      {SettingsAvailable}
    </table>
  </p>
</div>

<p class="clear">
    <h3>Inactive ({Inactive} <small>bots last 7 days</small>)</h3>
    <a href="index.php?action=settings&delinactive" onclick="return confirm('Are you sure you want to delete {Inactive} inactive bots?')" style="float: left;" class="btnred">Delete inactive bots</a>
   <a href="index.php?action=settings&resetpanel&csrf={CSRF}" onclick="return confirm('Are you sure you want to reset ALL statistics, settings, plugins and tasks?')" style="float: left; margin-left: 10px;" class="btnred">Reset panel</a>
</p>