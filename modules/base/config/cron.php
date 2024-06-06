<?php

/*$config['base_forward'] = [
    'title' => '用户行为数据获取',
    'cron' => '30 * * * *',
    'job' => ROOT_PATH . 'cli/cli.php base_forward forward'
];*/

$config['base_convert_ip2area'] = [
    'title' => '用户ip转地址',
    'cron' => '30 5 * * *',
    'job' => ROOT_PATH . 'cli/cli.php base_convert ip2area'
];
