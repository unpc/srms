<?php

/*
* @file file_sys.php 
* @author Jia Huang <jia.huang@geneegroup.com>
* @date 2012-07-02
* 
* @brief 文件系统测试脚本环境架设脚本之一
* @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/10-nfs/file_sys
*/

File_Sys_Test::teardown();
File_Sys_Test::setup();

class File_Sys_Test {

	static function teardown() {
		$db = Database::factory();
		$db->empty_database();
		Database::reset();
		ORM_Model::destroy('role');
		ORM_Model::destroy('user');
		ORM_Model::destroy('lab');

		// 2016-1-22 unpc 清空数据库之后需要更新ORM-S以保证数据表的存在
        foreach(Config::$items['schema'] as $name=>$schema) {
			$db = Database::factory();
			$schema = ORM_Model::schema($name);
			if ($schema) {
				$ret = $db->prepare_table($name, $schema);
		        if (!$ret) {
		            echo $name."表更新失败\n";
		        }
			}
		}
	}

	static function setup() {
		self::setup_roles();
		self::setup_users();
		self::setup_labs();
	}

	static function setup_roles() {
		$role_admin = O('role');
		$role_admin->name = '超级管理员';
		$role_admin->save();
		$role_perms = ['管理所有内容' => 'on', '管理组织机构' => 'on'];
		Properties::factory($role_admin)->set('perms', $role_perms)->save();
		Unit_Test::assert("新建角色 $role_admin->name", $role_admin->id > 0);

		$role_nfs_admin = O('role');
		$role_nfs_admin->name = 'NFS管理员';
		$role_nfs_admin->save();
		$role_perms = ['管理文件分区' => 'on'];
		Properties::factory($role_nfs_admin)->set('perms', $role_perms)->save();
		Unit_Test::assert("新建角色 $role_nfs_admin->name", $role_nfs_admin->id > 0);
	}

	static function setup_labs() {
		$lab = O('lab');
		$lab->name = 'LabOne';
		$lab->atime = time();
		$owner = O('user', ['name' => 'nfs实验室执法者']);
		$lab->owner = $owner;
		$lab->save();
		Unit_Test::assert("新建课题组 $lab->name", $lab->id > 0);
		$owner->lab = $lab;
		$owner->save();
	}

	static function setup_users() {
		$admin = O('user');
		$admin->name = '技术支持';
		$admin->token = 'genee|database';
		$admin->email = 'support@geneegroup.com';
		$admin->phone = '123456789';
		$admin->atime = time();
		$admin->save();
		$auth = new Auth('genee|database');
		$auth->create('123456');
		Unit_Test::assert("新建用户 $admin->name", $admin->id > 0);
		$role_admin = O('role', ['name' => '超级管理员']);
		if ($role_admin->id) {
			$connect_role = [$role_admin->id];
			$admin->connect(['role', $connect_role]);
			Unit_Test::assert("设定用户 $admin->name 角色为 $role_admin->name", TRUE);
		}

		$nfs_admin = O('user');
		$nfs_admin->name = 'NFS执法者';
		$nfs_admin->token = 'nfs.admin|database';
		$nfs_admin->email = 'nfs.admin@geneegroup.com';
		$nfs_admin->phone = '123456789';
		$nfs_admin->atime = time();
		$nfs_admin->save();
		$auth = new Auth('nfs.admin|database');
		$auth->create('123456');
		Unit_Test::assert("新建用户 $nfs_admin->name", $nfs_admin->id > 0);
		$role_nfs_admin = O('role', ['name' => 'NFS管理员']);
		if ($role_nfs_admin->id) {
			$connect_role = [$role_nfs_admin->id];
			$nfs_admin->connect(['role', $connect_role]);
			Unit_Test::assert("设定用户 $nfs_admin->name 角色为 $role_nfs_admin->name", TRUE);
		}

		$lab_admin = O('user');
		$lab_admin->name = 'nfs实验室执法者';
		$lab_admin->token = 'lab.admin|database';
		$lab_admin->email = 'lab.admin@geneegroup.com';
		$lab_admin->phone = '123456789';
		$lab_admin->atime = time();
		$lab_admin->save();
		$auth = new Auth('lab.admin|database');
		$auth->create('123456');
		Unit_Test::assert("新建用户 $lab_admin->name", $lab_admin->id > 0);

		$common_admin = O('user');
		$common_admin->name = 'nfs用户';
		$common_admin->token = 'common.admin|database';
		$common_admin->email = 'common.admin@geneegroup.com';
		$common_admin->phone = '123456789';
		$common_admin->atime = time();
		$common_admin->save();
		$auth = new Auth('common.admin|database');
		$auth->create('123456');
		Unit_Test::assert("新建用户 $common_admin->name", $common_admin->id > 0);
	}
}
