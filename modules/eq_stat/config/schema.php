<?php

$config['eq_perf'] = [
	'fields'=>[
			'name'=>['type'=>'varchar(50)','null'=>FALSE,'default'=>''],
			'collection'=>['type'=>'object', 'oname'=>'tag'],
			'dfrom'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'dto'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'rating_from'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'rating_to'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'rating_items'=>['type'=>'json'],
			'can_grade'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		],
	'indexes'=>[
			'name'=>['fields'=>['name']],
			'collection'=>['fields'=>['collection']],
		],	
];

$config['eq_perf_rating'] = [
	'fields'=>[
			'equipment'=>['type'=>'object', 'oname'=>'equipment'],
			'perf'=>['type'=>'object', 'oname'=>'eq_perf'],
			'user'=>['type'=>'object', 'oname'=>'user'],
			'scores'=>['type'=>'text'],
			'average'=>['type'=>'int', 'null'=>FALSE, 'default'=>0]
		],
	'indexes'=>[
			'unique'=>['fields'=>['user', 'equipment', 'perf'], 'type'=>'unique'],
			'equipment'=>['fields'=>['equipment']],
			'perf'=>['fields'=>['perf']],
			'user'=>['fields'=>['user']],
			'average'=>['fields'=>['average']],
		],	
];

$config['eq_stat'] = [
    'fields'=>[
            'equipment'=>['type'=>'object', 'oname'=>'equipment'], 
            'time'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
            'record_sample'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
            'time_total'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
            'time_open'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
            'time_valid'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
            //'time_class'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0), /* 教学机时 */
            'use_time'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
            'total_trainees'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
            'pubs'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
            'charge_total'=>['type'=>'double', 'null'=>FALSE, 'default'=>0]
            
    ],
    'indexes'=>[
    		'unique'=>['fields'=>['time', 'equipment'], 'type'=>'unique'],
            'equipment'=>['fields'=>['equipment']],
            'time'=>['fields'=>['time']],
            'record_sample'=>['fields'=>['record_sample']],
            'time_total'=>['fields'=>['time_total']],
            'time_open'=>['fields'=>['time_open']],
            'time_valid'=>['fields'=>['time_valid']],
            'use_time'=>['fields'=>['use_time']],
            'total_trainees'=>['fields'=>['total_trainees']],
            'pubs'=>['fields'=>['pubs']],
            'charge_total'=>['fields'=>['charge_total']]
    ]
];

