<?php namespace FormBuilder\Tag\Form;

/** Form extensions
 */

use FormBuilder\Tag\Tag;



/** Helper to handle a list of checkboxes.
 * It's bound to a property pattern: 'criteria[%s]'.
 * On POST, the `criteria` property receives a mapping { value: value }
 */
class InputCheckboxes extends _SelectFormItem {
    /** Template name for child inputs
     * @var string
     */
    protected $_nameTemplate;

    /** Create the checkboxes array container
     * @param string|null $nameTemplate
     *      Child checkbox name pattern, with '%s' in the right place
     */
    function __construct($nameTemplate, $itemWrapper = null) {
        $this->_nameTemplate = $nameTemplate;
        # Fake the name so we're an array of values :)
        $name = preg_replace('~\[%.?\]$~', '', $this->_nameTemplate);
        parent::__construct(null, $name);
    }

    /** The default item wrapper implementation
     * @param Tag $root
     * @param mixed $value
     * @param string $title
     */
    function _defaultItemWrapper(Tag $root, $value, $title){
        $root
            ->add(new Tag('label'))
            ->add(new InputCheckbox(  sprintf($this->_nameTemplate, $value)  , $value))
            ->addText($title)
        ;
    }

    /** Bulk add options as checkboxes, each wrapped into $itemContainer
     * @param string[] $options Select options: array( value => title )
     * @param Tag|null $itemWrapper
     *      Wrapper tag to use for each item.
     * @return _SelectFormItem
     */
    function options(array $options, $itemWrapper = null) {
        foreach ($options as $value => $title){
            $this_options[$value] = $value;
            # Decide on the wrapper
            $container = null;
            if ($itemWrapper instanceof Tag)
                $container = $this->add(clone $itemWrapper);
            else
                $container = $this;

            # Use the default
            $this->_defaultItemWrapper($container, $value, $title);
        }
        return parent::options(
            empty($options)
                ?array()
                :array_combine( $keys = array_keys($options), $keys ) );
    }

    protected function _onValueChanged($bind = false) {
        parent::_onValueChanged($bind);
        # Just ensure it's an array
        if ($bind && !is_array($this->_value))
            $this->_value = array();
        # Ensure all keys & values are valid
        if (!$bind)
            $this->_value = array_intersect_assoc( $this->_value, $this->_options );
    }
}
