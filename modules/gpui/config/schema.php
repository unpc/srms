<?php

//绑定状态
$config['equipment']['fields']['watcher_code'] = [
    'type' => 'varchar(50)',
    'null' => TRUE,
    'default' => NULL,
];

$config['equipment']['indexes']['watcher_code'] = [
    'type' => 'unique',
    'fields' => ['watcher_code']
];
