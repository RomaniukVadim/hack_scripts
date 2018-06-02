(function($) {
	$.fn.aprofSimpleSliderFancy = function() {
		return this.each(function() {
			var image_cnt = 0;
			var slider = $(this);
			var slide = $(this).find("li");
			var slide_width = $(slide).width();
			var slide_cnt = $(slide).size();
			var slider_width = $(this).width();
			var m = parseInt($(slide).css("margin-right"))+parseInt($(slide).css("margin-left"))+parseInt($(slide).css("padding-right"))+parseInt($(slide).css("padding-left"));
			var cnt = parseInt((slider_width+15)/(slide_width+15));
			var new_width = (slide_width+m)*cnt-m;
			var dw = $(this).parent().width();
			$(this).css("width",new_width+"px");
			$(this).find(".aprof-simple-slider-wraper").css("width",new_width+"px");
			slider_width = new_width;
			
			$(this).find(".aprof-simple-slider-larr").click(function(){
				if($(this).attr("disabled")=="disabled") return false;
				$(this).attr("disabled","disabled");
				var l = $(slider).find("ul").position().left;
				if(l<0){
					$(slider).find("ul").animate({
						left:"+="+(slide_width+m)+"px"
					},500,function(){
						$(slider).find(".aprof-simple-slider-larr").removeAttr("disabled");
						$(slider).find(".aprof-simple-slider-rarr").removeAttr("disabled");
					});
				}
				else{
					
					$(slider).find("ul").animate({
						left:"-"+(slide_cnt-cnt)*(slide_width+m)+"px"
					},500,function(){
						$(slider).find(".aprof-simple-slider-larr").removeAttr("disabled");
						$(slider).find(".aprof-simple-slider-rarr").removeAttr("disabled");
					});
				}
			});
			$(this).find(".aprof-simple-slider-rarr").click(function(){
				if($(this).attr("disabled")=="disabled") return false;
				$(this).attr("disabled","disabled");
				var l = $(slider).find("ul").position().left;
				var image_id = parseInt((l*(-1)+slider_width)/slide_width);
				if(image_id<slide_cnt){
					$(slider).find("ul").animate({
						left:"-="+(slide_width+m)+"px"
					},500,function(){
						$(slider).find(".aprof-simple-slider-larr").removeAttr("disabled");
						$(slider).find(".aprof-simple-slider-rarr").removeAttr("disabled");
					});
				}
				else{
					$(slider).find("ul").animate({
						left:"0px"
					},500,function(){
						$(slider).find(".aprof-simple-slider-larr").removeAttr("disabled");
						$(slider).find(".aprof-simple-slider-rarr").removeAttr("disabled");
					});
				}
			});
		});
	};
})(jQuery);