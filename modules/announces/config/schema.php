<?php
$config['announce'] = [
	'fields' => [
		'sender'=>['type'=>'object', 'oname'=>'user'],
		'receiver'=>['type'=>'json'],
		'title'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'content'=>['type'=>'text', 'null'=>FALSE, 'default'=>''],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtstart'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'dtend'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'must_read'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	],
	'indexes' => [
		'title'=>['fields'=>['title']],
		'ctime'=>['fields'=>['ctime']],
		'dtstart'=>['fields'=>['dtstart']],
		'dtend'=>['fields'=>['dtend']]
	],
];
$config['user_announce'] = [
	'fields' => [
  		# 收件人
		'receiver' => ['type'=>'object','oname'=>'user'],
		#发件人
		'sender' => ['type'=>'object','oname'=>'user'],
		# 公告
		'announce' => ['type'=>'object','oname'=>'announce'],
		#是否阅读
		'is_read' => ['type'=>'int','null'=>FALSE,'default' =>0],
        'ctime'=> ['type'=> 'int', 'null'=> FALSE, 'default'=> 0],
		
	],
	'indexes' => [
		'receiver'=>['fields'=>['receiver']],
		'sender'=>['fields'=>['sender']],
		'announce'=>['fields'=>['announce']],
		'is_read'=>['fields'=>['is_read']],
        'ctime'=> ['fields'=> ['ctime']],
	], 
];
