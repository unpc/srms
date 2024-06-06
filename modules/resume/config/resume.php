<?php
$config['info.msg.model'] = [
	'description'=>'更新提示',
	'body'=>'%subject 于 %date 更新了 %resume 的个人简历',
	'strtr'=>[
			'%subject'=>'更新者',
			'%resume'=>'简历',
			'%date'=>'时间'
		],
		];
