<?php
$config['push_room_resource_status'] = [
    'title' => '推送房间资源状态',
    'cron' => '0 * * * *',
    'job' => ROOT_PATH . 'cli/cli.php gateway push_room_resource_status'
];

$config['get_gapper_users'] = [
    'title' => '获取远程用户信息',
    'cron' => '30 3 * * *',
    'job' => ROOT_PATH . 'cli/cli.php gapper_user sync_user'
 ];

$config['connect_gapper_users'] = [
    'title' => '远程用户信息与本地用户关联',
    'cron' => '30 4 * * *',
    'job' => ROOT_PATH . 'cli/cli.php gapper_user connect_local'
];

$config['sync_gapper'] = [
    'title' => '远程同步gapper信息',
    'cron' => '30 5 * * *',
    'job' => ROOT_PATH . 'cli/cli.php gapper_sync sync_all'
];

$config['sync_local_update'] = [
    'title' => '同步远程和本地gapper关系',
    'cron' => '30 22 * * *',
    'job' => ROOT_PATH . 'cli/cli.php gapper_sync update_local_status'
];
