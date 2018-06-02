function drawChart(input_data, pie_display, table_display, title) {
	var data = new google.visualization.DataTable();
	data.addColumn('string', 'Version');
	data.addColumn('number', 'Count');
	data.addRows(input_data);

	var options = {
		title: title
		};

	var chart = new google.visualization.PieChart(pie_display);
	chart.draw(data, options);

	var table = new google.visualization.Table(table_display);
	table.draw(data, {/*showRowNumber: true*/});
	}

// Interval auto-update
var autoupdate_timer = setInterval(function(){
	$.get(window.location, function(data){
		$('.context').replaceWith( $('.context', data));
	});
}, 10*1000);
$('.context *').live('click', function(){
	clearInterval(autoupdate_timer);
	return true;
});

// Bot versions
$('a#botVersions').live('click', function(){
	$('#botVersions-td').show();
    var $display = $('#botVersions-Display');
    var $periods = $display.find('.period a');
    $periods.click(function(){
        var $this = $(this);
        $periods.removeClass('active');
        $this.addClass('active');
        var period_id = $this.data('id');

        var sets = {
            chart: {
                renderTo: $display.find('.chart').empty()[0],
                height: 400
            },
            title: { text: 'Versions' },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.y}, {point.percentage}%</b>'
            },
            series: [{
                         name: 'Installs',
                         type: 'pie',
                         data: _.map(window.botVersions[period_id], function(v){ return [v.v, v.n]; })
                     }]
        };

        var chart = new Highcharts.Chart(sets);

        // Table
        $display.find('.table table TBODY').empty().append(_.map(window.botVersions[period_id], function(v){
            return _.template('<tr><th><%- v %></th><td><%= n %></td></tr>', v);
        }).join(''));

        return false;
    }).filter(':first').click();

    return false;
});


// Botnet activity
$('#tr-botnet_activity').live('click', function(){
	var $ba = $('#botnet_activity').show('slow');
	var $tabs = $ba.find('ul.tabs li a');
	$tabs.click(function(){
		var $a = $(this);
		$tabs.removeClass('this');
		$a.addClass('this');
		$ba.find('.display').load($a.attr('href'));
		return false;
	}).filter(':eq(0)').click();
	return false;
});
