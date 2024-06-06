<?php 
$config['delete_expire_banned'] = [
	'title' => '删除过期的黑名单',
	'cron' => '5 1 * * *',
	'job' => ROOT_PATH . 'cli/cli.php eq_ban delete_expire_banned'
	];
