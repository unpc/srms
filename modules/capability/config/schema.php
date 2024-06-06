<?php

$config['capability_task'] = [
	'fields'=>[
			'source_id'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
            'name'=>['type'=>'varchar(100)', 'null'=>TRUE],
			'dtstart'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'dtend'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
            'datadtstart'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
            'datadtend'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
            'status'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		],
	'indexes'=>[
			'source_id'=>['fields'=>['source_id']],
			'dtstart'=>['fields'=>['dtstart']],
			'dtend'=>['fields'=>['dtend']],
		],
];

$config['capability_equipment_task'] = [
    'fields'=>[
        'source_id'=>['type'=>'int', 'null'=>TRUE, 'default'=>0],
        'equipment'=>['type'=>'object', 'oname'=>'equipment'],
        'group'=>['type'=>'object', 'oname'=>'tag'],
        'name'=>['type'=>'varchar(100)', 'null'=>TRUE],
        'process_status'=>['type'=>'int', 'null'=>TRUE, 'default'=>0],
        'submit_user'=>['type'=>'object', 'oname'=>'user'],
        'submit_time'=>['type'=>'int', 'null'=>TRUE, 'default'=>0],
        'capability_task'=>['type'=>'object', 'oname'=>'capability_task'],
    ],
    'indexes'=>[
        'capability_task'=>['fields'=>['capability_task']],
        'equipment'=>['fields'=>['equipment']],
    ],
];

$config['capability_equipment_task_user'] = [
    'fields'=>[
        'capability_equipment_task'=>['type'=>'object', 'oname'=>'capability_equipment_task'],
        'user'=>['type'=>'object', 'oname'=>'user'],
    ],
    'indexes'=>[
        'capability_equipment_task'=>['fields'=>['capability_equipment_task']],
        'user'=>['fields'=>['user']],
    ],
];
