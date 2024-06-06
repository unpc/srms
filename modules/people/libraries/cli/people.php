<?php

class CLI_People {
	//删除过期的找回密码
	static function delete_over_time_recovery() {
		$now = time();
		Q("recovery[overdue>0][overdue<={$now}]")->delete_all();
	}

	static function export_info() {
		$file = ROOT_PATH.'cli/export/people.csv';
		File::check_path($file);

		$output = new CSV($file, 'w');
		$output->write(
			[
				'id',
				'token',
				'name',
				'phone',
				'email',
				'ref_no',
				'is_active',
			]
		);
		/*
		$member_types = array(
			0=>'本科生',
			1=>'硕士研究生',
			2=>'博士研究生',
			3=>'其他学生',
			10=>'课题负责人(PI)',
			11=>'科研助理',
			12=>'PI助理/实验室管理员',
			13=>'其他教师',
			20=>'技术员',
			21=>'博士后',
			22=>'其他',
			);
		*/

		foreach (Q('user') as $u) {
			$output->write(
				[
					$u->id,
					$u->token,
					$u->name,
					$u->phone,
					$u->email,
					$u->ref_no,
					$u->atime ? 'TRUE' : 'FALSE',
					]
				);
		}

		$output->close();
	}

	static function import_users($file=null){
		/**
		* @file   import_user.php
		* @author Xiaopei Li <xiaopei.li@gmail.com>
		* @date   2012-08-16
		*
		* @brief  import users to default lab
		*
		* usage: SITE_ID=cf LAB_ID=test ./cli.php people import_user users.csv
		*
		* csv 格式要求: 用户名|密码|姓名|组织机构|联系电话|email
		*
		*/

		if (!($file && file_exists($file))) {
			print("usage: SITE_ID=cf LAB_ID=test ./cli.php people import_user users.csv\n");
			die;
		}

		/* group root */
		$group_root = Tag_Model::root('group');

		/* 读取输入文件 */
		$csv = new CSV($file, 'r');

		$user_total = 0;
		$user_new = 0;
		$user_old = 0;
		$user_failed = 0;

		$all_users = [];
		$user_ok = [];
		$user_existed = [];
		$failed_users = [];

		$escape_n_rows = 1;
		for (;$escape_n_rows--;) {
			$csv->read(',');
		}

		$n = 0;
		$password = '123456';

		while ($row = $csv->read(',')) {
			$user_total++;

			// CUC 规格如下: (xiaopei.li@2012-09-10)
			// 用户名 | 密码 | 姓名 | 组织机构 | 联系电话 | email | 性别 | 专业 | 过期时间

			// member_type | name | token | email | phone

			/* 读一行 */
			// $token = Auth::normalize(trim($row[0]));
			// $password = trim($row[1]);

			$member_type = trim($row[0]);

			$name = trim($row[1]);
			$first_name = mb_substr($name, 0, 1);
			$last_name = mb_substr($name, 1);
			$token_name = PinYin::code($last_name) . '.' . PinYin::code($first_name);
			$token = Auth::normalize($token_name);
			$token = strtr($token, [
							   ' ' => '',
							   ]);

			// echo $token ."\n";

			//	continue;

			// $token = trim($row[2]);


			/*
			$group_name = trim($row[3]);
			$group = O('tag', array(
						   'name' => $group_name,
						   'root' => $group_root,
						   ));
			if (!$group->id) {
				$group->name = $group_name;
				$group->root = $group_root;
				$group->save();
			}
			*/

			$phone = trim($row[4]);
			if (!$phone) {
				$phone = '未填写';
			}

			$email = trim($row[3]);
			if (!$email) {
				$email = '未填写_' . uniqid();
			}

			// printf("正在处理%s\n", $name);

			$user = O('user', ['token' => $token]);

			if (!$user->id) {

				$auth = new Auth($token);
				if (!$auth->create($password)) {
					$user_failed++;
					$failed_users[] = $name;
					continue;
				}

				/* 如果无此用户，则新建 */
				$user->token = $token;
				$user->member_type = $member_type;

				$user->name = $name;
				$user->phone = $phone;
				$user->email = $email;
				$user->atime = $user->mtime = $user->ctime = time();
				// $user->group = $group;
				$user->ref_no = NULL;
				$user->must_change_password = TRUE;
				/*
				switch (trim($row[6])) {
				case '男':
					$gender = 0;
					break;
				case '女':
					$gender = 1;
					break;
				default:
					$gender = -1;
				}
				*/

				// $user->gender = $gender;
				// $user->major = trim($row[7]);
				// $user->dto = strtotime($row[8]);

				if ($user->save()) {
					// $group->connect($user);

					$user_new++;
					$user_ok[] = $name;
				}
				else {
					$auth->remove();

					$user_failed++;
					$failed_users[] = $name;
				}
			}
			else {
				$user_old++;
				$user_existed[] = $name;
			}

			if ($n++ % 50 == 0) {
				echo '.';
			}

		}

		printf("\n=============\n");

		printf("共处理%d名用户\n", $user_total);
		printf("新导入%d名用户\n" , $user_new);
		printf("已有%d名用户\n", $user_old);
		if ($user_existed) {
			foreach ($user_existed as $e_u) {
				printf("%s\n", $e_u);
			}
		}


		printf("尝试导入，但失败%d名用户\n", $user_failed);

		if ($user_failed) {
			foreach ($failed_users as $f_u) {
				printf("%s\n", $f_u);
			}
		}
	}

	//将所有用户的隐私设置都设为 仅自己可见
	static function set_all_user_privacy_to_private() {
		$i = 0;

		foreach (Q('user') as $user) {
			$user->privacy = 1; // 1 为仅自己可见
			$user->save();

			if (++$i % 50 == 0) {
				echo '.';
			}

			if (++$I % 500 == 0) {
				echo "\n";
			}

		}

		echo "\n";
	}

