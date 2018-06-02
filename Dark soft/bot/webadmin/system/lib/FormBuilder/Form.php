<?php namespace FormBuilder\Tag\Form;

/** Form tags
 * Based on: http://www.whatwg.org/specs/web-apps/current-work/multipage/
 */

use FormBuilder\Tag\Tag;
use FormBuilder\Tag\TextTagInterface;
use FormBuilder\Tag\SimpleTagInterface;

/** Form
 */
class Form extends Tag {
    # TODO: csrf
    # TODO: a[] values handling in the bind*()
    # TODO: validation
    # TODO: each generator for an array or radios/checkboxes (virtual container element)
    # TODO: arrays of values (data[options][])
    # TODO: validation

    /** Create a new form
     * @param string|null $action
     *      Form action
     * @param string|null $method
     *      Submit method: 'GET', 'POST'
     * @param bool $multipart
     *      `true` to use multipart encoding for submission
     */
    function __construct($action = null, $method = 'POST', $multipart = false) {
        parent::__construct('form', array(
            'action' => $action,
            'method' => $method,
            'enctype' => $multipart? 'multipart/form-data' : null,
        ));
    }

    /** Get all the child form items
     * @return _FormItem[]
     */
    function getChildItems(){
        $items = array();
        foreach ($this->getChildren(true) as $tag)
            if ($tag instanceof _FormItem)
                $items[] = $tag;
        return $items;
    }

    /** Bind the storage object to form item values (referenced)
     * @param array|object $storage
     *      The array|object to bind the form items to
     * @param string $prefix
     *      Form items prefix to use. RegExp.
     */
    function bindStorage(&$storage, $prefix = '^'){
        $prefix = "~{$prefix}~S";
        $found = false;
        foreach ($this->getChildItems() as $item){
            $item_name = preg_replace($prefix, '', $item->_name, 1, $c);
            if ($c){ # regexp matched, form item matches
                $val = &static::_traverseData($storage, $item_name, $found);
//                xdebug_var_dump(array('$item->_name' => $item_name, '$found' => $found, '$val' => $val));
                if ($found)
                    $item->bindValue($val);
            }
        }
        return $this;
    }

    /** Bind the request object to form item values: copy
     * Note: always use after bindStorage()!
     * @param array $request
     */
    function bindRequest($request){
        $found = false;
        foreach ($this->getChildItems() as $item)
            if (!is_null($item->_name)){# skip items with no name
                $val = static::_traverseData($request, $item->_name, $found);
//                xdebug_var_dump(array('$item->_name' => $item->_name, '$found' => $found, '$val' => $val));
                if ($found)
                    $item->value($val);
            }
        return $this;
    }

    /** Enable JavaScript support for this form
     */
    function javascriptSupport(){
        return new FormJavascriptSupport($this);
    }

    #region Utility
    /** Tokenize paths like "a[b][c][d]"
     * @param string $path
     * @return string[]
     *      "a[b][c][d]" -> ['a','b','c','d']
     */
    static function _tokenizePath($path){
        $ret = array();
        $nmatches = preg_match_all('~^([^\[]+)|\[([^\]]*)\]~S', $path, $matches);
        for ($i=0; $i<$nmatches; $i++){
            $l_name = strlen($matches[1][$i])? $matches[1][$i] : $matches[2][$i]; # the name for the next level
            $ret[] = $l_name;
        }
        return $ret;
    }

    /** Tokenize paths like "a[b][c][d]" with trace
     * @param string $path
     * @return (array[2])[]
     *      array of array( path-prefix, name)
     *      "a[b][c]" => [ ['', 'a'], ['a', 'b'], ['a[b]', 'c'] ]
     */
    static function _tokenizePathTrace($path){
        $path = static::_tokenizePath($path);
        $ret = array();
        $trace = '';
        foreach ($path as $i => $name){
            $ret[] = array( $trace, $name );
            $trace .= $i==0? $name : "[{$name}]";
        }
        return $ret;
    }

