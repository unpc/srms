<?php
$config['close_expired_labs'] = [
	'title' => '关闭过期站点',
	'cron' => '5 5 * * *',
	'job' => ROOT_PATH . 'cli/cli.php lab close_expire_lab',
	];
