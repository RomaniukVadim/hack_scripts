<?php

require_once __DIR__.'/Tag.php';
require_once __DIR__.'/Html.php';
require_once __DIR__.'/Form.php';
require_once __DIR__.'/FormEx.php';

use FormBuilder\Tag;

/** HTML builder
 */
class HtmlBuilder extends Tag\Tag {
    function __construct() {
        parent::__construct(null);
    }
}

/** Form builder
 */
class FormBuilder extends Tag\Form\Form {
}



# Unittest
if (0 && 'unittest'){
    $html = new HtmlBuilder;
    $html->scriptInline('lol', 'a', 'b=1');

    print htmlspecialchars($html->render());
    die();
}
