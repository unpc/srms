<?php

$config['eq_chat'] = [
    'fields'=>[
        'user'=>['type'=>'object', 'oname'=>'user'],
        'equipment'=>['type'=>'object', 'oname'=>'equipment'],
        'name'=>['type'=>'varchar(500)', 'null'=>FALSE, 'default'=>''],
        'content'=>['type'=>'varchar(500)', 'null'=>FALSE, 'default'=>''],
        'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
    ],
    'indexes'=>[
        'user'=>['fields'=>['user']],
        'equipment'=>['fields'=>['equipment']],
        'ctime'=>['fields'=>['ctime']]
    ],
];
