<?php

$config['delete_expired_approval'] = [
    'title' => '删除审核逾期的预约',
    'cron' => '* * * * *',
    'job' => ROOT_PATH . 'cli/cli.php approval delete_expired_approval'
];