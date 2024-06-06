#!/usr/bin/env php
<?php
  /**
   * file   import_user_from_sky.php
   * author Rui Ma <rui.ma@geneegroup.com>
   * date   2014-07-15
   *
   * brief  import users from nankai_sky(cf-lite)
   *
   * usage: SITE_ID=cf LAB_ID=nankai ./import_user_from_sky.php users.csv
   *
   */

$_SERVER['SITE_ID'] = 'cf';
$_SERVER['LAB_ID'] = 'nankai';

require dirname(__FILE__). '/base.php';

function Usage() {
    echo <<<EOF
Usage: 
    SITE_ID=cf LAB_ID=nankai php foobar.php users_of_nankai_sky.csv

EOF;
}

class User_Same_Token_Exception  extends Error_Exception {} //相同token
class User_Same_Email_Exception extends Error_Exception {} //相同email
class User_Unknow_Exception extends Error_Exception {} //未知问题
class Lab_Not_Existed_Exception extends Error_Exception {} //实验室不存在

$file = $argv[1];

if (!($file && file_exists($file))) {
    Usage();
	die;
}

/* group root */
$group_root = Tag_Model::root('group');

/* 读取输入文件 */
$csv = new CSV($file, 'r');

$user_total = 0;

$user_token_existed = [];
$user_email_existed = [];
$lab_not_existed = [];
$user_success = []; //添加成功的用户
$user_failed = []; //正常添加，添加失败

//跳过头文件1行
$escape_n_rows = 1;
for (;$escape_n_rows--;) {
	$csv->read();
}

$n = 0;

while ($row = $csv->read()) {

    try {
        //    0    name 用户姓名
        //    1    token@一卡通用户 登录账号
        //    2    gender 性别
        //    3    member_type 人员类型
        //    4    major 专业
        //    5    organization 单位名称
        //    6    group_name 组织机构
        //    7    email 电子邮箱
        //    8    phone 联系电话
        //    9    addres 地址
        //    10    lab 课题组
        //    11    role 角色

        //用户总数
        $user_total ++;

        $name = trim($row[0]);

        $token = explode('@', $row[1])[0];
        $token = Auth::make_token($token, 'ids.nankai');

        $gender = $row[2] == '男' ? 0 : 1;

        $member_type = $row[3];
        list($a, $b) = explode('-', $member_type);
        $a = trim($a);
        $b = trim($b);

        //$a 学生
        //$b 硕士研究生
        $member_type = array_search($b, User_Model::$members[$a]);

        $major = $row[4]; 
        $organization = $row[5];
        $group = O('tag', ['name'=> $row[6], 'root'=> $group_root]);
        $email = $row[7];
        $phone = $row[8];
        $addres = $row[9];
        $lab = O('lab', ['name'=> str_replace('实验室', '课题组', trim($row[10]))]);

        if (!$lab->id) {
            throw new Lab_Not_Existed_Exception; //不存在lab
        }

        $user = O('user', ['token'=> $token]);

        if (O('user', ['token'=> $token])->id) {
            throw new User_Same_Token_Exception; //相同token
        }

        if (O('user', ['email'=> $email])->id) {
            throw new User_Same_Email_Exception; //相同email
        }

        //可添加用户
        $user = O('user');
        $user->name = $name;
        $user->token = $token;
        $user->gender = $gender;
        $user->member_type = $member_type;
        $user->major = $major;
        $user->organization = $organization;
        $user->group = $group;
        $user->email = $email;
        $user->phone = $phone;
        $user->addres = $addres;

        $user->atime = $user->mtime = $user->ctime = Date::time();

        if ($user->save()) {
            $user_success[] = $name;
        }
        else {
            $user_failed[] = $name;
        }

        if ($n++ % 50 == 0) {
            echo '.';
        }

    }
    catch(Lab_Not_Existed_Exception $e) {
        $lab_not_existed[] = $name;
    }
    catch(User_Same_Token_Exception $e) {
        $user_token_existed[] = $name;
    }
    catch(User_Same_Email_Exception $e) {
        $user_email_existed[] = $name;
    }
    catch(Error_Exception $e) {
        $user_failed[] = $name;
    }
}

printf("\n=============\n");

printf("共处理%d名用户\n", $user_total);
printf("新导入%d名用户\n" , count($user_success));
printf("已有%d名账号相同用户\n", count($user_token_existed));
if (count($user_token_existed)) {
	foreach ($user_token_existed as $k_u) {
		printf("%s\n", $k_u);
	}
}

printf("已有%d名邮箱相同用户 \n", count($user_email_existed));
if (count($user_email_existed)) {
    foreach($user_email_existed as $e_u) {
        printf("%s\n", $e_u);
    }
}

printf("已有%d名实验室不存在的用户 \n", count($lab_not_existed));
if (count($lab_not_existed)) {
    foreach($lab_not_existed as $l_u) {
        printf("%s\n", $l_u);
    }
}

printf("尝试导入，但失败%d名用户\n", $user_failed);

if ($user_failed) {
	foreach ($failed_users as $f_u) {
		printf("%s\n", $f_u);
	}
}
