<?php

$config['year_summary'] = [
	'title' => '站点季度统计',
        'cron' => '30 0 1 * *',
	// 'cron' => '* * * * *',
	'job' => ROOT_PATH . 'cli/year_summary.php > /volumes/report_'.SITE_ID.'_'.LAB_ID.'.csv &'
];
