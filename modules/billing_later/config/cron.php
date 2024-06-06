<?php

$config['update_lab_grant'] = [
	'title' => '同步远程财务账号信息',
	'cron' => '30 2 * * *',
	'job' => ROOT_PATH . 'cli/cli.php billing_later updateLabsGrant'
];

$config['billing_later_syncChargeLocked'] = [
	'title' => '锁定本地数据库eq_charge信息供远程数据获取',
	'cron' => '10 1 * * *',
	'job' => ROOT_PATH . 'cli/cli.php billing_later syncChargeLocked'
];

$config['sync_item_billing_status'] = [
    'title' => '每隔30分钟lims更新报销状态',
    'cron' => '*/30 * * * *',
    'job' => ROOT_PATH . 'cli/cli.php billing_later syncBlStatus'
];
