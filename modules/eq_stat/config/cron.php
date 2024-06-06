<?php

$config['save_data_everyday'] = [
	'title' => '每天更新统计数据',
	'cron' => '5 0 * * *',
	'job' => ROOT_PATH . 'cli/cli.php eq_stat save_data_everyday'
	];

