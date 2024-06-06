<?php

$config['cas_backend'] = 'cas.geneegroup';


$config['backends']['cas.geneegroup'] = [
    'title' => '统一身份认证',
    'handler' => 'cas',
    'readonly' => TRUE, 
    'allow_create' => FALSE,
    'remote_auth' => TRUE,
    'glogon_display' => TRUE
];