    /** Traverse into $data to find $name and return a reference of the found value
     * @param array|object $data
     *      The data to traverse. E.g. $_REQUEST
     * @param string $path
     *      The value to find. E.g. "sets[b][c][d]"
     * @param bool $found
     *      Output: was the value found
     * @return &mixed|&null
     */
    static function &_traverseData(&$data, $path, &$found = null){
        $cur = &$data; # the current $data level
        foreach (static::_tokenizePath($path) as $name){
            # Go deeper
            if (is_object($cur) && property_exists($cur, $name))
                $cur = &$cur->{$name};
            elseif (is_array($cur) && array_key_exists($name, $cur))
                $cur = &$cur[$name];
            else {
                $found = false;
                $null = null;
                return $null; # Failed to go deep enough
            }
        }
        $found = true;
        return $cur;
    }
    #endregion
}



/** Javascript supporter for the form
 * Required JS libraries: jQuery
 */
class FormJavascriptSupport {
    # TODO: labels
    # TODO: errors
    # TODO: validation

    /** The form item being supported
     * @var Form
     */
    protected $form;

    /** Enable JS-support for a form
     * @param Form $form
     */
    function __construct(Form $form) {
        $this->form = $form;
//        $this->form->javascript('theme/js/jquery-1.7.1.min.js'); # FIXME: remove, that's temp!
    }

    /** Attach an inline script to the form
     * @param string $script
     */
    protected function _formScript($script){
        $this->form->scriptInline(file_get_contents(__DIR__.'/i/'.$script), null, array('defer' => true));
    }

    /** _exportItemsMetadata() called
     * @var bool
     */
    private $_exportItemsMetadata_done = false;

    /** Export items metadata into items' <* data-formbuilder-item-meta>
     * It works just once
     */
    protected function _exportItemsMetadata(){
        if ($this->_exportItemsMetadata_done)
            return;
        $this->_exportItemsMetadata_done = true;

        # Mark the form
        $form_marker = uniqid();
        $this->form->addClass(array('formbuilder-form'))->data('formbuilder-form-marker', $form_marker);

        # Collect items' metadata
        $meta = array();
        foreach ($this->form->getChildItems() as $item){
            $item->addClass('formbuilder-item');
            $meta[ $item->_name ] = $item->exportExtensions();
        }

        # Prepare the environment
        $this->_formScript('_metaExport.js');
        $this->form->style()->addDirect(file_get_contents(__DIR__.'/i/default.css'));

        # Store the metadata in `document`
        $jsonset = sprintf(
            'document._formbuilder.jsupport.add_form_items_metadata(%s, %s);',
            json_encode($form_marker), # Form CSS class
            json_encode($meta) # The metadata
        );
        $this->form->scriptInline($jsonset);
    }

    /** Enable the `hints` support: show while focus
     * Each form item which has a ->hint() set will receive a baloon hint, active while you have focus
     */
    function hints(){
        $this->_exportItemsMetadata();
        $this->_formScript('hints.js');
        return $this;
    }

    /** Enable the `description` support: show on mouseover
     * Each form item which has a ->description() set will receive a baloon hint, active while you hover
     */
    function descriptions(){
        $this->_exportItemsMetadata();
        $this->_formScript('descriptions.js');
        return $this;
    }
}



/** Generic Form Item abstraction
 */
abstract class _FormItem extends Tag {
    /** Abstract form item
     * @param string $tagName
     * @param string $name
     *      Form item name
     * @param string|string[]|null $attributes
     */
    function __construct($tagName, $name, $attributes = null) {
        parent::__construct($tagName, $attributes);
        $this->_name = $name;
    }

    #region Attributes
    /** Form item name
     * @var string
     * @readonly
     */
    public $_name;

    /** Form item value, if any.
     * Is not always an attribute: thus, becomes virtual.
     * E.g. for <textarea> it becomes the contents
     * For <checkbox> this is a (bool) which specifies whether it's set
     * @var mixed|null
     * @readonly
     */
    protected  $_value;


