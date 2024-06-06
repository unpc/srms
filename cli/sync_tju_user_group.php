#!/usr/bin/env php
<?php

require "base.php";

try {
	/*
		定义tju用户的组织机构名称。
		每次使用之前都应确定bynon_user中的数据结构或者tju_cf系统中组织机构是否发生，应同步更新该数据
	*/
	$groups = [
		'1FA16784-D155-44EC-88F5-1E29A53C97F3' => 50,
		'E572DAA6-0ED3-4875-8AD4-45F178033029' => 28,
		'D65B5451-AE85-413F-A8AB-B573C653EC65' => 26,
		'939669E1-5451-4A3E-9A6C-BCC39505CBA0' => 51,
		'A693E6EA-868C-46A2-AB06-CAD7275E1AA7' => 49,
		'B2C13737-D544-42DC-AA04-E3644283D95F' => 47,
		'FF69D8D4-6252-4B4E-887E-FF49C104B686' => 27
	];
	
	$users = Q('user');
	
	$must_update_group = 0;
	$no_group_users = [];
	$not_must_users = [];
	$lab_success = 0;
	$success = 0;
	foreach ($users as $user) {
		$organization = $user->organizationid;
		if (!$organization) {
			$not_must_users[] = $user;
			continue;
		}
		$must_update_group ++;
		$group_id = $groups[$organization];
		if (!$group_id) {
			$no_group_users[$user->id] = $user;
			echo sprintf("%s[%d]用户查找不到对应group->%s\n", $user->name, $user->id, $organization);
			continue;
		}
		$group = O('tag', $group_id);
		$user->group = $user->group->id ? $user->group : $group;
		if ($user->save()) {
			$success ++;
			$u_group = $user->group;
			$u_group->connect($user);
			echo sprintf("%s[%d]用户成功更新group为=>%s\n", $user->name, $user->id, $user->group->name);
			$lab = Q("lab[owner={$user}]")->current();
			if ($lab->id) {
				$lab->group = $lab->group->id ? $lab->group : $group;
				$l_group = $lab->group;
				$l_group->connect($lab);
				$lab->save();
				$lab_success ++;
				echo sprintf("		%s[%d]实验室成功更新group为=>%s\n", $lab->name, $lab->id, $lab->group->name);
			}
		}
		else {
			echo sprintf("%s[%d]用户未能成功更新group->%s\n", $user->name, $user->id, $group->name);
		}
	}
	
	echo "\n\n\n\n";
	echo "===============================\n";
	echo '需要更新用户组信息人员数为:'.$must_update_group."\n";
	echo '查找不到组信息的人员数为：'.count($no_group_users)."\n";
	echo '成功更新组信息的人员数为：'.$success."\n";
	echo '成功更新实验室信息数为：'.$lab_success."\n";
	echo "===============================\n";
	echo "不需要更新用户如下：(".count($not_must_users).")\n";
	foreach ($not_must_users as $u) {
		echo sprintf("	%s[%d]用户\n", $u->name, $u->id);
	}
	
}
catch (Error_Exception $e) {
	Log::add($e->getMessage(), 'sync_tju_user_group');
}

?>
