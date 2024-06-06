#!/usr/bin/env php
<?php
  /**
   * @file   open_nfs.php
   * @author Xiaopei Li <toksea@gmail.com>
   * @date   2011.06.15
   * 
   * @brief  开通系统中所有现有课题组和成员的文件系统
   *
   * 注意: 必须以www-data运行
   * usage: sudo -u www-data SITE_ID=cf LAB_ID=test ./open_nfs.php
   * 
   */
require "base.php";

$labs = Q('lab');
$users = Q('user');

foreach ($labs as $lab) {
	if ($lab->nfs_size == 0) {	/* 开通 尚未开通的实验室 */
		NFS_Share::setup_share($lab);
	}
}

foreach ($users as $user) {
	NFS_Share::setup_share($user); /* 开通/更新 所有成员 */
}
