// Group Edit Form, Bundle Edit Form
(function($){
	// ColorBox Form
	$('#add-new-group, #add-new-bundle').live('click', function(){
		$(this).colorbox({
			scrolling: false,
			open: true
		});
		return false;
	});

	// Errors list in colorbox
	$('#bundles ul.errors a').live('click', function(){
		$(this).colorbox({
			minWidth: '90%',
			minHeight: '90%',
			maxWidth: '90%',
			maxHeight: '90%',
			scrolling: true,
			resize: true,
			open: true
		});
		return false;
	});

	// Make forms close the colorbox on save & update the page
	$('#ajax-edit-group, #ajax-edit-bundle').live('submit', function(){
		$.colorbox.close();
		$('.context').hide();
		$.get(window.location, function(data){
			$('.context').replaceWith( $('.context', data)).show();
		});
		return true;
	});

	// List-items editor init
	var listEditorInit = function(sel_parent, ul_list, ul_new, bt_remove, bt_save){
		// Remove a list item
		$(sel_parent+' '+bt_remove).live('click', function(e){
			$(e.target).closest('li').remove();
			return false;
		});

		// Add a list item
		$(sel_parent+' '+bt_save).live('click', function(e){
			var $new = $(e.target).closest(ul_new);
			var $form = $new.closest('form');
			var $list = $form.find(ul_list);

			// Collect values
			var values = {};
			$.each($form.serializeArray(), function() { values[this.name] = this.value; });
			values.name = $new.find('select option:selected').text();

			// User picked?
			if (values.uid === '')
				return false;

			// Display a new line
			$($list.find('.js-template').template(values)).appendTo($list);

			// Tell colorBox to update
			$.colorbox.resize();

			return false;
		});
	};

	// Init List-Items-Editor for the Group Permissions
	listEditorInit('form#ajax-edit-group  div#ajax-edit-group-perms',    'ul.perms',   'ul.new', 'ul.perms   li a.remove', 'ul.new button');
	// Init List-Items-Editor for the Bundle Injects
	listEditorInit('form#ajax-edit-bundle div#ajax-edit-bundle-injects', 'ul.injects', 'ul.new', 'ul.injects li a.remove', 'ul.new button');

	// Context menu
	switch ($('html').attr('lang')){
		case 'ru':
			window.lexicon = {
				'edit': 'Редактировать',
				'delete': 'Удалить',

				'delete-confirm': 'Действительно удалить'
			};
			break;
		default: // english fallback
			window.lexicon = {
				'edit': 'Edit',
				'delete': 'Remove',

				'delete-confirm': 'Really delete'
			};
			break;
	}

	$.contextMenu(window.AJAXcontextMenu({
		lexicon: window.lexicon,
		selector: '#groups.adminnable TBODY tr *, #bundles.adminnable TBODY tr *, #injects.adminnable TBODY tr *',
		items: {
			'edit': {
				callback: function(key, opt){
					var $this = $(this).closest('tr');
					var ajax_edit = $this.data('ajax-edit');
					if (ajax_edit !== undefined)
						$.colorbox({ href: ajax_edit, scrolling: false });
					else
						window.location.href = $this.find('th a').attr('href');
				}
			},
			'delete': {
				callback: function(key, opt){
					var $this = $(this).closest('tr');
					var name = $this.find('th').text();
					if (window.confirm(window.lexicon['delete-confirm'] + ' ' + name + '?' ))
						$.get($this.data('ajax-delete'), function(){
							$.jlog('deleted', name);
							$this.remove();
						});
				}
			}
		}
	}));
})(jQuery);
