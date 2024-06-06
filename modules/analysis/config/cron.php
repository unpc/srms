<?php

$config['analysis_full'] = [
    'title' => '推送基本信息的更新',
    'cron' => '35 0 * * *', // 推送每天的聚合数据
    'job' => ROOT_PATH . 'cli/cli.php analysis full',
];

$config['analysis_increment'] = [
    'title' => '推送每天的聚合数据',
    'cron' => '35 1 * * *', // 推送每天的聚合数据
    'job' => ROOT_PATH . 'cli/cli.php analysis increment',
];