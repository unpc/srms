<?php
$config['vidcam']['fields']['name'] = ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''];
$config['vidcam']['fields']['name_abbr'] = ['type'=> 'varchar(200)', 'null'=> FALSE, 'default'=> ''];
$config['vidcam']['fields']['location'] = ['type' => 'varchar(150)', 'null' => TRUE];
$config['vidcam']['fields']['location2'] = ['type'=>'varchar(150)', 'null'=> TRUE];
$config['vidcam']['fields']['control_address'] = ['type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''];
$config['vidcam']['fields']['stream_address'] = ['type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''];
$config['vidcam']['fields']['ip_address'] = ['type'=>'varchar(255)', 'null'=>TRUE];
$config['vidcam']['fields']['is_monitoring'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['vidcam']['fields']['is_monitoring_mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['vidcam']['fields']['uuid'] = ['type'=>'varchar(100)', 'null'=>TRUE];
$config['vidcam']['fields']['type'] = ['type'=>'int(1)', 'null'=>FALSE, 'default'=>1];
$config['vidcam']['fields']['atime'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['vidcam']['fields']['ctime'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['vidcam']['fields']['mtime'] = ['type' => 'int', 'null' => FALSE, 'default' => 0];
$config['vidcam']['indexes']['name'] = ['fields' => ['name']];
$config['vidcam']['indexes']['name_abbr'] = ['fields'=> ['name_abbr']];
$config['vidcam']['indexes']['location'] = ['fields' => ['location']];
$config['vidcam']['indexes']['location2'] = ['fields' => ['location2']];
$config['vidcam']['indexes']['control_address'] = ['fields'=>['control_address']];
$config['vidcam']['indexes']['stream_address'] = ['fields'=>['stream_address']];
$config['vidcam']['indexes']['ip_address'] = ['fields'=>['ip_address']];
$config['vidcam']['indexes']['is_monitoring_mtime'] = ['fields'=>['is_monitoring_mtime']];
$config['vidcam']['indexes']['uuid'] = ['fields' => ['uuid']];
$config['vidcam']['indexes']['type'] = ['fields' => ['type']];
$config['vidcam']['indexes']['atime'] = ['fields' => ['atime']];
$config['vidcam']['indexes']['ctime'] = ['fields' => ['ctime']];
$config['vidcam']['indexes']['mtime'] = ['fields' => ['mtime']];

//视频报警存储对象
$config['vidcam_alarm'] = [
    'engine' => 'InnoDB',
    'fields' => [
        'vidcam' => ['type' => 'object', 'oname' => 'vidcam'],                               //发生alarm的监控对象
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        ],
    'indexes' => [
        'vidcam'=> ['fields' => ['vidcam']],
        'ctime' => ['fields' => ['ctime']],
        ]
    ];

//截图数据存储对象
$config['vidcam_capture_data'] = [
    'fields' => [
        'vidcam' => ['type' => 'object', 'oname' => 'vidcam'],
        'is_alarm' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
        'ctime' => ['type' => 'int', 'null' => FALSE, 'default' => 0],
    ],
    'indexes' => [
        'vidcam'=> ['fields' => ['vidcam']],
        'ctime' => ['fields' => ['ctime']],
        'is_alarm' => ['fields' => ['is_alarm']]
    ]
];
