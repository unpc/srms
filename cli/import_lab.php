#!/usr/bin/env php
<?php
require 'base.php';
$file = $argv[1];

if (!($file && file_exists($file))) {
	print("usage: SITE_ID=cf LAB_ID=test ./import_lab.php labs.csv\n");
	die;
}

$now = Date::time();

/* group root */
$root = Tag_Model::root('group');
$group_root_name = '上海交通大学医学院';

/* 读取输入文件 */
$csv = new CSV($file, 'r');

$escape_n_rows = 1;
for (;$escape_n_rows--;) {
	$csv->read(',');
}

$cnt = 0;
$lab_success = [];
$lab_faild = [];

while ($row = $csv->read(',')) {

    $name = trim($row[0]);
    // $user_name = trim($row[1]);
    // $user = O('user', ['name' => $user_name]);

    $contact = trim($row[2]);


    $parent = O('tag', ['name' => $group_root_name, 'parent' => $root, 'root'=>$root]);
    if (!$parent->id) {
        echo "未找到输入的校内组织机构";
        die;
    }
    $group = O('tag', ['root' => $root, 'parent' => $parent, 'name' => trim($row[3])]);
    if (!$group->id) {
        $g = O('tag');
        $g->parent = $parent;
        $g->root = $root;
        $g->name = trim($row[3]);
        $g->save();
    } else {
        $g = $group;    
    }

    $subject = trim($row[6]);
    $util_area = trim($row[7]);
    $location = trim($row[8]);
    $location2 = trim($row[9]);
    $description = trim($row[10]);
    
    $lab = O('lab');
    $lab->name = $name;
    // $lab->owner = $user;
    $lab->contact = $contact;
    $lab->group = $g;
    $lab->subject = $subject;
    $lab->util_area = $util_area;
    $lab->location = $location;
    $lab->location2 = $location2;
    $lab->description = $description;
    $lab->atime = $now;

    if ($lab->save()) {
        $lab_success[] = $name;
    }
    else {
        $lab_faild[] = $name;
    }

    $cnt++;
}


printf("\n=============\n");
printf("共处理%d个课题组\n", $cnt);
printf("新导入%d个\n" , count($lab_success));
printf("尝试导入，但失败%d个课题组\n", count($lab_faild));
if ($lab_faild) {
    foreach ($lab_faild as $l_f) {
        printf("%s\n", $l_f);
    }
}
