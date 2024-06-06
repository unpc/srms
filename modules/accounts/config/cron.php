<?php
$config['fix_proj_list'] = [
	'title' => '修正 proj_list',
	'cron' => '5 * * * *',
	'job' => ROOT_PATH . 'cli/fix_lims_proj_list.php',
];
