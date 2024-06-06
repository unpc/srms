<?php

// $config['check_billing_account'] = [
//     'title'=> '检查财务账号与财务明细相符',
//     'cron'=> '35 1 * * *', //配置在miss_check之后
//     'job'=> ROOT_PATH. 'cli/cli.php billing_account check',
// ];

$config['caculate_billing_account'] = [
    'title'=> '计算财务账号与财务明细',
    'cron'=> '* * * * *',
    'job'=> ROOT_PATH. 'cli/cli.php billing_account update_balance',
];

$config['caculate_all_billing_account'] = [
    'title'=> '计算全部财务账号与财务明细',
    'cron'=> '30 1 * * *',
    'job'=> ROOT_PATH. 'cli/cli.php billing_account update_all_balance',
];

$config['refill_notification'] = [
	'title' => '实验室充值提醒',
	'cron' => '5 4 * * *',
	'job' => ROOT_PATH . 'cli/cli.php billing_account refill_notification'
    ];
    
$config['billing_notification_send'] = [
    'title' => '发送结算明细',
    'cron' => '0 5 * * *',
    'job' => ROOT_PATH . 'cli/cli.php billing_notification send'
];
