<?php

$config['yiqikong_app_update_equipments'] = [
    'title' => '更新仪器控仪器',
    'cron' => '0 2 * * *',
    'job' => ROOT_PATH . 'cli/cli.php yiqikong update_equipments'
];

$config['yiqikong_app_update_users'] = [
    'title' => '更新仪器控用户',
    'cron' => '15 2 * * *',
    'job' => ROOT_PATH . 'cli/cli.php yiqikong_user update_users'
];

$config['yiqikong_app_update_equipment_settings'] = [
    'title' => '更新仪器控数据',
    'cron' => '30 2 * * *',
    'job' => ROOT_PATH . 'cli/cli.php yiqikong update_equipment_settings'
];