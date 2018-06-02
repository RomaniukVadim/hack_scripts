<p>
    <a href="index.php?action=statistics&pluginstats" class="btnnormal">Plugin statistic</a>
</p>

<div id="left" style="clear: right;">
 <p style="margin: 0px;">
    <h3>Top 10 countries</h3>
    <table id="tablecss" style="border: 1px solid #42464F;">
      <tr>
        <th>Country</th>
    	<th>Amount</th>
      </tr>
      {Countries}
    </table>
 </p>
</div>

<div id="right">
  <p style="margin: 0px;">
    <h3>Operating systems</h3>
    <table id="tablecss" style="border: 1px solid #42464F;">
    <tr>
        <th>Operating system</th>
    	<th>Amount</th>
    </tr>
    {OS}
    </table>
  </p>
</div>

<div id="mitte">
  <p style="margin: 0px;">
    <h3>Last 10 infections</h3>
    <table id="tablecss" style="border: 1px solid #42464F;">
      <tr>
        <th>Country</th>
        <th>City</th>
    	<th>Date</th>
      </tr>
     {LastInfections}
    </table>
  </p>
</div>

<br style="clear:both;">