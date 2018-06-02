	<script src="js/jquery-1.11.1.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script>
		!function ($) {
		    $(document).on("click","ul.nav li.parent > a > span.icon", function(){          
		        $(this).find('em:first').toggleClass("glyphicon-minus");      
		    }); 
		    $(".sidebar span.icon").find('em:first').addClass("glyphicon-plus");
		}(window.jQuery);

		$(window).on('resize', function () {
		  if ($(window).width() > 768) $('#sidebar-collapse').collapse('show')
		})
		$(window).on('resize', function () {
		  if ($(window).width() <= 767) $('#sidebar-collapse').collapse('hide')
		})

	var tmt;
	function showpage(link)
	{
		$("#main").hide("show").html();
		$.ajax({
			type: "GET",
			url: link,
//			beforeSend: showloader(),
			success: function(msg){
				$.ajax({
					type: "POST",
					url: "index.php?act=setpage",
					data: "page=" + link,
					success: function(msg){
//						alert (msg);
					}
				})
//				$("#main").hide();
				clearTimeout(tmt);
				$("#main").html(msg).show("slow");
				if ($.isFunction(pageloaded))
					pageloaded();
			},
			error: function(msg){
				$("#main").html("Error when opening " + link).show("slow");
//				hideloader();
			}
		});
		return false;
	}

	<?php if ($page != "") print "showpage('".$page."');"?>

	var lgtm;


		$(document).off("change", "#input-file").on("change", "#input-file", function() {
		    if ($("#input-file").prop("files")[0]) {
		        var e = new FormData;
		        e.append("userfile", $("#input-file").prop("files")[0]);
				 $.ajax({
		            url: "uploadscan.php",
		            data: e,
		            contentType: !1,
		            processData: !1,
		            cache: !1,
		            type: "POST",
		            success: function(msg) {
						if (msg == "") 
							alert ("Файл успешно загружен.");
						else
							alert (msg);
		            }
		        })
		    }
		})


	</script>	
</body>

</html>
