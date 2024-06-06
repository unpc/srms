<?php
$config['expiration_inspect'] = [
	'title' => '每天0:30修改存货过期状态',
	'cron' => '30 0 * * *',
	'job' => ROOT_PATH . 'cli/cli.php inventory expiration_inspect'
];

$config['expiration_notify'] = [
	'title' => '每天2:30通知相关人员存货过期信息',
	'cron' => '30 2 * * *',
	'job' => ROOT_PATH . 'cli/cli.php inventory expiration_notify'
];