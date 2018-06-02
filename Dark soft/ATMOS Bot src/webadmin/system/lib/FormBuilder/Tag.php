<?php namespace FormBuilder\Tag;

/** Basic tags
 */

/** Abstract HTML Node
 */
abstract class _Node {
    /** The parent tag
     * @var Tag|null
     */
    protected $_parent;

    /** Set the parent tag
     * @param _Node|Tag $parent
     */
    function setParent($parent){
        $this->_parent = $parent;
        if ($this instanceof SimpleTagInterface)
            return $parent;
        return $this;
    }

    /** Return to the parent tag
     * @param int $to
     *      How many levels to go up
     * @return _Node|Tag|null
     */
    function up($to = 1){
        # $to = 1
        if ($to === 1)
            return $this->_parent;

        # $to = int
        if (is_int($to)){
            $c = $this;
            do {
                $c = $c->up();
            } while($c && $to-- > 0);
            return $c;
        }

        # Default
        return null;
    }

    /** Render the tag itself
     * @param bool $open
     *      `true` to render the tag
     *      `false` to close it
     * @return string
     */
    abstract function renderTag($open = true);

    /** Render the tag contents
     * @return string
     */
    abstract function renderContent();

    /** Render the tag and its contents
     * @return string
     */
    final function render(){
        return $this->renderTag(true).$this->renderContent().$this->renderTag(false);
    }

    function __toString(){
        return $this->renderTag(true);
    }
}



/** A tag that, being add()ed, returns a reference to the parent, not to self.
 * These are tags that are configured with a single __construct() call
 */
interface SimpleTagInterface {
}

/** A tag that contains pure text contents
 */
interface TextTagInterface {
}


#region Text Nodes
/** Plain node: just render its contents
 */
class DirectNode extends _Node implements TextTagInterface {
    /** The text content
     * @var string
     */
    protected $_content;

    /**
     * @param string $content
     */
    function __construct($content){
        $this->_content = $content;
    }

    function renderTag($open = true) {
        return null;
    }

    function renderContent(){
        return $this->_content;
    }

    function __toString(){
        return $this->renderContent();
    }
}



/** HTML node
 */
class HtmlNode extends DirectNode {
}



/** Plaintext node
 * Does `htmlspecialchars()` on the rendered content
 */
class TextNode extends HtmlNode {
    function renderContent() {
        return htmlspecialchars(parent::renderContent());
    }

    function __toString(){
        return $this->renderContent();
    }
}
#endregion



#region Generic
/** Tag node, capable of having attributes and children, iterable over its children
 */
abstract class _TagNode extends _Node implements \ArrayAccess, \IteratorAggregate, \Countable {
    #region Tag
    /** Tag name: 'b' -> '<b></b>'.
     * There's a special case of `null` tag which does not have any string representation and can be used as a void container
     * @var string
     */
    protected $_tagName;

    /** Create a custom tag
     * @param string $tagName
     *      The name of the tag
     * @param string|array $attributes
     *      Tag attributes: either an array, or a string which's parsed
     */
    function __construct($tagName, $attributes = null){
        $this->_tagName = $tagName;
        # Store the attributes
        $this->_bindAttributes();
        $this->attrs($attributes);
    }
    #endregion

    #region Children
    /** Child tags
     * @var Tag[]
     */
    protected $_children = array();

    /** Get all child items
     * @param bool $deep
     *      `false` to get immediate children, `true` to get all children
     */
    function getChildren($deep = true){
        if (!$deep)
            return $this->_children;

        $queue = $this->_children;
        $items = array();
        while (!is_null($tag = array_shift($queue))){
            $items[] = $tag;
            if ($tag instanceof _TagNode)
                $queue = array_merge($queue, $tag->_children);
        }
        return $items;
    }

    /** Add a child tag
     * @param _Node|Tag $tag
     */
    function add(_Node $tag){
        $this->_children[] = $tag;
        return $tag->setParent($this);
    }

    /** Add a Direct node
     * @param string $contents
     */
    function addDirect($contents){
        $this->add(new DirectNode($contents));
        return $this;
    }

    /** Add custom html
     * @param string $html
     */
    function addHtml($html){
        $this->add(new HtmlNode($html));
        return $this;
    }

    /** Add custom text. It's escaped on output.
     * @param string $text
     */
    function addText($text){
        $this->add(new TextNode($text));
        return $this;
    }

