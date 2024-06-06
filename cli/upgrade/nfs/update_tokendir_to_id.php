#!/usr/bin/env php
<?php

require "../../base.php";

$root_path = Config::get('nfs.root');
$user_path = 'users/';
$full_path = $root_path.$user_path;

Unit_Test::echo_assert("class_exists(nfs)",class_exists('nfs'));
Unit_Test::echo_assert("method_exists(nfs:file_list)",method_exists('nfs','file_list'));
$files = NFS::file_list($full_path, '');

try {
	if (count($files)) {
		$total = 0;
		foreach ($files as $file) {
			if ($file['dir']) {
				$token = $file['path'];
				$token = Auth::normalize($token);
				$user = O('user', ['token'=>$token]);
				if (!$user->id) continue;
				$new_path = $full_path.$user->id.'/';
				$old_path = $full_path.$file['path'].'/';
				$total++;
				rename($old_path, $new_path);
			}
		}
	}

	Upgrader::echo_success("更新了 {$total} 个目录!");
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'error');
}
