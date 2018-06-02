$(document).ready(function() {


 	//define chart clolors ( you maybe add more colors if you want or flot will add it automatic )
 	var chartColours = ['#62aeef', '#d8605f', '#72c380', '#6f7a8a', '#f7cb38', '#5a8022', '#2c7282'];

 	//generate random number for charts
	randNum = function(){
		return (Math.floor( Math.random()* (1+40-20) ) ) + 20;
	}


 	//check if element exist and draw chart
	if($(".chart").length) {
		$(function () {
			var d1 = [];
			var d2 = [];

			//here we generate data for chart
			for (var i = 0; i < 32; i++) {
				d1.push([new Date(Date.today().add(i).days()).getTime(),randNum()+i+i]);
				d2.push([new Date(Date.today().add(i).days()).getTime(),randNum()]);
			}

			var chartMinDate = d1[0][0]; //first day
    		var chartMaxDate = d1[31][0];//last day

    		var tickSize = [1, "day"];
    		var tformat = "%d/%m/%y";

		    //graph options
			var options = {
					grid: {
						show: true,
					    aboveData: true,
					    color: "#3f3f3f" ,
					    labelMargin: 5,
					    axisMargin: 0, 
					    borderWidth: 0,
					    borderColor:null,
					    minBorderMargin: 5 ,
					    clickable: true, 
					    hoverable: true,
					    autoHighlight: true,
					    mouseActiveRadius: 100
					},
			        series: {
			            lines: {
		            		show: true,
		            		fill: true,
		            		lineWidth: 2,
		            		steps: false
			            	},
			            points: {
			            	show:true,
			               	radius: 2.8,
			            	symbol: "circle",
			            	lineWidth: 2.5
			            }
			        },
			        legend: { 
			        	position: "ne", 
			        	margin: [0,-25], 
			        	noColumns: 0,
			        	labelBoxBorderColor: null,
			        	labelFormatter: function(label, series) {
						    // just add some space to labes
						    return label+'&nbsp;&nbsp;';
						},
						width: 40,
						height: 1
			    	},
			        colors: chartColours,
			        shadowSize:0,
			        tooltip: true, //activate tooltip
					tooltipOpts: {
						content: "%s: %y.0",
						xDateFormat: "%d/%m",
						shifts: {
							x: -30,
							y: -50
						},
						defaultTheme: false
					},
					yaxis: { min: 0 },
					xaxis: { 
			        	mode: "time",
			        	minTickSize: tickSize,
			        	timeformat: tformat,
			        	min: chartMinDate,
			        	max: chartMaxDate
			        }
			};  
			var plot = $.plot($(".chart"),
	           [{
	    			label: "Email Send", 
	    			data: d1,
	    			lines: {fillColor: "#f3faff"},
	    			points: {fillColor: "#fff"}
	    		}, 
	    		{	
	    			label: "Email Open", 
	    			data: d2,
	    			lines: {fillColor: "#fff8f7"},
	    			points: {fillColor: "#fff"}
	    		}], options);
		});
	}//End .chart if  

	//check if element exist and draw chat pie
	if($(".chart-pie-social").length) {
		$(function () {
			var options = {
				series: {
					pie: { 
						show: true,
						highlight: {
							opacity: 0.1
						},
						radius: 1,
						stroke: {
							width: 2
						},
						startAngle: 2,
						border: 30, //darken the main color with 30
						label: {
		                    show: true,
		                    radius: 2/3,
		                    formatter: function(label, series){
		                        return '<div class="pie-chart-label">'+label+'&nbsp;'+Math.round(series.percent)+'%</div>';
		                    }
		                }
					}				
				},
				legend:{
					show:false
				},
				grid: {
		            hoverable: true,
		            clickable: true
		        },
		        tooltip: true, //activate tooltip
				tooltipOpts: {
					content: "%s : %y.1"+"%",
					shifts: {
						x: -30,
						y: -50
					},
					defaultTheme: false
				}
			};
			var data = [
			    { label: "Facebook",  data: 64, color: chartColours[0]},
			    { label: "Twitter",  data: 25, color: chartColours[1]},
			    { label: "Google",  data: 11, color: chartColours[2]}
			];

		    $.plot($(".chart-pie-social"), data, options);

		});

	}//End of .cart-pie-social

	//Init campaign stats
	initPieChart();

	//------------- ToDo -------------//
	//toDo 
    function toDo () {
        var todos = $('.toDo');
        var items = todos.find('.task-item');
        var chboxes = items.find('input[type="checkbox"]');
        var close = items.find('.act');

        chboxes.change(function() {
           if ($(this).is(':checked')) {
                $(this).closest('.task-item').addClass('done');
            } else {
                $(this).closest('.task-item').removeClass('done');
            }
        });

        items.hover(
          function () {
            $(this).addClass('show');
          },
          function () {
            $(this).removeClass('show');
          }
        );

        close.click(function() {
            $(this).closest('.task-item').fadeOut('500');
            //Do other stuff here..
        });

    }

    toDo();

	//sortable
	$(function() {
	    $( "#today, #tomorrow" ).sortable({
	      connectWith: ".todo-list"
	    }).disableSelection();
	});

	//------------- Full calendar  -------------//

	$(function () {
		var date = new Date();
		var d = date.getDate();
		var m = date.getMonth();
		var y = date.getFullYear();
		
		//calendar example
		$('#dashboard-calendar').fullCalendar({
			//isRTL: true,
			//theme: true,
			header: {
				left: '',
				center: 'title,today,prev,next,month,agendaWeek,agendaDay',
				right: ''
			},
			firstDay: 1,
			dayNamesShort: ['Sunday', 'Monday', 'Tuesday', 'Wednesday',
 'Thursday', 'Friday', 'Saturday'],
			buttonText: {
	        	prev: '<i class="icon24 i-arrow-left-7"></i>',
	        	next: '<i class="icon24 i-arrow-right-8"></i>',
	        	today:'<i class="icon24 i-home-6"></i>'
	    	},
			editable: true,
			droppable: true, // this allows things to be dropped onto the calendar !!!
			drop: function(date, allDay) { // this function is called when something is dropped
			
				// retrieve the dropped element's stored Event Object
				var originalEventObject = $(this).data('eventObject');
				
				// we need to copy it, so that multiple events don't have a reference to the same object
				var copiedEventObject = $.extend({}, originalEventObject);
				
				// assign it the date that was reported
				copiedEventObject.start = date;
				copiedEventObject.allDay = allDay;
				
				// render the event on the calendar
				// the last `true` argument determines if the event "sticks" (http://arshaw.com/fullcalendar/docs/event_rendering/renderEvent/)
				$('#calendar').fullCalendar('renderEvent', copiedEventObject, true);
				$(this).remove();
			},
			events: [
				{
					title: 'All Day Event',
					start: new Date(y, m, 1)
				},
				{
					title: 'Long Event',
					start: new Date(y, m, d-5),
					end: new Date(y, m, d-2)
				},
				{
					id: 999,
					title: 'Repeating Event',
					start: new Date(y, m, d-3, 16, 0),
					allDay: false
				},
				{
					id: 999,
					title: 'Repeating Event',
					start: new Date(y, m, d+4, 16, 0),
					allDay: false
				},
				{
					title: 'Meeting',
					start: new Date(y, m, d, 10, 30),
					allDay: false
				},
				{
					title: 'Lunch',
					start: new Date(y, m, d, 12, 0),
					end: new Date(y, m, d, 14, 0),
					allDay: false,
					color: '#25a7e8',
					borderColor: '#0d7fb8'
				},
				{
					title: 'Birthday Party',
					start: new Date(y, m, d+1, 19, 0),
					end: new Date(y, m, d+1, 22, 30),
					allDay: false,
					color: '#d8605f',
					borderColor: '#b72827'
				},
				{
					title: 'Click for Google',
					start: new Date(y, m, 28),
					end: new Date(y, m, 29),
					url: 'http://google.com/'
				}
			],
			eventColor: '#72c380',
			eventBorderColor: '#379e49'
		});
	});

	//------------- Spark stats -------------//
	$('.spark>.positive').sparkline('html', { type:'bar', barColor:'#42b449'});
	$('.spark>.negative').sparkline('html', { type:'bar', barColor:'#db4a37'});

	//------------- Gauge -------------//
	var g = new JustGage({
	    id: "gauge", 
	    value: getRandomInt(0, 100), 
	    min: 0,
	    max: 100,
	    title: "server usage",
	    gaugeColor: '#6f7a8a',
	    labelFontColor: '#555',
	    titleFontColor: '#555',
	    valueFontColor: '#555',
	    showMinMax: false
	 });

	var g1 = new JustGage({
	    id: "gauge1", 
	    value: getRandomInt(100, 500), 
	    min: 100,
	    max: 500,
	    title: "Visitors now",
	    gaugeColor: '#6f7a8a',
	    labelFontColor: '#555',
	    titleFontColor: '#555',
	    valueFontColor: '#555',
	    showMinMax: false
	 });

	setInterval(function() {
      g.refresh(getRandomInt(0, 100));
      g1.refresh(getRandomInt(100, 500));
    }, 2500);

});

//Setup campaign stats
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

}