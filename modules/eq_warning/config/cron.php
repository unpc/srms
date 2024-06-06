<?php
$config['warning_month_data'] = [
	'title' => '按月预警',
	'cron' => '0 0 1 * *',
	'job' => ROOT_PATH . 'cli/cli.php eq_warning warning_month_data'
];
$config['warning_quarter_data'] = [
	'title' => '按季度预警',
	'cron' => '0 0 1 1,4,7,10 *',
	'job' => ROOT_PATH . 'cli/cli.php eq_warning warning_quarter_data'
];
$config['warning_year_data'] = [
	'title' => '按年预警',
	'cron' => '0 0 31 12 *',
	'job' => ROOT_PATH . 'cli/cli.php eq_warning warning_year_data'
];

$config['warning_everyday_data'] = [
	'title' => '周期内使用时长最大值预警',
	'cron' => '0 0 1 * *',
	'job' => ROOT_PATH . 'cli/cli.php eq_warning warning_max_data_everyday'
];
