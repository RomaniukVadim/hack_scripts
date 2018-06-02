// Infinite scroll
$('.infinite-container').waypoint('infinite', {
    container: 'auto',
    items: '.infinite-container > tr',
    more: '.infinite-more-link',
    offset: 'bottom-in-view',
    loadingClass: 'infinite-loading',
    onBeforePageLoad: $.noop,
    onAfterPageLoad: $.noop
});

// Ajax-config clean-up button
$('#balgrabber-reset-db-and-reload').live('click', function(){
    $.get('system/cron.php/cronjobs_balgrabber::cronjob_parse?reset=1', function(){
        window.location.reload();
    });
    $.jlog('info', 'Reloading');
    return false;
});
