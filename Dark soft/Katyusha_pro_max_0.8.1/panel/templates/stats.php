		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading" id="div-title"></div>
					<div class="panel-body" width="100%">
						<h3 id="hstats"></h3>
					</div>
				</div>
				<pre style="margin: 5px; height: 400px" id="logfile">Log file here</pre>
			</div>
		</div><!--/.row-->
<script>
	function pageloaded()
	{
		function refreshStats()
		{
			$.ajax({
				type: "GET",
				url: "stats.php?act=refresh",
				success: function(msg){
					$("#hstats").html(msg);
					tmt = setTimeout(refreshStats, <?php print $timeout?>);
				}
			})
		}

		refreshStats();

		tmt = setTimeout(refreshStats, <?php print $timeout?>);

		$("#page-title").text('<?php print $page_title?>');
		$("#div-title").text('<?php print $page_title?>');
		$("title").text ('Панель управления - <?php print $page_title?>');

		$("#main-navigation-menu .active").removeClass("active");
	    $("#listats").addClass("active");

		function readlog ()
		{
			$.ajax({
				type: "GET",
				url: "readlog.php",
				success: function(msg){
					$("#logfile").html(msg);
					setTimeout (readlog, <?php print $timeoutlog?>);
				}
			})
		}

	setTimeout (readlog, <?php print $timeoutlog?>);

	readlog ();
	}
</script>