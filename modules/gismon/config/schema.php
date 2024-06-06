<?php

$config['gis_building']['fields']['name'] = ['type'=>'varchar(150)', 'null'=>FALSE, 'default'=>''];
$config['gis_building']['fields']['name_abbr'] = ['type' => 'varchar(150)', 'null' => FALSE, 'default' => ''];
$config['gis_building']['fields']['longitude'] =  ['type'=>'double', 'null'=>FALSE, 'default'=>0];
$config['gis_building']['fields']['latitude'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];
$config['gis_building']['fields']['floors'] = ['type'=>'int', 'null'=>FALSE, 'default'=>1];
$config['gis_building']['fields']['width'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];
$config['gis_building']['fields']['height'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];
$config['gis_building']['fields']['ctime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['gis_building']['fields']['mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['gis_building']['fields']['group'] =  ['type'=> 'object', 'oname'=> 'tag_group'];

$config['gis_building']['indexes']['name'] = ['type'=>'unique', 'fields'=>['name']];
$config['gis_building']['indexes']['ctime'] = ['fields'=>['ctime']];
$config['gis_building']['indexes']['mtime'] = ['fields'=>['mtime']];
$config['gis_building']['indexes']['group'] =  ['fields'=> ['group']];



$config['gis_device']['fields']['building'] = ['type'=>'object', 'oname'=>'gis_building'];
$config['gis_device']['fields']['floor'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['gis_device']['fields']['x'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];
$config['gis_device']['fields']['y'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];
$config['gis_device']['fields']['object'] = ['type'=>'object'];


$config['gis_device']['indexes']['building'] = ['fields'=>['building']];
$config['gis_device']['indexes']['object'] = ['fields'=>['object'], 'type'=>'unique'];

