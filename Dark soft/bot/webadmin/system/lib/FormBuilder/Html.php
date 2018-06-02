<?php namespace FormBuilder\Tag\Html;

/** HTML tags
 * Based on: http://www.whatwg.org/specs/web-apps/current-work/multipage/
 */

use FormBuilder\Tag\DirectNode;
use FormBuilder\Tag\Tag;
use FormBuilder\Tag\TextTagInterface;
use FormBuilder\Tag\SimpleTagInterface;


/** <script>
 */
class Script extends Tag implements TextTagInterface {
}

/** <script>....</script>
 */
class ScriptInline extends Tag implements SimpleTagInterface {
    function __construct($script, $type = 'text/javascript', $attributes = null) {
        parent::__construct('script', $attributes);
        $this->attr('type', $type);
        $this->addDirect($script);
    }
}
