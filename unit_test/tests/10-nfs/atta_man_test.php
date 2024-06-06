<?php
/*
* @file atta_man_test.php
* @author Jia Huang <jia.huang@geneegroup.com>
* @date 2012-07-02
* 
* @brief 附件管理环境架设脚本之一
* @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/10-nfs/atta_man_test
*/


Atta_Man_Test::teardown();
Atta_Man_Test::setup();

class Atta_Man_Test {

	static function teardown() {
		$db = Database::factory();
		$db->empty_database();
		Database::reset();

        ORM_Model::destroy('role');
        ORM_Model::destroy('user');
        ORM_Model::destroy('equipment');

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
		self::setup_eq();
	}


	static function setup_roles() {
		$role_admin = O('role');
		$role_admin->name = '超级管理员';
		$role_admin->save();
		$role_perms = ['管理所有内容' => 'on', '管理组织机构' => 'on'];
		Properties::factory($role_admin)->set('perms', $role_perms)->save();
		Unit_Test::assert("新建角色 $role_admin->name", $role_admin->id > 0);

		$role_eq_admin = O('role');
		$role_eq_admin->name = '仪器管理员';
		$role_eq_admin->save();
		$role_perms = ['查看所有仪器的附件' => 'on', '上传/创建所有仪器的附件' => 'on', '下载所有仪器的附件' => 'on', '更改/删除所有仪器的附件' => 'on'];
		Properties::factory($role_eq_admin)->set('perms', $role_perms)->save();
		Unit_Test::assert("新建角色 $role_eq_admin->name", $role_eq_admin->id > 0);
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

		$eq_admin = O('user');
		$eq_admin->name = '柴志华';
		$eq_admin->token = 'chaizhihua|database';
		$eq_admin->email = 'chaizhihua@geneegroup.com';
		$eq_admin->phone = '123456789';
		$eq_admin->atime = time();
		$eq_admin->save();
		$auth = new Auth('chaizhihua|database');
		$auth->create('123456');
		Unit_Test::assert("新建用户 $eq_admin->name", $eq_admin->id > 0);
		$role_eq_admin = O('role', ['name' => '仪器管理员']);
		if ($role_eq_admin->id) {
			$connect_role = [$role_eq_admin->id];
			$eq_admin->connect(['role', $connect_role]);
			Unit_Test::assert("设定用户 $eq_admin->name 角色为 $role_eq_admin->name", TRUE);
		}

		$eq_incharge = O('user');
		$eq_incharge->name = '陈建宁';
		$eq_incharge->token = 'chenjianning|database';
		$eq_incharge->email = 'chenjianning@geneegroup.com';
		$eq_incharge->phone = '123456789';
		$eq_incharge->atime = time();
		$eq_incharge->save();
		$auth = new Auth('chenjianning|database');
		$auth->create('123456');
		Unit_Test::assert("新建用户 $eq_incharge->name", $eq_incharge->id > 0);

		$normal = O('user');
		$normal->name = '许宏山';
		$normal->token = 'xuhongshan|database';
		$normal->email = 'xuhongshan@geneegroup.com';
		$normal->phone = '123456789';
		$normal->atime = time();
		$normal->save();
		$auth = new Auth('xuhongshan|database');
		$auth->create('123456');
		Unit_Test::assert("新建用户 $normal->name", $normal->id > 0);
	}

	static function setup_eq() {
		$equipment = O('equipment');
		$equipment->name = '附件管理测试仪器';
		$equipment->save();
		$man_admin = O('user', ['name' => '陈建宁']);
		$equipment->connect($man_admin, 'incharge');
		$equipment->connect($man_admin, 'contact');
		Unit_Test::assert("新建仪器 $equipment->name", $equipment->id > 0);
	}
}
