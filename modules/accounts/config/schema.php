<?php

$config['lims_account'] = [
	'fields' => [
		'lab_name'	=>	['type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''],
		'lab_id'	=>	['type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''],
		'type'		=>	['type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''],
		'code_id'	=>	['type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''],
		'archive_url'	=>	['type'=>'text', 'null'=>FALSE, 'default'=>''],
		'url'			=>	['type'=>'text', 'null'=>FALSE, 'default'=>''],
        'admin_token'    =>  ['type'=> 'varchar(255)', 'null'=> FALSE, 'default'=> ''], //管理员token
		'admin_password'=>	['type'=>'varchar(255)', 'null'=>FALSE, 'default'=>''],
		'version'	=>	['type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''],
		'etime'		=>	['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'ctime'		=>	['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'		=>	['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'status'    =>	['type'=>'tinyint', 'null'=>FALSE, 'default'=>0],
        'timezone'    =>  ['type'=> 'varchar(255)', 'null'=> FALSE, 'default'=> ''], //时区
        'language'    =>  ['type'=> 'varchar(255)', 'null'=> FALSE, 'default'=> ''], //语言
        'currency'    =>  ['type'=> 'varchar(255)', 'null'=> FALSE, 'default'=> ''], //金额
        'nday'=> ['type'=> 'int', 'null'=> FALSE, 'default'=> 7], //过期提前提醒日, 默认为7天
	],
	'indexes' => [
		'lab_name'	=>	['fields'=>['lab_name']],
		'lab_id'	=>	['fields'=>['lab_id']],
		'code_id' 	=>	 ['fields'=>['code_id']],
		'etime'	=>	['fields'=>['etime']],
		'ctime'	=>	['fields'=>['ctime']],
		'mtime'	=>	['fields'=>['mtime']],
	],
];

$config['account_version'] = [
	'fields' => [
		'account'	=>	['type'=>'object', 'oname'=>'account'],
		'version'	=>	['type'=>'varchar(40)', 'null'=>FALSE, 'default'=>''],
		'dtstart'	=>	['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtend'		=>	['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'ctime'		=>	['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'description'	=>	['type'=>'text', 'null'=>TRUE],
	],
	'indexes' => [
		'dtstart'	=>	['fields'=>['dtstart']],
		'dtend'		=>	['fields'=>['dtend']],
		'account'	=>	['fields'=>['account']],
		'version'	=>	['fields'=>['version']],
		'ctime'		=>	['fields'=>['ctime']],
	],
];
