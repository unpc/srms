<?php

$config['tag_room']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default' => ''];
$config['tag_room']['fields']['name_abbr'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['tag_room']['fields']['parent'] = ['type'=>'object', 'oname'=>'tag_room'];
$config['tag_room']['fields']['root'] = ['type'=>'object', 'oname'=>'tag_room'];
$config['tag_room']['fields']['readonly'] = ['type'=>'tinyint', 'null'=>FALSE, 'default'=>0];
$config['tag_room']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_room']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_room']['fields']['weight'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['tag_room']['fields']['code'] = ['type'=>'varchar(150)', 'null'=>true];

$config['tag_room']['indexes']['name'] = ['fields'=>['name', 'parent'], 'type'=>'unique'];
$config['tag_room']['indexes']['parent'] = ['fields'=>['parent']];
$config['tag_room']['indexes']['root'] = ['fields'=>['root']];
$config['tag_room']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['tag_room']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['tag_room']['indexes']['weight'] = ['fields'=>['weight']];
$config['tag_room']['indexes']['code'] = ['fields'=>['code']];