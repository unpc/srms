<?php
$config['delete_over_time_recovery'] = [
	'title' => '删除过期的找回密码',
	'cron' => '25 1 * * *',
	'job' => ROOT_PATH . 'cli/cli.php people delete_over_time_recovery'
];
$config['disable_overdue_user'] = [
	'title' => '将过期用户设置为未激活',
	'cron' => '30 1 * * *',
	'job' => ROOT_PATH . 'cli/cli.php people disable_overdue_user'
];
