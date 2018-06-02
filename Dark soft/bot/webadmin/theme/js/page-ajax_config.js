$(function(){

	// actionDbConnect()
	$('#ajax-config-actionDbConnect-editor').die('submit').live('submit', function(){
		var $this = $(this);
		var $connections = $('#ajax-config-actionDbConnect textarea[name="config[db-connect]"]');

		var values = {};
		$.each($this.serializeArray(), function() { values[this.name] = this.value; });

		var connection = values.alias + ' = ' + 'mysql://' + values.user + ':' + values.pass + '@' + values.host + ':' + values.port + '/' + values.db;
		$connections.val( $connections.val() + "\r\n" + connection + "\r\n" );
		return false;
	});

    // actionMailer()
    $('#ajax-config-actionMailer #mailer-script-check').die('click').live('click', function(){
        var $this = $(this);
        var $results = $('#mailer-script-check-results').empty();
        var script_url = $('#ajax-config-actionMailer input[name="config[mailer][script_url]"]').val();
        $.get($this.data('ajax'), {'script_url': script_url}, function(data){
            $results.html(data);
            $.colorbox.resize();
        });
        return false;
    });
});
