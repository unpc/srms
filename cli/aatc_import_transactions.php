#!/usr/bin/env php
<?php
    /*
     * file aatc_import_transactions.php
     * author Rui Ma <rui.ma@geneegroup.com>
     * date 2014-10-31
     *
     * useage php aatc_import_transactions.php transactions.csv
     * brief 对南京林业大学分析中心的transactions进行数据导入操作
     * notice 该脚本只适用于2.11.x版本
     */


$_SERVER['SITE_ID'] = 'cf-mini';
$_SERVER['LAB_ID'] = 'aatc';

require 'base.php';

$file = $argv[1];

if (!$file || !File::exists($file)) {
    die("Usage: php aatc_import_transactions transactions.csv \n");
}

define('DISABLE_NOTIFICATION', TRUE);

$csv = new CSV($file, 'r');

//跳过表头
$csv->read();

//总数为0
$total_count = 0; 
$success_count = 0;

//单财务, 直接获取即可
$department = Billing_Department::get();

//row结构如下
//实验室名称,财务部门名称,收入金额,支出金额,操作人员名称,凭证号,日期,备注信息

$labs = [];
while($row = $csv->read()) {
    //进行总数增加
    $total_count ++;
    $lab = O('lab', [
        'name'=>$row[0],
    ]);

    //找到对应的实验室名称
    if (!$lab->id) {
        $labs[] = $row[0];
        echo "{$row[0]} 不存在, 无法导入\n";
        continue;
    }

    $account = O('billing_account', [
        'lab'=> $lab,
        'department'=> $department,
    ]);

    //由于之前已进行了所有财务账号的创建, 故此时不再进行判断

    $t = O('billing_transaction');
    $t->account = $account;
    $t->department = $department;

    //进行充值操作
    if ($row[2]) {
        $t->income = (double) $row[2];
        $t->manual = true; //充值属于手动操作
    }
    else {
        $t->outcome = (double) $row[3];
    }

    $t->user = O('user', [
        'name'=> $row[4],
    ]);

    $t->certificate = $row[5];

    $t->ctime = strtotime($row[6]); 

    $description = [];

    if ($row[2]) {
        $description = ['module'=>'billing',
            'template' => I18N::T('billing', '%user 对 %account 进行充值'),
            '%user'=>Markup::encode_Q($t->user),
            '%account'=>Markup::encode_Q($account->lab),
        ];
    }
    else {
        $description = [
            'module'=> 'billing',
            'template'=> '数据导入',
        ];
    }

    $description['amend'] = str_replace('仪器名称：财务充值；预约时间段：至预约人：()', '', $row[7]);


    $t->description = $description;

    if ($t->save()) {
        $success_count ++;
    }
    else {
        echo '添加失败'. join(',', $line). "\n";
    }
}

echo "\033[1;40;32m";
echo sprintf("\n导入数据总数为:%d\t成功数为:%d \n", $total_count, $success_count);
echo "\033[0m";

foreach(array_unique($labs) as $l) {
    echo $l;
    echo "\n";
}
