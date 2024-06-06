<?php
	$config['template']['security']['alarm_times'] = '您实验室的%name冰箱出现%count次温度异常';
	$config['template']['security']['need_fix'] = '目前传感器仍处于报警状态的有: ';

	$config['stat_time.yesterday'] = [
		'dtstart' => strtotime(date('Y-m-d')) - 86400,
		'dtend' => strtotime(date('Y-m-d')),
	];