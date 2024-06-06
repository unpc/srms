<?php
require dirname(__FILE__) . '/base.php';

define('DISABLE_NOTIFICATION', 1);

$file = $argv[1];
if (!($file && file_exists($file))) {
	die("usage: SITE_ID=cf LAB_ID=test import_labs_and_users.php import.csv\n");
}

// $backend = 'e.jiangnan.edu.cn';
$backend = '';
$backends = Config::get('auth.backends');

$group_root = Tag_Model::root('group');
$now = Date::time();


$member_types = [
	'本科生' => 0,
	'硕士研究生' => 1,
	'研究生' => 1,
	'硕士留学生' => 1,
	'硕士研究生' => 1,
	'博士研究生' => 2,
	'博士' => 2,
	'其他学生' => 3,
	'其他研究生' => 3,
	'课题负责人(PI)' => 10,
	'科研助理' => 11,
	'PI助理/实验室管理员' => 12,
	'实验室管理人员' => 12,
	'实验室管理员' => 12,
	'其他老师' => 13,
	'技术员' => 20,
	'实验员' => 20,
	'博士后' => 21,
	'其他' => 22,
	'课题组负责人' => 10,
	'课题组负责人（教师）' => 10,
	];
$groups = get_groups_raw_data();

$user_total = 0;
$user_new = 0;
$user_old = 0;
$user_failed = 0;
$failed_users = [];
$old_users = [];

$lab_total = 0;
$lab_new = 0;
$lab_old = 0;
$lab_failed = 0;
$failed_labs = [];
$old_labs = [];

$csv = new CSV($file, 'r');

$row_escaped = 1;
for (;$row_escaped--;) {
	$csv->read(',');
}

while ($row = $csv->read(',')) {

	$user_total++;

	// 课题组名称,所属学科,组织机构代码,人员类型,人员名称,e江南账号,e-mail,电话,
	$lab_name = trim($row[0]);
	$lab_subject = trim($row[1]);
	$group_no = trim($row[2]);
	$member_type = trim($row[3]);
	$name = trim($row[4]);
	$token_name = trim($row[5]);
	$email = trim($row[6]);

	//数据库的email是唯一非空的，如果数据中没有email但是要导入，可以使用下面的代码
	// $email = trim($row[6]) ?: uniqid('email_');

	$phone = trim($row[7]);
	$password = trim($row[8]);

	// printf("正在处理%s\n", $name);
	echo '.';

	$group_name = $groups[$group_no];

	$group = O('tag', [
				   'root' => $group_root,
				   'name' => $group_name,
				   ]);

	if ($lab_name != $last_labs_name) {
		$lab_total++;

		$lab = O('lab', [
					 'name' => $lab_name,
					 ]);

		if (!$lab->id) {
			$lab->name = $lab_name;
			$lab->subject = $lab_subject;
			$lab->group = $group;

			$lab->atime = $now;
			if ($lab->save()) {
				$lab_new++;
			}
			else {
				$lab_failed++;
				$failed_labs[] = $row;
			}
		}
		else {
			$lab_old++;
			$old_labs[] = $row;
		}
	}
	$last_labs_name = $lab_name;

	if ($backend) {
		$token = Auth::make_token($token_name, $backend);
	}
	else {
		$token = Auth::Normalize($token_name);
	}

	//密码默认为123456
	$password = $password ?: 123456;
	$auth = new Auth($token);
	if (!$auth->create($password)) {
		$user_failed++;
		$failed_users[] = $row;
		continue;
	}
	
	$user = O('user', [
				  'token' => $token,
				  ]);
	if (!$token_name || !$user->id) {
		$user->token = $token;
		$user->must_change_password = TRUE;
		$user->name = $name;
		$user->email = $email;
		$user->phone = $phone;
		$user->member_type = $member_type;
		$user->group = $group;
		
		$user->atime = $now;
		if ($user->save()) {
			$user->connect($lab);
			$user_new++;
		}
		else {
			$user_failed++;
			$failed_users[] = $row;

			$auth->remove();
		}
	}
	else {
		$user_old++;
		$old_users[] = $row;
	}

	if (10 == $user->member_type && !$lab->owner->id) {
		$lab->owner = $user;
		$lab->save();
	}
}

foreach (Q('user') as $u) {
	$u->group->connect($u);
}
foreach (Q('lab') as $l) {
	$l->group->connect($l);
}

echo "\n";

printf("=============\n");

printf("共涉及%d个实验室\n", $lab_total);
printf("新建立%d个实验室\n", $lab_new);
printf("已有%d个实验室\n", $lab_old);
if ($lab_old) {
	foreach ($old_labs as $ol) {
		echo join(',', [
					  $ol[0],
					  $ol[1],
					  $ol[2],
					  ]);
		echo "\n";
	}
}
printf("尝试导入，但失败%d实验室\n", $lab_failed);
if ($lab_failed) {
	foreach ($failed_labs as $ol) {
		echo join(',', [
					  $ol[0],
					  $ol[1],
					  $ol[2],
					  ]);
		echo "\n";
	}
}

printf("共处理%d名用户\n", $user_total);
printf("新导入%d名用户\n" , $user_new);
printf("已有%d名用户\n", $user_old);
if ($user_old) {
	foreach ($old_users as $ou) {
		echo join(',', $ou) . "\n";
	}
}

printf("尝试导入，但失败%d名用户\n", $user_failed);
if ($user_failed) {
	foreach ($failed_users as $fu) {
		echo join(',', $fu) . "\n";
	}
}



/* functions */
function get_groups_raw_data() {
	return [];
}

