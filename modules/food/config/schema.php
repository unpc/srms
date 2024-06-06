<?php

$config['food'] = [
	'fields'=>[
			'supplier'=>['type'=>'varchar(50)','null'=>FALSE,'default'=>''],
			'name'=>['type'=>'varchar(150)','null'=>FALSE,'default'=>''],
			'price'=>['type'=>'double','null'=>FALSE,'default'=>0],
			'reserve'=>['type'=>'varchar(200)', 'null'=>FALSE, 'default'=>''],
			'description'=>['type'=>'varchar(200)', 'null'=>FALSE, 'default'=>''],
			'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		],
	'indexes'=>[
			'supplier'=>['fields'=>['supplier']],
			'name'=>['fields'=>['name']],
			'price'=>['fields'=>['price']],
			'ctime'=>['fields'=>['ctime']],
			'mtime'=>['fields'=>['mtime']],
		],
];

$config['fd_order'] = [
	'fields'=>[
			'user'=>['type'=>'object','oname'=>'user'],
			'supplier'=>['type'=>'varchar(50)','null'=>FALSE,'default'=>''],
			'foods'=>['type'=>'varchar(150)','null'=>FALSE, 'default'=>''],
			'price'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
			'remarks'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
			'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'mode'=>['type'=>'int', 'null'=>FALSE, 'default'=>0]
		],
	'indexes'=>[
			'user'=>['fields'=>['user']],
			'foods'=>['fields'=>['foods']],
			'supplier'=>['fields'=>['supplier']],
			'remarks'=>['fields'=>['remarks']],
			'ctime'=>['fields'=>['ctime']],
			'mtime'=>['fields'=>['mtime']],
		],
];

$config['fd_order_log'] = [
	'fields'=>[
			'user'=>['type'=>'object','oname'=>'user'],
			'fd_order'=>['type'=>'object', 'oname'=>'fd_order'],
			'old_price'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
			'new_price'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
			'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],		
	],
	'indexes'=>[
			'user'=>['fields'=>['user']],
			'fd_order'=>['fields'=>['fd_order']],
			'ctime'=>['fields'=>['ctime']],
	],
];