    /** Called when the $_value changes.
     * Override me for a custom behavior
     * @param bool $bind
     *      `true` if using bindValue(), `false` otherwise
     */
    protected function _onValueChanged($bind = false){
    }

    /** Set the form item value
     * @param mixed $value
     */
    function value($value){
        $this->_value = $value;
        $this->_onValueChanged(false);
        return $this;
    }

    /** Bind a value reference
     * @param mixed $value
     */
    function bindValue(&$value){
        $this->_value = &$value;
        # Rebing the 'value' attribute
        if (array_key_exists('value', $this->_attributes))
            $this->_attributes['value'] = &$this->_value;
        $this->_onValueChanged(true);
        return $this;
    }

    /** Is it required?
     * @var bool
     */
    protected $_required = false;
    protected $_requred_error = 'Required';

    /** Set the required flag
     * @param bool $required
     * @return _FormItem
     */
    function required($required = true, $error_message = 'Required'){
        $this->_required = $required;
        $this->_requred_error = $error_message;
        return $this;
    }

    /** Is it disabled?
     * @var bool
     */
    protected $_disabled = false;

    /** Set the disabled flag
     * @param bool $disabled
     */
    function disabled($disabled = true){
        $this->_disabled = $disabled;
        return $this;
    }

    /** Is it readonly?
     * @var bool
     */
    protected $_readonly = false;

    /** Set the read-only flag
     * @param bool $readonly
     * @return _FormItem
     */
    function readonly($readonly = true){
        $this->_readonly = $readonly;
        return $this;
    }

    /** Max input length
     * @var int|null
     */
    protected $_maxlength;

    /** Maximum input length
     * @param int $maxlength
     */
    function maxlength($maxlength){
        $this->_maxlength = $maxlength;
        return $this;
    }

    /** Min value
     * @var int|null
     */
    protected $_min;

    /** Max value
     * @var int|null
     */
    protected $_max;

    /** Min, max values
     * @param int|null $min
     * @param int|null $max
     */
    function minmax($min = null, $max = null){
        $this->_min = $min;
        $this->_max = $max;
        return $this;
    }

    protected function _bindAttributes() {
        parent::_bindAttributes();
        $this->_attributes['name'] = &$this->_name;
        $this->_attributes['required'] = &$this->_required;
        $this->_attributes['disabled'] = &$this->_disabled;
        $this->_attributes['readonly'] = &$this->_readonly;
        $this->_attributes['maxlength'] = &$this->_maxlength;
        $this->_attributes['min'] = &$this->_min;
        $this->_attributes['max'] = &$this->_max;
    }
    #endregion

    #region Extensions
    /** Go up the tree and find the closest Form tag
     * @return Form
     */
    function getParentForm(){
        $tag = $this;
        do {
            $tag = $tag->up();
        } while ($tag && !$tag instanceof Form);
        return $tag;
    }

    /** Get an array of values of all extension variables which are not reflected in HTML rendering of the tag
     * JS support uses that
     * @return array()
     */
    function exportExtensions(){
        return array(
            'label' => $this->_label,
            'hint' => $this->_hint,
            'description' => $this->_description,
            'errors' => $this->_errors,
        );
    }

    /** Item label
     * @var string|null
     */
    protected $_label;

    /** Set a label for the form item.
     * This just stores the data, rendering happens elsewhere
     * @param string $text
     */
    function label($text){
        $this->_label = $text;
        return $this;
    }

    /** Item hint
     * This just stores the data, rendering happens elsewhere
     * @var string|null
     */
    protected $_hint;

    /** Set a hint for the form item.
     * This usually tells how the data should be entered.
     * @param string $text
     */
    function hint($text){
        $this->_hint = $text;
        return $this;
    }

    /** Item description
     * This just stores the data, rendering happens elsewhere
     * @var string|null
     */
    protected $_description;

    /** Set a description for the form item
     * This usually describes what the input is for
     * @param string $text
     */
    function description($text){
        $this->_description = $text;
        return $this;
    }

