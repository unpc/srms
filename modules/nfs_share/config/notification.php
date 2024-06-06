<?php

$config['clean_setting'] = [
	'description' => '设置文件删除提醒',
	'title' => '文件删除提醒',
	'body' => '文件删除提醒：系统将于 %cleancharge，对更新/ 上传时间在 %dtstart - %dtend 的文件，文件一旦清理，不可恢复，请尽快下载/ 备份。',
    'i18n_module' => 'nfs_share',
	'strtr' => [
        '%cleancharge' => '清理时间',
		'%dtstart' => '文件清理时间范围开始时间',
		'%dtend' => '文件清理时间范围结束时间'
	],
	'send_by' => [
		'messages' => ['通过消息中心发送', 1],
    ]
];
