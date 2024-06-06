#!/usr/bin/env php
<?php
/**
 * @file   import_eq.php
 * @author Xiaopei Li <xiaopei.li@gmail.com>
 * @date   2012-08-16
 *
 * @brief  import eqs to default lab
 *
 * usage: SITE_ID=cf LAB_ID=test ./import_eq.php eq.csv
 *
 * csv 格式要求: 
 * 0仪器名称*    1仪器编号    2仪器分类    3放置地点（楼宇）    4放置地点（房间）    5负责人*    6联系人*    7联系电话    8联系邮箱    9组织机构    10规格  11型号  12价格  13生产厂家    14制造国家    15购置日期    16出厂日期    17分类号 18主要规格及技术指标   19主要功能及特色 20主要附件及配置 21主要测试和研究领域
 *
 */

require 'base.php';

$file = $argv[1];
$now = Date::time();

if (!($file && file_exists($file))) {
    print("usage: SITE_ID=cf LAB_ID=test ./import_eq.php eq.csv\n");
    die;
}

/* group root */
$root = Tag_Model::root('group');
$group_root_name = '东北电力大学';

/* 读取输入文件 */
$csv = new CSV($file, 'r');

$eq_total = 0;
$eq_new = 0;
$eq_failed = 0;

$all_eqs = [];
$eq_ok = [];
$eq_existed = [];
$failed_eqs = [];

$escape_n_rows = 1;
for (;$escape_n_rows--;) {
    $csv->read(',');
}


while ($row = $csv->read(',')) {

    $eq_total++;

    /* 读一行 */
    $name = trim($row[0]);
    $ref_no = trim($row[1]);
    $tags = explode(',', trim($row[2]));
    $location = trim($row[3]);
    $location2 = trim($row[4]);
    $incharge_name = trim($row[5]);
    $incharge = O('user', ['name' => $incharge_name]);
    $contact_name = trim($row[6]);
    $contact = O('user', ['name' => $contact_name]);
    $phone = trim($row[7]);
    $email = trim($row[8]);

    $root = Tag_Model::root('group');
    $parent = O('tag', ['name' => $group_root_name, 'parent' => $root, 'root'=>$root]);
    if (!$parent->id) {
        echo "未找到输入的校内组织机构";
        die;
    }
    $group = O('tag', ['root' => $root, 'parent' => $parent, 'name' => trim($row[9])]);
    if (!$group->id) {
        $g = O('tag');
        $g->parent = $parent;
        $g->root = $root;
        $g->name = trim($row[9]);
        $g->save();
    } else {
        $g = $group;
    }

    $specification = trim($row[10]);
    $model_no = trim($row[11]);
    $price = trim($row[12]);
    $manufacturer = trim($row[13]);
    $manu_at = trim($row[14]);
    $purchased_date = strtotime(trim($row[15]));
    $manu_date = strtotime(trim($row[16]));
    $cat_no = trim($row[17]);
    $tech_specs = trim($row[18]);
    $features = trim($row[19]);
    $configs = trim($row[20]);

    printf("正在处理%s\n", $name);

    $eq = O('equipment');

    $eq->name = $name;
    $eq->ref_no = $ref_no ? : NULL;
    $eq->location = $location;
    $eq->location2 = $location2;
    $eq->phone = $phone;
    $eq->email = $email;
    $eq->group = $g;
    $eq->specification = $specification;
    $eq->model_no = $model_no;
    $eq->price = $price;
    $eq->manufacturer = $manufacturer;
    $eq->manu_at = $manu_at;
    $eq->purchased_date = $purchased_date;
    $eq->manu_date = $manu_date;
    $eq->cat_no = $cat_no;
    $eq->tech_specs = $tech_specs;
    $eq->features = $features;
    $eq->configs = $configs;

    if ($eq->save()) {
        $group->connect($eq);
        $eq->connect($incharge, 'incharge');
        $eq->connect($contact, 'contact');
        $incharge->follow($eq);
        Tag_Model::replace_tags($eq, (array)$tags, 'equipment');

        $eq_new++;
        $eq_ok[] = $name;

    }
    else {
        $eq_failed++;
        $failed_eqs[] = $name;
    }
}

printf("=============\n");

printf("共处理%d台仪器\n", $eq_total);
printf("新导入%d台仪器\n" , $eq_new);
printf("尝试导入，但失败%d台仪器\n", $eq_failed);

if ($eq_failed) {
    foreach ($failed_eqs as $f_u) {
        if (is_array($f_u)) {
            printf("%s:\t%s\n", $f_u['name'], $f_u['reason']);
        }
        else {
            printf("%s\n", $f_u);
        }

    }
}