    /** Errors for this item
     * @var string[]
     */
    protected $_errors = array();

    /** Set the error text associated with this form item
     * This just stores the data, rendering happens elsewhere
     * @param string $text
     */
    function error($text){
        $this->_errors[] = $text;
        return $this;
    }
    #endregion
}



/** Generic Form Item abstraction which receives manual user input
 */
abstract class _InputFormItem extends _FormItem {
    #region Attributes
    /** Validation RegExp pattern
     * @var string|null
     * @readonly
     */
    protected $_pattern;
    protected $_pattern_error;

    function exportExtensions() {
        return parent::exportExtensions() + array(
            'pattern:error' => $this->_pattern_error
        );
    }

    /** Set the regexp validation pattern
     * @param string $pattern
     * @param string $error_message
     */
    function pattern($pattern, $error_message = 'Invalid input'){
        $this->_pattern = $pattern;
        if ($error_message)
            $this->_pattern_error = $error_message;
        return $this;
    }

    /** Placeholder value
     * @var string|null
     */
    protected $_placeholder;

    /** The placeholder value
     * @param string $placeholder
     */
    function placeholder($placeholder){
        $this->_placeholder = $placeholder;
        return $this;
    }

    protected function _bindAttributes() {
        parent::_bindAttributes();
        $this->_attributes['pattern'] = &$this->_pattern;
        $this->_attributes['placeholder'] = &$this->_placeholder;
    }
    #endregion
}



/** Generic Form Item which provides a range of choices
 */
abstract class _SelectFormItem extends _FormItem {
    /** Choices for this form item, in the form of array(value => title)
     * @var string[]
     */
    protected $_options = array();

    /** Options for the input: array( value => title )
     * @param string[] $options
     */
    function options(array $options){
        $this->_options = $options;
        return $this;
    }

    #region Attributes
    /** Specifies that multiple options can be selected.
     * Virtual: only <select> uses it as an attribute
     * @var bool
     */
    protected $_multiple = false;

    /** Multiple options can be selected
     * @param bool $multiple
     */
    function multiple($multiple = true){
        $this->_multiple = $multiple;
        return $this;
    }
    #endregion
}



/** <option>
 */
class Option extends Tag implements SimpleTagInterface {
    /** Option title
     * @var string
     */
    protected $_title;

    /** Create the <option> tag
     * @param string $value Option value
     * @param string $title Option value
     * @param bool $disabled Is it disabled?
     */
    function __construct($value, $title, $disabled = false) {
        parent::__construct('option', array('value' => $value, 'disabled' => $disabled));
        $this->addText($title);
    }
}


/** <select>
 */
class Select extends _SelectFormItem {
    /** <select>
     * @param string $name Item name
     */
    function __construct($name) {
        parent::__construct('select', $name);
    }

    /** Bulk add options
     * @param string[] $options Select options: array( value => title )
     * @return _SelectFormItem
     */
    function options(array $options) {
        foreach ($options as $value => $title)
            $this->add(new Option($value, $title));
        return parent::options($options);
    }

    protected function _onValueChanged($bind = false) { # todo: OPTGROUP support
        parent::_onValueChanged($bind);
        foreach ($this->getChildren() as $child)
            if ($child instanceof Option)
                $child->attr('selected', $child->_attributes['value'] == $this->_value);
    }

    protected function _bindAttributes() {
        parent::_bindAttributes();
        $this->_attributes['multiple'] = &$this->_multiple;
    }
}



/** <textarea>
 */
class Textarea extends _InputFormItem implements TextTagInterface {
    /** <textarea>
     * @param string $name Item name
     * @param int|null $cols The number of columns
     * @param int|null $rows The number of rows
     */
    function __construct($name, $cols = null, $rows = null) {
        parent::__construct('textarea', $name, array('cols' => $cols, 'rows' => $rows));
    }

    function renderContent() {
        return htmlspecialchars($this->_value); # it doesn't have any child tags
    }
}



