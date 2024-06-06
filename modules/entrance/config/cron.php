<?php

$config['sync_records_remote_door'] = [
    'title' => '每隔10分钟lims从门禁模块获取使用记录',
    'cron' => '*/10 * * * *',
    'job' => ROOT_PATH . 'cli/cli.php Remote_Door get_door_records'
];