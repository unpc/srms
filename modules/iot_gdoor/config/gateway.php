<?php
$config['domain_url'] = 'http://uno.test.gapper.in/';
$config['domain'] = $config['domain_url'] .'gapper/';
$config['server'] = [
    'url'         => $config['domain'] . 'gateway/api/v1/',
    'refresh_url' => $config['domain'] . 'gateway/oauth/server/token',
    'params'      => [
        'client_id'     => 'lims',
        'client_secret' => 'c088c68c66bf6b83ed48fa768d737438',
    ],
];
