#!/usr/bin/env php
<?php
  /**
   * @file   set_equipments_department.php
   * @author Xiaopei Li <xiaopei.li@geneegroup.com>
   * @date   2011.07.12
   * 
   * @brief  设置仪器收费中心
   * 输入文件第一行为收费中心的id，第二行为","分割的仪器id
   * 如:
   * 1
   * 1,2,3,4,5
   *
   * usage: SITE_ID=cf LAB_ID=test ./set_equipments_department.php foo.txt
   * 
   */
require "base.php";

$f = fopen($argv[1], 'r') or die("usage: SITE_ID=cf LAB_ID=test ./set_equipments_department.php foo.txt \n");

$department_id = trim(fgets($f));
$eq_ids = explode(',', fgets($f));

$department = Fin_Department::get($department_id);
if (!$department->id) {
	die("invalid department\n");
}

$failed_eq_ids = [];
foreach ($eq_ids as $eq_id) {
	$equipment = O('equipment', $eq_id);
	if (!$equipment->id) {
		$failed_eq_ids[] = $eq_id;
		continue;
	}

	$equipment->billing_dept = $department;
	if (!$equipment->save()) {
		$failed_eq_ids[] = $eq_id;
	}
}
printf("处理%d台仪器\n", count($eq_ids));
printf("其中%d台处理失败\n", count($failed_eq_ids));
if (count($failed_labs)) {
	printf("失败的为: %s", join(',', $failed_eq_ids));
}