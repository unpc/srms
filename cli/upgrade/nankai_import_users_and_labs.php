#!/usr/bin/env php
<?php 
  /**
   * @file   nankai_import_users_and_labs.php
   * @author Xiaopei Li <xiaopei.li@gmail.com>
   * @date   2011.07.08
   * 
   * @brief  import users and labs
   * 	     the first line of each lab is considered as PI
   *
   * usage: SITE_ID=cf LAB_ID=test ./nankai_import_users_and_labs.php users.csv
   * 
   */
require '../base.php';

$file = $argv[1];

if (!($file && file_exists($file))) {
	print("usage: SITE_ID=cf LAB_ID=test ./nankai_import_users_and_labs.php users.csv\n");
	die;
}

// 课题组名是否加"课题组"的开关
$add_ketizu = TRUE;

$time = date('Ymdhis', time());

// 数据库备份文件名
$dbfile = LAB_PATH . 'private/backup/nankai_import_users_and_labs_'.$time.'.sql';

// 导入结果文件名
$result_file = LAB_PATH . 'private/backup/nankai_import_users_and_labs_'.$time.'_result.csv';

$u = new Upgrader;

// 检查是否需要升级
$u->check = function() {
	return TRUE;
};

// 备份
$u->backup = function() use($dbfile) {
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "备份数据库");

	File::check_path($dbfile);
	$db = Database::factory();
	return $db->snapshot($dbfile);
};

// 恢复
$u->restore = function() use($dbfile) {
	Upgrader::echo_message(Upgrader::MESSAGE_NORMAL, "恢复数据库");

	$db = Database::factory();
	$db->restore($dbfile);
};

// 升级
$u->upgrade = function() use ($file, $result_file, $add_ketizu) {
	/* 设置验证后台 */
	$backend = '|ids.nankai.edu.cn';

	/* group root */
	$group_root = Tag_Model::root('group');

	/* 读取输入文件 */
	$csv = new CSV($file, 'r');

	$user_total = 0;
	$user_new = 0;
	$user_old = 0;
	$user_failed = 0;

	$lab_total = 0;
	$lab_new = 0;
	$lab_old = 0;
	$lab_failed = 0;

	$lab = NULL;
	$prev_lab_name = '';

	$all_users = [];
	
	
	$user_ok = [];
	$lab_ok = [];
	
	$user_existed = [];
	$lab_existed = [];
	
	$failed_users = [];
	$failed_labs = [];

	while ($row = $csv->read(',')) {
		$user_total++;

		/* 读一行 */
		$name = trim($row[0]);
		$stuff_no = trim($row[1]);
		$token = $stuff_no . $backend;
		$card_no = trim($row[2]);
		if (!$card_no) {			/* 如果无物理卡号，就暂时将其设为职工号 */
			$card_no = NULL;
		}
		$email = trim($row[3]);
		if (!$email) {
			$email = join(explode(' ', PinYin::code($name))).rand(10, 99).'@nankai.edu.cn';
		}
	
		$lab_name = trim($row[4]);

		if ($add_ketizu) {
			$lab_name .= '课题组';
		}
	

		printf("正在处理%s\n", $name);

		/* 仅按token查询user */
		/* 忽略事先注册的同名用户 */
		$user = O('user', ['token' => $token]);

		if (!$user->id) {
			/* 如果无此用户，则新建 */
			$user->token = $token;
			$user->name = $name;
			$user->card_no = $card_no;
			$user->email = $email;
			$user->atime = $user->mtime = $user->ctime = time();
			if ($user->save()) {
				$user_new++;
				$user_ok[] = $user;
			}
			else {
				$user_failed++;
				$failed_users[] = $user;
			}
		}
		else {
			/* 如果已在系统 */
			if ($user->atime <= 0) {
				/* 且未激活(可能是自行注册) */
				$user->card_no = $card_no;
				$user->atime = time();
				if ($user->save()) {
					$user_new++;
					$user_ok[] = $user;
				}
				else {
					$user_failed++;
					$failed_users[] = $user;
				}
				/* 则激活 */
			}
			else {
				/* 且已激活 */
				$user_old++;
				/* 则不动 */
				$user_existed[] = $user;
			}
		}

		if ($lab_name != $prev_lab_name) {
			printf("%s为%s的PI\n", $name, $lab_name);

			$lab_total++;

			// $is_old_lab = count(Q("lab[name={$lab_name}][owner={$user}]"));
			$lab = O('lab', ['name' => $lab_name, 'owner' => $user]);
		
			// if ( $is_old_lab <= 0) {
			if (!$lab->id) {
				// $lab = O('lab');
				$lab->name = $lab_name;
				$lab->owner = $user;

				$lab_group_name = trim($row[5]);
				$lab_group = O('tag', [
								   'name' => $lab_group_name,
								   'root' => $group_root]);
			
				$lab->group = $lab_group;
			

				if ($lab->save()) {
					$lab_new++;
					$lab_group->connect($lab);
					$lab_ok[] = $lab;
				}
				else {
					$lab_failed++;
					$failed_labs[] = $lab;
				}
			}
			else {
				// $lab = O('lab', array('name' => $lab_name, 'owner' => $owner));
				$lab_old++;
				$lab_existed = $lab;
			}

			$prev_lab_name = $lab_name;
		}

		$user->lab = $lab;
		$user->group = $lab->group;
	
		if ($user->save()) {
			$lab->group->connect($user);
		}
		$all_users[] = $user;
	}
	
	printf("=============\n");

	printf("共处理%d名用户\n", $user_total);
	printf("新导入%d名用户\n" , $user_new);
	printf("已有%d名用户\n", $user_old);
	printf("尝试导入，但失败%d名用户\n", $user_failed);

	if ($user_failed) {
		foreach ($failed_users as $f_u) {
			printf("%s\n", $f_u->name);
		}
	}

	printf("共涉及%d个实验室\n", $lab_total);
	printf("新建立%d个实验室\n", $lab_new);
	printf("已有%d个实验室\n", $lab_old);
	printf("尝试导入，但失败%d实验室\n", $lab_failed);

	$result = new CSV($result_file, 'w');

	foreach ($all_users as $user) {
		$result->write(
			[
				$user->id,
				$user->name,
				$user->token,
				]);
	}
	$result->close();
	
	if ($user_failed || $lab_failed) {
		return FALSE;
	}
	return TRUE;
};

$u->verify = function() {
	Upgrader::echo_success('导入成功');
	return TRUE;
};

$u->post_upgrade = function() {};

$u->run();