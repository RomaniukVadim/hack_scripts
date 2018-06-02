$(document).ready(function() {
	
	$('#container ul:nth-child(even)').hide();
	
	$('h3:nth-child(even)').addClass('selected');
	$('h3').on('click', function() {
		
		$(this).addClass('selected').siblings().removeClass('selected');
		$('#container ul[data-for]').hide();
		$('#container ul[data-for='+$(this).attr('id')+']').fadeIn();
	});
});