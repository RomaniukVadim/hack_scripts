// Context menu
window.lexicon.extend({
    'ru': {
        'run': 'Запуск',
        'edit': 'Редактировать',
        'delete': 'Удалить',
        'reset': 'Сбросить',

        'delete?': 'Действительно удалить `{title}`?'
    },
    'en': {
        'run': 'Run',
        'edit': 'Edit',
        'delete': 'Remove',
        'reset': 'Reset',

        'delete?': 'Really delete `{title}`?'
    }
});

// CRUD menu
var default_crud_items = {
    'edit': {
        callback: function(key, opt){
            var $this = $(this).closest('tr');
            $.colorbox({
                href: $this.data('ajax-edit'),
                resize: true,
                onComplete: function(){
                    $('form.crud#crud-criterion').trigger('criterion-changed'); // trigger form handler that hides certain fields
                }
            });
        }
    },
    'delete': {
        callback: function(key, opt){
            var $this = $(this).closest('tr');
            if (window.confirm(  window.lexicon.get('delete?').template({title: $this.find('th').text()})  ))
                $.get($this.data('ajax-edit'), {'delete': 1}, function(){ $this.remove(); });
        }
    }
};

$.contextMenu(window.AJAXcontextMenu({
    selector: 'table.crud#neurostat_analyses TBODY tr',
    items: _.extend(
        {
            'run': {
                disabled: function(key,opt){ return $(this).closest('tr').hasClass('running'); },
                callback: function(key, opt){
                    var $tr = $(this).closest('tr');
                    $.get('?m=reports_neurostat/ajaxAnalysisRun', {aid: $tr.data('aid')}, function(data, status, xhr){
                        if (xhr.readyState == 4){
                            var location = xhr.getResponseHeader("X-Location");
                            if (location && location.length)
                                window.location.href = location;
                        }
                    });

                    var $table = $tr.closest('table');
                    $table.load(window.location + ' #' + $table.attr('id') + '>*');
                }
            },
            'reset': {
                callback: function(key, opt){
                    var $this = $(this).closest('tr');
                    $.get($this.data('ajax-edit'), {'reset': 1}, function(){
                        var $table = $this.closest('table');
                        $table.load(window.location + ' #' + $table.attr('id') + '>*');
                    });
                }
            }
        }, default_crud_items
    )
}));

$.contextMenu(window.AJAXcontextMenu({
    selector: 'table.crud TBODY tr',
    items: default_crud_items
}));

// CRUD form submit
$('form.crud').live('submit', function(e){
    var $form = $(this);

    // POST the form
    $.post($form.attr('action'), $form.serialize(), function(data){
        // Update the corresponding table
        var updateme = {
            'crud-criterion': '#neurostat_criteria',
            'crud-profile': '#neurostat_profiles',
            'crud-analysis': '#neurostat_analyses'
        }[ $form.attr('id') ];
        $(updateme).load(window.location + ' ' + updateme + '>*');
        $.colorbox.close();
    });

    $form.find('[type=submit]').fadeTo(0.5).attr('disabled', true);

    e.preventDefault();
    return true;
});

// Switch criterion[sets] sub-form on type change
$('form.crud#crud-criterion').live('criterion-changed', function(){
    var $this = $(this);
    var criterion = $this.find('select[name="criterion[type]"]').val();
    var is_bot_criterion = /^Bot/.test(criterion);

    $this.find('[name="criterion[days_limit]"], [name="criterion[c_stat]"]')
        .closest('dd')
        .prev().andSelf()
        [  is_bot_criterion? 'hide' : 'show'  ]();
});
$('form.crud#crud-criterion select[name="criterion[type]"]').live('change', function(){
    var $this = $(this);
    var $form = $this.closest('form');

    $.get('?m=reports_neurostat/crudCriterion', {type: $this.val()}, function(data){
        var $cs = $form.find('.criterion-sets').replaceWith(
            $('.criterion-sets', data)
        );
        $form.trigger('criterion-changed');
    });
    return true;
});
