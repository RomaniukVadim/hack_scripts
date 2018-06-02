<?php
return array (
    'values' => array (
        'title' => array (
            'type' => 'string',
            'title' => 'Page title',
            'help' => 'Displayed in the page title',
        ),
        'logo' =>  array (
            'type' => 'image',
            'title' => 'Logo image',
            'help' => 'URL to the logo image',
        ),
        'background_base' => array (
            'type' => 'color',
            'title' => 'Base background',
            'default' => '#444444',
        ),
        'background_page' => array (
            'type' => 'color',
            'title' => 'Page background',
            'default' => '#EEEEEE',
        ),
        'background_header' => array (
            'type' => 'color',
            'title' => 'Header & Footer background',
            'default' => '#DDDDDD',
        ),
        'text_color' => array (
            'type' => 'color',
            'title' => 'Text color',
            'default' => '#444444',
        ),
    ),
);
