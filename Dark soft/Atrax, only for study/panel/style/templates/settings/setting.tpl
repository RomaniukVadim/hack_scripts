<h3 align="center">{notification}</h3>

<h3>Name</h3>
{Name}

<h3>Type</h3>
{Type}

<h3>Parameters</h3>
{Parameters}

<p class="clear">
  <form method="post">
    <input type="submit" name="submit" value="Install now" class="btngreen" style="margin-left: 50%;" />
    <input type="hidden" name="pname" value="{Name}" />
    <input type="hidden" name="ptype" value="{Type}" />
    <input type="hidden" name="pfname" value="{Pfname}" />
    <input type="hidden" name="pparams" value="{Parameters}" />
  </form>
</p>