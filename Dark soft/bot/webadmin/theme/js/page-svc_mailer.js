(function($){

    // Page: Index
    $(function(){
        // Selftest
        $('#selftest').load('?m=/svc_mailer/Ajax_Config_CheckScript');

        // Context menu
        switch ($('html').attr('lang')){
            case 'ru':
                window.lexicon = {
                    'run': 'Запуск',
                    'reset': 'Сброс',
                    'delete': 'Удалить',
                    'copy': 'Копия'
                };
                break;
            default: // english fallback
                window.lexicon = {
                    'run': 'Run',
                    'reset': 'Reset',
                    'delete': 'Delete',
                    'copy': 'Copy'
                };
                break;
        }

        $.contextMenu(window.AJAXcontextMenu({
            lexicon: window.lexicon,
            selector: '#tasks TBODY tr *',
            callback: function(key, opt, ajaxSuccess){
                $.get( '?m=/svc_mailer/AjaxTasks_' + key , $(this).closest('tr').data('ajax'), function(){
                    // Reload the table
                    $('#tasks').load('?m=/svc_mailer/index_tasks #tasks');
                    // Call success
                    ajaxSuccess && ajaxSuccess.apply(this, arguments);
                } );
                return true;
            },
            items: {
                'run': {},
                'reset': {},
                'delete': {},
                'copy': {
                    callback: function(){
                        window.location.href = '?m=/svc_mailer/new&' + $(this).closest('tr').data('ajax');
                    }
                }
            }
    }));
    });

    // Page: new
    $(function(){
        var $form = $('#new_mailing');
        if (!$form.length) return;

        // Account picker
        $('#from #accounts input').live('change click select', function(){
            var $this = $(this);
            $('#from > input').val($this.val());
            return true;
        });

        // Init WYSIWYG
        $('#message .html textarea').tinymce({
                mode : "exact", elements: '',
                theme : "advanced",
                plugins : "pagebreak,style,layer,table,save,advhr,advimage,advlink,emotions,iespell,insertdatetime,preview,media,searchreplace,print,contextmenu,paste,directionality,fullscreen,noneditable,visualchars,nonbreaking,xhtmlxtras,template",
                theme_advanced_buttons1 : "code,preview,|,bold,italic,underline,strikethrough,|,justifyleft,justifycenter,justifyright,justifyfull,|,removeformat,formatselect,fontselect,fontsizeselect",
                theme_advanced_buttons2 : "bullist,numlist,|,outdent,indent,blockquote,|,link,unlink,image,|,forecolor,backcolor,|,tablecontrols,|,hr,visualaid,|,sub,sup,charmap",
                theme_advanced_toolbar_location : "top",
                theme_advanced_toolbar_align : "left",
                theme_advanced_statusbar_location : "bottom",
                theme_advanced_resizing : true
            });

        // Format switch
        $('#format input:radio', $form).change(function(){
            var format = $('#format input:radio:checked', $form).val();
            $('#message #message-formats li').hide().filter('.'+format).show();
            return true;
        }).change();

        // Auto-text
        $('#message .plain a').click(function(){
            $('#message .plain textarea').val(  $('#message .html textarea').text()  );
            return false;
        });
    });
})(jQuery);