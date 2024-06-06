<?php

$config['template']['element_count'] = [
    'title' => '按元素',
    'content' => [
        'sample_form' => [
            'script' => 'private:sample_form/element_count.lua',
            'params' => [
                '%options' => ['*' => ['minimum_fee' => 0,'unit_price' => 0]]
            ]
        ]
    ],
    'i18n_module' => 'sample_form',
    'category' => 'sample_form'
];
