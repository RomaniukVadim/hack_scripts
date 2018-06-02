// Form feeder
window.js_form_feeder = function(sel, data){
	var $form = $(sel);
	for (var k in data){
		var $item = $form.find('[name="'+k+'"]');
		if (!$item.length) continue;
		
		// Determine the type
		var last = $item.length - 1; // use the last item: in case of hidden+checkboxes
		var type = ($item[last].tagName + ':' + ($($item[last]).attr('type') || '')).toLowerCase();
		switch (type){
			case 'input:hidden':
			case 'input:text':
			case 'input:password':
			case 'textarea:':
				$item.val(data[k]);
				break;
			case 'input:checkbox':
				$item.filter('[value="'+data[k]+'"]').attr('checked', true);
				break;
			case 'input:radio':
				$item.filter('[value="'+data[k]+'"]').attr('checked', true);
				break;
			case 'select:':
				$item.find('option[value="'+data[k]+'"]').attr('selected', true);
				break;
			}
		}
	};

// Global AJAX spinner
$(function(){
	document.mouse = (function($element){
		var pos = $element.position();
		if (!pos) pos = {left: 0, top: 0};
		return {
			pageX: pos.left,
			pageY: pos.top
		};
	})($('.context'));
});
$(document).on({
	'mousemove.ajax-spinner': function(e){ document.mouse = e; },
	'ajax-start.ajax-spinner': function(e){
        if (!document.mouse) return;
		var pos = {
			'top': document.mouse.pageY + 'px',
			'left': document.mouse.pageX + 'px'
		};
		$('.ajax-spinner').css(pos).show();
	},
	'ajax-stop.ajax-spinner': function(e){ $('.ajax-spinner').hide(); }
});

// Ajax status: spinner & errors handling
$.ajaxSetup({
	beforeSend: function() { $(document).trigger('ajax-start.ajax-spinner'); },
	success: function() { $(document).trigger('ajax-stop.ajax-spinner'); },
	complete: function() { $(document).trigger('ajax-stop.ajax-spinner'); if ($.colorbox) $.colorbox.resize(); },
	error: function(xhr){
		$.jlog('ajax-error', '{status} {statusText} <br> {type} {url}', {
			'type': this.type,
			'url': this.url,
			'status': xhr.status,
			'statusText': xhr.statusText,
			'responseText': xhr.responseText
			});
		}
	});


// Massive checkbox control
$('.massive_checkbox_control a').live('click', function(){
	var $this = $(this);
	var $checkboxes = $($this.closest('div').data('target'));
	switch ($this.data('action')){
		case 'all':
			$checkboxes.attr('checked', true);
			break;
		case 'none':
			$checkboxes.attr('checked', false);
			break;
		case 'inv':
			$checkboxes.each(function(){
				var $this = $(this);
				$this.attr('checked', $this.attr('checked')? false : true );
				});
			break;
		}
	return false;
	});

// Links that AJAX-load & replace itself
$('a.ajax_replace').live('click', function(){
	var $this = $(this);
	var $div = $('<div><img src="theme/throbber.gif" /></div>');
	$(this).replaceWith($div);
	$.get( $(this).attr('href'), function(data){
		$div.replaceWith(data);
		});
	return false;
	});

// Links that display another page in Colorbox
$('a.ajax_colorbox, a.ajax_config').live('click', function(){
	$.get( $(this).attr('href'), function(data){
		$.colorbox({ html: data, resize: true });
		});
	return false;
	});

// AJAX forms that just save themselves. `data-jlog-title` attribute is the message
$('form.ajax_form_save').live('submit', function(e){
	var $form = $(this);
	$form.find('input[type=submit], button[type=submit]').replaceWith('<img src="theme/throbber.gif" />');
	$.post($form.attr('action'), $form.serialize(), function(data){
		$form.replaceWith(data);
        var title = $form.data('jlog-title');
        if (title)
            $.jlog('saved', title);
		});
    e.preventDefault();
	});

// AJAX forms that update themselves. `data-jlog-title` attribute is the message
$('form.ajax_form_update').live('submit', function(e){
	var $form = $(this);
	$form.fadeTo(500, 0.1);
	$.post($form.attr('action'), $form.serialize(), function(data){
        var title = $form.data('jlog-title');
        if (title)
		    $.jlog('saved', title);
	}).complete(function(){  $form.fadeTo(200, 1);  });
    e.preventDefault();
});

// Colorbox selector: data-sel
$('a.sel_colorbox').live('click', function(e){
	var $content = $(  $(this).data('sel')  );
	$.colorbox({ html: $content.clone(true).show(), resize: true });
    e.preventDefault();
	});

// NamedPreset
$('.namedPreset select').live('change', function(){
	var $this = $(this);
	var $that = $(  $this.data('bind')  );
	$that.val($this.val());
});
