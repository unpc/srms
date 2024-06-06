<?php

$config['delete_overdue_capture_and_data'] = [
	'title' => '定时清空过期capture_data和capture图片',
	'cron' => '47 1 * * *',
	'job' =>  ROOT_PATH . 'cli/delete_overdue_capture_and_data.php'
];

$config['stream_refresh_list'] = [
	'title' => '更新大华视频监控列表',
	'cron' => '55 3 * * *',
	'job' => ROOT_PATH . 'cli/cli.php stream refresh_list',
];
