<?php
$config['check_error_data'] = [
	'title' => '检查错误数据',
	'cron' => '5 3 * * *',
	'job' => ROOT_PATH . 'cli/cli.php check error_data'
	];
/* $config['stat'] = [
	'title' => '每周一做一周使用统计',
	'cron' => '35 3 * * 1',
	'job' => ROOT_PATH . 'cli/cli.php stat run'
]; */
$config['check_group_connect'] = [
    'title' => '检查课题组组织机构关联',
    'cron' => '0 4 * * *',
    'job' => ROOT_PATH . 'cli/check_group_connect.php'
    ];
