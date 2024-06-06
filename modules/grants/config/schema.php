<?php

// 经费
$config['grant'] = [
	'fields' => [
		'project'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'source'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'ref'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'amount'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
		'description'=>['type'=>'varchar(250)', 'null'=>TRUE],
		'user'=>['type'=>'object', 'oname'=>'user'],
		'expense'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
		'balance'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
		'avail_balance'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtstart'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtend'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
    'indexes' => [
		'project'=>['fields'=>['project']],
		'source'=>['fields'=>['source']],
		'amount'=>['fields'=>['amount']],
		'user'=>['fields'=>['user']],
		'expense'=>['fields'=>['expense']],
		'balance'=>['fields'=>['balance']],
		'avail_balance'=>['fields'=>['avail_balance']],
		'ctime'=>['fields'=>['ctime']],
		'mtime'=>['fields'=>['mtime']],
		'dtstart'=>['fields'=>['dtstart']],
		'dtend'=>['fields'=>['dtend']],
	]
];

// 经费用途
$config['grant_portion'] = [
	'fields' => [
		'name'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'grant'=>['type'=>'object', 'oname'=>'grant'],
		'parent'=>['type'=>'object', 'oname'=>'grant_portion'],
		'amount'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
		'expense'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
		'balance'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
		'avail_balance'=>['type'=>'double', 'null'=>FALSE, 'default'=>0],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'name'=>['fields'=>['name']],
		'grant'=>['fields'=>['grant']],
		'parent'=>['fields'=>['parent']],
		'amount'=>['fields'=>['amount']],
		'expense'=>['fields'=>['expense']],
		'balance'=>['fields'=>['balance']],
		'avail_balance'=>['fields'=>['avail_balance']],
		'ctime'=>['fields'=>['ctime']],
		'mtime'=>['fields'=>['mtime']],
	],
];


$config['grant_expense'] = [
	'fields' => [
		'summary'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'grant'=>['type'=>'object', 'oname'=>'grant'],
		'portion'=>['type'=>'object', 'oname'=>'grant_portion'],
		'user'=>['type'=>'object', 'oname'=>'user'],
		'amount' => ['type'=>'double', 'null'=>FALSE, 'default'=>0],
		'invoice_no' => ['type'=>'varchar(50)', 'null'=>TRUE],
		'ctime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime' => ['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'summary'=>['fields'=>['summary']],
		'grant'=>['fields'=>['grant']],
		'portion'=>['fields'=>['portion']],
		'user'=>['fields'=>['user']],
		'amount' => ['fields'=>['amount']],
		'invoice_no' => ['fields'=>['invoice_no']],
		'ctime' => ['fields'=>['ctime']],
		'mtime' => ['fields'=>['mtime']],
	],
];


