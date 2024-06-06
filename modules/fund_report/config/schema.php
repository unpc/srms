<?php

$config['fund_report_annual'] = [
	'fields'=>[
			'source_id'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'dtstart'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'dtend'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		],
	'indexes'=>[
			'source_id'=>['fields'=>['source_id']],
			'dtstart'=>['fields'=>['dtstart']],
			'dtend'=>['fields'=>['dtend']],
		],
];

$config['fund_report_apply'] = [
    'fields'=>[
        'source_id'=>['type'=>'int', 'null'=>TRUE, 'default'=>0],
        'fund_report_annual'=>['type'=>'object', 'oname'=>'fund_report_annual'],
        'num'=>['type'=>'varchar(50)', 'null'=>TRUE],
        'equipment'=>['type'=>'object', 'oname'=>'equipment'],
        'user'=>['type'=>'object', 'oname'=>'user'],
        'ctime'=>['type'=>'int', 'null'=>TRUE, 'default'=>0],
        'type'=>['type'=>'varchar(50)', 'null'=>TRUE],
        'status'=>['type'=>'varchar(50)', 'null'=>TRUE],
    ],
    'indexes'=>[
        'fund_report_annual'=>['fields'=>['fund_report_annual']],
        'equipment'=>['fields'=>['equipment']],
        'status'=>['fields'=>['status']],
    ],
];