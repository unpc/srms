#!/usr/bin/env php
<?php
    /*
     * file export_users.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013-11-26
     *
     * useage SITE_ID=cf LAB_ID=test php export_users.php
     * brief 用于对某个SITE LAB下的用户进行导出成CSV文件
     */

require dirname(dirname(__FILE__)). '/base.php';

$group_root = Tag_Model::root('group');
switch($_SERVER['LAB_ID']) {
    case 'nankai' :
        $export_group = O('tag', ['root'=> $group_root, 'name'=> '化学学院']);
        break;
    case 'tju' :
        $export_group =  O('tag', ['root'=> $group_root, 'name'=> '化工学院']);
        break;
    default :
        die("Wrong SITE_ID\n");
}

$a = [];


$admin_user = O('user', ['token'=> 'genee|database']);
$a[] = $admin_user->id;
//解决admin导入不重复问题
$admin_user->email = $admin_user->email . $_SERVER['LAB_ID'];

//需要对admin用户进行导出
//不知为何 append后，limit不会while遍历出来
$user_query = Q("$export_group user");

$start = 0;
$perpage = 10;

$file_name = strtolower("{$_SERVER['SITE_ID']}_{$_SERVER['LAB_ID']}"). '_users.csv';

$csv = new CSV($file_name, 'w');

$head = [
    '姓名',             //name
    '账号',             //token
    '电子邮箱',         //email
    '有效起始时间',     //dfrom
    '有效结束时间',     //dto
    '联系方式',         //phone
    '联系地址',         //address
    '组织机构',         //group
    '性别',             //gender
    '人员类型',         //member_type
    '学号/工号',        //ref_no
    '专业',             //major
    '单位名称',         //organization
    '是否激活',         //atime
    '是否不可删除',     //undeletable
    '是否隐藏',         //hidden
];

$csv->write($head);

$db = Database::factory();

function write_user_to_csv($user) {
    global $csv;

    $data = [];
    $data[] = $user->name;
    $data[] = $user->token;
    $data[] = $user->email;
    $data[] = $user->dfrom;
    $data[] = $user->dto;
    $data[] = $user->phone;
    $data[] = $user->address;
    $data[] = $user->group->name;
    $data[] = $user->gender;
    $data[] = $user->member_type;
    $data[] = $user->ref_no;
    $data[] = $user->major;
    $data[] = $user->organization;
    $data[] = $user->atime;
    $data[] = $user->undeletable;
    $data[] = $user->hidden;

    $csv->write($data);
}

echo "开始进行用户导出\n";

write_user_to_csv($admin_user);
while(count($users = $user_query->limit($start, $perpage))) {

    foreach($users as $user) {
        if (in_array($user->id, $a)) continue;
        switch($_SERVER['LAB_ID']) {
            case 'nankai' :
                $token_condition = 'ids.nankai';
                break;
            case 'tju' :
                $token_condition = 'database';
                break;
        }

        $pos = strpos($user->token, $token_condition);

        //存在backend，并且backend为str的最后，避免出现 genee|database%less.nankai这种错误的tju数据导入
        if (! ($pos && $pos + strlen($token_condition) == strlen($user->token))) continue;

        write_user_to_csv($user);
        echo '.';
        $a[] = $user->id;
    }

    $start += $perpage;
}

//进行incharge导出
$start = 0;
$user_query = Q('equipment user.incharge');
while(count($users = $user_query->limit($start, $perpage))) {

    foreach($users as $user) {
        if (in_array($user->id, $a)) continue;
        switch($_SERVER['LAB_ID']) {
            case 'nankai' :
                $token_condition = 'ids.nankai';
                break;
            case 'tju' :
                $token_condition = 'database';
                break;
        }

        $pos = strpos($user->token, $token_condition);

        //存在backend，并且backend为str的最后，避免出现 genee|database%less.nankai这种错误的tju数据导入
        if (! ($pos && $pos + strlen($token_condition) == strlen($user->token))) continue;

        write_user_to_csv($user);
        echo '.';
        $a[] = $user->id;
    }

    $start += $perpage;
}

//进行超级管理员导出
$start = 0;
$user_query = Q("role[name='超级管理员'] user");

while(count($users = $user_query->limit($start, $perpage))) {

    foreach($users as $user) {
        if (in_array($user->id, $a)) continue;
        switch($_SERVER['LAB_ID']) {
            case 'nankai' :
                $token_condition = 'ids.nankai';
                break;
            case 'tju' :
                $token_condition = 'database';
                break;
        }

        $pos = strpos($user->token, $token_condition);

        //存在backend，并且backend为str的最后，避免出现 genee|database%less.nankai这种错误的tju数据导入
        if (! ($pos && $pos + strlen($token_condition) == strlen($user->token))) continue;

        write_user_to_csv($user);
        echo '.';
        $a[] = $user->id;
    }

    $start += $perpage;
}

//进行该组织机构下课题组下的用户进行导出
$start = 0;
$user_query = Q('equipment user.incharge');
while(count($users = $user_query->limit($start, $perpage))) {

    foreach($users as $user) {
        if (in_array($user->id, $a)) continue;
        switch($_SERVER['LAB_ID']) {
            case 'nankai' :
                $token_condition = 'ids.nankai';
                break;
            case 'tju' :
                $token_condition = 'database';
                break;
        }

        $pos = strpos($user->token, $token_condition);

        //存在backend，并且backend为str的最后，避免出现 genee|database%less.nankai这种错误的tju数据导入
        if (! ($pos && $pos + strlen($token_condition) == strlen($user->token))) continue;

        write_user_to_csv($user);
        echo '.';
        $a[] = $user->id;
    }

    $start += $perpage;
}

//进行超级管理员导出
$start = 0;
$user_query = Q("{$export_group} lab user");

while(count($users = $user_query->limit($start, $perpage))) {

    foreach($users as $user) {
        if (in_array($user->id, $a)) continue;
        switch($_SERVER['LAB_ID']) {
            case 'nankai' :
                $token_condition = 'ids.nankai';
                break;
            case 'tju' :
                $token_condition = 'database';
                break;
        }

        $pos = strpos($user->token, $token_condition);

        //存在backend，并且backend为str的最后，避免出现 genee|database%less.nankai这种错误的tju数据导入
        if (! ($pos && $pos + strlen($token_condition) == strlen($user->token))) continue;

        write_user_to_csv($user);
        echo '.';
        $a[] = $user->id;
    }

    $start += $perpage;
}

$csv->close();

echo "\n导出成功! 详见文件$file_name\n";
