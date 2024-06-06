<?php

class Env_Helper {
	
	static function prepare_lab($prefix) {
		$lab = O('lab');
		$lab->name = '测试实验室'.$prefix;
		$lab->save();
		Unit_Test::assert("建立 $lab->name", $lab->id > 0);
		self::enlist($lab);
		return $lab;
	}
	
	static function prepare_user($prefix, $lab) {
		$user = O('user', ['token'=>'temp_token_'.$prefix]);
		$user->name = '测试用户'.$prefix;
		$user->token = 'temp_token_'.$prefix;
		$user->email = 'temp'.$prefix.'@test.com';
		$user->lab = $lab;
		$user->save();
		Unit_Test::assert("建立 $user->name", $user->id > 0);
		self::enlist($user);
		return $user;
	}
	
	static function prepare_equipment($prefix) {
		$equipment = O('equipment');
		$equipment->name = '测试仪器'.$prefix;
		$equipment->save();
		Unit_Test::assert("建立 $equipment->name", $equipment->id > 0);
		self::enlist($equipment);
		return $equipment;
	}
	
	private static $_objects = [];	
	static function enlist($object) {
		self::$_objects[] = $object;
	}
	
	static function destroy() {
		foreach(self::$_objects as $object) {
			$object->delete();
		}
		self::$_objects = NULL;
	}
	/*
	NO.TASK#228（guoping.zhang@2010.11.15)
	为测试创建财务部门
	*/
	static function prepare_department($suffix) {
		$department = O('billing_department');
		$department->name = '测试财务部门' . $suffix;
		$department->save();
		Unit_Test::assert("建立 $department->name", $department->id > 0);
		self::enlist($department);
		return $department;
	}
	/*
	NO.TASK#228（guoping.zhang@2010.11.15)
	为测试创建收费帐号
	*/
	static function prepare_account($suffix, $department, $lab) {
		$account = O('billing_account');
		$account->name = '测试收费帐号' . $suffix;
		$account->department = $department;
		$account->lab = $lab;
		$account->save();
		Unit_Test::assert("建立 $account->name", $account->id > 0);
		self::enlist($account);
		return $account;
	}
}