    function offsetExists($k) {     return isset(               $this->_children[$k] );     }
    function offsetGet($k) {        return                      $this->_children[$k];       }
    function offsetSet($k, $v) {    return                      $this->_children[$k] = $v;  }
    function offsetUnset($k) {      unset(                      $this->_children[$k] );     }
    function count() {              return count(               $this->_children );         }
    function getIterator() {        return new \ArrayIterator(  $this->_children );         }

    #endregion

    #region Attributes
    /** Tag attributes
     * @var (string|bool|null)[]
     */
    protected $_attributes = array();

    /** Set an attribute, or an array of attributes, to a value.
     * Use `true` for flag attributes
     * @param string|mixed[] $name
     * @param mixed $value
     */
    function attr($name, $value = null){
        if (is_array($name))
            return $this->attrs($name);
        else
            $this->_attributes[$name] = $value;
        return $this;
    }

    /** Set multiple attributes
     * @param string|mixed[] $attrs
     */
    function attrs($attrs){
        if (is_array($attrs))
            foreach ($attrs as $n => $v)
                $this->_attributes[$n] = $v;
        elseif (is_string($attrs))
            foreach (explode(' ', $attrs) as $s){
                list($name, $value) = explode('=', $s) + array(1 => true);
                $this->_attributes[$name] = trim($value, ' "\'\r\n\t');
            }
        return $this;
    }

    /** Bind attribute field references to $_attributes
     */
    protected function _bindAttributes(){
    }

    /** Prepare all the custom tag attributes into the $_attributes array
     */
    abstract protected function _prepareAttributes();
    #endregion

    #region Render
    /** Prepare the rendered attributes string
     * @return null|string
     */
    protected function _renderAttributes(){
        $this->_prepareAttributes();
        if (empty($this->_attributes))
            return null;

        $s = '';
        foreach ($this->_attributes as $name => $value)
            if (is_bool($value))
                $s .= $value? " {$name}" : '';
            elseif (!is_null($value))
                $s .= sprintf(' %s="%s"', $name, htmlspecialchars($value));
        return $s;
    }

    function renderTag($open = true) {
        # A special case
        if (is_null($this->_tagName))
            return '';
        # Text tags should always have the closing one
        $empty = (! $this instanceof TextTagInterface) && empty($this->_children);
        # Closing tag
        if (!$open)
            return $empty? '' : "</{$this->_tagName}>";
        # Opening tag
        return "<{$this->_tagName}" . $this->_renderAttributes() . ($empty? ' />' : '>');
    }

    function renderContent() {
        $s = '';
        foreach ($this->_children as $child)
            $s .= $child->render();
        return $s;
    }
    #endregion
}