	static function add($token=null, $name=null, $email=null) {
		try {

			
			if (!$token) {
				throw new Error_Exception('没有设置token');
			}
			
			echo "Account: $token\n";
			
			$user = O('user');

			$user->token = Auth::normalize($token);
			echo 'Email[nobody@geneegroup.com]: ';
			$email = trim(fgets(STDIN));
			$user->email = $email ?: 'nobody@geneegroup.com';

			echo 'Name[Doe John]: ';
			$name = trim(fgets(STDIN));
			$user->name = $name ?: 'Doe John';

			$user->member_type = 0;
			$user->atime = time();

			echo 'Password: ';
			$password = trim(fgets(STDIN)) ?: '123456';

			$auth = new Auth($token);
			if( !$auth->create($password)) {
				throw new Error_Exception('用户注册失败。');
			}
			
			$user->save();
			if (!$user->id) {
				$auth->remove(); //添加新成员失败，去掉已添加的 token
				throw new Error_Exception('用户保存失败。');
			}
			
		}
		catch (Error_Exception $e) {
			echo $e->getMessage()."\n";
		}
	}


	/*
		批量导入普通用户脚本
	*/
	static function create_users($file) {
		if (!file_exists($file)) {
			die("usage: SITE_ID=cf LAB_ID=test php cli.php people create_users users.csv\n");
		}

		$group_root = Tag_Model::root('group');
		$csv = new CSV($file, 'r');
		$csv->read(',');
		$total_count = $success_count = 0;

		while ($row = $csv->read(',')) {
			$total_count ++;
			if (!$row[0] || !$row[1]) continue;

			$token = Auth::normalize($row[1]);
			$user = O('user', ['token' => $token]);
			if ($user->id) continue;
			$user = O('user', ['email' => $row[9]]);
			if ($user->id) continue;
			$auth = new Auth($token);
			if (!$auth->is_readonly() && !$auth->create($row[2])) continue;
			foreach ((array)User_Model::get_members() as $key => $members) {
				foreach ($members as $k => $member) {
					if ($member == $row[4]) {
						$member_type = $k;
						break 2;
					}
				}
			}

			$group = O('tag_group', ['root' => $group_root, 'name' => $row[8]]);

			$roles = explode(',', $row[13]);

			$user = O('user');
			$user->token = $token;
			$user->name = $row[0];
			$user->gender = trim($row[3]) == '女' ? 1 : 0;
			$user->member_type = (int)$member_type;
			$user->ref_no = $row[5] ?: NULL;
			$user->major = $row[6];
			$user->organization = $row[7];
			$user->group = $group;
			$user->email = $row[9];
			$user->phone = $row[10];
			$user->address = $row[11];
			$lab = O('lab', ['name' => $row[12]]);
			$user->dfrom = $row['14'] ? Date::get_day_start(strtotime($row['14'])) : 0;
			$user->atime = $row['15'] ? Date::get_day_end(strtotime($row['15'])) : 0;
			
			if ($user->save()) {
				$user->connect($lab);
				$success_count ++;
				$group->connect($user);
				foreach ($roles as $role) {
					$r = O('role', ['name' => $role]);
					if ($r->id) $user->connect($r);
				}
				echo "\033[1;40;32m";
				echo sprintf("%s ==> 添加成功[%d]\n", $user->name, $user->id);
				echo "\033[0m";
			}
			else {
				$auth->remove();
			}


		}

		$csv->close();

		echo "\033[1;40;32m";
		echo sprintf("\n导入数据总数为:%d\t成功数为:%d \n", $total_count, $success_count);
		echo "\033[0m";
	}

	/*
		批量导入机主用户脚本
	*/

	static function create_incharges($file) {
		if (!file_exists($file)) {
			die("usage: SITE_ID=cf LAB_ID=test php cli.php people create_incharges incharges.csv\n");
		}

		$group_root = Tag_Model::root('group');
		$csv = new CSV($file, 'r');
		$csv->read(',');
		$total_count = $success_count = 0;

		while ($row = $csv->read(',')) {
			$total_count ++;
			if (!$row[0] || !$row[1]) continue;

			$token = Auth::normalize($row[1]);
			$user = O('user', ['token' => $token]);
			if ($user->id) continue;
			$user = O('user', ['email' => $row[5]]);
			if ($user->id) continue;
			$auth = new Auth($token);
			if (!$auth->is_readonly() && !$auth->create($row[2])) continue;

			$group = O('tag_group', ['root' => $group_root, 'name' => $row[3]]);

			$charger = O('user');
			$charger->name = $row[0];
			$charger->token = $token;
			$charger->group = $group;
			$charger->phone = $row[4];
			$charger->email = $row[5];
			$charger->atime = time();
			if ($charger->save()) {
				$success_count ++;
				$group->connect($charger);
				echo "\033[1;40;32m";
				echo sprintf("%s ==> 机主添加成功[%d]\n", $charger->name, $charger->id);
				echo "\033[0m";
			}
			else {
				$auth->remove();
			}
		}

		$csv->close();
		echo "\033[1;40;32m";
		echo sprintf("\n导入数据总数为:%d\t成功数为:%d \n", $total_count, $success_count);
		echo "\033[0m";	
	}

	// 将过期用户设置为未激活
	static function disable_overdue_user() {
		$now = Date::time();
		foreach (Q("user[dto<$now][dto!=0][atime!=0]") as $user) {
			$user->unactive()->save();
			Log::add(strtr('[people] 因过期将用户%user_name[%user_id]设置成未激活', [
			   '%user_name' => $user->name,
			   '%user_id' => $user->id,
			   ]), 'journal');
		}
	}
}

