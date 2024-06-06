#!/usr/bin/env php
<?php
  /**
   * @file   import_user.php
   * @author Xiaopei Li <xiaopei.li@gmail.com>
   * @date   2012-08-16
   *
   * @brief  import users to default lab
   *
   * usage: SITE_ID=cf LAB_ID=test ./import_user.php users.csv
   *
   * csv 格式要求: 用户名|密码|姓名|组织机构|联系电话|email
   *
   */

require 'base.php';

$file = $argv[1];

if (!($file && file_exists($file))) {
	print("usage: SITE_ID=cf LAB_ID=test ./import_user.php users.csv\n");
	die;
}

$now = Date::time();

/* group root */
$root = Tag_Model::root('group');
$group_root_name = '东北电力大学';

$must_change_password = TRUE;

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

// 人员类型
$member_types = [];
foreach (User_Model::$members as $members){
	$member_types += array_flip($members);
}

// 角色
$member_roles = [];
foreach (L('ROLES') as $r) {
	$member_roles[$r->name] = $r;
}

while ($row = $csv->read(',')) {
	$user_total++;

    //0用户姓名*   1登录帐号*   2密码* 3性别  4人员类型 （）   5学工号 6专业()  7单位名称    8组织机构    9电子邮箱*   10联系电话*（）   11地址（）  12课题组*    13角色（） 14所在开始时间  15所在结束时间

	/* 读一行 */
	$name = trim($row[0]);
	$token = Auth::normalize(trim($row[1]), 'database');
	$password = trim($row[2]);
    $gender_name = trim($row[3]);
    $gender = ($gender_name == '男' ? 0 : 1);
    $member_type = $member_types[trim($row[4])];
    $ref_no = trim($row[5]);
    $major = trim($row[6]);
    $organization = trim($row[7]);
    
    $parent = O('tag', ['name' => $group_root_name, 'parent' => $root, 'root'=>$root]);
    if (!$parent->id) {
    	echo "未找到输入的校内组织机构";
    	die;
    }
    $group = O('tag', ['root' => $root, 'parent' => $parent, 'name' => trim($row[8])]);
    if (!$group->id) {
        $g = O('tag');
        $g->parent = $parent;
        $g->root = $root;
        $g->name = trim($row[8]);
        $g->save();
    } else {
        $g = $group;    
    }

    $email = trim($row[9]);
    $phone = trim($row[10]);
    $address = trim($row[11]);
    $lab_name = trim($row[12]);
    $roles = explode(',', trim($row[13]));
    $dfrom = strtotime(trim($row[14]));
    $dto = strtotime(trim($row[15]));

	$user = O('user', ['token' => $token]);

	if (!$user->id) {

		$auth = new Auth($token);
		if (!$auth->create($password)) {
			$user_failed++;
			$failed_users[] = $name;
			continue;
		}

		/* 如果无此用户，则新建 */
		$user->name = $name;
		$user->token = $token;
        $user->gender = $gender;
		$user->member_type = $member_type;
		$user->ref_no = $ref_no;
        $user->major = $major;
        $user->organization = $organization;
		$user->group = $g;
		$user->email = $email;
		$user->phone = $phone;
		$user->address = $address;
        $user->atime = $now;
		$user->must_change_password = $must_change_password;
		if (!($dfrom > 0 && $dto > 0 && $dfrom > $dto)) {
			$user->dfrom = $dfrom;
			$user->dto = $dto;
		}
		
        if ($user->save()) {
        	$connect_role = [];
			foreach($roles as $role) {
				if (!array_key_exists($role, $member_roles)) continue;
				$connect_role[] = $member_roles[$role]->id;
			}
			if (count($connect_role)) $user->connect(['role', $connect_role]);
			Event::trigger('user.after_role_change', $user, $connect_role, []);

			$user_new++;
			$user_ok[] = $name;
			
            $lab = O('lab', [
                     'name' => $lab_name,
                     ]);
            if (!$lab->id) {
                $lab->name = $lab_name;
                // $lab->subject = $lab_subject;
                $lab->group = $group;

                $lab->atime = $now;
            }

			if ($lab_name == $name."课题组") {
				$lab->owner = $user;
				$lab->save();
        		if ($lab->group->id) $lab->group->connect($lab);
			}
            $user->save();
            $user->connect($lab);

        	if ($user->group->id) $user->group->connect($user);
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



