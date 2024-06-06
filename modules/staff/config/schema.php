<?php

$config['staff']['fields']=[
	'user'			=>['type'=>'object',		'oname'=>'user'],
	'job_number'	=>['type'=>'varchar(4)',	'null'=>FALSE,	'default'=>''],
	'role'			=>['type'=>'int',			'null'=>FALSE,	'default'=>1],
	'IDnumber'		=>['type'=>'varchar(19)',	'null'=>FALSE,	'default'=>''],
	'birthplace'	=>['type'=>'varchar(16)',	'null'=>FALSE,	'default'=>''],
	'birthday'		=>['type'=>'int',	  		'null'=>FALSE,	'default'=>0],
	'professional'	=>['type'=>'varchar(64)',	'null'=>FALSE,	'default'=>''],
	'school'		=>['type'=>'varchar(64)',	'null'=>FALSE,	'default'=>''],
	'position'		=>['type'=>'object',		'oname'=>'position'],
	'practice_time'	=>['type'=>'int',			'null'=>FALSE,	'default'=>0],
	'trial_time'	=>['type'=>'int',			'null'=>FALSE,	'default'=>0],
	'normal_time'	=>['type'=>'int',			'null'=>FALSE,	'default'=>0],
	'start_time'	=>['type'=>'int',			'null'=>FALSE,	'default'=>0],
	'contract_time'	=>['type'=>'int',			'null'=>FALSE,	'default'=>0],
	'salary'		=>['type'=>'text',			'null'=>FALSE,	'default'=>''],
	'positions'		=>['type'=>'text',			'null'=>FALSE,	'default'=>''],
	'insurance'		=>['type'=>'text',			'null'=>FALSE,	'default'=>''],
	'remark'		=>['type'=>'text',			'null'=>FALSE,	'default'=>''],
	'atime'			=>['type'=>'int',			'null'=>FALSE,	'default'=>0],
	'ctime'			=>['type'=>'int',			'null'=>FALSE,	'default'=>0]
];

$config['staff']['indexes'] = [
	'position'		=>['fields'=>['position']],
	'user'			=>['fields'=>['user'],	'type'=>'unique']
];
