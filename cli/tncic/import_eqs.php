#!/usr/bin/env php
<?php
    /*
     * file import_eqs.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2013-11-26
     *
     * useage SITE_ID=cf LAB_ID=test php import_eqs.php
     * brief 对CSV文件进行读取，创建系统中现有用户
     */

$_SERVER['SITE_ID'] = 'cf';
$_SERVER['LAB_ID'] = 'tncic';

require dirname(dirname(__FILE__)). '/base.php';

$file = $argv[1];
$source = $argv[2];

if (!$file || !File::exists($file) || !$source) {
    die("Usage: SITE_ID=xx LAB_ID=xx php import_eqs.php file.csv source\n");
}

$csv = new CSV($file, 'r');

//head跳过
$csv->read();

$db = Database::factory();

$count = $success = $failure = 0;

$root = Tag_Model::root('group');

while($data = $csv->read()) {

    /*
     *    $data 结构 :
     *     0=> 名称
     *     1=> ID编号
     *     2=> 状态(0: 正常, 1: 故障, 2: 废弃)
     *     3=> 是否接受预约
     *     4=> 是否接受送样
     *     5=> 控制方式
     *     6=> 放置地址(楼宇)
     *     7=> 放置地址(房间号)
     *     8=> 联系人
     *     9=> 组织机构
     */

    ++ $count;

    $eq = O('equipment');
    $eq->name = $data[0];
    if ($eq->save()) {

        ++ $success;

        $eq->status = $data[2];
        $eq->accept_reserv = $data[3];
        $eq->accept_sample = $data[4];
        $eq->control_mode = (string) $data[5];

        $eq->location = $data[6];
        $eq->location2 = $data[7];

        $eq->source = $source;

        $contacts = array_filter(explode(',', $data[8]));

        if (count($contacts)) {
            foreach($contacts as $c) {
                $users = Q("user[name={$c}]");
                foreach($users as $u) {
                    if ($u->source == $source) {
                        $eq->connect($u, 'contact');
                        $eq->connect($u, 'incharge');
                    }
                }
            }
        }

        $group = O('tag', ['root'=> $root, 'name'=> $data[9]]);
        if ($group->id) {
            $group->connect($eq);
            $eq->group = $group;
        }

        $eq->original_id = $data[1];

        $eq->save();
    }
    else {
        $fails[] = $data[2]. ':'. $data[0];
        ++ $failure;     
    }
    unset($data);
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
