<?php

$config['template']['test_project_count'] = [
    'title' => '按测试项目样品数计费',
    'content' => [
        'test_project' => [
            'script' => 'private:test_project/test_project_count.lua',
            'params' => [
                '%options' => ['*' => ['unit_price' => 0]]
            ]
        ]
    ],
    'i18n_module' => 'test_project',
    'category' => 'test_project'
];
