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
	function a_is_dir ($file)
	{
		return ((fileperms("$file") & 0x4000) == 0x4000);
	}
	$files = getFiles ($kpath."sqlmap_dumps");
	foreach ($files as $file):
		if (is_dir($kpath."sqlmap_dumps/".$file)): 
?>
<tr>
	<td colspan="2">

<svg class="glyph stroked folder" style="width: 25px; height: 25px"><use xlink:href="#stroked-folder"/></svg>
&nbsp;&nbsp;
<?php print "<b style=\"color: #3A9FAD\">".$file."</b>"?></td>
</tr>
<?php
			$files1 = getFiles ($kpath."sqlmap_dumps/".$file."/dump");
			foreach ($files1 as $file1):
				if (is_dir($kpath."sqlmap_dumps/".$file."/dump/".$file1)): 
?>
<tr>
	<td colspan="2">
<?php print str_repeat("&nbsp;", 15)?>
<svg class="glyph stroked app window with content" style="width: 25px; height: 25px"><use xlink:href="#stroked-app-window-with-content"/></svg>
&nbsp;&nbsp;
<?php print "<b>".$file1."</b>"?></td>
</tr>
<?php
					$files2 = getFiles ($kpath."sqlmap_dumps/".$file."/dump/".$file1);
					foreach ($files2 as $file2):
				
?>
<tr>
	<td><?php print str_repeat("&nbsp;", 30).$file2?></td>
	<td><button class="btn btn-info btn-open" dir-name="<?php print $file."/dump/".$file1?>" file-name="<?php print $file2?>">Открыть</button>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button class="btn btn-info btn-save" dir-name="<?php print $file."/dump/".$file1?>" file-name="<?php print $file2?>">Скачать</button></td>
</tr>
<?php
					endforeach;
				endif;
			endforeach;
		endif;
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
			var dname = $(this).attr("dir-name");
			$.ajax({
				type: "POST",
				url: "dumps.php?act=view",
				data: "fname=" + fname + '&dname=' + dname,
				success: function(msg){   
					$("#filename").text(fname);
					$("#filebody").html(msg);
					$("#div-file").modal("show");
				}
			})
		})

		$(document).off("click", ".btn-save").on("click", ".btn-save", function(){
			var fname = $(this).attr("file-name");
			var dname = $(this).attr("dir-name");
			window.location.href = "dumps.php?act=download&fname=" + fname + "&dname=" + dname;
		})

		$("#page-title").text('<?php print $page_title?>');
		$("#div-title").text('<?php print $page_title?>');
		$("title").text ('Панель управления - <?php print $page_title?>');

		$("#main-navigation-menu .active").removeClass("active");
	    $("#lidumps").addClass("active");
	}
</script>