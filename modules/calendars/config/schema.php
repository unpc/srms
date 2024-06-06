<?php

if ($GLOBALS['preload']['calendars.enable_repeat_event']) {
#ifdef (calendars.enable_repeat_event)	
	$config['cal_component'] = [
		'fields' => [
			'calendar'=>['type'=>'object', 'oname'=>'calendar'],
			'type'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'subtype'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'name'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
			'description'=>['type'=>'text', 'null'=>TRUE],
			//organizer的id
			'organizer'=>['type'=>'object', 'oname'=>'user'],
			'dtstart'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'dtend'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'tzone'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
	/*
	  NO.TASK#261 (xiaopei.li@2010.11.20)
	*/
			'cal_rrule'=>['type'=>'object', 'oname'=>'cal_rrule'],
		],
		'indexes' => [
			'calendar'=>['fields'=>['calendar']],
			'type'=>['fields'=>['type']],
			'name'=>['fields'=>['name']],
			'organizer'=>['fields'=>['organizer']],
			'dtstart'=>['fields'=>['dtstart']],
			'dtend'=>['fields'=>['dtend']],
			'ctime'=>['fields'=>['ctime']],
			'mtime'=>['fields'=>['mtime']],
		],
	];

	/*
	  NO.TASK#261 (xiaopei.li@2010.11.20)
	*/
	$config['cal_rrule'] = [
		 'fields' => [ 
			  'rule'=>['type'=>'text'], 
			  'dtfrom'=>['type'=>'int', 'null'=>FALSE, 'default'=>'0'], 
			  'dtto'=>['type'=>'int', 'null'=>FALSE, 'default'=>'0'], 
			  ]
		 ];

	/*
	$config['cal_rrule'] = array(
		'fields' => array(
			'cal_component'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0),
			'freq'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0),
			'enddate'=>array('type'=>'int', 'null'=>FALSE, 'default'=>0),
			'bylist'=>array('type'=>'text', 'null'=>TRUE),
		),
		'indexes' => array(
			'calcomponent'=>array('type'=>'unique', 'fields'=>array('calcomponent')),
			'email'=>array('fields'=>array('email')),
		),
	);
	*/
#endif
}
else{
#ifndef (calendars.enable_repeat_event) 
	$config['cal_component'] = [
		'fields' => [
			'calendar'=>['type'=>'object', 'oname'=>'calendar'],
			'type'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'subtype'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'name'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
			'description'=>['type'=>'text', 'null'=>TRUE],
			//organizer的id
			'organizer'=>['type'=>'object', 'oname'=>'user'],
			'dtstart'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'dtend'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'tzone'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
			'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		],
		'indexes' => [
			'calendar'=>['fields'=>['calendar']],
			'type'=>['fields'=>['type']],
			'name'=>['fields'=>['name']],
			'organizer'=>['fields'=>['organizer']],
			'dtstart'=>['fields'=>['dtstart']],
			'dtend'=>['fields'=>['dtend']],
			'ctime'=>['fields'=>['ctime']],
			'mtime'=>['fields'=>['mtime']],
		],
	];
#endif
}

$config['calendar'] = [
	'fields' => [
		'name'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
		'description'=>['type'=>'text', 'null'=>TRUE],
		'parent'=>['type'=>'object'],
		'ctime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'mtime'=>['type'=>'int', 'null'=>FALSE, 'default'=>0],
		'type'=>['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''],
	],
	'indexes' => [
		'parent'=>['fields'=>['parent']],
		'ctime'=>['fields'=>['ctime']],
		'mtime'=>['fields'=>['mtime']],
	],
];

