<?php
// 

$config['status_judge'] = [
	'title' => '预约记录状态标记',
	'cron' => '00 * * * *',
	'job' =>  ROOT_PATH . 'cli/cli.php eq_reserv status_judge',
];

$config['ban_record'] = [
	'title' => '处理标记之后的违规行为',
	'cron' => '10 * * * *',
	'job' =>  ROOT_PATH . 'cli/cli.php eq_reserv ban_record',
];

$config['auto_create_record'] = [
    'title'=> '自动创建对应的使用记录',
    'cron'=> '35 2 * * *',
    'job'=> ROOT_PATH. 'cli/cli.php eq_reserv auto_create_record',
];
