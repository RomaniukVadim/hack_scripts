$(document).ready(function() {
 	
 	//--------------- Sliders ------------------//
	//simple slider
	$( "#slider" ).slider(); 

	//with 20 increments
	$( "#slider1" ).slider({
		range: "min",
		value:100,
		min: 1,
		max: 500,
		step: 20,
		slide: function( event, ui ) {
			$( "#amount" ).val( "$" + ui.value );
		}
	});
	$( "#amount" ).val( "$" + $( "#slider" ).slider( "value" ) );

	//range slider
	$( "#slider-range" ).slider({
		range: true,
		min: 0,
		max: 500,
		values: [ 75, 300 ],
		slide: function( event, ui ) {
			$( "#amount1" ).val( "Price range: $" + ui.values[ 0 ] + " - $" + ui.values[ 1 ] );
		}
	});
	$( "#amount1" ).val( "Price range: $" + $( "#slider-range" ).slider( "values", 0 ) +
		" - $" + $( "#slider-range" ).slider( "values", 1 ) );

	//with minimum
	$( "#slider-range-min" ).slider({
		range: "min",
		value: 37,
		min: 1,
		max: 700,
		slide: function( event, ui ) {
			$( "#amount2" ).val( "Maximum price: $" + ui.value );
		}
	});
	$( "#amount2" ).val( "Maximum price: $" + $( "#slider-range-min" ).slider( "value" ) );
	//with maximum
	$( "#slider-range-max" ).slider({
		range: "max",
		min: 1,
		max: 10,
		value: 2,
		slide: function( event, ui ) {
			$( "#amount3" ).val("Minimum number: " + ui.value );
		}
	});
	$( "#amount3" ).val( "Minimum number: " + $( "#slider-range-max" ).slider( "value" ) );

	//vertical sliders
	$( "#eq > span" ).each(function() {
		// read initial values from markup and remove that
		var value = parseInt( $( this ).text(), 10 );
		$( this ).empty().slider({
			value: value,
			range: "min",
			animate: true,
			orientation: "vertical"
		});
	});

	//------------- Range sliders -------------//
	$("#slider-basic").rangeSlider({
		bounds: {min: 0, max: 100}
	});
	//edit slider
	$("#edit-range-slider").editRangeSlider({
		bounds: {min: 0, max: 200}
	});
	//date slider
	$("#date-range-slider").dateRangeSlider({
		bounds: { min: new Date(2013, 0, 1),max: new Date(2013, 11, 31)},
		defaultValues:{min: new Date(2013, 5, 1), max: new Date(2013, 9, 31)}
	});
	//with no arrows
	$("#slider-noarrow").rangeSlider({
		arrows:false
	});
	// hide/show lables
	$("#slider-labels").rangeSlider({
		valueLabels:"change",
		durationIn: 1000,
		durationOut: 1000
	});
	//custom formater 
	$("#slider-formatter").rangeSlider({
	    formatter:function(val){
	        var value = Math.round(val * 5) / 5,
	          decimal = value - Math.round(val);
	        return decimal == 0 ? value.toString() + ".0" : value.toString();
	    }
	});

	//------------- Progress bars -------------//
	$( "#progressbar" ).progressbar({
		value: 37
	});

	//Animated progress bar
	$('#progress1').anim_progressbar();

	// from second #5 till 15
    var iNow = new Date().setTime(new Date().getTime() + 5 * 1000); // now plus 5 secs
    var iEnd = new Date().setTime(new Date().getTime() + 15 * 1000); // now plus 15 secs
    $('#progress2').anim_progressbar({start: iNow, finish: iEnd, interval: 100});

    // we will just set interval of updating to 2 sec
    $('#progress3').anim_progressbar({interval: 2000});

    //Circular bar init
	initPieChart();

	//--------------- Dialogs ------------------//
	$('#openDialog').click(function(){
		$('#dialog').dialog('open');
		return false;
	});

	$('#openModalDialog').click(function(){
		$('#modal').dialog('open');
		return false;
	});

	// JQuery Dialog			
	$('#dialog').dialog({
		autoOpen: false,
		dialogClass: 'dialog',
		buttons: {
			"Close": function() { 
				$(this).dialog("close"); 
			}
		}
	});

	// JQuery UI Modal Dialog			
	$('#modal').dialog({
		autoOpen: false,
		modal: true,
		dialogClass: 'dialog',
		buttons: {
			"Close": function() { 
				$(this).dialog("close"); 
			}
		}
	});

	$("div.dialog button").addClass("btn");

	//Boostrap modal
	$('#myModal').modal({ show: false});

	//------------- jGrowl notification -------------//
	//simple notice
    $(".notice").click(function() {
    	$.jGrowl("Hello this is simple notice", {
    		group: 'notice',
    		sticky: true,
    		closeTemplate: '<i class="icon16 i-close-2"></i>',
    		animateOpen: {
		        width: 'show',
		        height: 'show'
		    }
    	});
	});
	$(".success").click(function() {
    	$.jGrowl("<i class='icon16 i-checkmark-3'></i> Nice job you done this.", {
    		group: 'success',
    		closeTemplate: '<i class="icon16 i-close-2"></i>',
    		animateOpen: {
		        width: 'show',
		        height: 'show'
		    }
    	});
	});
	$(".error").click(function() {
    	$.jGrowl("<i class='icon16 i-cancel-circle'></i> Something terrible is happen here.", {
    		group: 'error',
    		closeTemplate: '<i class="icon16 i-close-2"></i>',
    		animateOpen: {
		        width: 'show',
		        height: 'show'
		    }
    	});
	});
	$(".info").click(function() {
    	$.jGrowl("<i class='icon16 i-info'></i> Please check out this awesome info window.", {
    		group: 'info',
    		closeTemplate: '<i class="icon16 i-close-2"></i>',
    		animateOpen: {
		        width: 'show',
		        height: 'show'
		    }
    	});
	});
	$(".top-left").click(function() {
    	$.jGrowl("Show in top left corner", {
    		group: 'notice',
    		position: 'top-left',
    		closeTemplate: '<i class="icon16 i-close-2"></i>',
    		animateOpen: {
		        width: 'show',
		        height: 'show'
		    }
    	});
	});
	$(".bottom-left").click(function() {
    	$.jGrowl("Show in bottom left corner", {
    		group: 'notice',
    		position: 'bottom-left',
    		closeTemplate: '<i class="icon16 i-close-2"></i>',
    		animateOpen: {
		        width: 'show',
		        height: 'show'
		    }
    	});
	});
	$(".bottom-right").click(function() {
    	$.jGrowl("Show in bottom right corner", {
    		group: 'notice',
    		position: 'bottom-right',
    		closeTemplate: '<i class="icon16 i-close-2"></i>',
    		animateOpen: {
		        width: 'show',
		        height: 'show'
		    }
    	});
	});
	$(".top-center").click(function() {
    	$.jGrowl("Show in top center", {
    		group: 'notice',
    		position: 'center',
    		closeTemplate: '<i class="icon16 i-close-2"></i>',
    		animateOpen: {
		        width: 'show',
		        height: 'show'
		    }
    	});
	});
	
	$(".gray").click(function() {
    	$.jGrowl("Gray theme notification", {
    		group: 'gray',
    		closeTemplate: '<i class="icon16 i-close-2"></i>',
    		animateOpen: {
		        width: 'show',
		        height: 'show'
		    }
    	});
	});

	$(".sticky").click(function() {
    	$.jGrowl("I`m sticky notice", {
    		closeTemplate: '<i class="icon16 i-close-2"></i>',
    		animateOpen: {
		        width: 'show',
		        height: 'show'
		    }
    	});
	});

	$(".headmsg").click(function() {
    	$.jGrowl("With some header", {
    		header: 'Important!!!',
    		closeTemplate: '<i class="icon16 i-close-2"></i>',
    		animateOpen: {
		        width: 'show',
		        height: 'show'
		    }
    	});
	});
	
});

//Setup circular bar
var initPieChart = function() {
	$(".percentage").easyPieChart({
        barColor: '#62aeef',
        borderColor: '#227dcb',
        trackColor: '#d7e8f6',
        scaleColor: false,
        lineCap: 'butt',
        lineWidth: 20,
        size: 80,
        animate: 1500
    });
    $(".percentage-red").easyPieChart({
        barColor: '#d8605f',
        trackColor: '#f6dbdb',
        scaleColor: false,
        lineCap: 'butt',
        lineWidth: 20,
        size: 80,
        animate: 1500
    });
    $(".percentage-green").easyPieChart({
        barColor: '#72b110',
        trackColor: '#dff0c6',
        scaleColor: false,
        lineCap: 'butt',
        lineWidth: 20,
        size: 80,
        animate: 1500
    });
    $(".percentage-gray").easyPieChart({
        barColor: '#e0e0e0',
        trackColor: '#f7f7f7',
        scaleColor: false,
        lineCap: 'butt',
        lineWidth: 20,
        size: 80,
        animate: 1500
    });

}