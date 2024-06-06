<?php
$config['notif_grants'] = [
    'title' => '进行经费过期消息提醒',
    'cron' => '30 2 * * *',
    'job' => ROOT_PATH . 'cli/notif_grants.php'
];
