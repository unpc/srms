<?php

$config['delete_overdue_eq_meter_data'] = [
	'title' => '每周删除过期的电流信息',
	'cron' => '15 3 * * 0',
	'job' => ROOT_PATH . 'cli/cli.php eq_meter delete_overdue_eq_meter_data'
];

$config['update_eq_mon_mtime'] = [
	'title' => '每10分钟更新不活动仪器的状态',
	'cron' => '*/10 * * * *',
	'job' => ROOT_PATH . 'cli/cli.php equipment update_eq_mon_mtime'
];

$config['control_sync_status'] = [
	'title' => '每10分钟向glogon/epc-server更新仪器状态',
	'cron' => '*/10 * * * *',
	'job' => ROOT_PATH . 'cli/cli.php equipment control_sync_status'
];

$config['training_overdue'] = [
	'title' => '更新培训过期状态',
	'cron' => '0 2 * * *',
	'job' => ROOT_PATH . 'cli/cli.php training training_overdue'
];

$config['training_before_delete_notif'] = [
    'title'=> '培训过期前7天进行消息提醒',
    'cron'=> ' 40 0 * * *',
    'job'=> ROOT_PATH. 'cli/cli.php training training_before_delete_notif',
];

$config['delete_expire_training'] = [
    'title'=> '培训过期前7天进行消息提醒',
    'cron'=> ' 00 1 * * *',
    'job'=> ROOT_PATH. 'cli/cli.php training delete_expire_training',
];

$config['usenotice_message_realtime_check'] = [
    'title' => '检查仪器使用预警',
    'cron' => '* * * * *',
    'job' => ROOT_PATH . 'cli/cli.php UseNotice usenotice_message_realtime_check',
];

// 每天执行一次
$config['eq_record_segment'] = [
    'title'=> '仪器统计未闭合记录计入当月统计',
    'cron' => '0 3 * * *',
    'job'=> ROOT_PATH. 'cli/cli.php segment_eq_record segment',
];