/** A completely custom tag.
 * It also have a magic method which created child tags by name & properties :)
 *
 * Some tags have objects which define more functionality (<form>)
 * Some tags just have a preset to bind commonly used properties fast (<option>)
 * All other tags are just `Tag`s which receive the $attrs argument.
 *
 * Forms:
 * @method \FormBuilder\Tag\Form\Form form($action = null, $method = 'POST', $multipart = false)
 * @method \FormBuilder\Tag\Tag label($attrs = null)
 *
 * @method \FormBuilder\Tag\Form\Select select($name)
 * @method \FormBuilder\Tag\Form\Option option($value, $title, $disabled = false)
 * @method \FormBuilder\Tag\Form\Button button($type = 'submit', $html, $name = null, $value = null)
 * @method \FormBuilder\Tag\Form\Textarea textarea($name, $cols = null, $rows = null)
 * @method \FormBuilder\Tag\Form\Input input($type, $name, $attributes = null)
 *
 * @method \FormBuilder\Tag\Form\Input inputHidden($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputText($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputSearch($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputPassword($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputNumber($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputEmail($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputTel($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputUrl($name, $attributes = null)
 *
 * @method \FormBuilder\Tag\Form\Input inputColor($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputTime($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputDate($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputDateTime($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputMonth($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputWeek($name, $attributes = null)
 *
 * @method \FormBuilder\Tag\Form\InputFile inputFile($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputImage($name, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputRange($name, $attributes = null)
 *
 * @method \FormBuilder\Tag\Form\InputCheckbox inputCheckbox($name, $value, $attributes = null)
 * @method \FormBuilder\Tag\Form\InputDefaultCheckbox inputDefaultCheckbox($name, $value_on, $value_off, $attributes = null)
 * @method \FormBuilder\Tag\Form\InputCheckboxes inputCheckboxes($nameTemplate, $itemContainer = null)
 * @method \FormBuilder\Tag\Form\InputRadio inputRadio($name, $value, $attributes = null)
 *
 * @method \FormBuilder\Tag\Form\Input inputButton($name, $value, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputReset($name, $value, $attributes = null)
 * @method \FormBuilder\Tag\Form\Input inputSubmit($name, $value, $attributes = null)
 *
 * Basic support:
 * @method \FormBuilder\Tag\Tag style($attrs = null)
 * @method \FormBuilder\Tag\Tag link($attrs = null)
 * @method \FormBuilder\Tag\Tag a($attrs = null)
 * @method \FormBuilder\Tag\Tag img($attrs = null)
 *
 * @method \FormBuilder\Tag\Tag h1($attrs = null)
 * @method \FormBuilder\Tag\Tag h2($attrs = null)
 * @method \FormBuilder\Tag\Tag h3($attrs = null)
 * @method \FormBuilder\Tag\Tag h4($attrs = null)
 * @method \FormBuilder\Tag\Tag h5($attrs = null)
 * @method \FormBuilder\Tag\Tag h6($attrs = null)
 *
 * @method \FormBuilder\Tag\Tag div($attrs = null)
 * @method \FormBuilder\Tag\Tag code($attrs = null)
 * @method \FormBuilder\Tag\Tag br($attrs = null)
 * @method \FormBuilder\Tag\Tag p($attrs = null)
 * @method \FormBuilder\Tag\Tag pre($attrs = null)
 * @method \FormBuilder\Tag\Tag span($attrs = null)
 *
 * @method \FormBuilder\Tag\Tag dl($attrs = null)
 * @method \FormBuilder\Tag\Tag dt($attrs = null)
 * @method \FormBuilder\Tag\Tag dd($attrs = null)
 *
 * @method \FormBuilder\Tag\Tag ul($attrs = null)
 * @method \FormBuilder\Tag\Tag ol($attrs = null)
 * @method \FormBuilder\Tag\Tag li($attrs = null)
 *
 * @method \FormBuilder\Tag\Tag table($attrs = null)
 * @method \FormBuilder\Tag\Tag caption($attrs = null)
 * @method \FormBuilder\Tag\Tag thead($attrs = null)
 * @method \FormBuilder\Tag\Tag tbody($attrs = null)
 * @method \FormBuilder\Tag\Tag tfoot($attrs = null)
 * @method \FormBuilder\Tag\Tag tr($attrs = null)
 * @method \FormBuilder\Tag\Tag td($attrs = null)
 * @method \FormBuilder\Tag\Tag th($attrs = null)
 *
 * @method \FormBuilder\Tag\Tag article($attrs = null) Defines an article
 * @method \FormBuilder\Tag\Tag aside($attrs = null) Defines content aside from the page content
 * @method \FormBuilder\Tag\Tag details($attrs = null) Defines additional details that the user can view or hide
 * @method \FormBuilder\Tag\Tag figure($attrs = null) Specifies self-contained content, like illustrations, diagrams, photos, code listings, etc.
 * @method \FormBuilder\Tag\Tag figcaption($attrs = null) Defines a caption for a <figure> element
 * @method \FormBuilder\Tag\Tag footer($attrs = null) Defines a footer for a document or section
 * @method \FormBuilder\Tag\Tag header($attrs = null) Defines a header for a document or section
 * @method \FormBuilder\Tag\Tag meter($attrs = null) Defines a scalar measurement within a known range (a gauge)
 * @method \FormBuilder\Tag\Tag nav($attrs = null) Defines navigation links
 * @method \FormBuilder\Tag\Tag progress($attrs = null) Represents the progress of a task
 * @method \FormBuilder\Tag\Tag section($attrs = null) Defines a section in a document
 * @method \FormBuilder\Tag\Tag summary($attrs = null) Defines a visible heading for a <details> element
 * @method \FormBuilder\Tag\Tag time($attrs = null) Defines a date/time
 *
 * Tag presets & extended support:
 * @method \FormBuilder\Tag\HTML\Script script($attrs = null)
 * @method \FormBuilder\Tag\HTML\ScriptInline scriptInline($script, $type = 'text/javascript', $attributes = null)
 * @method \FormBuilder\Tag\Tag javascript($src, $attributes = null)
 * @method \FormBuilder\Tag\Tag css($href, $media = null, $attributes = null)
 *
 * @method \FormBuilder\Tag\Tag fieldset($legend = null)
 * @method \FormBuilder\Tag\Tag optgroup($label = null, $disabled = null)
 */
