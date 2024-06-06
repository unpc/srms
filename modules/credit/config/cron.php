<?php

$config['update_user_levels'] = [
    'title' => '每日计算用户等级',
    'cron'  => '01 00 * * *',
    'job'   => ROOT_PATH . 'cli/cli.php credit update_user_levels',
];

$config['update_user_feedback'] = [
    'title' => '每日统计未反馈',
    'cron'  => '00 00 * * *',
    'job'   => ROOT_PATH . 'cli/cli.php credit update_user_feedback',
];
