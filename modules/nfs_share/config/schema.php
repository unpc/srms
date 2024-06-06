<?php

$config['user']['fields']['nfs_size'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];
$config['user']['fields']['nfs_mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['user']['fields']['nfs_used'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];

$config['user']['indexes']['nfs_size'] = ['fields'=>['nfs_size']];
$config['user']['indexes']['nfs_mtime'] = ['fields'=>['nfs_mtime']];
$config['user']['indexes']['nfs_used'] = ['fields'=>['nfs_used']];


$config['lab']['fields']['nfs_size'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];
$config['lab']['fields']['nfs_mtime'] = ['type'=>'int', 'null'=>FALSE, 'default'=>0];
$config['lab']['fields']['nfs_used'] = ['type'=>'double', 'null'=>FALSE, 'default'=>0];

$config['lab']['indexes']['nfs_size'] = ['fields'=>['nfs_size']];
$config['lab']['indexes']['nfs_mtime'] = ['fields'=>['nfs_mtime']];
$config['lab']['indexes']['nfs_used'] = ['fields'=>['nfs_used']];

	
