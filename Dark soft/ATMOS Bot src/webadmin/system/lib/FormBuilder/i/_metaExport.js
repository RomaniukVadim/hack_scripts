(function($){
    /** FormBuilder functions
     * @constructor
     */
    var FormBuilderFun = function(){
    };

    /** Display a tooltip
     * @param {jQuery} $root The item to display the tooltip for
     * @param {String} html Tooltip HTML text
     * @return {jQuery} The tooltip
     */
    FormBuilderFun.prototype.tooltip = function($root, html){ // TODO: positioning: top/bottom/left/right
        // Create a tooltip, don't forget to remove it
        var $tooltip = $('<div class="formbuilder-tooltip"></div>').html(html);
        $tooltip.appendTo(document.body);

        // Position it
        var pos = $root.offset();

        $tooltip.offset({
            top: pos.top + $root.outerHeight(true) + parseInt($tooltip.css('margin-top')),
            left: pos.left
        });

        return $tooltip;
    };



    /** JavaScript FormBuilder support
     * @constructor
     */
    var JSupport = function(){
        /** Form metadata: { form-marker: { input-name: Object } }
         * @type {Object.<String, Object.<String, Object>>}
         */
        this.form_meta = {};
    };

    /** Set Form items metadata
     * @param {String} form_marker Form marker, assigned to its `formbuilder-form-marker` data-attribute
     * @param {Object} items_metadata Items metadata: { input-name: Object }
     */
    JSupport.prototype.add_form_items_metadata = function(form_marker, items_metadata){
        this.form_meta[form_marker] = items_metadata;
    };

    /** Get metadata object for a single form item
     * @param {Node} item The form item node to get the metadata for
     * @return {Object?}
     */
    JSupport.prototype.get_form_item_metadata = function(item){
        var $item = $(item);
        var form_marker = $item.closest('form.formbuilder-form').data('formbuilder-form-marker');
        var item_name = $item.attr('name');
        try { return this.form_meta[form_marker][item_name]; }
        catch (e){ return undefined; }
    };



    /** FormBuilder Helper
     * @constructor
     */
    var FormBuilder = function(){
        /** Various helper functions
         * @type {Object.<String, Function>}
         */
        this.fun = new FormBuilderFun();

        /** JavaScript support
         * @type {JSupport}
         */
        this.jsupport = new JSupport();
    };

    // Initialize
    /**
     * @type {FormBuilder}
     */
    document._formbuilder;

    if (document._formbuilder === undefined)
        document._formbuilder = new FormBuilder();
})(jQuery);
