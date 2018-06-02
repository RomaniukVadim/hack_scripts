$('.formbuilder-form .formbuilder-item')
    .live('mouseenter', function(){
        var $this = $(this);
        var item_meta = document._formbuilder.jsupport.get_form_item_metadata($this);

        if (item_meta && item_meta.description){
            var $tooltip = document._formbuilder.fun.tooltip($this, item_meta.description).addClass('description');
            $this.one('mouseleave', function(){ $tooltip.remove(); });
        }
        return true;
    });
