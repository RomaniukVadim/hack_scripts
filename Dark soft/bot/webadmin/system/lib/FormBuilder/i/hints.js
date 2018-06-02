$('.formbuilder-form .formbuilder-item')
    .live('focus', function(){
        var $this = $(this);
        var item_meta = document._formbuilder.jsupport.get_form_item_metadata($this);

        if (item_meta && item_meta.hint){
            var $tooltip = document._formbuilder.fun.tooltip($this, item_meta.hint).addClass('hint');
            $this.one('blur', function(){ $tooltip.remove(); });
        }
        return true;
    });
