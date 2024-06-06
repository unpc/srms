<?php

$config['template']['material_count'] = [
    'title' => '按耗材',
    'content' => [
        'material' => [
            'script' => 'private:material/material_count.lua',
            'params' => [
                '%options' => ['*' => ['unit_price' => 0]]
            ]
        ]
    ],
    'i18n_module' => 'material',
    'category' => 'material'
];
