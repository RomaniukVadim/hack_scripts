            <div id="div-file" class="modal fade">
              <div class="modal-dialog">
	              <div class="modal-content">
		              <div class="modal-header">
            			    <h4><font color=black id="filename"> </font></h4>
			          </div>
            		  <div class="modal-body">

						<pre id="filebody">
						</pre>

						<div class="row">
				            <div class="control-group col-lg-12"> 
									<hr size="1">
									<button class="btn btn-info pull-right" data-dismiss="modal"> Close </button>
					              </div>
				            </div>
						</div>
				            </div>
						</div>

					</div>

		<div class="row">
			<div class="col-lg-12">
				<div class="panel panel-default">
					<div class="panel-heading" id="div-title"></div>
					<div class="panel-body" width="100%">
						<table class="table table-stripped">
							<tbody>
<?php
	$files = getFiles ($kpath."reports");
	foreach ($files as $file):
?>
<tr>
	<td><?php print $file?></td>
	<td><button class="btn btn-info btn-open" file-name="<?php print $file?>">Открыть</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button class="btn btn-info btn-save" file-name="<?php print $file?>">Скачать</button></td>
</tr>
<?php
	endforeach;
?>
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div><!--/.row-->
<script>
	function pageloaded()
	{
		$(document).off("click", ".btn-open").on("click", ".btn-open", function(){
			var fname = $(this).attr("file-name");
			$.ajax({
				type: "POST",
				url: "reports.php?act=view",
				data: "fname=" + fname,
				success: function(msg){   
					$("#filename").text(fname);
					$("#filebody").text(msg);
					$("#div-file").modal("show");
				}
			})
		})

		$(document).off("click", ".btn-save").on("click", ".btn-save", function(){
			var fname = $(this).attr("file-name");
			window.location.href = "reports.php?act=download&fname=" + fname;
		})

		$("#page-title").text('<?php print $page_title?>');
		$("#div-title").text('<?php print $page_title?>');
		$("title").text ('Панель управления - <?php print $page_title?>');

		$("#main-navigation-menu .active").removeClass("active");
	    $("#lireports").addClass("active");
	}
</script>