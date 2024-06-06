#!/usr/bin/env php
<?php
  /**
   * @file   set_lab_members_group.php
   * @author Xiaopei Li <toksea@gmail.com>
   * @date   2011.06.15
   * 
   * @brief  设置实验室成员的组织机构为实验室的组织机构
   *
   * usage: SITE_ID=cf LAB_ID=test ./set_lab_members_group.php
   * 
   */
require "base.php";

$labs = Q('lab');

foreach($labs as $lab) {
	echo "正在处理 {$lab->name}\n";

	$lab_group = $lab->group;
	
	if (!$lab_group->id) {
		echo "{$lab->name} 未设置组织机构\n";
		
		continue;
	}
	echo "{$lab->name} 的组织机构是 {$lab_group->name}\n";
	
	$members = Q("user[lab={$lab}]");

	foreach ($members as $member) { /* 如果实验室成员未设置过组织机构，则将其组织机构设置为实验室的 */
		if (!$member->group->id) {
			$lab_group->connect($member);
			$member->group = $lab_group;
			if ($member->save()) {
				green_print("\t{$member->name} 的组织机构已设为 {$lab_group->name}\n");
			}
			else {
				red_print("\t{$member->name} 的组织机构设置失败\n");
			}
		}
		else {
			echo "\t{$member->name} 已有组织机构 {$member->group->name} \n";
		}
	}
}

/*
  几个彩色打印的函数
  参考zhen.liu的博客:http://blog.csdn.net/ajaxuser/archive/2010/10/16/5945675.aspx
*/
function green_print($content) {
	color_print(32, 40, $content);
}

function red_print($content) {
	color_print(31, 40, $content);
}

function color_print($front, $backgroud, $content) {
	printf("\033[1;%d;%dm%s\033[0m", $front, $backgroud, $content);
}