class Tag extends _TagNode {
    /** The mapping from tag names to classes that implement it:
     *
     * string => string: Create the named class, arguments are transparently transferred
     * string => array:
     *      Instantiate the class named as the 1st argument.
     *      Other items define the mapping:
     *          string => string: Attribute value ('type' => 'hidden':  set 'type' attribute as 'hidden') - preset
     *          string => int: Attribute position ('type' => 1:         set 'type' attribute from arg 1) - grab argument
     *          int => int: Argument position     (0 => 1:              pass #0 argument from arg 1) - grab argument
     *          int => string: Argument value     (0 => 'hidden':       pass #0 argument as 'hidden') - preset
     *      NOTE: if you specify numeric array keys explicitly - remember array_shift() will decrease all there by 1!
     *            Thus, 1 => 'lol' becomes 0 => 'lol'
     *
     * @var string[]|int[]
     */
    static public $TAGS = array(
        # Form
        'form'          => '\FormBuilder\Tag\Form\Form',
        'select'        => '\FormBuilder\Tag\Form\Select',
        'option'        => '\FormBuilder\Tag\Form\Option',
        'button'        => '\FormBuilder\Tag\Form\Button',
        'textarea'      => '\FormBuilder\Tag\Form\Textarea',
        'input'         => '\FormBuilder\Tag\Form\Input',

        'inputhidden'   => array('\FormBuilder\Tag\Form\Input', 'hidden', 0, 1),
        'inputtext'     => array('\FormBuilder\Tag\Form\Input', 'text', 0, 1),
        'inputsearch'   => array('\FormBuilder\Tag\Form\Input', 'search', 0, 1),
        'inputpassword' => array('\FormBuilder\Tag\Form\Input', 'password', 0, 1),
        'inputnumber'   => array('\FormBuilder\Tag\Form\Input', 'number', 0, 1),
        'inputemail'    => array('\FormBuilder\Tag\Form\Input', 'email', 0, 1),
        'inputtel'      => array('\FormBuilder\Tag\Form\Input', 'tel', 0, 1),
        'inputurl'      => array('\FormBuilder\Tag\Form\Input', 'url', 0, 1),

        'inputcolor'    => array('\FormBuilder\Tag\Form\Input', 'color', 0, 1),
        'inputtime'     => array('\FormBuilder\Tag\Form\Input', 'time', 0, 1),
        'inputdate'     => array('\FormBuilder\Tag\Form\Input', 'date', 0, 1),
        'inputdatetime' => array('\FormBuilder\Tag\Form\Input', 'datetime', 0, 1),
        'inputmonth'    => array('\FormBuilder\Tag\Form\Input', 'month', 0, 1),
        'inputweek'     => array('\FormBuilder\Tag\Form\Input', 'week', 0, 1),

        'inputfile'     => '\FormBuilder\Tag\Form\InputFile',
        'inputimage'    => array('\FormBuilder\Tag\Form\Input', 'image', 0, 1),
        'inputrange'    => array('\FormBuilder\Tag\Form\Input', 'range', 0, 1),

        'inputcheckbox'         => '\FormBuilder\Tag\Form\InputCheckbox',
        'inputdefaultcheckbox'  => '\FormBuilder\Tag\Form\InputDefaultCheckbox',
        'inputcheckboxes'       => '\FormBuilder\Tag\Form\InputCheckboxes',
        'inputradio'            => '\FormBuilder\Tag\Form\InputRadio',

        'inputbutton'   => array('\FormBuilder\Tag\Form\InputButton', 'button', 0, 1, 2),
        'inputreset'    => array('\FormBuilder\Tag\Form\InputButton', 'reset', 0, 1, 2),
        'inputsubmit'   => array('\FormBuilder\Tag\Form\InputButton', 'submit', 0, 1, 2),

        # Presets & Extended support
        'script'        => '\FormBuilder\Tag\HTML\Script',
        'scriptinline'  => '\FormBuilder\Tag\HTML\ScriptInline',
        'javascript'    => array('\FormBuilder\Tag\HTML\Script', 'script', 1,   'type' => 'text/javascript', 'src' => 0),
        'css'           => array('\FormBuilder\Tag\Tag', 'link', 2,     'rel' => 'stylesheet', 'href' => 0, 'media' => 1),

        'fieldset'      => array('\FormBuilder\Tag\Tag', 'legend' => 0),
        'optgroup'      => array('\FormBuilder\Tag\Tag', 'label' => 0, 'disabled' => 1),
    );

