<?php
$exam_url = 'http://test-env.labmai.com/labmai/exam/fe';
$config['remote_exam_app'] = 'labmai_exam';
$config['remote_exam_url'] = $exam_url;
$config['remote_exam'] = [
	'labmai_exam' => [
		'domain' => $exam_url,
		'paths' => [
			'do' => '/my-exam/do/%id'
		],
	],
];
