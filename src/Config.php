<?php

return [
    'page_mapping' => \Magein\Admin\View\PageMapping::class,
    'page_path' => 'App\Admin\Page',
    'page_auth' => true,
    'page_action' => true,
    'log' => [
        'switch' => true,
        'path' => storage_path('logs/view.log')
    ]
];
