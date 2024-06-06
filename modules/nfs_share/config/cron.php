<?php
$config['nfs_sync'] = [
	'title' => '文件系统',
	'cron' => '25 2 * * *',
	'job' => ROOT_PATH . 'cli/cli.php nfs_share sync',
	];

$config['nfs_sync_status'] = [
	'title' => '每1分钟查询下是否设定需要删除的文件',
	'cron' => '*/1 * * * *',
	'job' => ROOT_PATH . 'cli/cli.php nfs_share clean'
];
