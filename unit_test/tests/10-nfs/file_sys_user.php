<?php
/*
* @file file_sys_user
* @author Jia Huang <jia.huang@geneegroup.com>
* @date 2012-07-02
* 
* @brief 文件系统环境架设脚本之一
* @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/10-nfs/file_sys_user
*/

require_once 'file_sys.php';

File_Sys_User_Test::setup();

class File_Sys_User_Test {

	static function setup() {
		self::setup_roles();
	}
	static function setup_roles() {
		$user_admin = O('role');
		$user_admin->name = '成员管理员';
		$user_admin->save();
		$role_perms = ['查看所有成员的附件' => 'on', '下载所有成员的附件' => 'on', '上传/创建所有成员的附件' => 'on', '更改/删除所有成员的附件' => 'on'];
		Properties::factory($user_admin)->set('perms', $role_perms)->save();
		Unit_Test::assert("新建角色 $user_admin->name", $user_admin->id > 0);
	}
}
