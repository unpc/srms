<?php

//数据异常和无数据提醒，每分钟运行
$config['abnormal_nodata_notification'] = [
    'title' => '检查无数据和数据异常',
    'cron' => '* * * * *',
    'job' => ROOT_PATH . 'cli/cli.php envmon abnormal_nodata_notification'
];

$config['delete_env_datapoint'] = [
    'title' => '删除一段时间之前的env_datapoint',
    'cron' => '0 0 * * *',
    'job' => ROOT_PATH . 'cli/cli.php envmon delete_env_datapoint'
];
