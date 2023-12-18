<?php

return
[
	'views_style_directory'=> 'default-theme',
	'separate_style_according_to_actions' =>
    [
        'index'=>
        [
            'extends'=>'backend.layouts.app',
            'section'=>'content'
        ],
        'create'=>
        [
            'extends'=>'backend.layouts.app',
            'section'=>'content'
        ],
        'edit'=>
        [
            'extends'=>'backend.layouts.app',
            'section'=>'content'
        ],
        'show'=>
        [
            'extends'=>'backend.layouts.app',
            'section'=>'content'
        ],
    ],
    'paths' =>
    [
        'service' =>
        [
            'path' => app_path('Services'),
            'namespace' => 'App\Services'
        ]
    ]

];
