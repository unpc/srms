#!/usr/bin/env php
<?php
  /**
   * @file   set_equipments_group.php
   * @author Xiaopei Li <xiaopei.li@geneegroup.com>
   * @date   2011.07.12
   * 
   * @brief  设置仪器组织机构
   * 输入文件第一行为组织机构的id，第二行为","分割的仪器id
   * 如:
   * 1
   * 1,2,3,4,5
   *
   * usage: SITE_ID=cf LAB_ID=test ./set_equipments_group.php foo.txt
   * 
   */
require "base.php";

$f = fopen($argv[1], 'r') or die("usage: SITE_ID=cf LAB_ID=test ./set_equipments_group.php foo.txt \n");

$group_id = trim(fgets($f));
$eq_ids = explode(',', fgets($f));

$group_root = Tag_Model::root('group');
$group = O('tag', $group_id);
if (!$group->id || $group->root->id != $group_root->id) {
	die("invalid group id\n");
}
$failed_eq_ids = [];
foreach ($eq_ids as $eq_id) {
	$equipment = O('equipment', $eq_id);
	if (!$equipment->id) {
		$failed_eq_ids[] = $eq_id;
		continue;
	}

	$group_root->disconnect($equipment);
	$group->connect($equipment);
	$equipment->group = $group;
	if (!$equipment->save()) {
		$failed_eq_ids[] = $eq_id;
	}
}
printf("处理%d台仪器\n", count($eq_ids));
printf("其中%d台处理失败\n", count($failed_eq_ids));
if (count($failed_labs)) {
	printf("失败的为: %s", join(',', $failed_eq_ids));
}