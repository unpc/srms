<?php
$config['upgrade_nrii_address'] = [
	'title' => '更新行政省市区列表',
	'cron' => '0 1 1 * *',
	'job' => ROOT_PATH . 'cli/cli.php nrii upgrade_address'
];

