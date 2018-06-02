
 
  
 $( ".loginbg li" ).hover(
  function() {
   $('.loginbg li').removeClass('active');
    $(this).addClass('active');
  }
); 
  

$(window).scroll(function(){

   if ($(window).scrollTop() > 600) {
    $(".plans").addClass('plango');
    }
	

	

	
	
	
	
	
});



function loader() {
$('.link span').css('opacity','1');
}

setTimeout(loader, 2000);


function loading() {
$('#loader-wrapper').addClass('load');
$('#loader-wrapper').css('opacity','0');
$('#loader-wrapper').fadeIn(600);

$( "#loader-wrapper" ).fadeIn( "slow", function() {
$('#loader-wrapper').css('display', 'none');
  });


$('body').css('overflow', 'auto');
}




$(window).bind("load", function() {
loading();

function animateslider() {
$('.prem ul li').addClass('act');
$('.linered').css('margin-right', '0');
$('.lineblack').css('margin-left', '0');
$('.order').css('margin-right', '0');
}


setTimeout(animateslider, 300);




$( ".sum .ui-slider-handle" ).hover(
  function() {
    $('.sum .ui-widget-header').addClass('ui-anim');
  }, function() {
    $('.sum .ui-widget-header').removeClass('ui-anim');
  }
); 


$( ".days .ui-slider-handle" ).hover(
  function() {
    $('.days .ui-widget-header').addClass('ui-anim');
  }, function() {
    $('.days .ui-widget-header').removeClass('ui-anim');
  }
); 

});



 $('.flexslider').flexslider({
    animation: "slide",
    slideshowSpeed: 7000
  });