#!/usr/bin/env php
<?php

require 'base.php';

$root = Config::get('nfs.root');

Search_NFS::empty_index();

$path = '';
$path_type = 'share';
$start = 0;
$num = 10;

for(;;) {
	$users = Q('user')->limit($start,$num);
	if (count($users) == 0) break;
	$start += $num;
	foreach ($users as $user) {
		Search_NFS::update_nfs_indexes($user, $path, $path_type, FALSE);
	}
}

$start = 0;
$num = 10;

for(;;) {
	$labs = Q('lab')->limit($start,$num);
	if (count($labs) == 0) break;
	$start += $num;
	foreach ($labs as $lab) {
		Search_NFS::update_nfs_indexes($lab, $path, $path_type, FALSE);
	}
}

$object = null;
Search_NFS::update_nfs_indexes($object, $path, $path_type, FALSE);

$path = '';
$path_type = 'attachments';
$object_arr = ['award', 'eq_record', 'eq_sample', 'equipment', 'publication', 'stock', 'cal_component'];
foreach ($object_arr as $key => $name) {
	$start = 0;
	$num = 10;

	for(;;) {
		$objects = Q("$name")->limit($start,$num);
		if (count($objects) == 0) break;
		$start += $num;
		foreach ($objects as $object) {
			Search_NFS::update_nfs_indexes($object, $path, $path_type, FALSE);
		}
	}
}