<?php namespace lib\fun\TokenSpy;

require_once __DIR__.'/_FObject.php';

/** Page
 */
class Page extends _FObject {
    const OBJECT_TYPE = 'page';

    /** The page name
     * @var string
     */
    public $name;

    /** The page title
     * @var string
     */
    public $title;

    /** The page data
     * @var array
     */
    public $data = array();

    /** The page timeout, seconds
     * @var int|null
     */
    public $timeout;

    /** Get page proxy PHP script path, if exists
     * @return string|null
     */
    function getProxyScript(){
        $script = $this->_obj_path.'/proxy.php';
        if (file_exists($script))
            return $script;
        return null;
    }

    /** Render a page
     * @param array $context
     *      Additional context variables
     */
    function render(array $context = array()){
        $page = $this->_obj_path.'/index.php';
        return static::_render($page, $context);
    }

    /** Make a test 'static' page
     * @return Page
     */
    static function makeTestStaticPage(){
        $Page = new static('static');
        $Page->title = 'Example page';
        $Page->data = array(
            'text' => <<<HTML
<p>Three Rings for the Elven-kings under the sky,
<p>Seven for the Dwarf-lords in their halls of stone,
<p>Nine for Mortal Men doomed to die,
<p>One for the Dark Lord on his dark throne
<p>In the Land of Mordor where the Shadows lie.
<p>One Ring to rule them all, One Ring to find them,
<p>One Ring to bring them all and in the darkness bind them
<p>In the Land of Mordor where the Shadows lie.
HTML
        );
        return $Page;
    }
}
