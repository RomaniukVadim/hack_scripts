$(document).ready(function() {

	//define chart clolors ( you maybe add more colors if you want or flot will add it automatic )
 	var chartColours = ['#62aeef', '#d8605f', '#72c380', '#6f7a8a', '#f7cb38', '#5a8022', '#2c7282'];

 	//generate random number for charts
	randNum = function(){
		return (Math.floor( Math.random()* (1+40-20) ) ) + 20;
	}


	  


function makemePie(eid, conf, link_)
{
	
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
						border: 30 //darken the main color with 30
					}				
				},
				legend:{
					show:true,
					labelFormatter: function(label, series) {
					    // series is the series object for the label
					    return '<a href="'+link_ + label + '">' + label + '</a>';
					},
					margin: 50,
					width: 20,
					padding: 1
				},
				grid: {
		            hoverable: true,
		            clickable: true
		        },
		        tooltip: true, //activate tooltip
				tooltipOpts: {
					content: "%s : %y.0 bots",
					shifts: {
						x: -30,
						y: -50
					},
					defaultTheme: false
				}
			};
			
			
		    $.plot($("."+eid), conf, options);
}


	makemePie('chart-pie-os', ChartPieOs, 'bots.php?os=');
	makemePie('chart-pie-perms', ChartPiePerms, 'bots.php?perms=');
	makemePie('chart-pie-arch', ChartPieArch, 'bots.php?arch=');
	



	//check if element exist and draw chart ordered bars
	if($(".chart-bars-ordered").length) {
		$(function () {
			//generate some data
			var d1 = [];
		    for (var i = 0; i <= 10; i += 1)
		        d1.push([i, parseInt(Math.random() * 30)]);
		 
		    var d2 = [];
		    for (var i = 0; i <= 10; i += 1)
		        d2.push([i, parseInt(Math.random() * 30)]);
		 
		    var d3 = [];
		    for (var i = 0; i <= 10; i += 1)
		        d3.push([i, parseInt(Math.random() * 30)]);
		 
		    var data = new Array();
		 
		     data.push({
		     	label: "Data One",
		        data:d1,
		        bars: {order: 1}
		    });
		    data.push({
		    	label: "Data Two",
		        data:d2,
		        bars: {order: 2}
		    });
		    data.push({
		    	label: "Data Three",
		        data:d3,
		        bars: {order: 3}
		    });

			var options = {
					bars: {
						show:true,
						barWidth: 0.2,
						fill:1
					},
					grid: {
						show: true,
					    aboveData: false,
					    color: "#3f3f3f" ,
					    labelMargin: 5,
					    axisMargin: 0, 
					    borderWidth: 0,
					    borderColor:null,
					    minBorderMargin: 5 ,
					    clickable: true, 
					    hoverable: true,
					    autoHighlight: false,
					    mouseActiveRadius: 20
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
			        tooltip: true, //activate tooltip
					tooltipOpts: {
						content: "%s : %y.0",
						shifts: {
							x: -30,
							y: -50
						}
					}
			};

			$.plot($(".chart-bars-ordered"), data, options);
		});

	}//End of .cart-bars-ordered

	//check if element exist and draw chart stacked bars
	if($(".chart-bars-stacked").length) {
		$(function () {
			//some data
			var d1 = [];
		    for (var i = 0; i <= 10; i += 1)
		        d1.push([i, parseInt(Math.random() * 30)]);
		 
		    var d2 = [];
		    for (var i = 0; i <= 10; i += 1)
		        d2.push([i, parseInt(Math.random() * 30)]);
		 
		    var d3 = [];
		    for (var i = 0; i <= 10; i += 1)
		        d3.push([i, parseInt(Math.random() * 30)]);
		 
		    var data = new Array();
		 
		    data.push({
		     	label: "Data One",
		        data:d1
		    });
		    data.push({
		    	label: "Data Two",
		        data:d2
		    });
		    data.push({
		    	label: "Data Tree",
		        data:d3
		    });

			var stack = 0, bars = true, lines = false, steps = false;

			var options = {
					grid: {
						show: true,
					    aboveData: false,
					    color: "#3f3f3f" ,
					    labelMargin: 5,
					    axisMargin: 0, 
					    borderWidth: 0,
					    borderColor:null,
					    minBorderMargin: 5 ,
					    clickable: true, 
					    hoverable: true,
					    autoHighlight: true,
					    mouseActiveRadius: 20
					},
			        series: {
			        	stack: stack,
		                lines: { show: lines, fill: true, steps: steps },
		                bars: { show: bars, barWidth: 0.5, fill:1}
				    },
			        xaxis: {ticks:11, tickDecimals: 0},
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
			        shadowSize:1,
			        tooltip: true, //activate tooltip
					tooltipOpts: {
						content: "%s : %y.0",
						shifts: {
							x: -30,
							y: -50
						}
					}
			};

			$.plot($(".chart-bars-stacked"), data, options);
		});

	}//End of .cart-bars-stacked

	//check if element exist and draw chart horizontal bars
	if($(".chart-bars-horizontal").length) {
		$(function () {
			//some data
			//Display horizontal graph
		    var d1 = [];
		    for (var i = 0; i <= 5; i += 1)
		        d1.push([parseInt(Math.random() * 30),i ]);

		    var d2 = [];
		    for (var i = 0; i <= 5; i += 1)
		        d2.push([parseInt(Math.random() * 30),i ]);

		    var d3 = [];
		    for (var i = 0; i <= 5; i += 1)
		        d3.push([ parseInt(Math.random() * 30),i]);
		                
		    var data = new Array();
		    data.push({
		        data:d1,
		        bars: {
		            horizontal:true, 
		            show: true, 
		            barWidth: 0.2, 
		            order: 1
		        }
		    });
			data.push({
			    data:d2,
			    bars: {
			        horizontal:true, 
			        show: true, 
			        barWidth: 0.2, 
			        order: 2
			    }
			});
			data.push({
			    data:d3,
			    bars: {
			        horizontal:true, 
			        show: true, 
			        barWidth: 0.2, 
			        order: 3
			    }
			});


			var options = {
					grid: {
						show: true,
					    aboveData: false,
					    color: "#3f3f3f" ,
					    labelMargin: 5,
					    axisMargin: 0, 
					    borderWidth: 0,
					    borderColor:null,
					    minBorderMargin: 5 ,
					    clickable: true, 
					    hoverable: true,
					    autoHighlight: false,
					    mouseActiveRadius: 20
					},
			        series: {			        	
				        bars: {
				        	show:true,
							horizontal: true,
							barWidth:0.2,
							fill:1
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
			        tooltip: true, //activate tooltip
					tooltipOpts: {
						content: "%s : %y.0",
						shifts: {
							x: -30,
							y: -50
						}
					}
			};

			$.plot($(".chart-bars-horizontal"), data, options);
		});
	}//End of .cart-bars-horizontal

	//check if element exist and draw auto updating chart
	if($(".chart-updating").length) {
		$(function () {
			// we use an inline data source in the example, usually data would
		    // be fetched from a server
		    var data = [], totalPoints = 50;
		    function getRandomData() {
		        if (data.length > 0)
		            data = data.slice(1);

		        // do a random walk
		        while (data.length < totalPoints) {
		            var prev = data.length > 0 ? data[data.length - 1] : 50;
		            var y = prev + Math.random() * 10 - 5;
		            if (y < 0)
		                y = 0;
		            if (y > 100)
		                y = 100;
		            data.push(y);
		        }

		        // zip the generated y values with the x values
		        var res = [];
		        for (var i = 0; i < data.length; ++i)
		            res.push([i, data[i]])
		        return res;
		    }

		    // Update interval
		    var updateInterval = 250;

		    // setup plot
		    var options = {
		        series: { 
		        	shadowSize: 0, // drawing is faster without shadows
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
		        grid: {
					show: true,
				    aboveData: false,
				    color: "#3f3f3f" ,
				    labelMargin: 5,
				    axisMargin: 0, 
				    borderWidth: 0,
				    borderColor:null,
				    minBorderMargin: 5 ,
				    clickable: true, 
				    hoverable: true,
				    autoHighlight: false,
				    mouseActiveRadius: 20
				}, 
				colors: chartColours,
		        tooltip: true, //activate tooltip
				tooltipOpts: {
					content: "Value is : %y.0",
					shifts: {
						x: -30,
						y: -50
					}
				},	
		        yaxis: { min: 0, max: 100 },
		        xaxis: { show: true}
		    };
		    var plot = $.plot($(".chart-updating"), [ getRandomData() ], options);

		    function update() {
		        plot.setData([ getRandomData() ]);
		        // since the axes don't change, we don't need to call plot.setupGrid()
		        plot.draw();
		        
		        setTimeout(update, updateInterval);
		    }

		    update();
		});
	}//End of .cart-updating

});

