<?php

$config['template']['service_count'] = [
    'title' => '按服务项目计费',
    'content' => [
        'service' => [
            'script' => 'private:service/service_count.lua',
            'params' => [
                '%options' => ['*' => []]
            ]
        ]
    ],
    'i18n_module' => 'technical_service',
    'category' => 'service'
];
$config['template']['service_sample_count'] = [
    'title' => '按样品数计费',
    'content' => [
        'service' => [
            'script' => 'private:service/service_sample_count.lua',
            'params' => [
                '%options' => ['*' => []]
            ]
        ]
    ],
    'i18n_module' => 'technical_service',
    'category' => 'service'
];
