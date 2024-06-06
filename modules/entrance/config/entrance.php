<?php
$config['export_columns.entrance']['name']      = '名称';
$config['export_columns.entrance']['location']  = '地理位置';
$config['export_columns.entrance']['user']      = '刷卡者';
$config['export_columns.entrance']['lab']       = '实验室';
$config['export_columns.entrance']['date']      = '刷卡时间';
$config['export_columns.entrance']['direction'] = '方向';

$config['max_free_access_cards'] = 200;

$config['handlers'] = [
    'genee' => [
        'id' => 1,
        'name' => '自产门禁',
        'short_name' => '自产',
    ],
    'hikvision' => [
        'id' => 2,
        'name' => '海康门禁',
        'short_name' => '海康',
        'driver_name' => 'hikvision'
    ]
];
$config['remote_door_hanlder'] = 'lims';
