#!/usr/bin/env php
<?php
  /**
   * @file   add_role_to_eq_charges.php
   * @author Xiaopei Li <toksea@gmail.com>
   * @date   Wed Jun  1 10:59:28 2011
   * 
   * @brief  将所有仪器负责人加入到某一指定的角色
   *
   * usage: SITE_ID=cf LAB_ID=test ./add_role_to_eq_charges.php 5
   * 其中5为角色id
   * 
   */
require "base.php";

$role_to_add = O('role', $argv[1]);
if (!$role_to_add->id) {
	echo 'invalid role' . "\n";
	die;
}

echo '对所有仪器负责人添加' . $role_to_add->name . '权限？(yes/no)';
$fh = fopen('php://stdin', 'r') or die($php_errormesg);
$ret = fgets($fh, 5);
if (trim($ret) != 'yes') {
	die;
}

$all_incharges = Q("equipment user.incharge");
echo '共有' . count($all_incharges) . "个仪器负责人\n";
foreach ($all_incharges as $user) {
	echo $user->name . "...";
	if (!$user->connected_with($role_to_add)) {
		if ($user->connect($role_to_add)) {
			echo "链接成功 \n";
		}
		else {
			echo "链接失败 \n";
		}
	}
	else {
		echo "已链接 \n";
	}
}

