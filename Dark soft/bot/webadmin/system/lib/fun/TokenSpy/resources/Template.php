<?php namespace lib\fun\TokenSpy;

require_once __DIR__.'/_FObject.php';

/** Template
 */
class Template extends _FObject {
    const OBJECT_TYPE = 'template';

    /** The template name
     * @var string
     */
    public $name;

    /** Render a page into a template
     * @param Page $page
     * @param array $context
     *      Additional context variables
     */
    function render(Page $page, array $context = array()){
        $template = $this->_obj_path.'/index.php';

        $context += array(
            'template' => $this,
            'page' => $page,
        );

        return static::_render(
            $template,
            array(
                'page_content' => $page->render($context)
            ) + $context
        );
    }
}
