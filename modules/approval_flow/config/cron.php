<?php

$config['delete_expired_approval'] = [
    'title' => '删除审核逾期的预约',
    'cron' => '* * * * *',
    'job' => ROOT_PATH . 'cli/cli.php approval delete_expired_approval'
];

$config['delete_expired_approval'] = [
    'title' => '自动审核课题组下到达审核时限的送样/预约',
    'cron' => '* * * * *',
    'job' => ROOT_PATH . 'cli/cli.php approval lab_approval_unlimit_time'
];
