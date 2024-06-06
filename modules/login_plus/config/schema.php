<?php
$config['login_attempt'] = [
    'fields' => [
        'user' => ['type'=>'object', 'oname'=>'user'],                                  //用户
        'ctime' => ['type' => 'int','null' => FALSE,'default' => 0],                    //错误时间
    ],
    'indexes' => [
        'user' => ['fields'=>['user']],
        'ctime' => ['fields'=>['ctime']],
    ],
];