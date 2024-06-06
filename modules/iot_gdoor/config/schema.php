<?php

$config['iot_gdoor'] = [
    'fields'=>[
        'gdoor_id'=>['type'=>'int','null'=>false,'default'=>0],
        'ctime'=>['type'=>'int', 'null'=>false, 'default'=>0],
    ],
    'indexes'=>[
        'gdoor_id'=>['fields'=>['gdoor_id']],
        'ctime'=>['fields'=>['ctime']],
    ],
];