/** <button>
 */
class Button extends _FormItem implements SimpleTagInterface {
    /** <button>
     * @param string $type Button type: 'button', 'submit', 'reset'
     * @param string $html HTML used as the button title
     * @param null $name Item name
     * @param null $value Item value
     */
    function __construct($type = 'submit', $html, $name = null, $value = null) {
        parent::__construct('button', $name, array('type' => $type, 'value' => $value));
        $this->addHtml($html);
    }

    protected function _prepareAttributes() {
        parent::_prepareAttributes();
        $this->_attributes['value'] = $this->_value;
    }
}



/** <input *>, generic
 */
class Input extends _InputFormItem {
    /** <input *>, generic
     * @param string $type Input type
     * @param string $name Input name
     * @param string|string[]|null $attributes Attributes
     */
    function __construct($type, $name, $attributes = null) {
        parent::__construct('input', $name, $attributes);
        $this->attr('type', $type);
    }

    protected function _bindAttributes() {
        parent::_bindAttributes();
        $this->_attributes['value'] = &$this->_value;
    }
}



/** <input type="submit,reset,button">
 */
class InputButton extends Input {
    /** <input type="submit,reset,button">
     * @param string $type Input type
     * @param string $name Input name
     * @param mixed|null $value The input button value
     * @param string|string[]|null $attributes Attributes
     */
    function __construct($type, $name, $value, $attributes = null) {
        parent::__construct($type, $name, $attributes);
        $this->value($value);
    }
}



/** <input type=checkbox>, <input type=radio> base class
 */
class _InputCheckable extends Input {
    /** <input type=checkbox>, <input type=radio>
     * @param string $type
     * @param string $name
     * @param mixed $value
     * @param string|string[]|null $attributes Attributes
     */
    function __construct($type, $name, $value, $attributes = null) {
        parent::__construct($type, $name, $attributes);
        $this->_value = false; # Provide the default
        $this->_checkedValue = $value;
    }

    /** The 'value' attribute here is stored separately,
     * because `value` is reserved for the actual result, which is `true` or `false` here
     * @var mixed
     */
    protected $_checkedValue;

    protected function _bindAttributes() {
        parent::_bindAttributes();
        unset($this->_attributes['value']); # unbind
    }

    protected function _prepareAttributes() {
        parent::_prepareAttributes();
        $this->_attributes['value'] = $this->_checkedValue; # rewrite
        $this->_attributes['checked'] = ($this->_checkedValue == $this->_value);
    }
}



/** <input type=radio>
 */
class InputRadio extends _InputCheckable {
    /** <input type=radio>
     * @param string $name
     * @param string $value
     * @param string|string[]|null $attributes
     */
    function __construct($name, $value, $attributes = null) {
        parent::__construct('radio', $name, $value, $attributes);
    }
}

/** <input type=checkbox>
 */
class InputCheckbox extends _InputCheckable {
    /** <input type=checkbox>
     * @param string $name
     * @param string $value
     * @param string|string[]|null $attributes
     */
    function __construct($name, $value, $attributes = null) {
        parent::__construct('checkbox', $name, $value, $attributes);
    }
}



/** <input type=checkbox>, with an extra <input type=hidden> to provide the default value
 */
class InputDefaultCheckbox extends InputCheckbox {
    protected $_uncheckedValue;
    /**
     * @param string $name
     * @param string $value_on Value when checked
     * @param string $value_off Value when unchecked
     * @param string|string[]|null $attributes
     */
    function __construct($name, $value_on, $value_off, $attributes = null) {
        parent::__construct($name, $value_on, $attributes);
        $this->_uncheckedValue = $value_off;
    }

    function renderTag($open = true) {
        $s = '';
        if ($open)
            $s .= '<input type="hidden" name="'.htmlspecialchars($this->_name).'" value="'.htmlspecialchars($this->_uncheckedValue).'" />';
        return $s.parent::renderTag($open);
    }
}





class InputFile extends Input {} # TODO
