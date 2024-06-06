<?php

$config['adjust_approve'] = [
    'title' => '检查预约审核中逾期和审核人员通知',
    'cron' => '* * * * *',
    'job' => ROOT_PATH . 'cli/cli.php reserv_approve adjustApprove'
];
