<?php

$config['sms']['remote_addresses'] = [
   '127.0.0.1',
];
$config['white_list_glogon'] = [
   '172.17.42.1',
];
$config['white_list_eq_reserv'] = [
   '172.17.42.1',
];
$config['white_list_eq_mon'] = [
   '172.17.42.1',
];
$config['white_list_cacs'] = [
   '172.17.42.1',
];
$config['white_list_epc'] = [
   '172.17.42.1',
];
$config['white_list_entrance'] = [
   '172.17.42.1',
];
$config['white_list_envmon'] = [
   '172.17.42.1',
];
$config['white_list_tszz'] = [
   '172.17.42.1',
];
$config['white_list_vidmon'] = [
   '172.17.42.1',
];

$config['auth']['token_free_routes'] = [];
$config['auth']['token_free_routes'][] = '/^api\/v1\/agent-token/';
$config['auth']['token_free_routes'][] = '/^api\/v1\/uno/';
// for test
// $config['auth']['token_free_routes'][] = '/^api\/v1/';
// $config['auth']['token_free_routes'][] = '/^equipment\/api\/v1/';
// for test ends

$config['auth']['agent_token_routes'] = [];
$config['auth']['agent_token_routes'][] = '/^api\/v1\/log-permission/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/log(\/)?$/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/current-log/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/binding[s]?($|\/)/';
// $config['auth']['agent_token_routes'][] = '/^api\/v1\/device-state/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/users/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/equipment-sheet/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/equipment-booking-sheet/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/equipment-sample-sheet/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/equipment-log-sheet/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/door-auth/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/current-user/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/user-info/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/user-card/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/group\/root/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/group\/[^d]+\/children/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/group/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/user\/[^d]+\/permissions/';
$config['auth']['agent_token_routes'][] = '/^equipment\/api\/v1\/[^d]+/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/billing-equipments/';
$config['auth']['agent_token_routes'][] = '/^message\/api\/v1/';
$config['auth']['agent_token_routes'][] = '/^api\/v1\/role/';