    #region Magic tags
    /** Magic tags constructor
     * @param string $name
     *      The name for the new tag
     * @param array $args
     *      Tag constructor arguments
     * @return Tag
     */
    function __call($tagName, $args) {
        $tagName = strtolower($tagName);
        if (isset(static::$TAGS[$tagName])){
            $className = '\FormBuilder\Tag\Tag'; # the default
            $classArgs = array(); # contructor arguments
            $attributes = array(); # named attributes
            $preset = static::$TAGS[$tagName];# shortcut

            # Get $class, $args from the preset
            if (is_string($preset)){
                $className = $preset;
                $classArgs = $args;
            } elseif (is_array($preset)){
                $className = array_shift($preset);
                # Prepare the named attributes sequence
                foreach ($preset as $a => $b){
                    if (is_int($a) && is_int($b)) # $a:Argument, $b:potision
                        $classArgs[$a] = isset($args[$b])? $args[$b] : null;
                    elseif (!is_int($a) && !is_int($b)) # $a:Attribute, $b:value
                        $attributes[$a] = $b;
                    elseif (is_int($a)) # $a;Argument, $b:value
                        $classArgs[$a] = $b;
                    elseif (is_int($b)) # $a:Attribute, $b:position
                        $attributes[$a] = isset($args[$b])? $args[$b] : null;
                }

                # Remove nulls from the end so constructor can accept the defaults
                for ($i=count($classArgs)-1; $i>=0; $i--)
                    if (is_null($classArgs[$i]))
                        unset($classArgs[$i]);
                    else break;
            } else
                $classArgs = $args; # the default

            if (0 && 'debug')
                xdebug_var_dump(array(
                    '$preset' => $preset, '$args' => $args,
                    '$className' => $className, '$classArgs' => $classArgs,
                    '$attributes' => $attributes
                ));

            # Instantiate
            $r = new \ReflectionClass($className);
            $tag = $r->newInstanceArgs($classArgs);
            if ($attributes)
                $tag->attr($attributes);
        } else
            $tag = new Tag($tagName, reset($args)); # Generic tag

        return $this->add($tag);
    }
    #endregion


    #region Attributes
    /** (attribute "class") Tag CSS classes set
     * @var string[]
     */
    protected $_classes = array();

    /** Add CSS class, or a string of classes, or an array of classes
     * - addClass('first')
     * - addClass('first active')
     * - addClass(array('first', 'active'))
     * @param string|string[] $class
     */
    function addClass($class){
        if (is_string($class))
            $class = explode(' ', $class);
        foreach ((array)$class as $class)
            $this->_classes[] = $class;
        return $this;
    }

    /** id
     * @var string|null
     */
    protected $_id;

    function id($id){
        $this->_id = $id;
        return $this;
    }

    /** Data attributes
     * @var mixed[]
     */
    protected $_data = array();

    /** Assign data-* attribute[s] to the tag:
     * - data('name', 'value')
     * - data(array('name' => 'value', ...))
     * @param string|mixed[] $name
     * @param string|null $value
     */
    function data($name, $value = null){
        if (is_string($name))
            $this->_data[$name] = $value;
        else
            foreach ($name as $n => $v)
                $this->_data[$n] = $v;
        return $this;
    }

    protected function _bindAttributes() {
        parent::_bindAttributes();
        $this->_attributes['id'] = &$this->_id;
    }


    protected function _prepareAttributes(){
        $this->_attributes['class'] = implode(' ', $this->_classes)?: null;
        foreach ($this->_data as $n => $v)
            $this->_attributes["data-{$n}"] = $v;
    }
    #endregion
}
#endregion
