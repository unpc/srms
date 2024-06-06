<?php
$config['command_list'] = [
    'lscpu' => [
        'name' => 'CPU 信息',
        'command' => 'lscpu'
    ],
    'free' => [
        'name' => '内存 信息',
        'command' => 'free -m'
    ],
    'df' => [
        'name' => '硬盘 信息',
        'command' => 'df -lh'
    ],
    'w' => [
        'name' => '负载 信息',
        'command' => 'w'
    ],
    'disk' => [
        'name' => '用户文件目录 信息',
        'command' => 'du -h --max-depth=2 /home/disk/%SITE_ID/%LAB_ID/share/'
    ],
    'stat' => [
        'name' => '季度统计信息',
        'command' => 'cat /volumes/report_%SITE_ID_%LAB_ID.csv'
    ]
];