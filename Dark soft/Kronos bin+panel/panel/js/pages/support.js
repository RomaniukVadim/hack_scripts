$(document).ready(function() {
	
 	//support page scroll
	if($(".scroll-y").length) {
		$(".scroll-y").niceScroll({
			cursoropacitymax: 0.7,
			cursorborderradius: 6,
			cursorwidth: "5px"
		});
	}
	if($(".support-section").length) {
		$(".support-section div.tab-content>.tab-pane.active").niceScroll({
			cursoropacitymax: 0.7,
			cursorborderradius: 6,
			cursorwidth: "5px"
		});
	}
});