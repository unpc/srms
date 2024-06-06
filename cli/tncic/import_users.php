#!/usr/bin/env php
<?php
    /*
     * file import_users.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013-11-26
     *
     * useage SITE_ID=cf LAB_ID=test php import_users.php
     * brief 对CSV文件进行读取，创建系统中现有用户
     */

$_SERVER['SITE_ID'] = 'cf';
$_SERVER['LAB_ID'] = 'tncic';

require dirname(dirname(__FILE__)). '/base.php';

$file = $argv[1];
$source = $argv[2];


if (!in_array($source, ['tju', 'nankai'])) {
    unset($source);
}
else {
    $source_backend = $source == 'tju' ? 'yiqi.tju' : 'less.nankai';
}

if (!$file || !File::exists($file) || !$source) {
    die("Usage: SITE_ID=xx LAB_ID=xx php import_users.php file.csv source\n");
}

$csv = new CSV($file, 'r');

//head跳过
$csv->read();

$db = Database::factory();

$count = $success = $failure = 0;

$root = Tag_Model::root('group');

while($data = $csv->read()) {

    /*
     *  $data 结构:
     *    0=> 姓名
     *    1=> 账号
     *    2=> 电子邮箱
     *    3=> 有效起始时间
     *    4=> 有效结束时间
     *    5=> 联系方式
     *    6=> 联系地址
     *    7=> 组织机构
     *    8=> 性别
     *    9=> 人员类型
     *    10=> 学号/工号
     *    11=> 专业
     *    12=> 单位名称
     *    13=> 是否激活
     *    14=> 是否不可删除
     *    15=> 是否隐藏
     */

    ++ $count;

    $user = O('user');
    $user->name = $data[0];

    $user->token = $data[1] ? $data[1]. '%'. $source_backend : NULL;

    $user->email = $data[2];
    $user->dfrom = $data[3];
    $user->dto = $data[4];
    $user->phone = $data[5];
    $user->address = $data[6];

    $user->gender = $data[8];
    $user->member_type = $data[9];
    $user->ref_no = $data[10] ? : NULL;
    $user->major = $data[11];
    $user->organization = $data[12];
    $user->atime = $data[13];
    $user->undeleteable = $data[14];
    $user->hidden = $data[15];

    $user->source = $source;

    if ($user->save()) {
        echo '.';
        ++ $success;

        $group = O('tag', ['root'=> $root, 'name'=> $data[7]]);
        if ($group->id) {
            $user->group = $group;
            $group->connect($user);
            $user->save();
        }

    }
    else {
        echo 'f';
        $fails[] = $data[1]. ':'. $data[0];
        ++ $failure;     
    }
}

$csv->close();

echo "共计数据: $count 条\n";
echo "成功导入: $success 条\n";
echo "导入失败: $failure 条\n";

if ($failure) {
    foreach($fails as $fail) {
        echo "\t$fail\n";
    }
}
