<?php

$config['delete_expire_auth'] = [
	'title' => '删除过期的通过授权记录',
	'cron' => '0 4 * * *',
	'job' => ROOT_PATH . 'cli/cli.php meeting delete_expire_auth'
	];
