<?php

$config['exam'] = [
	'fields'=>[
			'title'=>['type'=>'varchar(150)','null'=>FALSE,'default'=>''],
			'creator'=>['type'=>'object','oname'=>'user'],
			'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'remote_id'=>['type'=>'int', 'null'=>FALSE, 'default'=>0], // 第三方对接系统的ID
			'remote_app'=>['type'=>'varchar(150)','null'=>FALSE,'default'=>''], // 第三方对接系统的名称
			'remote_url' =>['type'=>'varchar(150)','null'=>FALSE,'default'=>''], // 第三方对接系统的url
		],
	'indexes'=>[
			'creator'=>['fields'=>['creator']],
			'title'=>['fields'=>['title']],
			'ctime'=>['fields'=>['ctime']],
		],
];
