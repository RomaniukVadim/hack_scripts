// Analysis results: Bot
$('#analysis-results td.reports a, #analysis-results-bot ul li a').live('click', function(){
    var $this = $(this);
    $.colorbox({
        open: true,
        href: $this.attr('href')
    });
    return false;
});
