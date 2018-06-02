// Plots: software, firewall, antivirus
if (window.data && window.data.analytics)
$(function(){
    var sets = {
        chart: {
            renderTo: 'analytics-chart',
            type: 'bar',
            height: (6+window.data.analytics.length)*24
        },
        title: { text: 'Analytics' },
        xAxis: {
            //categories: _.pluck(window.data.analytics, 'soft')
            categories: _.map(window.data.analytics, function(v){ return v.vendor + '/' + v.product; })
        },
        yAxis: {
            min: 0,
            title: { text: 'Count' }
        },
        tooltip: {
            formatter: function() { return ''+ this.x +': '+ this.y +' installs'; }
        },
        series: [{
                     name: 'Installs',
                     //data: _.pluck(window.data.analytics, 'count')
                     data: _.map(window.data.analytics, function(v){ return parseInt(v.count); })
                 }]
    };

    var chart = new Highcharts.Chart(sets);

    // Now draw a table that displays this data
    $('#analytics-table table TBODY').append(_.map(window.data.analytics, function(v){
        return _.template('<tr><td><%- vendor %></td><td><%- product %></td><td><%= count %></td></tr>', v);
    }).join(''));

});



// Soft Search
if (window.data && window.data.search)
$(function(){
    var $results = $('#search-results');
    var $display = $results.children('TBODY');
    var $throbber = $results.children('TFOOT');

    // Prepare an array of callbacks
    var callbacks = _(window.data.search.tables).map(function(table){
        return function(next){
            var request = _(window.data.search).omit('tables');
            if (request.botId === null) delete request.botId;
            if (request.soft === null) delete request.soft;
            request.table = table;

            $.get('?m=stats_soft/ajaxSearch', request, function(data, status, req){
                $display.append(data);

                // Proceed to the next callback
                if (!req.getResponseHeader('x-stop-chain')) // headers
                    next();
            });
        };
    });

    // Perform async chained execution of the array of callbacks
    _(callbacks).reduceRight(_.wrap, function(){
        $throbber.remove();
    })();
});
