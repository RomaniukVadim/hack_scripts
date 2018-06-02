<?php namespace lib\fun\TokenSpy;

use Citadel\Models\TokenSpy\Rule;

require_once __DIR__.'/_FObject.php';

/** Skeleton
 */
class Skeleton extends _FObject {
    const OBJECT_TYPE = 'skeleton';

    /** The skeleton name
     * @var string
     */
    public $name;

    /** Skeleton info
     * @var object
     */
    protected $info;

    /** Skeleton values
     * @var array
     */
    public $values = array();

    function __construct($name) {
        parent::__construct($name);
//        $this->info = json_decode(file_get_contents($this->_obj_path.'/info.json'), JSON_OBJECT_AS_ARRAY);
        $this->info = include $this->_obj_path.'/info.php';
        if (empty($this->info))
            throw new \RuntimeException("Can't parse 'info.json' for '$name'");
    }

    /** Get default values
     * @return array
     */
    function getDefaultValues(){
        $defaults = array();
        foreach ($this->info['values'] as $name => $def)
            $defaults[$name] = isset($def['default'])? $def['default'] : null;
        return $defaults;
    }

    /** Get the HTML for the form, with Angular bindings, bootstrap-based.
     * Special handling is needed for:
     *      .ts-skeleton-sets div.ckeditor-inline
     *          Init inline CKEditor
     *      .ts-skeleton-sets input.input-colorpicker
     *          Init Color picker (https://github.com/vanderlee/colorpicker)
     * @param string $ng_model The name of Angular model to bind to
     * @return string
     */
    function getForm($ng_model){
        $form = "\t\t<fieldset class='ts-skeleton-sets'>\n";

        foreach ($this->info['values'] as $name => $def){
            # Feed the defaults
            $def += array(
                'type' => 'string',
                'title' => "((ts:$name))",
                'help' => '',
                'default' => '',
            );

            # Input
            $input = "--unknown-input-{$def['type']}--";
            $model = "{$ng_model}.{$name}";
            switch ($def['type']){
                case 'string':
                    $input = "<input type='text' ng-model='{$model}' class='input-xxlarge' />";
                    break;
                case 'text':
                    $input =
                        "<textarea ng-model='{$model}' ng-hide=1></textarea>".
                        "<div ng-model='{$model}' contenteditable class='ckeditor-inline'></div>";
                    break;
                case 'url':
                    $input =
                        "<input type='url' ng-model='{$model}' class='input-xxlarge' placeholder='//example.com/page.htm' />".
                        "<a ng-href='{$model}' target='_blank' class='url-preview'>Preview</a>";
                    break;
                case 'image':
                    $input =
                        "<input type='text' ng-model='{$model}' class='input-xxlarge' placeholder='//example.com/image.png' />".
                        "<img ng-src='{{{$model}}}' class='image-preview' />";
                    break;
                case 'color':
                    $input = "<input type='text' ng-model='{$model}' class='input-small input-colorpicker' placeholder='#99DD99' style='background-color: {{{$model}}};' pattern='^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$' />";
                    break;
            }

            # HTML
            $form .= <<<HTML
        <div class="control-group control-group-{$name}">
            <label class="control-label">{$def['title']}</label>
            <div class="controls">{$input}</div>
            <span class="help-block">{$def['help']}</span>
        </div>
HTML;

        }

        $form .= "\t\t</fieldset>\n";
        return $form;
    }

    /** The rendered template contents
     * @var string
     */
    protected $_template_contents;

    /** Render a skeleton into a template
     * @param array $values
     *      Placeholder data
     */
    function render(){
        $skeleton = file_get_contents($this->_obj_path.'/index.php');

        # Prepare the replaces
        $replace = array();
        foreach ($this->values as $key => $value){
            $replace['((ts:'.$key.'))'] = $value;
            $replace["<ts:$key>"] = htmlspecialchars($value);
        }

        # Process
        $this->_template_contents = str_replace(
            array_keys($replace),
            array_values($replace),
            $skeleton
        );

        return $this;
    }

    /** Save as template
     * @param string $template_name
     */
    function saveAs($template_name){
        $path = Template::_dpath().'/'.$template_name;
        if (!file_exists($path))
            mkdir($path);
        file_put_contents($path.'/index.php', $this->_template_contents);
    }

    /** Save for Rule
     * @param Rule $Rule
     * @return string The resulting template name
     */
    function saveForRule(Rule $Rule){
        $this->saveAs(
            $template_name = sprintf('.%d', $Rule->id)
        );
        return $template_name;
    }
}
